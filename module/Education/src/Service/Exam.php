<?php

namespace Education\Service;

use Application\Service\FileStorage as FileStorageService;
use DateTime;
use DirectoryIterator;
use Education\Form\{
    AddCourse as AddCourseForm,
    Bulk as BulkForm,
    TempUpload as TempUploadForm,
};
use Doctrine\ORM\Exception\ORMException;
use Education\Mapper\{
    Exam as ExamMapper,
    Course as CourseMapper,
};
use Education\Model\{
    Course as CourseModel,
    Exam as ExamModel,
    Summary as SummaryModel,
};
use Exception;
use Laminas\Form\Fieldset;
use Laminas\Http\Response\Stream;
use Laminas\Mvc\I18n\Translator;
use Laminas\Stdlib\Parameters;
use RuntimeException;
use User\Permissions\NotAllowedException;

/**
 * Exam service.
 */
class Exam
{
    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var FileStorageService
     */
    private FileStorageService $storageService;

    /**
     * @var CourseMapper
     */
    private CourseMapper $courseMapper;

    /**
     * @var ExamMapper
     */
    private ExamMapper $examMapper;

    /**
     * @var AddCourseForm
     */
    private AddCourseForm $addCourseForm;

    /**
     * @var TempUploadForm
     */
    private TempUploadForm $tempUploadForm;

    /**
     * @var BulkForm
     */
    private BulkForm $bulkSummaryForm;

    /**
     * @var BulkForm
     */
    private BulkForm $bulkExamForm;

    /**
     * @var array
     */
    private array $config;

    /**
     * Bulk form.
     *
     * @var BulkForm|null
     */
    protected ?BulkForm $bulkForm = null;

    public function __construct(
        AclService $aclService,
        Translator $translator,
        FileStorageService $storageService,
        CourseMapper $courseMapper,
        ExamMapper $examMapper,
        AddCourseForm $addCourseForm,
        TempUploadForm $tempUploadForm,
        BulkForm $bulkSummaryForm,
        BulkForm $bulkExamForm,
        array $config,
    ) {
        $this->aclService = $aclService;
        $this->translator = $translator;
        $this->storageService = $storageService;
        $this->courseMapper = $courseMapper;
        $this->examMapper = $examMapper;
        $this->addCourseForm = $addCourseForm;
        $this->tempUploadForm = $tempUploadForm;
        $this->bulkSummaryForm = $bulkSummaryForm;
        $this->bulkExamForm = $bulkExamForm;
        $this->config = $config;
    }

    /**
     * Search for a course.
     *
     * @param array $data
     *
     * @return array|null Courses, null if form is not valid
     */
    public function searchCourse(array $data): ?array
    {
        return $this->courseMapper->search($data['query']);
    }

    /**
     * Get a course.
     *
     * @param string $code
     *
     * @return CourseModel|null
     */
    public function getCourse(string $code): ?CourseModel
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
    public function getExamDownload(int $id): ?Stream
    {
        if (!$this->aclService->isAllowed('download', 'exam')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to download exams'));
        }

        $exam = $this->examMapper->find($id);

        if (is_null($exam)) {
            return null;
        }

        return $this->storageService->downloadFile($exam->getFilename(), $this->examToFilename($exam), true);
    }

