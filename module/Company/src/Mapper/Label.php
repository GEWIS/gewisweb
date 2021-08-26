<?php

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\JobLabel;
use Company\Model\JobLabel as LabelModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

/**
 * Mappers for labels.
 */
class Label extends BaseMapper
{
    /**
     * Finds the label with the given slug.
     *
     * @param int $labelSlug
     */
    public function findLabel($labelSlug)
    {
        return $this->getRepository()->findOneBy(['slug' => $labelSlug]);
    }

    /**
     * Finds the label with the given id.
     *
     * @param int $labelId
     */
    public function findLabelById($labelId)
    {
        return $this->getRepository()->findOneBy(['id' => $labelId]);
    }

    public function findVisibleLabelByLanguage($labelLanguage)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c')
            ->select('c')->where('c.language=:lang')
            ->setParameter('lang', $labelLanguage);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find the same label, but in the given language.
     */
    public function siblingLabel($label, $lang)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c');
        $qb->select('c')
            ->where('c.languageNeutralId=:labelId')
            ->andWhere('c.language=:language')
            ->setParameter('labelId', $label->getLanguageNeutralId())
            ->setParameter('language', $lang);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findAllLabelsById($labelId)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c')
            ->select('c')->where('c.languageNeutralId=:labelId')
            ->setParameter('labelId', $labelId);

        return $qb->getQuery()->getResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return JobLabel::class;
    }
}
