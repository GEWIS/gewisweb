<?php

namespace Application\Service;

use Decision\Model\{
    Member as MemberModel,
    OrganInformation as OrganInformationModel,
};
use Laminas\Mail\Message;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part as MimePart;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use User\Model\User as UserModel;

/**
 * This service is used for sending emails.
 */
class Email
{
    public function __construct(
        private readonly PhpRenderer $renderer,
        private readonly TransportInterface $transport,
        private readonly array $emailConfig,
    ) {
    }

    /**
     * Send an email.
     *
     * @param String $type Type that this email belongs to. A key in the config file for email.
     * @param String $view Template of the email
     * @param String $subject Subject of the email
     * @param array $data Variables that you want to have available in the template
     */
    public function sendEmail(
        string $type,
        string $view,
        string $subject,
        array $data,
    ): void {
        $message = $this->createMessageFromView($view, $data);

        $message->addFrom($this->emailConfig['from']);
        $message->addTo($this->emailConfig['to'][$type]);
        $message->setSubject($subject);

        $this->transport->send($message);
    }

    /**
     * Send an email as a given user. The user will be added as reply-to header to the email.
     *
     * @param String $type Type that this email belongs to. A key in the config file for email.
     * @param String $view Template of the email
     * @param String $subject Subject of the email
     * @param array $data Variables that you want to have available in the template
     * @param MemberModel $user The user as which the email should be sent
     */
    public function sendEmailAsUser(
        string $type,
        string $view,
        string $subject,
        array $data,
        MemberModel $user,
    ): void {
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
     * @param MemberModel $recipient The receiver of this email
     * @param String $view Template of the email
     * @param String $subject Subject of the email
     * @param array $data Variables that you want to have available in the template
     * @param MemberModel $user The user as which the email should be sent
     */
    public function sendEmailAsUserToUser(
        MemberModel $recipient,
        string $view,
        string $subject,
        array $data,
        MemberModel $user,
    ): void {
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
     * @param string $type Type that this email belongs to. A key in the config file for email.
     * @param string $view Template of the email
     * @param string $subject Subject of the email
     * @param array $data Variables that you want to have available in the template
     * @param OrganInformationModel $organ The organ as which the email should be sent
     */
    public function sendEmailAsOrgan(
        string $type,
        string $view,
        string $subject,
        array $data,
        OrganInformationModel $organ,
    ): void {
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
     * @param string $view Template of the email
     * @param array $data Variables that you want to have available in the template
     *
     * @return Message the constructed instance containing the given view as HTML body
     */
    private function createMessageFromView(
        string $view,
        array $data,
    ): Message {
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
     * @return string
     */
    public function render(
        string $template,
        array $vars,
    ): string {
        $model = new ViewModel($vars);
        $model->setTemplate($template);

        return $this->renderer->render($model);
    }
}
