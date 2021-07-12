<?php

namespace User\Service;

use User\Model\NewUser as NewUserModel;

use Decision\Model\Member as MemberModel;

use Laminas\Mail\Message;
use Laminas\Mail\Transport\TransportInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;

class Email
{

    /**
     * @var Translator
     */
    private $translator;

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

    public function __construct(Translator $translator, PhpRenderer $renderer, TransportInterface $transport, array $emailConfig)
    {
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
    public function sendRegisterEmail(NewUserModel $newUser, MemberModel $member)
    {
        $body = $this->render(
            'user/email/register',
            [
            'user' => $newUser,
            'member' => $member
            ]
        );


        $message = new Message();

        $message->addFrom($this->emailConfig['from']);
        $message->addTo($newUser->getEmail());
        $message->setSubject($this->translator->translate('Account activation code for the GEWIS Website'));
        $message->setBody($body);

        $this->transport->send($message);
    }

    /**
     * Send password lost email.
     *
     * @param NewUserModel $activation
     * @param MemberModel $member
     */
    public function sendPasswordLostMail(NewUserModel $newUser, MemberModel $member)
    {
        $body = $this->render(
            'user/email/reset',
            [
            'user' => $newUser,
            'member' => $member
            ]
        );


        $message = new Message();

        $message->addFrom($this->emailConfig['from']);
        $message->addTo($newUser->getEmail());
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
     * @return string/
     */
    public function render($template, $vars)
    {
        $model = new ViewModel($vars);
        $model->setTemplate($template);

        return $this->renderer->render($model);
    }
}
