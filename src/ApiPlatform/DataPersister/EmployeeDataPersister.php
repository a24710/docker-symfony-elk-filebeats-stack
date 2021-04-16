<?php


namespace App\ApiPlatform\DataPersister;


use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use App\Entity\Employee;
use App\Services\ElasticSearchManager;
use Doctrine\ORM\EntityManagerInterface;

class EmployeeDataPersister implements ContextAwareDataPersisterInterface
{
    protected ElasticSearchManager $elasticSearchManager;
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, ElasticSearchManager $elasticSearchManager)
    {
        $this->entityManager = $entityManager;
        $this->elasticSearchManager = $elasticSearchManager;
    }

    public function supports($data, array $context = []): bool
    {
        return ($data instanceof Employee);
    }

    public function persist($data, array $context = [])
    {
        $itemOperationName = $context['collection_operation_name'] ?? null;

        //add elastic search index to POST operations
        if ($itemOperationName === 'post'){
            $this->entityManager->persist($data);
            $this->entityManager->flush();
            $this->elasticSearchManager->indexEntity($data);
        }
    }

    public function remove($data, array $context = [])
    {
        $this->entityManager->remove($data);
        $this->entityManager->flush();
    }
}