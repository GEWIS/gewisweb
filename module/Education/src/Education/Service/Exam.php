<?php

namespace Education\Service;

use Application\Service\AbstractAclService;

use Education\Model\Course as CourseModel;
use Education\Model\Exam as ExamModel;
use Education\Model\Summary as SummaryModel;

use Zend\Form\FormInterface;

/**
 * Exam service.
 */
class Exam extends AbstractAclService
{

    /**
     * Bulk form.
     *
     * @var \Education\Form\Bulk
     */
    protected $bulkForm;

    /**
     * Search for a course.
     *
     * @param array $data
     *
     * @return array Courses, null if form is not valid
     */
    public function searchCourse($data)
    {
        $form = $this->getSearchCourseForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $data = $form->getData();
        $query = $data['query'];

        return $this->getCourseMapper()->search($query);
    }

    /**
     * Get a course
     *
     * @param string $code
     *
     * @return CourseModel
     */
    public function getCourse($code)
    {
        return $this->getCourseMapper()->findByCode($code);
    }

    /**
     * Get an exam
     *
     * @param int $id
     *
     * @return ExamModel
     */
    public function getExamDownload($id)
    {
        if (!$this->isAllowed('download')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to download exams')
            );
        }

        $exam = $this->getExamMapper()->find($id);

