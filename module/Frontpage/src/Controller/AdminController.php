<?php

declare(strict_types=1);

namespace Frontpage\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Override;

class AdminController extends AbstractActionController
{
    #[Override]
    public function indexAction(): ViewModel
    {
        return new ViewModel();
    }
}
