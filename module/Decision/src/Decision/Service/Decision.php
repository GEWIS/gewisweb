<?php

namespace Decision\Service;

use Application\Service\AbstractAclService;

use Decision\Model\Authorization as AuthorizationModel;
use Decision\Model\MeetingNotes as NotesModel;

use Decision\Model\MeetingDocument;
use User\Permissions\NotAllowedException;

/**
 * Decision service.
 */
class Decision extends AbstractAclService
{

    /**
     * Get all meetings.
     *
     * @param int|null $limit The amount of meetings to retrieve, default is all
     * @return array Of all meetings
     */
    public function getMeetings($limit = null)
    {
        if (!$this->isAllowed('list_meetings')) {
            $translator = $this->getTranslator();

            throw new NotAllowedException(
                $translator->translate('You are not allowed to list meetings.')
            );
        }

        return $this->getMeetingMapper()->findAll($limit);
    }

    /**
     * Get past meetings.
     *
     * @param int|null $limit The amount of meetings to retrieve, default is all
     * @param string|null $type Constraint on the type of the meeting, default is none
     * @return array Of all meetings
     */
    public function getPastMeetings($limit = null, $type = null)
    {
        if (!$this->isAllowed('list_meetings')) {
            $translator = $this->getTranslator();

            throw new NotAllowedException(
                $translator->translate('You are not allowed to list meetings.')
            );
        }

        return $this->getMeetingMapper()->findPast($limit, $type);
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
     * Returns the latest upcoming AV or null if there is none.
     *
     * @return \Decision\Model\Meeting|null
     */
    public function getLatestAV()
    {
        return $this->getMeetingMapper()->findLatestAV();
    }

    /**
     * Returns the closest upcoming meeting for members
     *
     * @return \Decision\Model\Meeting|null
     */
    public function getUpcomingMeeting()
    {
        return $this->getMeetingMapper()->findUpcomingMeeting();
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

    public function deleteDocument($post)
    {
        if (!$this->isAllowed('delete_document', 'meeting')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to delete meeting documents.')
            );
        }
        $id = $post->toArray()['document'];
        $document = $this->getMeetingDocument($id);
        $this->getMeetingMapper()->remove($document);
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
     *
     * @return array
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
     * Gets the authorization of the current user for the given meeting
     *
     * @param integer $meetingNumber
     *
     * @return \Decision\Model\Authorization|null
     */
    public function getUserAuthorization($meetingNumber)
    {
        if (!$this->isAllowed('view_own', 'authorization')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view authorizations.')
            );
        }

        $lidnr = $this->sm->get('user_service_user')->getIdentity()->getLidnr();

        return $this->getAuthorizationMapper()->findUserAuthorization($meetingNumber, $lidnr);
    }

    public function createAuthorization($data)
    {
        $form = $this->getAuthorizationForm();
        $authorization = new AuthorizationModel();
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }
        $user = $this->sm->get('user_service_user')->getIdentity();
        $authorizer = $user->getMember();
        $recipient = $this->getMemberMapper()->findByLidnr($data['recipient']);
        if (is_null($recipient) || $recipient->getLidnr() === $authorizer->getLidnr()) {
            return false;
        }

        $meeting = $this->getLatestAV();
        if (is_null($meeting)) {
            return false;
        }

        $authorization->setAuthorizer($authorizer);
        $authorization->setRecipient($recipient);
        $authorization->setMeetingNumber($meeting->getNumber());
        $this->getAuthorizationMapper()->persist($authorization);

        // Send an email to the recipient
        $this->getEmailService()->sendEmailAsUserToUser(
            $recipient,
            'email/authorization_received',
            'Machtiging ontvangen | Authorization received',
            ['authorization' => $authorization],
            $authorizer
        );

        // Send a confirmation email to the authorizing member
        $this->getEmailService()->sendEmailAsUserToUser(
            $authorizer,
            'email/authorization_sent',
            'Machtiging verstuurd | Authorization sent',
            ['authorization' => $authorization],
            $recipient
        );

        return $authorization;

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
     * Get the Authorization form.
     *
     * @return \Decision\Form\Authorization
     */
    public function getAuthorizationForm()
    {
        if (!$this->isAllowed('create', 'authorization')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not authorize people.')
            );
        }

        return $this->sm->get('decision_form_authorization');
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
     * Get the email service.
     *
     * @return \Application\Service\Email
     */
    public function getEmailService()
    {
        return $this->sm->get('application_service_email');
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
     * Get the member mapper.
     *
     * @return \Decision\Mapper\Member
     */
    public function getMemberMapper()
    {
        return $this->sm->get('decision_mapper_member');
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

    /**
     * Returns whether the current role is allowed to view files.
     * @return bool
     */
    public function isAllowedToBrowseFiles()
    {
        return $this->isAllowed('browse', 'files');
    }
}
