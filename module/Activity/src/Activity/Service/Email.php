<?php

namespace Activity\Service;

use Application\Service\AbstractService;

use User\Model\NewUser as NewUserModel;

use Decision\Model\Member as MemberModel;

use Zend\Mail\Message;
use Zend\View\Model\ViewModel;
use Activity\Model\Activity;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Message as MimeMessage;

class Email extends AbstractService
{

    /**
     * Send registration email.
     *
     * @param NewUserModel $newUser
     * @param MemberModel $member
     */
    public function sendActivityCreationEmail(Activity $activity)
    {
        $body = $this->render('email/activity', [
            'activity' => $activity
        ]);

        $translator = $this->getServiceManager()->get('translator');

        $html = new MimePart($body);
        $html->type = "text/html";

        $mimeMessage = new MimeMessage();
        $mimeMessage->setParts([$html]);

        $message = new Message();

        $config = $this->getConfig();

        $message->addFrom($config['from']);
        $message->addTo($config['to']['activity_creation']);
        $message->setSubject('Nieuwe activiteit aangemaakt op de GEWIS website | New activity was created on the GEWIS website');
        $message->setBody($mimeMessage);

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
        return $this->sm->get('view_manager')->getRenderer();
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
