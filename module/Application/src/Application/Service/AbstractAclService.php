<?php

namespace Application\Service;

use User\Model\User;
use Zend\Permissions\Acl\Resource\ResourceInterface;

abstract class AbstractAclService extends AbstractService
{

    /**
     * Get the ACL.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    abstract public function getAcl();

    /**
     * Get the default resource ID.
     *
     * This is used by {@link isAllowed()} when no resource is specified.
     *
     * @return string
     */
    abstract protected function getDefaultResourceId();

    /**
     * Get the current user's role.
     *
     * @return User|string
     */
    public function getRole()
    {
        return $this->getServiceManager()->get('user_role');
    }


	
	/**
     * Check if a operation is allowed for an user.
     *
	 * If no user is given, this will assume the only role is guest
     * If no resource is given, this will use the resource given by
     * {@link getDefaultResourceId()}.
     *
     * @param string $operation Operation to be checked.
     * @param string|ResourceInterface $resource Resource to be checked
	 * @param User $user user to check operation for if null then current user is used     
	 *
     * @return boolean
     */
    public function isAllowed($operation, $resource=null, $user=null)
    {
		
        if (null === $resource) {
            $resource = $this->getDefaultResourceId();
        }
		if (null === $user){
			$user = $this -> getRole();
		}
		
		
		$roles = array();
		if ($user instanceof User ){
			$roles = $user->getRoleNames();
		}else{
			$roles[] = "guest";
		}
		
		var_dump($roles);
		
		foreach($roles as $role){
			if($this->getAcl()->isAllowed($role, $resource, $operation)){
				return true;
			}
		}
		return false;
    }

    /**
     * Checks if an operation is allowed, and if not it throws a 403 error
     * If no resource is given, this will use the resource given by
     * {@link getDefaultResourceId()}.
     *
     * @param string $operation Operation to be checked.
     * @param string|ResourceInterface $resource Resource to be checked
     *
     * @throws \User\Permission\NotAllowedException if ths user is not allowed to get the resource
     */
    public function allowedOrException($operation, $resource = null, $what = null, $user = null)
    {
        if (is_null($what)) {
            $what = 'this';
;       }

        if (!$this->isAllowed($user, $resource, $operation)) {
            throw new \User\Permissions\NotAllowedException(
                'Not allowed to view ' . $what
            );
        }
    }
}
