<?php

namespace Decision\Mapper;
use Decision\Model\CompanyInfo as companyinfo;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMappingBuilder;




class Settings
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

    /**
     * Find all available company information
     *
     * @param string $cName the name of the company who's information
     * will be fetched.
     *
     * @return array Information of company
     */
    public function findCompanyInfo($cName)
    {
        $builder = new ResultSetMappingBuilder($this->em);
        $builder->addRootEntityFromClassMetadata('Decision\Model\CompanyInfo', 'ci');

        $select = $builder->generateSelectClause(['ci' => 't1']);
        $sql = "SELECT $select FROM Company AS t1".
        " WHERE t1.name = '$cName'";

        $query = $this->em->createNativeQuery($sql, $builder);
        return $query->getResult();
    }

    /**
     * Find all available company information
     *
     * @param string $cName the name of the company who's information
     * will be fetched.
     *
     * @return array Information of company
     */
    public function findCompanyPackageInfo($cID)
    {
        $builder = new ResultSetMappingBuilder($this->em);
        $builder->addRootEntityFromClassMetadata('Decision\Model\CompanyPackageInfo', 'cpi');

        $select = $builder->generateSelectClause(['cpi' => 't1']);
        $sql = "SELECT $select FROM CompanyPackage AS t1".
            " WHERE t1.company_id = $cID";

        $query = $this->em->createNativeQuery($sql, $builder);
        return $query->getResult();
    }


    public function setCompanyData($collumns, $values, $company){
        $builder = new ResultSetMappingBuilder($this->em);
        //$builder->addRootEntityFromClassMetadata('Decision\Model\CompanyInfo', 'cpi');

        //$select = $builder->generateSelectClause(['cpi' => 't1']);

        $sql = "UPDATE Company SET ";
        for($i = 0; $i < count($collumns); $i++){
            if($i != count($collumns) - 1) {
                if(is_string($values[$i])) {
                    $sql .= "$collumns[$i] = '$values[$i]', ";
                }else{
                    $sql .= "$collumns[$i] = $values[$i], ";
                }
            }else{
                if(is_string($values[$i])) {
                    $sql .= "$collumns[$i] = '$values[$i]'";
                }else{
                    $sql .= "$collumns[$i] = $values[$i], ";
                }
            }
        }
        $sql .= " WHERE name = '$company'";

        $query = $this->em->createNativeQuery($sql, $builder);
        $query->getResult();

    }

}
