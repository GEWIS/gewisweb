<?php

namespace User\Service;

use Application\Service\AbstractService;

use Company\Model\Company as CompanyModel;

use User\Model\NewCompany;
use Zend\Mail\Message;
use Zend\View\Model\ViewModel;

class CompanyEmail extends AbstractService
{
    /**
     * Send registration email.
     *
     * @param CompanyModel $company
     * @param NewCompany $newcompany
     */
    public function sendActivationEmail(CompanyModel $company, NewCompany $newCompany)
    {

        $body = $this->render('user/email/company-activation.phtml', [
            'company' => $company,
            'newcompany' => $newCompany
        ]);

        $translator = $this->getServiceManager()->get('translator');

        $message = new Message();

        $config = $this->getConfig();

        $message->addFrom($config['from']);
        $message->addTo($company->getContactEmail());
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
    /**public function sendPasswordLostMail(NewUserModel $newUser, MemberModel $member)
    {
        $body = $this->render('user/email/reset', [
            'user' => $newUser,
            'member' => $member
        ]);

        $translator = $this->getServiceManager()->get('translator');

        $message = new Message();

        $config = $this->getConfig();

        $message->addFrom($config['from']);
        $message->addTo($newUser->getEmail());
        $message->setSubject($translator->translate('Password reset code for the GEWIS Website'));
        $message->setBody($body);

        $this->getTransport()->send($message);
    }*/

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
