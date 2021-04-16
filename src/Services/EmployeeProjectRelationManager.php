<?php


namespace App\Services;


use App\Entity\Employee;
use App\Entity\EmployeeProjectRelation;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;

class EmployeeProjectRelationManager
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(Employee $employee, Project $project, ?string $role, bool $flush): EmployeeProjectRelation
    {
        $relation = new EmployeeProjectRelation();
        $relation->setProject($project);
        $relation->setEmployee($employee);
        $relation->setRole($role);

        if ($flush){
            $this->entityManager->persist($relation);
            $this->entityManager->flush();
        }

        return $relation;
    }
}