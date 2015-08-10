<?php

namespace Decision\Service;

use Application\Service\AbstractAclService;

use Decision\Form\Notes;

use Decision\Model\MeetingDocument;

/**
 * Decision service.
 */
class Decision extends AbstractAclService
{

    /**
     * Get all meetings.
     *
     * @return array Of all meetings
     */
    public function getMeetings()
    {
        if (!$this->isAllowed('list_meetings')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to list meetings.')
            );
        }

        return $this->getMeetingMapper()->findAll();
    }

    /**
     * Get information about one meeting.
     *
     * @param string $type
     * @param int $number
     *
     * @return Decision\Model\Meeting
     */
    public function getMeeting($type, $number)
    {
        if (!$this->isAllowed('view', 'meeting')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view meetings.')
            );
        }

        return $this->getMeetingMapper()->find($type, $number);
    }

    /**
     * Check if there are notes for a meeting and get the URL if so.
     *
     * @param \Decision\Model\Meeting $meeting
     *
     * @return string|null
     */
    public function getMeetingNotes(\Decision\Model\Meeting $meeting)
    {
        if (!$this->isAllowed('view_notes', 'meeting')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view meeting notes.')
            );
        }
        $config = $this->getServiceManager()->get('config');
        $config = $config['meeting-notes'];

        $filename = $meeting->getType() . '/' . $meeting->getNumber() . '.pdf';
        $path = $config['upload_dir'] . '/' . $filename;

        if (file_exists($path)) {
            return $config['public_dir'] . '/' . $filename;
        }
        return null;
    }

    /**
     * Get the base path for meeting documents.
     *
     * @param Decision\Model\Meeting $meeting
     *
     * @return string
     */
    public function getMeetingDocumentBasePath(\Decision\Model\Meeting $meeting)
    {
        $config = $this->getServiceManager()->get('config');
        $config = $config['meeting-documents'];

        return $config['public_dir'] . '/'
             . $meeting->getType() . '/' . $meeting->getNumber();
    }

    /**
     * Upload meeting notes.
     *
     * @param array|Traversable $post
     * @param array|Traversable $files
     *
     * @return boolean If uploading was a success
     */
    public function uploadNotes($post, $files)
    {
        $form = $this->getNotesForm();

        $data = array_merge_recursive($post->toArray(), $files->toArray());

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $data = $form->getData();

        $config = $this->getServiceManager()->get('config');
        $config = $config['meeting-notes'];

        $filename = $data['meeting'] . '.pdf';
        $path = $config['upload_dir'] . '/' . $filename;


        // finish upload

        $this->getFileStorageService()->storeUploadedFile($data['upload']);
        return true;
    }

    /**
     * Upload a meeting document.
     *
     * @param array|Traversable $post
     * @param array|Traversable $files
     *
     * @return boolean If uploading was a success
     */
    public function uploadDocument($post, $files)
    {
        $form = $this->getDocumentForm();

        $data = array_merge_recursive($post->toArray(), $files->toArray());

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $data = $form->getData();

        $config = $this->getServiceManager()->get('config');
        $config = $config['meeting-documents'];

        $filename = $data['meeting'] . '/' .$data['upload']['name'];
        $path = $config['upload_dir'] . '/' . $filename;

        if (file_exists($path)) {
            $form->setError(Notes::ERROR_FILE_EXISTS);
            return false;
        }

        $meeting = explode('/', $data['meeting']);
        $meeting = $this->getMeeting($meeting[0], $meeting[1]);

        $document = new MeetingDocument();
        $document->setPath($data['upload']['name']);
        $document->setName($data['name']);
        $document->setMeeting($meeting);

        // finish upload and save in the database
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), $config['dir_mode'], true);
        }
        move_uploaded_file($data['upload']['tmp_name'], $path);

        $this->getMeetingMapper()->persistDocument($document);

        return true;
    }

    /**
     * Search for decisions.
     *
     * @param array|Traversable $data Search data
     *
     * @return array Search results
     */
    public function search($data)
    {
        if (!$this->isAllowed('search')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to search decisions.')
            );
        }

        $form = $this->getSearchDecisionForm();

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $data = $form->getData();

        return $this->getDecisionMapper()->search($data['query']);
    }

    /**
     * Get the Notes form.
     *
     * @return Decision\Form\Notes
     */
    public function getNotesForm()
    {
        if (!$this->isAllowed('upload_notes', 'meeting')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to upload notes.')
            );
        }
        return $this->sm->get('decision_form_notes');
    }

    /**
     * Get the Document form.
     *
     * @return Decision\Form\Document
     */
    public function getDocumentForm()
    {
        if (!$this->isAllowed('upload_document', 'meeting')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to upload meeting documents.')
            );
        }
        return $this->sm->get('decision_form_document');
    }

    /**
     * Get the SearchDecision form.
     *
     * @return Decision\Form\SearchDecision
     */
    public function getSearchDecisionForm()
    {
        return $this->sm->get('decision_form_searchdecision');
    }

    /**
     * Get the meeting mapper.
     *
     * @return Decision\Mapper\Meeting
     */
    public function getMeetingMapper()
    {
        return $this->sm->get('decision_mapper_meeting');
    }

    /**
     * Get the decision mapper.
     *
     * @return Decision\Mapper\Decision
     */
    public function getDecisionMapper()
    {
        return $this->sm->get('decision_mapper_decision');
    }

    /**
     * Gets the storage service.
     *
     * @return \Application\Service\FileStorage
     */
    public function getFileStorageService()
    {
        return $this->sm->get('application_service_storage');
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'decision';
    }

    /**
     * Get the Acl.
     *
     * @return Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->sm->get('decision_acl');
    }
}
