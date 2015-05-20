<?php

namespace Activity\Mapper;

use Doctrine\ORM\EntityManager;

class Signup
{

    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function isSignedUp($activityId, $userId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\ActivitySignup', 'a')
            ->where('a.user_id = ?1')
            ->andWhere('a.activity_id = ?2')
            ->setParameters([
                1 => $userId,
                2 => $activityId
            ]);
        $result = $qb->getQuery()->getResult();
        return count($result) != 0;
    }
	public function getSignedUp($activityId)
    {
        $qb = $this->em->createQueryBuilder();
		
		//get all users that have signed up for the activity
        $qb->select('a.user_id')
            ->from('Activity\Model\ActivitySignup', 'a')
            ->where('a.activity_id = ?1')
            ->setParameters([
                1 => $activityId
            ]);
        $result = $qb->getQuery()->getResult();
		
		//get all names with the corresponding member numbers
		$names = array();
		foreach($result as $lidnr){
			$qb2 = $this->em->createQueryBuilder();
			$qb2->select('b.lastName, b.middleName, b.firstName')
				->from('Decision\Model\Member', 'b')
				->where('b.lidnr = ?1')
				->setParameters([
					1 => $lidnr['user_id']
				]);
			$nameArray = $qb2->getQuery()->getResult()[0];
			if(strlen($nameArray["middleName"]) == 0){
				$names[] = $nameArray["firstName"] . " " . $nameArray["lastName"];
			}else{
				$names[] = $nameArray["firstName"] . " " . $nameArray["middleName"] . " " . $nameArray["lastName"];
			}
		}
        return $names;
    }

}
