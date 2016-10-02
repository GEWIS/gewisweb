<?php

namespace User\Authentication;

use \Zend\Authentication\AuthenticationService as ZendAuthService;

class AuthenticationService extends ZendAuthService
{
    public function getIdentity()
    {
        $storage = $this->getStorage();
        if ($storage->isEmpty()) {
            return;
        }
        $mapper = $this->getAdapter()->getMapper();
        return $mapper->findByLidnr($storage->read()->getLidnr());
    }
}