<?php

namespace Application\Service;



use Decision\Model\Member;
use Decision\Model\OrganInformation;
use User\Model\User;
use Zend\Mail\Message;
use Zend\Mail\Transport\TransportInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\View\Model\ViewModel;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Message as MimeMessage;
use Zend\View\Renderer\PhpRenderer;

/**
 * This service is used for sending emails.
 * @package Application\Service
 */
class Email implements ServiceManagerAwareInterface
{
    /**
     * Service manager.
     *
     * @var ServiceManager
     */
    protected $sm;

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
     * Send an email.
     *
     * @param $type String Type that this email belongs to. A key in the config file for email.
     * @param $view String Template of the email
     * @param $subject String Subject of the email
     * @param $data array Variables that you want to have available in the template.
     */
    public function sendEmail($type, $view, $subject, $data)
    {
        $message = $this->createMessageFromView($view, $data);

        $config = $this->getConfig();

        $message->addFrom($config['from']);
        $message->addTo($config['to'][$type]);
        $message->setSubject($subject);

        $this->getTransport()->send($message);
    }

    /**
     * Send an email as a given user. The user will be added as reply-to header to the email.
     *
     * @param $type String Type that this email belongs to. A key in the config file for email.
     * @param $view String Template of the email
     * @param $subject String Subject of the email
     * @param $data array Variables that you want to have available in the template.
     * @param $user User The user as which the email should be sent.
     */
    public function sendEmailAsUser($type, $view, $subject, $data, $user)
    {
        $message = $this->createMessageFromView($view, $data);

        $config = $this->getConfig();

        $message->addFrom($config['from']);
        $message->addTo($config['to'][$type]);
        $message->setSubject($subject);
        $message->setReplyTo($user->getEmail());

        $this->getTransport()->send($message);
    }

    /**
     * Send an email as a given user. The user will be added as reply-to header to the email.
     *
     * @param $recipient Member The receiver of this email.
     * @param $view String Template of the email
     * @param $subject String Subject of the email
     * @param $data array Variables that you want to have available in the template.
     * @param $user Member The user as which the email should be sent.
     */
    public function sendEmailAsUserToUser($recipient, $view, $subject, $data, $user)
    {
        $message = $this->createMessageFromView($view, $data);

        $config = $this->getConfig();

        $message->addFrom($config['from']);
        $message->addTo($recipient->getEmail());
        $message->setSubject($subject);
        $message->setReplyTo($user->getEmail());

        $this->getTransport()->send($message);
    }

    /**
     * Send an email as a given user. The user will be added as reply-to header to the email.
     *
     * @param $type String Type that this email belongs to. A key in the config file for email.
     * @param $view String Template of the email
     * @param $subject String Subject of the email
     * @param $data array Variables that you want to have available in the template.
     * @param $organ OrganInformation The organ as which the email should be sent.
     */
    public function sendEmailAsOrgan($type, $view, $subject, $data, $organ)
    {
        $message = $this->createMessageFromView($view, $data);

        $config = $this->getConfig();

        $message->addFrom($config['from']);
        $message->addTo($config['to'][$type]);
        $message->setSubject($subject);
        $message->setReplyTo($organ->getEmail());

        $this->getTransport()->send($message);
    }

    /**
     * Constructs the Message instance for a given view with given variables.
     *
     * @param $view String Template of the email
     * @param $data array Variables that you want to have available in the template.
     * @return Message The constructed instance containing the given view as HTML body.
     */
    private function createMessageFromView($view, $data)
    {
        $body = $this->render($view, $data);

        $html = new MimePart($body);
        $html->type = "text/html";

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
     * @return TransportInterface
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
