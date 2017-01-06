<?php

namespace Company\Mapper;

use Company\Model\Job as JobModel;
use Doctrine\ORM\EntityManager;

/**
 * Mappers for jobs.
 *
 * NOTE: Jobs will be modified externally by a script. Modifications will be
 * overwritten.
 */
class Job
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

    /**
     * Find all jobs.
     *
     * @return array
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Saves all modified entities that are marked persistant
     *
     */
    public function save()
    {
        $this->em->flush();
    }

    /**
     *
     * Checks if $slugName is only used by object identified with $cid
     *
     * @param string $slugName The slugName to be checked
     * @param int $cid The id to ignore
     *
     */
    public function isSlugNameUnique($companySlug, $slugName, $jid, $category)
    {
        $objects = $this->findJobBySlugName($companySlug, $slugName, $category);
        foreach ($objects as $job) {
            if ($job->getID() != $jid && $category != $job->getCategory()->getId()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Inserts a job into a given package
     *
     * @param mixed $package
     */
    public function insertIntoPackage($package, $lang, $languageIndependentId)
    {
        $job = new JobModel($this->em);
        $job->setLanguage($lang);
        $job->setLanguageIndependentId($languageIndependentId);
        $this->em->persist($job);
        $this->em->flush();
        if ($id == -1) {
            $id = $category->getId();
        }

        $job->setPackage($package);

        if ($id == -1) {
            $id = $category->getId();
        }
        $job->setLanguageIndependentId($id);
        return $job;
    }

    /**
     * Find all jobs identified by $jobSlugName that are owned by a company
     * identified with $companySlugName
     *
     * @param mixed $companySlugName
     * @param mixed $jobSlugName
     */
    public function findJobByCategory($jobCategory)
    {
        $qb = $this->getRepository()->createQueryBuilder('j');
        $qb->select('j')->join('j.category', 'cat')->where('cat.slug =:category');
        $qb->setParameter('category', $jobCategory);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all jobs identified by $jobSlugName that are owned by a company
     * identified with $companySlugName
     *
     * @param mixed $companySlugName
     * @param mixed $jobSlugName
     */
    public function findHiddenJobByLanguageIndependentId($companySlugName, $languageIndependentId)
    {
        $qb = $this->getRepository()->createQueryBuilder('j');
        $qb->select('j')->join('j.package', 'p')->join('p.company', 'c')->where('j.languageIndependentId=:jobId')
            ->andWhere('c.slugName=:companySlugName');
        $qb->setParameter('jobId', $languageIndependentId);
        $qb->setParameter('companySlugName', $companySlugName);

        return $qb->getQuery()->getResult();
    }
    /**
     * Find all jobs identified by $jobSlugName that are owned by a company
     * identified with $companySlugName
     *
     * @param mixed $companySlugName
     * @param mixed $jobSlugName
     */
    public function findJobBySlugName($companySlugName, $jobSlugName, $category)
    {
        $qb = $this->getRepository()->createQueryBuilder('j');
        $qb->select('j')->join('j.package', 'p')->join('p.company', 'c')->join('j.category', 'cat')->where('j.slugName=:jobId')
            ->andWhere('cat.slug=:category')->andWhere('c.slugName=:companySlugName');
        $qb->setParameter('jobId', $jobSlugName);
        $qb->setParameter('companySlugName', $companySlugName);
        $qb->setParameter('category', $category);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all jobs that are owned by a company identified with $companySlugName
     *
     * @param mixed $companySlugName
     */
    public function findJobByCompanySlugName($companySlugName, $jobCategory)
    {
        $qb = $this->getRepository()->createQueryBuilder('j');
        $qb->select('j')->join('j.package', 'p')->join('p.company', 'c')->join('j.category', 'cat')->where('c.slugName=:companySlugName')->andWhere('cat.slug=:jobCategory');
        $qb->setParameter('companySlugName', $companySlugName);
        $qb->setParameter('jobCategory', $jobCategory);

        return $qb->getQuery()->getResult();
    }


    public function persist($job)
    {
        $this->em->persist($job);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\Job');
    }
    public function createObjectSelectConfig($targetClass, $property, $label, $name, $locale)
    {
        return [
            'name' => $name,
            'type' => 'DoctrineModule\Form\Element\ObjectSelect',
            'options' => [
                'label' => $label,
                'object_manager' => $this->em,
                'target_class' => $targetClass,
                'property' => $property,
                'find_method'    => [
                    'name'   => 'findBy',
                    'params' => [
                        'criteria' => ['language' => $locale],
                        // Use key 'orderBy' if using ORM
                        //'orderBy'  => ['lastname' => 'ASC'],

                    ],
                ],
            ]
            //'attributes' => [
            //'class' => 'form-control input-sm'
            //]
        ];

    }
}
