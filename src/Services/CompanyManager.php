<?php


namespace App\Services;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CompanyManager extends BaseEntityManager
{
    public function __construct(
        ElasticSearchManager $elasticSearchManager,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ) {
        parent::__construct($elasticSearchManager, $entityManager, $validator);
    }
}