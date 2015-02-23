<?php

namespace Application\Service;

use Zend\Permissions\Acl\Acl;
use Zend\Permissions\Acl\Exception\InvalidArgumentException;

class AclWrapper extends Acl
{
	/**
	 * override of the default isAllowed method so that InvalidArgumentException is replaced by a return of false
	 * 
	 * @param $role role of the user that wants to access the resource operation
	 * @param $resource to be used by the role
	 * @param $operation to be executed on the resource
	 *
	 * @return boolean 
	 */
	public function isAllowed($role=NULL, $resource=NULL, $operation=NULL)
	{
		try{
			$some =  parent::isAllowed($role, $resource, $operation);
			return $some;
		}catch(InvalidArgumentException $e){
			return false;
		}
		return false;
	}
	
	/**
	 * override of the default allow method so that undefined role or resource is added to the acl
	 * and the InvalidArgumentException is not thrown anymore
	 *
	 * @param $role role of the user that wants to access the resource operation
	 * @param $resource to be used by the role
	 * @param $operation to be executed on the resource
	 *
	 * @return void
	 */
	public function allow($roles = null, $resources = null, $privileges = null, Assertion\AssertionInterface $assert = null){
		$safeParameters = $this->prepAllowDeny($roles, $resources);
		$roles = $safeParameters[0];
		$resources = $safeParameters[1];
		parent::allow($roles, $resources, $privileges, $assert);
	}
	
	/**
	 * override of the default deny method so that undefined role or resource is added to the acl
	 * and the InvalidArgumentException is not thrown anymore
	 *
	 * @param $roles roles of the user that wants to access the resources operations
	 * @param $resources to be used by the role
	 * @param $operation to be executed on the resource
	 *
	 * @return void
	 */
	public function deny($roles = null, $resources = null, $privileges = null, Assertion\AssertionInterface $assert = null){		
		$safeParameters = $this->prepAllowDeny($roles, $resources);
		$roles = $safeParameters[0];
		$resources = $safeParameters[1];
		parent::deny($roles, $resources, $privileges, $assert);
	}
	
	/**
	 * functional method that supports the allow and deny methods which adds all
	 * unknown roles and resources to the acl
	 *
	 * @param $role role of the user that wants to access the resource operation
	 * @param $resource to be used by the role
	 *
	 * @return void
	 */
	private function prepAllowDeny($roles = null, $resources = null){
		if(!is_Null($roles)){
			if(!is_Array($roles)){
				$roles = array($roles);
			}
			foreach($roles as $role){
				if(!$this->hasRole($role)){
					$this->addRole($role);
				}
			}
		}
		if(!is_Null($resources)){
			if(!is_Array($resources)){
				$resources = array($resources);
			}
			foreach($resources as $resource){
				if(!$this->hasResource($resource)){
					var_dump($resource);
					$this->addResource($resource);
				}
			}
		}
		return array($roles, $resources);
	}
}