<?php

namespace Application\Service;

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
     * @param \User\Model\User $user
     * @param string $pincode
     *
     * @return bool indicating whether the pincode was correct.
     * @throws \Exception
     *
     */
    public function checkPincode($user, $pincode)
    {
        return false;
    }

    /**
     * Checks a password against the old website's database and saves it in the new database if corrrect
     *
     * @param \User\Model\User $user
     * @param string $password
     * @param \Zend\Crypt\Password\Bcrypt $bcrypt
     *
     * @return bool indicating if password was correct
     * @throws \Exception
     *
     */
    public function checkPassword($user, $password, $bcrypt)
    {
        return false;
    }
}
