<?php
namespace Activity\Service;

use Activity\Model\Activity as ActivityModel;
use Activity\Model\ActivitySignup;
use Application\Service\AbstractAclService;
use \Decision\Model\Member;

class Signup extends AbstractAclService
{
    /**
     * Get the ACL.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->getServiceManager()->get('activity_acl');
    }

    /**
     * Get the default resource ID.
     *
     * This is used by {@link isAllowed()} when no resource is specified.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'activitySignup';
    }

    /**
     * Get a list of all the members that are signed up for an activity
     *
     * @param ActivityModel $activity
     * @return array
     */
	public function getSignedUp(ActivityModel $activity){
		$signUpMapper = $this->getServiceManager()->get('activity_mapper_signup');
        return $signUpMapper->getSignedUp($activity->get('id'));
	}

    /**
     * Check if a member is signed up for an activity
     *
     * @param ActivityModel $activity
     * @param \Decision\Model\Member $user
     * @return boolean
     */
    public function isSignedUp(ActivityModel $activity, Member $user)
    {
        $signUpMapper = $this->getServiceManager()->get('activity_mapper_signup');
        return $signUpMapper->isSignedUp($activity->get('id'), $user->getLidnr());
    }

    /**
     * Sign up  an activity
     *
     * @param ActivityModel $activity
     */
    public function signUp(ActivityModel $activity)
    {
        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');

        // Find the current user
        $user = $this->getServiceManager()->get('user_role');
        if ($user === 'guest') {
            throw new \InvalidArgumentException('Guests can not create activities');
        }
        $user = $em->merge($user);

        $signup = new ActivitySignup();
        $signup->setActivity($activity);
        $signup->setUser($user);


        $em->persist($signup);
        $em->flush();
    }

    /**
     * Undo an activity sign up
     *
     * @param ActivityModel $activity
     * @param Member $user
     */
	public function signOff(ActivityModel $activity, Member $user)
    {
        $signUpMapper = $this->getServiceManager()->get('activity_mapper_signup');
        $signUp =  $signUpMapper->getSignUp($activity->get('id'), $user->getLidnr());

        // If the user was not signed up, no need to signoff anyway
        if (is_null($signUp)) {
            return;
        }

        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');
		$em->remove($signUp);
		$em->flush();
    }
}
