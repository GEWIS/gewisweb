<?php

namespace User\Service;

use Application\Service\AbstractService;

use User\Model\NewUser as NewUserModel;

use Decision\Model\Member as MemberModel;

use Zend\Mail\Message;
use Zend\View\Model\ViewModel;

class Email extends AbstractService
{
    /**
     * Send registration email.
     *
     * @param NewUserModel $newUser
     * @param MemberModel $member
     */
    public function sendRegisterEmail(NewUserModel $newUser, MemberModel $member)
    {
        $body = $this->render('user/email/register', [
            'user' => $newUser,
            'member' => $member
        ]);

        $translator = $this->getTranslator();

        $message = new Message();

        $config = $this->getConfig();

        $message->addFrom($config['from']);
        $message->addTo($newUser->getEmail());
        $message->setSubject($translator->translate('Account activation code for the GEWIS Website'));
        $message->setBody($body);

        $this->getTransport()->send($message);
    }

    /**
     * Send password lost email.
     *
     * @param NewUserModel $activation
     * @param MemberModel $member
     */
    public function sendPasswordLostMail(NewUserModel $newUser, MemberModel $member)
    {
        $body = $this->render('user/email/reset', [
            'user' => $newUser,
            'member' => $member
        ]);

        $translator = $this->getTranslator();

        $message = new Message();

        $config = $this->getConfig();

        $message->addFrom($config['from']);
        $message->addTo($newUser->getEmail());
        $message->setSubject($translator->translate('Password reset code for the GEWIS Website'));
        $message->setBody($body);

        $this->getTransport()->send($message);
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
        $renderer = $this->getRenderer();

        $model = new ViewModel($vars);
        $model->setTemplate($template);

        return $renderer->render($model);
    }

    /**
     * Get the renderer for the email.
     *
     * @return PhpRenderer
     */
    public function getRenderer()
    {
        return $this->sm->get('ViewRenderer');
    }

    /**
     * Get the email transport.
     *
     * @return Zend\Mail\Transport\TransportInterface
     */
    public function getTransport()
    {
        return $this->sm->get('user_mail_transport');
    }

    /**
     * Get email configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        $config = $this->sm->get('config');
        return $config['email'];
    }
}
