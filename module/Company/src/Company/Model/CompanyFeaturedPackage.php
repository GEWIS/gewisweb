<?php

namespace Company\Model;

use Doctrine\ORM\Mapping as ORM;

//use Doctrine\Common\Collections\ArrayCollection;
//use Zend\Permissions\Acl\Role\RoleInterface;
//use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * CompanyFeaturedPackage model.
 *
 * @ORM\Entity
 */
class CompanyFeaturedPackage extends CompanyPackage //implements RoleInterface, ResourceInterface
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // todo
    }

    public function publish()
    {
        // todo
    }

    public function unpublish()
    {
        // todo
    }

    public function create()
    {
        // todo
    }

    public function save()
    {
        // todo   
    }

    public function delete()
    {
        // todo
    }
}
