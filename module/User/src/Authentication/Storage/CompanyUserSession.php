<?php

declare(strict_types=1);

namespace User\Authentication\Storage;

use Laminas\Authentication\Storage\Session as SessionStorage;

class CompanyUserSession extends SessionStorage
{
    /**
     * Construct the session storage for companies. Use a separate namespace for the session container to ensure users
     * and company users are separated.
     */
    public function __construct()
    {
        parent::__construct('Laminas_Auth_CompanyUser');
    }
}
