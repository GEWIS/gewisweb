<?php

namespace User\Authentication;

use \Zend\Authentication\AuthenticationService as ZendAuthService;

class AuthenticationService extends ZendAuthService
{
    protected $identity = null;
    public function getIdentity()
    {
        if ($this->identity !== null) {
            return $this->identity;
        }
        $storage = $this->getStorage();
        if ($storage->isEmpty()) {
            return;
        }
        $mapper = $this->getAdapter()->getMapper();
        $this->identity = $mapper->findByLidnr($storage->read()->getLidnr());
        return $this->identity;
    }
}
