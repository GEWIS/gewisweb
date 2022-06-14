<?php

namespace User\Service;

use Decision\Model\Member as MemberModel;
use Laminas\Mail\Message;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use User\Model\NewUser as NewUserModel;

class Email
{
    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var PhpRenderer
     */
    private PhpRenderer $renderer;

    /**
     * @var TransportInterface
     */
    private TransportInterface $transport;

    /**
     * @var array
     */
    private array $emailConfig;

    /**
     * @param Translator $translator
     * @param PhpRenderer $renderer
     * @param TransportInterface $transport
     * @param array $emailConfig
     */
    public function __construct(
        Translator $translator,
        PhpRenderer $renderer,
        TransportInterface $transport,
        array $emailConfig,
    ) {
        $this->translator = $translator;
        $this->renderer = $renderer;
        $this->transport = $transport;
        $this->emailConfig = $emailConfig;
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

        $message = new Message();

        $message->addFrom($this->emailConfig['from']);
        $message->addTo($member->getEmail());
        $message->setSubject($this->translator->translate('Account activation code for the GEWIS Website'));
        $message->setBody($body);

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

        $message = new Message();

        $message->addFrom($this->emailConfig['from']);
        $message->addTo($member->getEmail());
        $message->setSubject($this->translator->translate('Password reset code for the GEWIS Website'));
        $message->setBody($body);

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
