<?php

namespace Application\Service;

use Application\Service\AbstractService;

use User\Model\NewUser as NewUserModel;

use Decision\Model\Member as MemberModel;

use Zend\Mail\Message;
use Zend\View\Model\ViewModel;
use Activity\Model\Activity as ActivityModel;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Message as MimeMessage;

/**
 * This service is used for sending emails.
 * @package Application\Service
 */
class Email extends AbstractService
{

    /**
     * Send an email.
     *
     * @param $type String Type that this email belongs to. A key in the config file for email.
     * @param $view String Template of the email
     * @param $subject String Subject of the email
     * @param $data array Variables that you want to have available in the template.
     */
    public function sendEmail($type, $view, $subject, $data)
    {
        $body = $this->render($view, $data);

        $html = new MimePart($body);
        $html->type = "text/html";

        $mimeMessage = new MimeMessage();
        $mimeMessage->setParts([$html]);

        $message = new Message();

        $config = $this->getConfig();

        $message->addFrom($config['from']);
        $message->addTo($config['to'][$type]);
        $message->setSubject($subject);
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
     * @return \Zend\Mail\Transport\TransportInterface
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
