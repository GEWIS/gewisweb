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
     * Constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function save()
    {
        $this->em->flush();
    }

    public function delete($packetID)
    {
        $packet = $this->findEditablePacket($packetID);
        $this->findEditablePacket($packetID);
        $this->em->remove($packet);
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

    public function findEditablePacket($packetID)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('p');
        $qb->select('p')->where('p.id=:packetID');
        $qb->setParameter('packetID', $packetID);
        $qb->setMaxResults(1);
        $packets = $qb->getQuery()->getResult();
        if (count($packets) != 1) {
            return;
        }

        return $packets[0];
    }
    public function insertPacketIntoCompany($company)
    {
        $packet = new PacketModel($this->em);

        $packet->setCompany($company);
        $this->em->persist($packet);

        return $packet;
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\CompanyPacket');
    }
}
