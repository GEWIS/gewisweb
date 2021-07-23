<?php

namespace Application\Extensions\Doctrine;

class AttributeDriver extends \Doctrine\ORM\Mapping\Driver\AttributeDriver
{
    /**
     * {@link \DoctrineModule\Service\DriverFactory} assumes the incorrect constructor for
     * {@link \Doctrine\ORM\Mapping\Driver\AttributeDriver}, hence initialisation of the latter fails. This extension
     * overwrites the constructor to fix this (until it is fixed in DoctrineModule).
     *
     * See https://github.com/doctrine/DoctrineModule/issues/728.
     */
    public function __construct($reader, $paths = null)
    {
        parent::__construct($paths);
    }
}
