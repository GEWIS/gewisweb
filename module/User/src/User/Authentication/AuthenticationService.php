<?php

namespace User\Authentication;

use Laminas\Authentication\AuthenticationService as LaminasAuthService;

class AuthenticationService extends LaminasAuthService
{
    protected $identity = null;

    public function getIdentity()
    {
        if (null !== $this->identity) {
            return $this->identity;
        }
        $storage = $this->getStorage();
        if ($storage->isEmpty()) {
            return;
        }
        $mapper = $this->getAdapter()->getMapper();
        $user = $storage->read();
        if (is_object($user)) {
            $user = $user->getLidnr();
        }
        $this->identity = $mapper->findByLidnr($user);

        return $this->identity;
    }
}
