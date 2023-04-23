<?php

declare(strict_types=1);

namespace Education\Service;

use Application\Model\Enums\Languages;
use Application\Service\FileStorage as FileStorageService;
use DateTime;
use DirectoryIterator;
use Education\Form\{
    Course as CourseForm,
    Bulk as BulkForm,
    TempUpload as TempUploadForm,
};
use Education\Mapper\{
    CourseDocument as CourseDocumentMapper,
    Course as CourseMapper,
};
use Education\Model\{
    Course as CourseModel,
    Course,
    CourseDocument as CourseDocumentModel,
    Enums\ExamTypes,
    Exam as ExamModel,
    Summary as SummaryModel,
};
use Exception;
use InvalidArgumentException;
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
    protected ?BulkForm $bulkForm = null;

    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly FileStorageService $storageService,
        private readonly CourseMapper $courseMapper,
        private readonly CourseDocumentMapper $courseDocumentMapper,
        private readonly CourseForm $courseForm,
        private readonly TempUploadForm $tempUploadForm,
        private readonly BulkForm $bulkSummaryForm,
        private readonly BulkForm $bulkExamForm,
        private readonly array $config,
    ) {
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
    public function getDocumentDownload(int $id): ?Stream
    {
        if (!$this->aclService->isAllowed('download', 'course_document')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to download course documents')
            );
        }

        /** @var CourseDocumentModel|null $document */
        $document = $this->courseDocumentMapper->find($id);

        if (null === $document) {
            return null;
        }

        return $this->storageService->downloadFile(
            $document->getFilename(),
            $this->courseDocumentToFilename($document),
            true,
            $document->getScanned(),
        );
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
        $temporaryEducationConfig = $this->getConfig('education_temp');

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
        $this->courseDocumentMapper->transactional(
            function ($mapper) use ($data, $type, $temporaryEducationConfig, $storage): void {
                foreach ($data['documents'] as $documentData) {
                    // finalize document upload
                    if ('exam' === $type) {
                        $document = new ExamModel();
                    } elseif ('summary' === $type) {
                        $document = new SummaryModel();
                    } else {
                        throw new InvalidArgumentException('Course document does not have proper type');
                    }

                    $document->setDate(new DateTime($documentData['date']));
                    $document->setCourse($this->getCourse($documentData['course']));

                    if ($document instanceof SummaryModel) {
                        $document->setAuthor($documentData['author']);
                    }

                    if ($document instanceof ExamModel) {
                        $document->setExamType(ExamTypes::from($documentData['examType']));
                    }

                    $document->setLanguage(Languages::from($documentData['language']));
                    $document->setScanned(boolval($documentData['scanned']));
                    $localFile = $temporaryEducationConfig['upload_' . $type . '_dir'] . '/' . $documentData['file'];
                    $document->setFilename($storage->storeFile($localFile));

                    $mapper->persist($document);
                }
            }
        );

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
     * @param CourseDocumentModel $document
     *
     * @return string Filename
     */
    public function courseDocumentToFilename(CourseDocumentModel $document): string
    {
        $code = $document->getCourse()->getCode();
        $filename = [];
        $filename[] = $code;

        if ($document instanceof SummaryModel) {
            $filename[] = $document->getAuthor();
            $filename[] = 'summary';
        } else {
            $filename[] = 'exam';
        }

        $filename[] = $document->getDate()->format('Y-m-d');

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
        if (!$this->aclService->isAllowed('delete', 'course_document')) {
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
        if (!$this->aclService->isAllowed('upload', 'course_document')) {
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
                $examData = $this->guessCourseDocumentData($file->getFilename());

                if ('summary' === $type) {
                    $examData['author'] = $this->guessSummaryAuthor($file->getFilename());
                }

                $examData['file'] = $file->getFilename();
                $data[] = $examData;
            }
        }

        $form = $this->bulkForm->get('documents');

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
    public function guessCourseDocumentData(string $filename): array
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
        if (!$this->aclService->isAllowed('upload', 'course_document')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to upload exams'));
        }

        return $this->tempUploadForm;
    }

    /**
     * Get the add course form.
     *
     * @return CourseForm
     * @throws NotAllowedException When not allowed to upload
     */
    public function getCourseForm(?CourseModel $course = null): CourseForm
    {
        if (
            !$this->aclService->isAllowed('add', 'course')
            || !$this->aclService->isAllowed('edit', 'course')
        ) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to add courses'));
        }

        if (null === $course) {
            $this->courseForm->setObject(new CourseModel());
        } else {
            $this->courseForm->setObject($course);
            $this->courseForm->setCurrentCode($course->getCode());
            $this->courseForm->setData($course->toArray());
        }

        return $this->courseForm;
    }

    /**
     * Add a new course.
     */
    public function saveCourse(CourseModel $course): bool
    {
        $this->courseMapper->persist($course);

        return true;
    }

    /**
     * Delete a course and all its documents.
     */
    public function deleteCourse(Course $course): void
    {
        /** @var ExamModel|SummaryModel $exam */
        foreach ($course->getDocuments() as $exam) {
            $this->storageService->removeFile($exam->getFilename());
            $this->courseDocumentMapper->remove($exam);
        }

        $this->courseMapper->remove($course);
    }

    /**
     * Get all courses.
     */
    public function getAllCourses(): array
    {
        return $this->courseMapper->findAll();
    }

    /**
     * Get all documents of a specific type for a specific course.
     *
     * @psalm-param class-string<ExamModel>|class-string<SummaryModel> $type
     */
    public function getDocumentsForCourse(
        CourseModel $course,
        string $type,
    ): array {
        return $this->courseDocumentMapper->findDocumentsByCourse($course, $type);
    }

    /**
     * Get a specific course document.
     *
     * @psalm-suppress MoreSpecificReturnType
     */
    public function getDocument(int $id): ExamModel|SummaryModel|null
    {
        return $this->courseDocumentMapper->find($id);
    }

    /**
     * Delete a course document
     */
    public function deleteDocument(ExamModel|SummaryModel $document): void
    {
        $this->storageService->removeFile($document->getFilename());
        $this->courseDocumentMapper->remove($document);
    }
}
