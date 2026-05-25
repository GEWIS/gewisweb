<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\Signup;
use App\Entity\Activity\SignupFieldValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SignupFieldValue>
 */
class SignupFieldValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            SignupFieldValue::class,
        );
    }

    /**
     * Finds all field values associated with the $signup.
     *
     * @return SignupFieldValue[]
     */
    public function getFieldValuesBySignup(Signup $signup): array
    {
        return $this->findBy(['signup' => $signup]);
    }
}
