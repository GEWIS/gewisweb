<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container as SessionContainer;

class IndexController extends AbstractActionController
{

    /**
     * Action to switch language.
     */
    public function langAction()
    {
        $session = new SessionContainer('lang');
        $session->lang = $this->params()->fromRoute('lang');

        if ($session->lang != 'en' && $session->lang != 'nl') {
            $session->lang = 'nl';
        }

        return $this->redirect()->toRoute('home');
    }
}
