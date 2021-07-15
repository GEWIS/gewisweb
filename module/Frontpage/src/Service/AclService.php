<?php

namespace Frontpage\Service;

use Doctrine\Common\Collections\Collection;
use Laminas\I18n\Translator\TranslatorInterface;
use User\Authentication\AuthenticationService;

class AclService extends \User\Service\AclService
{
    private Collection $pages;

    public function setPages(Collection $pages)
    {
        // Recreate the ACL to erase old page permissions.
        $this->createAcl();
        $this->pages = $pages;
        foreach ($this->pages as $page) {
            $requiredRole = $page->getRequiredRole();
            $this->acl->addResource($page);
            $this->acl->allow($requiredRole, $page, 'view');
        }
    }

    protected function createAcl()
    {
        parent::createAcl();

        $this->acl->addResource('page');
        $this->acl->addResource('poll');
        $this->acl->addResource('poll_comment');
        $this->acl->addResource('news_item');

        $this->acl->allow('user', 'poll', ['vote', 'request']);
        $this->acl->allow('user', 'poll_comment', ['view', 'create', 'list']);
    }
}
