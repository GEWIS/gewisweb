<?php

namespace Decision\Service;

use Application\Service\AbstractAclService;

use Decision\Form\Notes;
use Decision\Model\MeetingNotes as NotesModel;

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

    public function getMeetingsByType($type)
    {
        if (!$this->isAllowed('list_meetings')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to list meetings.')
            );
        }

        return $this->getMeetingMapper()->findByType($type);
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
     * Get meeting documents corresponding to a certain id.
     *
     * @param $id
     * @return \Decision\Model\MeetingDocument
     */
    public function getMeetingDocument($id)
    {
        return $this->getMeetingMapper()->findDocument($id);
    }

    /**
     * Returns a download for a meeting document
     *
     * @param \Decision\Model\MeetingDocument $meetingDocument
     *
     * @return response|null
     */
    public function getMeetingDocumentDownload($meetingDocument)
    {
        if (!$this->isAllowed('view_documents', 'meeting')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view meeting documents.')
            );
        }

        if (is_null($meetingDocument)) {
            return null;
        }

        $path = $meetingDocument->getPath();
        $extension = $extension = pathinfo($path, PATHINFO_EXTENSION);
        $fileName = $meetingDocument->getName() . '.' . $extension;

        return $this->getFileStorageService()->downloadFile($path, $fileName);
    }

    /**
     * Returns a download for meeting notes
     *
     * @param \Decision\Model\Meeting $meeting
     *
     * @return response|null
     */
    public function getMeetingNotesDownload(\Decision\Model\Meeting $meeting)
    {
        if (!$this->isAllowed('view_notes', 'meeting')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view meeting notes.')
            );
        }

        if (is_null($meeting->getNotes())) {
            return null;
        }

        $path = $meeting->getNotes()->getPath();
        $fileName = $meeting->getType() . '-' . $meeting->getNumber() . '.pdf';

        return $this->getFileStorageService()->downloadFile($path, $fileName);
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
        $parts = explode('/', $data['meeting']);
        $meeting = $this->getMeeting($parts[0], $parts[1]);
        $path = $this->getFileStorageService()->storeUploadedFile($data['upload']);

        $meetingNotes = $meeting->getNotes();
        if (is_null($meetingNotes)) {
            $meetingNotes = new NotesModel();
            $meetingNotes->setMeeting($meeting);
        }
        $meetingNotes->setPath($path);

        $mapper = $this->getDecisionMapper();
        $mapper->persist($meetingNotes);

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

        $path = $this->getFileStorageService()->storeUploadedFile($data['upload']);

        $meeting = explode('/', $data['meeting']);
        $meeting = $this->getMeeting($meeting[0], $meeting[1]);

        $document = new MeetingDocument();
        $document->setPath($path);
        $document->setName($data['name']);
        $document->setMeeting($meeting);

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
     * Retrieves all authorizations for the given meeting number.
     *
     * @param integer $meetingNumber
     */
    public function getAllAuthorizations($meetingNumber)
    {
        if (!$this->isAllowed('view_all', 'authorization')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view all authorizations.')
            );
        }

        return $this->getAuthorizationMapper()->find($meetingNumber);
    }

    /**
     * Retrieves all authorizations for the given meeting number for the current user.
     *
     * @param integer $meetingNumber
     */
    public function getUserAuthorizations($meetingNumber)
    {
        if (!$this->isAllowed('view_own', 'authorization')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view authorizations.')
            );
        }

        $lidnr = $this->sm->get('user_auth')->getLidnr();

        return $this->getAuthorizationMapper()->find($meetingNumber, $lidnr);
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
     * @return \Decision\Mapper\Meeting
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
     * Get the authorization mapper.
     *
     * @return \Decision\Mapper\Authorization
     */
    public function getAuthorizationMapper()
    {
        return $this->sm->get('decision_mapper_authorization');
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
