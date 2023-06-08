<?php

declare(strict_types=1);

namespace Application\Service;

use Decision\Model\Member as MemberModel;
use Decision\Model\OrganInformation as OrganInformationModel;
use Laminas\Mail\Header\MessageId;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Mime;
use Laminas\Mime\Part as MimePart;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;

use function mb_encode_mimeheader;

/**
 * This service is used for sending emails.
 */
class Email
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly PhpRenderer $renderer,
        private readonly TransportInterface $transport,
        private readonly array $emailConfig,
    ) {
    }

    /**
     * Send an email.
     *
     * @param String $type    Type that this email belongs to. A key in the config file for email.
     * @param String $view    Template of the email
     * @param String $subject Subject of the email
     * @param array  $data    Variables that you want to have available in the template
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function sendEmail(
        string $type,
        string $view,
        string $subject,
        array $data,
    ): void {
        $message = $this->createMessageFromView($view, $data);

        $message->setFrom($this->emailConfig['from']['address'], $this->emailConfig['from']['name']);
        $message->setTo($this->emailConfig['to'][$type]['address'], $this->emailConfig['to'][$type]['name']);
        $message->setSubject(
            mb_encode_mimeheader(
                $subject,
                'UTF-8',
                'Q',
                '',
            ),
        );

        $this->transport->send($message);
    }

    /**
     * Send an email as a given user. The user will be added as reply-to header to the email.
     *
     * @param String      $type    Type that this email belongs to. A key in the config file for email.
     * @param String      $view    Template of the email
     * @param String      $subject Subject of the email
     * @param array       $data    Variables that you want to have available in the template
     * @param MemberModel $user    The user as which the email should be sent
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function sendEmailAsUser(
        string $type,
        string $view,
        string $subject,
        array $data,
        MemberModel $user,
    ): void {
        $message = $this->createMessageFromView($view, $data);

        $message->setFrom($this->emailConfig['from']['address'], $this->emailConfig['from']['name']);
        $message->setTo($this->emailConfig['to'][$type]['address'], $this->emailConfig['to'][$type]['name']);
        $message->setSubject(
            mb_encode_mimeheader(
                $subject,
                'UTF-8',
                'Q',
                '',
            ),
        );
        $message->setReplyTo(
            $user->getEmail(),
            mb_encode_mimeheader(
                $user->getFullName(),
                'UTF-8',
                'Q',
                '',
            ),
        );

        $this->transport->send($message);
    }

    /**
     * Send an email as a given user. The user will be added as reply-to header to the email.
     *
     * @param MemberModel $recipient The receiver of this email
     * @param String      $view      Template of the email
     * @param String      $subject   Subject of the email
     * @param array       $data      Variables that you want to have available in the template
     * @param MemberModel $user      The user as which the email should be sent
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function sendEmailAsUserToUser(
        MemberModel $recipient,
        string $view,
        string $subject,
        array $data,
        MemberModel $user,
    ): void {
        $message = $this->createMessageFromView($view, $data);

        $message->setFrom($this->emailConfig['from']['address'], $this->emailConfig['from']['name']);
        $message->setTo(
            $recipient->getEmail(),
            mb_encode_mimeheader(
                $recipient->getFullName(),
                'UTF-8',
                'Q',
                '',
            ),
        );
        $message->setSubject(
            mb_encode_mimeheader(
                $subject,
                'UTF-8',
                'Q',
                '',
            ),
        );
        $message->setReplyTo(
            $user->getEmail(),
            mb_encode_mimeheader(
                $user->getFullName(),
                'UTF-8',
                'Q',
                '',
            ),
        );

        $this->transport->send($message);
    }

    /**
     * Send an email as a given user. The user will be added as reply-to header to the email.
     *
     * @param string                $type    Type that this email belongs to. A key in the config file for email.
     * @param string                $view    Template of the email
     * @param string                $subject Subject of the email
     * @param array                 $data    Variables that you want to have available in the template
     * @param OrganInformationModel $organ   The organ as which the email should be sent
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function sendEmailAsOrgan(
        string $type,
        string $view,
        string $subject,
        array $data,
        OrganInformationModel $organ,
    ): void {
        $message = $this->createMessageFromView($view, $data);

        $message->setFrom($this->emailConfig['from']['address'], $this->emailConfig['from']['name']);
        $message->setTo($this->emailConfig['to'][$type]['address'], $this->emailConfig['to'][$type]['name']);
        $message->setSubject(
            mb_encode_mimeheader(
                $subject,
                'UTF-8',
                'Q',
                '',
            ),
        );
        $message->setReplyTo(
            $organ->getEmail(),
            mb_encode_mimeheader(
                $organ->getOrgan()->getAbbr(),
                'UTF-8',
                'Q',
                '',
            ),
        );

        $this->transport->send($message);
    }

    /**
     * Constructs the Message instance for a given view with given variables.
     *
     * @param string $view Template of the email
     * @param array  $data Variables that you want to have available in the template
     *
     * @return Message the constructed instance containing the given view as HTML body
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    private function createMessageFromView(
        string $view,
        array $data,
    ): Message {
        $body = $this->render($view, $data);

        $html = new MimePart($body);
        $html->setType(Mime::TYPE_HTML);

        $mimeMessage = new MimeMessage();
        $mimeMessage->setParts([$html]);

        $message = new Message();
        $message->getHeaders()->addHeader((new MessageId())->setId());
        $message->setBody($mimeMessage);

        return $message;
    }

    /**
     * Render a template with given variables.
     *
     * @param array $vars
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
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
