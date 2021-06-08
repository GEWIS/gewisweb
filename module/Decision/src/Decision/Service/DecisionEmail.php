<?php


namespace Decision\Service;

use Application\Service\AbstractService;
use Company\Model\Company as CompanyModel;
use Decision\Model\Member as MemberModel;
use Zend\Mail\Message;
use Zend\View\Model\ViewModel;

class DecisionEmail extends AbstractService
{

    /**
     * Send registration email.
     *
     * @param CompanyModel $company
     */
    public function sendApprovalMail(CompanyModel $company)
    {
        $body = $this->render('email/companyUpdate', [
            'company' => $company
        ]);

        $translator = $this->getServiceManager()->get('translator');

        $message = new Message();

        $config = $this->getConfig();

        $message->addFrom($config['from']);
        $message->addTo($config['company_change']);
        $message->setSubject($translator->translate('Request to review changes made by company'));
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


