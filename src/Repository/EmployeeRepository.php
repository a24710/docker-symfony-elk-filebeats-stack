<?php


namespace App\Repository;


use App\Entity\Employee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EmployeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employee::class);
    }

    public function getEmployeeCount(): int
    {
        $qb = $this->createQueryBuilder('employee');
        $qb->select('COUNT(employee.id)');
        $result = $qb->getQuery()->getSingleScalarResult();

        return (int) $result;
    }
}

