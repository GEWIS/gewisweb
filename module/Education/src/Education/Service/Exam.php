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
     * Upload a new exam.
     *
     * @param array $post POST Data
     * @param array $files FILES Data
     *
     * @return boolean
     */
    public function upload($post, $files)
    {
        $form = $this->getUploadForm();
        $form->bind(new ExamModel());

        $data = array_merge_recursive($post->toArray(), $files->toArray());

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $exam = $form->getData();
        $data = $form->getData(FormInterface::VALUES_AS_ARRAY);

        /**
         * Persist the exam and save the uploaded file.
         *
         * We do this in a transactional block, so if there is something
         * wrong, we only have to throw an exception and Doctrine will roll
         * back the transaction. This comes in handy if we are somehow unable
         * to process the upload. This does allow us to get the ID of the
         * exam, which we need in the upload process.
         */
        $this->getExamMapper()->transactional(function ($mapper) use ($exam, $data) {
            $mapper->persist($exam);

            $this->finishUpload($exam, $data['upload']);
        });

        return true;
    }

    /**
     * Move the uploaded file to the right place.
     *
     * @param ExamModel $exam
     * @param array $upload Upload data
     */
    protected function finishUpload(ExamModel $exam, array $upload)
    {
        $config = $this->getConfig();

        $filename = $config['upload_dir'] . '/' . $this->examToFilename($exam);

        // make sure the directory exists, and move the file
        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), $config['dir_mode'], true);
        }
        move_uploaded_file($upload['tmp_name'], $filename);
    }

    /**
     * Get a filename from an exam (or summary).
     *
     * We do this, since we have so many courses, that most filesystems get
     * choked up on the directory size. By dividing it into subdirectories, we
     * get a much better performance from the filesystem.
     *
     * Exams will have a filename of the following format:
     *
     * <code>-<id>-exam-<year>-<month>-<day>.pdf
     *
     * Summaries have the following format:
     *
     * <code>-<id>-<author>-summary-<year>-<month>-<day>.php
     *
     * @param ExamModel $exam
     *
     * @return string Filename
     */
    public function examToFilename(ExamModel $exam)
    {
        $code = $exam->getCourse()->getCode();
        $dir = substr($code, 0, 2) . '/' . substr($code, 2) . '/';

        $filename = array();

        $filename[] = $code;
        $filename[] = $exam->getId();

        if ($exam instanceof SummaryModel) {
            $filename[] = $exam->getAuthor();
            $filename[] = 'summary';
        } else {
            $filename[] = 'exam';
        }

        $filename[] = $exam->getDate()->format('Y-m-d');


        return $dir . implode('-', $filename) . '.pdf';
    }

    /**
     * Get the education config, as used by this service.
     *
     * @return array
     */
    public function getConfig()
    {
        $config = $this->sm->get('config');
        return $config['education'];
    }

    /**
     * Get the Upload form.
     *
     * @return \Education\Form\Upload
     *
     * @throws \User\Permissions\NotAllowedException When not allowed to upload
     */
    public function getUploadForm()
    {
        if (!$this->isAllowed('upload')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to upload exams')
            );
        }
        return $this->sm->get('education_form_upload');
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
