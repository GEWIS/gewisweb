<?php

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\JobLabel as JobLabelModel;

/**
 * Mappers for labels.
 */
class Label extends BaseMapper
{
    /**
     * Finds the label with the given slug.
     *
     * @param int $jobLabelId
     *
     * @return JobLabelModel|null
     */
    public function find(int $jobLabelId): ?JobLabelModel
    {
        return $this->getRepository()->find($jobLabelId);
    }

    /**
     * @return array
     */
    public function findVisibleLabels(): array
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('c')
            ->select('c')
            ->where('c.hidden = :hidden')
            ->setParameter('hidden', false);

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
            ->setParameter('jobLabelId', $label->getLanguageNeutralId())
            ->setParameter('language', $lang);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return JobLabeLModel::class;
    }
}
