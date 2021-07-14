<?php

namespace Education\Service;

use Application\Service\AbstractAclService;
use Application\Service\FileStorage;
use DateTime;
use DirectoryIterator;
use Doctrine\Common\Collections\Collection;
use Education\Form\AddCourse;
use Education\Form\Bulk;
use Education\Form\SearchCourse;
use Education\Form\TempUpload;
use Education\Mapper\Course;
use Education\Model\Course as CourseModel;
use Education\Model\Exam as ExamModel;
use Education\Model\Summary as SummaryModel;
use Exception;
use Laminas\Http\Response\Stream;
use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Acl;
use User\Model\User;
use User\Permissions\NotAllowedException;

/**
 * Exam service.
 */
class Exam extends AbstractAclService
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var User|string
     */
    private $userRole;

    /**
     * @var Acl
     */
    private $acl;

    /**
     * @var FileStorage
     */
    private $storageService;

    /**
     * @var Course
     */
    private $courseMapper;

    /**
     * @var \Education\Mapper\Exam
     */
    private $examMapper;

    /**
     * @var AddCourse
     */
    private $addCourseForm;

    /**
     * @var SearchCourse
     */
    private $searchCourseForm;

    /**
     * @var TempUpload
     */
    private $tempUploadForm;

    /**
     * @var Bulk
     */
    private $bulkSummaryForm;

    /**
     * @var Bulk
     */
    private $bulkExamForm;

    /**
     * @var array
     */
    private $config;

    public function __construct(
        Translator $translator,
        $userRole,
        Acl $acl,
        FileStorage $storageService,
        Course $courseMapper,
        \Education\Mapper\Exam $examMapper,
        AddCourse $addCourseForm,
        SearchCourse $searchCourseForm,
        TempUpload $tempUploadForm,
        Bulk $bulkSummaryForm,
        Bulk $bulkExamForm,
        array $config
    ) {
        $this->translator = $translator;
        $this->userRole = $userRole;
        $this->acl = $acl;
        $this->storageService = $storageService;
        $this->courseMapper = $courseMapper;
        $this->examMapper = $examMapper;
        $this->addCourseForm = $addCourseForm;
        $this->searchCourseForm = $searchCourseForm;
        $this->tempUploadForm = $tempUploadForm;
        $this->bulkSummaryForm = $bulkSummaryForm;
        $this->bulkExamForm = $bulkExamForm;
        $this->config = $config;
    }

    public function getRole()
    {
        return $this->userRole;
    }

    /**
     * Bulk form.
     *
     * @var Bulk
     */
    protected $bulkForm;

    /**
     * Search for a course.
     *
     * @param array $data
     *
     * @return Collection|null Courses, null if form is not valid
     */
    public function searchCourse($data)
    {
        $form = $this->searchCourseForm;
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $data = $form->getData();
        $query = $data['query'];

        return $this->courseMapper->search($query);
    }

    /**
     * Get a course.
     *
     * @param string $code
     *
     * @return CourseModel
     */
    public function getCourse($code)
    {
        return $this->courseMapper->findByCode($code);
    }

    /**
     * Get an exam.
     *
     * @param int $id
     *
     * @return Stream|null
     */
    public function getExamDownload($id)
    {
        if (!$this->isAllowed('download')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to download exams'));
        }

        $exam = $this->examMapper->find($id);

        return $this->storageService->downloadFile($exam->getFilename(), $this->examToFilename($exam));
    }

    /**
     * Finish the bulk edit.
     *
     * @param array $data POST Data
     *
     * @return bool
     */
    protected function bulkEdit($data, $type)
    {
        $form = $this->getBulkForm($type);

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $data = $form->getData();

        $config = $this->getConfig('education_temp');

        $messages = [];

        // check if all courses exist
        foreach ($data['exams'] as $key => $examData) {
            if (is_null($this->getCourse($examData['course']))) {
                // course doesn't exist
                $messages['exams'][$key] = [
                    'course' => [$this->translator->translate("Course doesn't exist")],
                ];
            }
        }

        if (!empty($messages)) {
            $form->setMessages($messages);

            return false;
        }

        /**
         * Persist the exams and save the uploaded files.
         *
         * We do this in a transactional block, so if there is something
         * wrong, we only have to throw an exception and Doctrine will roll
         * back the transaction. This comes in handy if we are somehow unable
         * to process the upload. This does allow us to get the ID of the
         * exam, which we need in the upload process.
         */
        $storageService = $this->storageService;
        $this->examMapper->transactional(function ($mapper) use ($data, $type, $config, $storageService) {
            foreach ($data['exams'] as $examData) {
                // finalize exam upload
                $exam = new ExamModel();
                if ('summary' === $type) {
                    $exam = new SummaryModel();
                }

                $exam->setDate(new DateTime($examData['date']));
                $exam->setCourse($this->getCourse($examData['course']));
                if (SummaryModel::class === get_class($exam)) {
                    $exam->setAuthor($examData['author']);
                    $exam->setExamType(ExamModel::EXAM_TYPE_SUMMARY);
                }

                if (ExamModel::class === get_class($exam)) {
                    $exam->setExamType($examData['examType']);
                }
                $exam->setLanguage($examData['language']);

                $localFile = $config['upload_' . $type . '_dir'] . '/' . $examData['file'];

                $exam->setFilename($storageService->storeFile($localFile));

                $mapper->persist($exam);
            }
        });

        return true;
    }

    public function bulkExamEdit($data)
    {
        return $this->bulkEdit($data, 'exam');
    }

    public function bulkSummaryEdit($data)
    {
        return $this->bulkEdit($data, 'summary');
    }

    /**
     * Temporary exam upload.
     *
     * Uploads exams into a temporary folder.
     *
     * @param array $post POST Data
     * @param array $files FILES Data
     * @param string $uploadDirectory the directory to place the exam in
     *
     * @return bool
     */
    protected function tempUpload($post, $files, $uploadDirectory)
    {
        $form = $this->getTempUploadForm();

        $data = array_merge_recursive($post->toArray(), $files->toArray());

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $filename = $data['file']['name'];
        $path = $uploadDirectory . '/' . $filename;
        $tmpPath = $data['file']['tmp_name'];

        if (!file_exists($tmpPath)) {
            return false;
        }

        if (!file_exists($path)) {
            return move_uploaded_file($tmpPath, $path);
        }

        return true;
    }

    public function tempExamUpload($post, $files)
    {
        $config = $this->getConfig('education_temp');

        return $this->tempUpload($post, $files, $config['upload_exam_dir']);
    }

    public function tempSummaryUpload($post, $files)
    {
        $config = $this->getConfig('education_temp');

        return $this->tempUpload($post, $files, $config['upload_summary_dir']);
    }

    /**
     * Get a filename from an exam (or summary).
     *
     * Exams will have a filename of the following format:
     *
     * <code>-exam-<year>-<month>-<day>.pdf
     *
     * Summaries have the following format:
     *
     * <code>-<author>-summary-<year>-<month>-<day>.pdf
     *
     * @return string Filename
     */
    public function examToFilename(ExamModel $exam)
    {
        $code = $exam->getCourse()->getCode();

        $filename = [];

        $filename[] = $code;

        if ($exam instanceof SummaryModel) {
            $filename[] = $exam->getAuthor();
            $filename[] = 'summary';
        } else {
            $filename[] = 'exam';
        }

        $filename[] = $exam->getDate()->format('Y-m-d');

        return implode('-', $filename) . '.pdf';
    }

    /**
     * Get the education config, as used by this service.
     *
     * @return array
     */
    public function getConfig($key = 'education')
    {
        $config = $this->config;

        return $config[$key];
    }

    /**
     * Deletes a temp uploaded exam or summary.
     *
     * @param string $filename The file to delete
     * @param string $type The type to delete (exam/summary)
     */
    public function deleteTempExam($filename, $type = 'exam')
    {
        if (!$this->isAllowed('delete')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete exams'));
        }
        $config = $this->getConfig('education_temp');
        $dir = $config['upload_' . $type . '_dir'];
        unlink($dir . '/' . stripslashes($filename));
    }

    /**
     * Get the bulk edit form.
     *
     * @return Bulk
     *
     * @throws NotAllowedException When not allowed to upload
     */
    protected function getBulkForm($type)
    {
        if (!$this->isAllowed('upload')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to upload exams'));
        }
        if (null !== $this->bulkForm) {
            return $this->bulkForm;
        }

        // fully load the bulk form
        if ('summary' === $type) {
            $this->bulkForm = $this->bulkSummaryForm;
        } elseif ('exam' === $type) {
            $this->bulkForm = $this->bulkExamForm;
        } else {
            throw new Exception('Unsupported bulk form type');
        }

        $config = $this->getConfig('education_temp');

        $dir = new DirectoryIterator($config['upload_' . $type . '_dir']);
        $data = [];

        foreach ($dir as $file) {
            if ($file->isFile() && '.' != substr($file->getFilename(), 0, 1)) {
                $examData = $this->guessExamData($file->getFilename());
                if ('summary' === $type) {
                    $examData['author'] = $this->guessSummaryAuthor($file->getFilename());
                }
                $examData['file'] = $file->getFilename();
                $data[] = $examData;
            }
        }

        $this->bulkForm->get('exams')->populateValues($data);

        return $this->bulkForm;
    }

    /**
     * Get the bulk summary edit form.
     *
     * @return Bulk
     */
    public function getBulkSummaryForm()
    {
        return $this->getBulkForm('summary');
    }

    /**
     * Get the bulk exam edit form.
     */
    public function getBulkExamForm()
    {
        return $this->getBulkForm('exam');
    }

    /**
     * Guesses the course code and date based on an exam's filename.
     *
     * @param string $filename
     *
     * @return array
     */
    public function guessExamData($filename)
    {
        $matches = [];
        $course = preg_match('/\d[a-zA-Z][0-9a-zA-Z]{3,4}/', $filename, $matches) ? $matches[0] : '';
        $filename = str_replace($course, '', $filename);

        $today = new DateTime();
        $year = preg_match('/20\d{2}/', $filename, $matches) ? $matches[0] : $today->format('Y');
        $filename = str_replace($year, '', $filename);
        $month = preg_match_all('/[01]\d/', $filename, $matches) ? $matches[0][0] : $today->format('m');
        $filename = str_replace($month, '', $filename);
        $day = preg_match_all('/[0123]\d/', $filename, $matches) ? $matches[0][0] : $today->format('d');

        $language = strstr($filename, 'nl') ? 'nl' : 'en';

        return [
            'course' => $course,
            'date' => $year . '-' . $month . '-' . $day,
            'language' => $language,
        ];
    }

    /**
     * Guesses the summary author based on a summary's filename.
     *
     * @param string $filename
     *
     * @return array|string
     */
    public static function guessSummaryAuthor($filename)
    {
        $parts = explode('.', $filename);
        foreach ($parts as $part) {
            // We assume names are more than 3 characters and don't contain numbers
            if (strlen($part) > 3 && 0 == preg_match('/\\d/', $part)) {
                return $part;
            }
        }

        return '';
    }

    /**
     * Get the Temporary Upload form.
     *
     * @return TempUpload
     *
     * @throws NotAllowedException When not allowed to upload
     */
    public function getTempUploadForm()
    {
        if (!$this->isAllowed('upload')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to upload exams'));
        }

        return $this->tempUploadForm;
    }

    /**
     * Get the add course form.
     *
     * @return AddCourse
     *
     * @throws NotAllowedException When not allowed to upload
     */
    public function getAddCourseForm()
    {
        if (!$this->isAllowed('upload')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to add courses'));
        }

        return $this->addCourseForm;
    }

    /**
     * Add a new course.
     *
     * @param array $data Course data
     *
     * @return CourseModel New course. Null when the course could not be added.
     */
    public function addCourse($data)
    {
        $form = $this->getAddCourseForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // get the course
        $data = $form->getData();

        // check if course already exists
        $existingCourse = $this->getCourse($data['code']);
        if (null !== $existingCourse) {
            return null;
        }

        // check if parent course exists
        if (strlen($data['parent']) > 0) {
            $existingCourse = $this->getCourse($data['parent']);
            if (null === $existingCourse) {
                return null;
            }
        }

        // save the data
        $newCourse = new CourseModel();
        $newCourse->setCode($data['code']);
        $newCourse->setName($data['name']);
        if (strlen($data['parent']) > 0) {
            $newCourse->setParent($this->getCourse($data['parent']));
        }
        $newCourse->setUrl($data['url']);
        $newCourse->setYear($data['year']);
        $newCourse->setQuartile($data['quartile']);

        $this->courseMapper->persist($newCourse);

        return $newCourse;
    }

    /**
     * Get the Acl.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->acl;
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'exam';
    }
}
