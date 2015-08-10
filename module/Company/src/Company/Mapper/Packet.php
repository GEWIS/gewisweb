<?php

namespace Company\Mapper;

use Company\Model\CompanyPacket as PacketModel;
use Doctrine\ORM\EntityManager;

/**
 * Mappers for packet.
 *
 * NOTE: Packets will be modified externally by a script. Modifycations will be
 * overwritten.
 */
class Packet
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

    public function save(){
        $this->em->flush();
    }

    /**
     * Find all Packets.
     *
     * @return array
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }
    
    
    public function insertPacketIntoCompany($company){
        $packet = new PacketModel($this->em);

        $packet->setCompany($company);
        $this->em->persist($packet);
//        $this->em->persist($job->getCompany());
        
//        $this->em->merge($company);
//        $this->em->merge($job);

        return $packet;
    }

    
    
    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\Pqcket');
    }
}