    /**
     * Finish the bulk edit.
     *
     * @param array $data POST Data
     * @param string $type
     *
     * @return bool
     * @throws Exception
     */
    protected function bulkEdit(
        array $data,
        string $type,
    ): bool {
        $form = $this->getBulkForm($type);

        $form->setData($data);
        // TODO: Move the form check to the controller.
        if (!$form->isValid()) {
            return false;
        }

        $data = $form->getData();

        $temporaryEducationConfig = $this->getConfig('education_temp');

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
        $storage = $this->storageService;
        $this->examMapper->transactional(function ($mapper) use ($data, $type, $temporaryEducationConfig, $storage): void {
            foreach ($data['exams'] as $examData) {
                // finalize exam upload
                $exam = new ExamModel();
                if ('summary' === $type) {
                    $exam = new SummaryModel();
                }

                $exam->setDate(new DateTime($examData['date']));
                $exam->setCourse($this->getCourse($examData['course']));

                if ($exam instanceof SummaryModel) {
                    $exam->setAuthor($examData['author']);
                    $exam->setExamType(ExamModel::EXAM_TYPE_SUMMARY);
                }

                if (get_class($exam) === ExamModel::class) {
                    $exam->setExamType($examData['examType']);
                }

                $exam->setLanguage($examData['language']);
                $localFile = $temporaryEducationConfig['upload_' . $type . '_dir'] . '/' . $examData['file'];
                $exam->setFilename($storage->storeFile($localFile));

                $mapper->persist($exam);
            }
        });

        return true;
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws Exception
     */
    public function bulkExamEdit(array $data): bool
    {
        return $this->bulkEdit($data, 'exam');
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws Exception
     */
    public function bulkSummaryEdit(array $data): bool
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
    protected function tempUpload(
        array $post,
        array $files,
        string $uploadDirectory,
    ): bool {
        $form = $this->getTempUploadForm();

        $data = array_merge_recursive($post, $files);

        $form->setData($data);
        // TODO: Move the form check to the controller.
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

    /**
     * @param Parameters $post
     * @param Parameters $files
     *
     * @return bool
     */
    public function tempExamUpload(
        Parameters $post,
        Parameters $files,
    ): bool {
        $temporaryEducationConfig = $this->getConfig('education_temp');

        return $this->tempUpload($post->toArray(), $files->toArray(), $temporaryEducationConfig['upload_exam_dir']);
    }

    /**
     * @param Parameters $post
     * @param Parameters $files
     *
     * @return bool
     */
    public function tempSummaryUpload(
        Parameters $post,
        Parameters $files,
    ): bool {
        $temporaryEducationConfig = $this->getConfig('education_temp');

        return $this->tempUpload($post->toArray(), $files->toArray(), $temporaryEducationConfig['upload_summary_dir']);
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
     * @param ExamModel $exam
     *
     * @return string Filename
     */
    public function examToFilename(ExamModel $exam): string
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
     * @param string $key
     *
     * @return array
     */
    public function getConfig(string $key = 'education'): array
    {
        return $this->config[$key];
    }

    /**
     * Deletes a temp uploaded exam or summary.
     *
     * @param string $filename The file to delete
     * @param string $type The type to delete (exam/summary)
     */
    public function deleteTempExam(
        string $filename,
        string $type = 'exam',
    ): void {
        if (!$this->aclService->isAllowed('delete', 'exam')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete exams'));
        }

        $temporaryEducationConfig = $this->getConfig('education_temp');
        $dir = $temporaryEducationConfig['upload_' . $type . '_dir'];
        unlink($dir . '/' . stripslashes($filename));
    }

    /**
     * Get the bulk edit form.
     *
     * @param string $type
     *
     * @return BulkForm
     * @throws Exception
     */
    protected function getBulkForm(string $type): BulkForm
    {
        if (!$this->aclService->isAllowed('upload', 'exam')) {
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

        $temporaryEducationConfig = $this->getConfig('education_temp');

        $dir = new DirectoryIterator($temporaryEducationConfig['upload_' . $type . '_dir']);
        $data = [];

        foreach ($dir as $file) {
            if ($file->isFile() && !str_starts_with($file->getFilename(), '.')) {
                $examData = $this->guessExamData($file->getFilename());

                if ('summary' === $type) {
                    $examData['author'] = $this->guessSummaryAuthor($file->getFilename());
                }

                $examData['file'] = $file->getFilename();
                $data[] = $examData;
            }
        }

        $form = $this->bulkForm->get('exams');

        if (!$form instanceof Fieldset) {
            throw new RuntimeException('The form could not be retrieved');
        }

        $form->populateValues($data);

        return $this->bulkForm;
    }

    /**
     * Get the bulk summary edit form.
     *
     * @return BulkForm
     * @throws Exception
     */
    public function getBulkSummaryForm(): BulkForm
    {
        return $this->getBulkForm('summary');
    }

    /**
     * Get the bulk exam edit form.
     *
     * @return BulkForm
     * @throws Exception
     */
    public function getBulkExamForm(): BulkForm
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
    public function guessExamData(string $filename): array
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

        $language = str_contains($filename, 'nl') ? 'nl' : 'en';

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
     * @return string
     */
    public static function guessSummaryAuthor(string $filename): string
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
     * @return TempUploadForm
     * @throws NotAllowedException When not allowed to upload
     */
    public function getTempUploadForm(): TempUploadForm
    {
        if (!$this->aclService->isAllowed('upload', 'exam')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to upload exams'));
        }

        return $this->tempUploadForm;
    }

    /**
     * Get the add course form.
     *
     * @return AddCourseForm
     * @throws NotAllowedException When not allowed to upload
     */
    public function getAddCourseForm(): AddCourseForm
    {
        if (!$this->aclService->isAllowed('upload', 'exam')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to add courses'));
        }

        return $this->addCourseForm;
    }

    /**
     * Add a new course.
     *
     * @param array $data Course data
     *
     * @return CourseModel|null New course. Null when the course could not be added.
     * @throws ORMException
     */
    public function addCourse(array $data): ?CourseModel
    {
        // TODO: Move the form check to the controller.
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
}
