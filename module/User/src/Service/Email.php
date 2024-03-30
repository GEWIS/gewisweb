<?php

declare(strict_types=1);

namespace User\Service;

use Company\Model\Company as CompanyModel;
use Decision\Model\Member as MemberModel;
use Laminas\Mail\Header\MessageId;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Mime;
use Laminas\Mime\Part as MimePart;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use User\Model\NewCompanyUser as NewCompanyUserModel;
use User\Model\NewUser as NewUserModel;

use function mb_encode_mimeheader;

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
     * Send registration email.
     */
    public function sendRegisterEmail(
        NewUserModel $newUser,
        MemberModel $member,
    ): void {
        $body = $this->render(
            'user/email/register',
            [
                'user' => $newUser,
                'member' => $member,
            ],
        );

        $html = new MimePart($body);
        $html->setType(Mime::TYPE_HTML);

        $mimeMessage = new MimeMessage();
        $mimeMessage->addPart($html);

        $message = new Message();
        $message->getHeaders()->addHeader((new MessageId())->setId());
        $message->setFrom($this->emailConfig['from']['address'], $this->emailConfig['from']['name']);
        $message->setTo(
            $member->getEmail(),
            mb_encode_mimeheader(
                $member->getFullName(),
                'UTF-8',
                'Q',
                '',
            ),
        );
        $message->setSubject('Your account for the GEWIS website');
        $message->setBody($mimeMessage);

        $this->transport->send($message);
    }

    public function sendCompanyRegisterMail(
        NewCompanyUserModel $newCompanyUser,
        CompanyModel $company,
    ): void {
        $body = $this->render(
            'user/email/company-register',
            [
                'user' => $newCompanyUser,
                'company' => $company,
            ],
        );

        $html = new MimePart($body);
        $html->setType(Mime::TYPE_HTML);

        $mimeMessage = new MimeMessage();
        $mimeMessage->addPart($html);

        $message = new Message();
        $message->getHeaders()->addHeader((new MessageId())->setId());
        $message->setFrom($this->emailConfig['from']['address'], $this->emailConfig['from']['name']);
        $message->setTo(
            $newCompanyUser->getEmail(),
            mb_encode_mimeheader(
                $newCompanyUser->getCompany()->getRepresentativeName(),
                'UTF-8',
                'Q',
                '',
            ),
        );
        $message->setSubject('Your company account for the GEWIS Career Platform');
        $message->setBody($mimeMessage);

        $this->transport->send($message);
    }

    /**
     * Send password lost email.
     */
    public function sendPasswordLostMail(
        NewUserModel $newUser,
        MemberModel $member,
    ): void {
        $body = $this->render(
            'user/email/reset',
            [
                'user' => $newUser,
                'member' => $member,
            ],
        );

        $html = new MimePart($body);
        $html->setType(Mime::TYPE_HTML);

        $mimeMessage = new MimeMessage();
        $mimeMessage->addPart($html);

        $message = new Message();
        $message->getHeaders()->addHeader((new MessageId())->setId());
        $message->setFrom($this->emailConfig['from']['address'], $this->emailConfig['from']['name']);
        $message->setTo(
            $member->getEmail(),
            mb_encode_mimeheader(
                $member->getFullName(),
                'UTF-8',
                'Q',
                '',
            ),
        );
        $message->setSubject('Password reset request for the GEWIS website');
        $message->setBody($mimeMessage);

        $this->transport->send($message);
    }

    public function sendCompanyPasswordLostMail(
        NewCompanyUserModel $newCompanyUser,
        CompanyModel $company,
    ): void {
        $body = $this->render(
            'user/email/company-reset',
            [
                'user' => $newCompanyUser,
                'company' => $company,
            ],
        );

        $html = new MimePart($body);
        $html->setType(Mime::TYPE_HTML);

        $mimeMessage = new MimeMessage();
        $mimeMessage->addPart($html);

        $message = new Message();
        $message->getHeaders()->addHeader((new MessageId())->setId());
        $message->setFrom($this->emailConfig['from']['address'], $this->emailConfig['from']['name']);
        $message->setTo(
            $newCompanyUser->getEmail(),
            mb_encode_mimeheader(
                $newCompanyUser->getCompany()->getRepresentativeName(),
                'UTF-8',
                'Q',
                '',
            ),
        );
        $message->setSubject('Password reset request for the GEWIS Career Platform');
        $message->setBody($mimeMessage);

        $this->transport->send($message);
    }

    /**
     * Render a template with given variables.
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