        return $this->getFileStorageService()
            ->downloadFile($exam->getFilename(), $this->examToFilename($exam));
    }

    /**
     * Finish the bulk edit.
     *
     * @param array $data POST Data
     *
     * @return boolean
     */
    protected function bulkEdit($data, $type)
    {
        $form = $this->getBulkForm($type);

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $data = $form->getData();

        $storageService = $this->getFileStorageService();
        $config = $this->getConfig('education_temp');

        $messages = [];

        // check if all courses exist
        foreach ($data['exams'] as $key => $examData) {
            if (is_null($this->getCourse($examData['course']))) {
                // course doesn't exist
                $messages['exams'][$key] = [
                    'course' => [$this->getTranslator()->translate("Course doesn't exist")]
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

        $this->getExamMapper()->transactional(function ($mapper) use ($data, $type, $config, $storageService) {
            foreach ($data['exams'] as $examData) {
                // finalize exam upload
                $exam = new ExamModel();
                if ($type === 'summary') {
                    $exam = new SummaryModel();
                }

                $exam->setDate(new \DateTime($examData['date']));
                $exam->setCourse($this->getCourse($examData['course']));
                if ($type === 'summary') {
                    $exam->setAuthor($examData['author']);
                    $exam->setExamType(ExamModel::EXAM_TYPE_SUMMARY);
                }

                if ($type === 'exam') {
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
     * @return boolean
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

        if (!file_exists($path)) {
            move_uploaded_file($data['file']['tmp_name'], $path);
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
     * @param ExamModel $exam
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
        $config = $this->sm->get('config');
        return $config[$key];
    }

    /**
     * Deletes a temp uploaded exam or summary
     *
     * @param $filename The file to delete
     * @param $type The type to delete (exam/summary)
     */
    public function deleteTempExam($filename, $type = 'exam')
    {
        if (!$this->isAllowed('delete')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to delete exams')
            );
        }
        $config = $this->getConfig('education_temp');
        $dir = $config['upload_' . $type . '_dir'];
        unlink($dir . '/' . stripslashes($filename));
    }

    /**
     * Get the bulk edit form.
     *
     * @return \Education\Form\Bulk
     *
     * @throws \User\Permissions\NotAllowedException When not allowed to upload
     */
    protected function getBulkForm($type)
    {
        if (!$this->isAllowed('upload')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to upload exams')
            );
        }
        if (null !== $this->bulkForm) {
            return $this->bulkForm;
        }

        // fully load the bulk form
        $this->bulkForm = $this->sm->get('education_form_bulk_' . $type);

        $config = $this->getConfig('education_temp');

        $dir = new \DirectoryIterator($config['upload_' . $type . '_dir']);
        $data = [];

        foreach ($dir as $file) {
            if ($file->isFile() && substr($file->getFilename(), 0, 1) != '.') {
                $examData = $this->guessExamData($file->getFilename());
                if ($type === 'summary') {
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
     * Get the bulk summary edit form
     *
     * @return \Education\Form\Bulk
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

        $today = new \DateTime();
        $year = preg_match('/20\d{2}/', $filename, $matches) ? $matches[0] : $today->format('Y');
        $filename = str_replace($year, '', $filename);
        $month = preg_match_all('/[01]\d/', $filename, $matches) ? $matches[0][0] : $today->format('m');
        $filename = str_replace($month, '', $filename);
        $day = preg_match_all('/[0123]\d/', $filename, $matches) ? $matches[0][0] : $today->format('d');

        $language = strstr($filename, 'nl') ? 'nl' : 'en';

        return [
            'course' => $course,
            'date' => $year . '-' . $month . '-' . $day,
            'language' => $language
        ];
    }

    /**
     * Guesses the summary author based on a summary's filename.
     *
     * @param string $filename
     *
     * @return array
     */
    public function guessSummaryAuthor($filename)
    {
        $parts = explode('.', $filename);
        foreach ($parts as $part) {
            // We assume names are more than 3 characters and don't contain numbers
            if (strlen($part) > 3 && preg_match('/\\d/', $part) == 0) {
                return $part;
            }
        }

        return '';
    }

    /**
     * Get the Temporary Upload form.
     *
     * @return \Education\Form\TempUpload
     *
     * @throws \User\Permissions\NotAllowedException When not allowed to upload
     */
    public function getTempUploadForm()
    {
        if (!$this->isAllowed('upload')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to upload exams')
            );
        }
        return $this->sm->get('education_form_tempupload');
    }

    /**
     * Get the add course form.
     *
     * @return \Education\Form\AddCourse
     *
     * @throws \User\Permissions\NotAllowedException When not allowed to upload
     */
    public function getAddCourseForm()
    {
        if (!$this->isAllowed('upload')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to add courses')
            );
        }
        return $this->sm->get('education_form_add_course');
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
        if ($existingCourse !== null) {
            return null;
        }

        // check if parent course exists
        if (strlen($data['parent']) > 0) {
            $existingCourse = $this->getCourse($data['parent']);
            if ($existingCourse === null) {
                return null;
            }
        }

        // save the data
        $newCourse = new CourseModel($this->getCourseMapper());
        $newCourse->setCode($data['code']);
        $newCourse->setName($data['name']);
        if (strlen($data['parent']) > 0) {
            $newCourse->setParent($this->getCourse($data['parent']));
        }
        $newCourse->setUrl($data['url']);
        $newCourse->setYear($data['year']);
        $newCourse->setQuartile($data['quartile']);

        $this->getCourseMapper()->persist($newCourse);

        return $newCourse;
    }

    /**
     * Get the storage service.
     *
     * @return \Application\Service\FileStorage
     */
    public function getFileStorageService()
    {
        return $this->sm->get('application_service_storage');
    }

    /**
     * Get the SearchExam form.
     *
     * @return \Education\Form\SearchCourse
     */
    public function getSearchCourseForm()
    {
        return $this->sm->get('education_form_searchcourse');
    }

    /**
     * Get the course mapper.
     *
     * @return \Education\Mapper\Course
     */
    public function getCourseMapper()
    {
        return $this->getServiceManager()->get('education_mapper_course');
    }

    /**
     * Get the exam mapper.
     *
     * @return \Education\Mapper\Exam
     */
    public function getExamMapper()
    {
        return $this->sm->get('education_mapper_exam');
    }

    /**
     * Get the Acl.
     *
     * @return Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->getServiceManager()->get('education_acl');
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
