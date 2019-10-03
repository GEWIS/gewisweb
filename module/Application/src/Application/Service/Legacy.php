<?php

namespace Application\Service;

use Exception;
use User\Model\User;
use Zend\Crypt\Password\Bcrypt;

/**
 * This service contains all bindings to legacy systems such as SuSOS and the old website.
 * Hopefully this service will no longer be needed in the future. For that reason ugly code is
 * tolerated inside this service.
 */
class Legacy extends AbstractService
{

    /**
     * Checks if a SuSOS pincode is correct.
     *
     * @throws Exception
     *
     * @param User $user
     * @param string $pincode
     *
     * @return bool indicating whether the pincode was correct.
     */
    public function checkPincode($user, $pincode)
    {
        return false;
    }

    /**
     * Checks a password against the old website's database and saves it in the new database if corrrect
     *
     * @throws Exception
     *
     * @param User $user
     * @param string $password
     * @param Bcrypt $bcrypt
     *
     * @return bool indicating if password was correct
     */
    public function checkPassword($user, $password, $bcrypt)
    {
        return false;

    }

    /**
     * Gets the infima.
     *
     * @return string
     */
    public function getInfima()
    {
        return 'Not implemented';
    }
}
