<?php

namespace App\Services;

use App\Entity\BaseEntity;
use App\Entity\Company;
use App\Entity\Employee;
use App\Utils\EmployeeCSVExporter;
use App\Utils\EmployeeCSVImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmployeeManager extends BaseEntityManager
{
    protected EmployeeCSVExporter $employeeCSVExporter;
    protected EmployeeCSVImporter $employeeCSVImporter;

    public function __construct(EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        EmployeeCSVExporter $employeeCSVExporter,
        EmployeeCSVImporter $employeeCSVImporter,
        ElasticSearchManager $elasticSearchManager)
    {
        parent::__construct($elasticSearchManager, $entityManager, $validator);

        $this->employeeCSVExporter = $employeeCSVExporter;
        $this->employeeCSVImporter = $employeeCSVImporter;
    }

    public function getAllEmployeesInCSVFormat(): string
    {
        //grab all the employees from the db
        $employees = $this->entityManager
            ->getRepository(Employee::class)
            ->findAll();

        $csvContent = $this->employeeCSVExporter->getCSVFromEmployees($employees);

        return $csvContent;
    }

    public function importEmployeesFromCSVFile(File $file, array &$errors): bool
    {
        $mimeType = $file->getMimeType();

        if (strpos($mimeType, 'csv') === false){
            $errors[] = ['error' => 'file is not a csv'];
            return false;
        }

        $filePath = $file->getRealPath();
        $fileContent = file_get_contents($filePath);

        if ($fileContent === false){
            $errors[] = ['error' => 'file could not be read'];
            return false;
        }

        $result = $this->employeeCSVImporter->updateEmployeesFromCSV($fileContent, $errors);

        return $result;
    }
}





