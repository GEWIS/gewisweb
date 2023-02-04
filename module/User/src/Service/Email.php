<?php

namespace User\Service;

use Company\Model\Company as CompanyModel;
use Decision\Model\Member as MemberModel;
use Laminas\Mail\Header\MessageId;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\Mime\{
    Mime,
    Part as MimePart,
    Message as MimeMessage,
};
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use User\Model\{
    NewCompanyUser as NewCompanyUserModel,
    NewUser as NewUserModel,
};

class Email
{
    public function __construct(
        private readonly Translator $translator,
        private readonly PhpRenderer $renderer,
        private readonly TransportInterface $transport,
        private readonly array $emailConfig,
    ) {
    }

    /**
     * Send registration email.
     *
     * @param NewUserModel $newUser
     * @param MemberModel $member
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
            ]
        );

        $html = new MimePart($body);
        $html->setType(Mime::TYPE_HTML);

        $mimeMessage = new MimeMessage();
        $mimeMessage->addPart($html);

        $message = new Message();
        $message->getHeaders()->addHeader((new MessageId())->setId());
        $message->addFrom($this->emailConfig['from']);
        $message->addTo($member->getEmail());
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
            ]
        );

        $html = new MimePart($body);
        $html->setType(Mime::TYPE_HTML);

        $mimeMessage = new MimeMessage();
        $mimeMessage->addPart($html);

        $message = new Message();
        $message->getHeaders()->addHeader((new MessageId())->setId());
        $message->addFrom($this->emailConfig['from']);
        $message->addTo($newCompanyUser->getEmail());
        $message->setSubject('Your company account for the GEWIS Career Platform');
        $message->setBody($mimeMessage);

        $this->transport->send($message);
    }

    /**
     * Send password lost email.
     *
     * @param NewUserModel $newUser
     * @param MemberModel $member
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
            ]
        );

        $html = new MimePart($body);
        $html->setType(Mime::TYPE_HTML);

        $mimeMessage = new MimeMessage();
        $mimeMessage->addPart($html);

        $message = new Message();
        $message->getHeaders()->addHeader((new MessageId())->setId());
        $message->addFrom($this->emailConfig['from']);
        $message->addTo($member->getEmail());
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
            ]
        );

        $html = new MimePart($body);
        $html->setType(Mime::TYPE_HTML);

        $mimeMessage = new MimeMessage();
        $mimeMessage->addPart($html);

        $message = new Message();
        $message->getHeaders()->addHeader((new MessageId())->setId());
        $message->addFrom($this->emailConfig['from']);
        $message->addTo($newCompanyUser->getEmail());
        $message->setSubject('Password reset request for the GEWIS Career Platform');
        $message->setBody($mimeMessage);

        $this->transport->send($message);
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
