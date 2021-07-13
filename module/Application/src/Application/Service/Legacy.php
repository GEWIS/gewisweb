<?php

namespace Application\Service;

use Exception;
use Laminas\Crypt\Password\Bcrypt;
use User\Model\User;

/**
 * This service contains all bindings to legacy systems such as SuSOS and the old website.
 * Hopefully this service will no longer be needed in the future. For that reason ugly code is
 * tolerated inside this service.
 *
 * TODO: Verify whether we still need this class or not and remove it
 */
class Legacy
{
    /**
     * Checks if a SuSOS pincode is correct.
     *
     * @param User   $user
     * @param string $pincode
     *
     * @return bool indicating whether the pincode was correct
     *
     * @throws Exception
     */
    public function checkPincode($user, $pincode)
    {
        throw new Exception('This operation is not supported.');
    }

    /**
     * Checks a password against the old website's database and saves it in the new database if corrrect.
     *
     * @param User   $user
     * @param string $password
     * @param Bcrypt $bcrypt
     *
     * @return bool indicating if password was correct
     *
     * @throws Exception
     */
    public function checkPassword($user, $password, $bcrypt)
    {
        throw new Exception('This operation is not supported.');
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
