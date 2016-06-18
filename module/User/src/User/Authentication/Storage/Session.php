<?php

namespace User\Authentication\Storage;

use Zend\Authentication\Storage;

class Session extends Storage\Session
{

    public function setRememberMe($rememberMe = 0, $time = 1209600)
    {
        if ($rememberMe == 1) {
            $this->session->getManager()->rememberMe($time);
        }
    }

    public function forgetMe()
    {
        $this->session->getManager()->forgetMe();
    }

    public function getId()
    {
        return $this->session->getManager()->getId();
    }
}
