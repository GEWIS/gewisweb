<?php

namespace User\Authentication;

use Zend\Authentication\AuthenticationService as ZendAuthService;

class CompanyAuthenticationService extends ZendAuthService
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
        $company = $storage->read();
        if (is_object($company)) {
            $company = $company->getLidnr();
        }
        $this->identity = $mapper->findById($company);
        return $this->identity;
    }
}
