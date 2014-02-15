<?php

namespace User\Service;

use User\Model\NewUser as NewUserModel;

use Decision\Model\Member as MemberModel;

use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\Mail\Message;
use Zend\View\Model\ViewModel;

class Email implements ServiceManagerAwareInterface
{

    /**
     * Service manager.
     *
     * @var ServiceManager
     */
    protected $sm;


    /**
     * Send registration email.
     *
     * @param NewUserModel $newUser
     * @param MemberModel $member
     */
    public function sendRegisterEmail(NewUserModel $newUser, MemberModel $member)
    {
        $body = $this->render('user/email/register', array(
            'user' => $newUser,
            'member' => $member
        ));

        $message = new Message();

        // TODO: configuration for this
        $message->addFrom('web@gewis.nl');
        $message->addTo($newUser->getEmail());
        $message->setBody($body);

        $this->send($message);
    }

    /**
     * Send a message.
     *
     * @param Message $message
     */
    public function send(Message $message)
    {
        // TODO: send the message
        var_dump($message);
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
        return $this->sm->geT('view_manager')->getRenderer();
    }

    /**
     * Set the service manager.
     *
     * @param ServiceManager $sm
     */
    public function setServiceManager(ServiceManager $sm)
    {
        $this->sm = $sm;
    }

    /**
     * Get the service manager.
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->sm;
    }
}
