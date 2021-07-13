<?php

namespace Application\Service;

use Decision\Model\Member;
use Decision\Model\OrganInformation;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part as MimePart;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use User\Model\User;

/**
 * This service is used for sending emails.
 */
class Email
{
    /**
     * @var PhpRenderer
     */
    private $renderer;

    /**
     * @var TransportInterface
     */
    private $transport;

    /**
     * @var array
     */
    private $emailConfig;

    public function __construct(PhpRenderer $renderer, TransportInterface $transport, array $emailConfig)
    {
        $this->renderer = $renderer;
        $this->transport = $transport;
        $this->emailConfig = $emailConfig;
    }

    /**
     * Send an email.
     *
     * @param $type String Type that this email belongs to. A key in the config file for email.
     * @param $view String Template of the email
     * @param $subject String Subject of the email
     * @param $data array Variables that you want to have available in the template
     */
    public function sendEmail($type, $view, $subject, $data)
    {
        $message = $this->createMessageFromView($view, $data);

        $message->addFrom($this->emailConfig['from']);
        $message->addTo($this->emailConfig['to'][$type]);
        $message->setSubject($subject);

        $this->transport->send($message);
    }

    /**
     * Send an email as a given user. The user will be added as reply-to header to the email.
     *
     * @param $type String Type that this email belongs to. A key in the config file for email.
     * @param $view String Template of the email
     * @param $subject String Subject of the email
     * @param $data array Variables that you want to have available in the template
     * @param $user user The user as which the email should be sent
     */
    public function sendEmailAsUser($type, $view, $subject, $data, $user)
    {
        $message = $this->createMessageFromView($view, $data);

        $message->addFrom($this->emailConfig['from']);
        $message->addTo($this->emailConfig['to'][$type]);
        $message->setSubject($subject);
        $message->setReplyTo($user->getEmail());

        $this->transport->send($message);
    }

    /**
     * Send an email as a given user. The user will be added as reply-to header to the email.
     *
     * @param $recipient member The receiver of this email
     * @param $view String Template of the email
     * @param $subject String Subject of the email
     * @param $data array Variables that you want to have available in the template
     * @param $user member The user as which the email should be sent
     */
    public function sendEmailAsUserToUser($recipient, $view, $subject, $data, $user)
    {
        $message = $this->createMessageFromView($view, $data);

        $message->addFrom($this->emailConfig['from']);
        $message->addTo($recipient->getEmail());
        $message->setSubject($subject);
        $message->setReplyTo($user->getEmail());

        $this->transport->send($message);
    }

    /**
     * Send an email as a given user. The user will be added as reply-to header to the email.
     *
     * @param $type String Type that this email belongs to. A key in the config file for email.
     * @param $view String Template of the email
     * @param $subject String Subject of the email
     * @param $data array Variables that you want to have available in the template
     * @param $organ organInformation The organ as which the email should be sent
     */
    public function sendEmailAsOrgan($type, $view, $subject, $data, $organ)
    {
        $message = $this->createMessageFromView($view, $data);

        $message->addFrom($this->emailConfig['from']);
        $message->addTo($this->emailConfig['to'][$type]);
        $message->setSubject($subject);
        $message->setReplyTo($organ->getEmail());

        $this->transport->send($message);
    }

    /**
     * Constructs the Message instance for a given view with given variables.
     *
     * @param $view String Template of the email
     * @param $data array Variables that you want to have available in the template
     *
     * @return Message the constructed instance containing the given view as HTML body
     */
    private function createMessageFromView($view, $data)
    {
        $body = $this->render($view, $data);

        $html = new MimePart($body);
        $html->type = 'text/html';

        $mimeMessage = new MimeMessage();
        $mimeMessage->setParts([$html]);

        $message = new Message();
        $message->setBody($mimeMessage);

        return $message;
    }

    /**
     * Render a template with given variables.
     *
     * @param string $template
     * @param array $vars
     *
     * @return string/
     */
    public function render($template, $vars)
    {
        $model = new ViewModel($vars);
        $model->setTemplate($template);

        return $this->renderer->render($model);
    }
}
