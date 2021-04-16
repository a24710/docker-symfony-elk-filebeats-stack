<?php


namespace App\Utils;


use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\Employee;
use App\Entity\EmployeeProjectRelation;
use App\Entity\Project;
use App\Services\EmployeeProjectRelationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class EmployeeCSVImporter
{
    private const _CSV_COLUMNS = 13;
    private const _ID_INDEX = 'ID';
    private const _FIRST_NAME_INDEX = 'First Name';
    private const _LAST_NAME_INDEX = 'Last Name';
    private const _EMAIL_INDEX = 'Email';
    private const _COMPANY_INDEX = 'Company';
    private const _POSITION_INDEX = 'Position';
    private const _PROJECT_INDEX = 'Project';
    private const _SALARY_INDEX = 'Salary';
    private const _BONUS_INDEX = 'Bonus';
    private const _INSURANCE_INDEX = 'Insurance Amount';
    private const _TOTAL_COST_INDEX = 'Total Cost';
    private const _EMPLOYMENT_START_INDEX = 'Employment Start';
    private const _EMPLOYMENT_END_INDEX = 'Employment End';

    protected EntityManagerInterface $entityManager;
    protected EmployeeProjectRelationManager $employeeProjectRelationManager;
    protected ValidatorInterface $validator;

    public function __construct(EntityManagerInterface $entityManager,
        EmployeeProjectRelationManager $employeeProjectRelationManager,
        ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->employeeProjectRelationManager = $employeeProjectRelationManager;
        $this->validator = $validator;
    }

    public function updateEmployeesFromCSV(string $csvData, array &$errors): bool
    {
        $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
        $csvLines = $serializer->decode($csvData, 'csv');
        $lineIndex = 1; //start in 1, line zero is the header
        $errors = [];

        foreach ($csvLines as $csvLine){
            $this->updateEmployeeFromCSVLine($csvLine, $errors, $lineIndex);
            $lineIndex++;
        }

        $result = (count($errors) === 0);

        return $result;
    }

    protected function updateEmployeeFromCSVLine(array $line, array &$errors, int $lineIndex)
    {
        if (count($line) !== self::_CSV_COLUMNS){
            $errors[] = $this->addError('column count is not ' . (string) self::_CSV_COLUMNS, $lineIndex, 0);
            return;
        }

        //skip line?
        if (StringUtils::nullOrEmpty($line[self::_ID_INDEX])){
            return;
        }

        if (StringUtils::nullOrEmpty($line[self::_FIRST_NAME_INDEX])){
            $error = 'No first name provided';
            $errors[] = $this->addError($error, $lineIndex, self::_FIRST_NAME_INDEX);
            return;
        }

        if (StringUtils::nullOrEmpty($line[self::_LAST_NAME_INDEX])){
            $error = 'No last name provided';
            $errors[] = $this->addError($error, $lineIndex, self::_LAST_NAME_INDEX);
            return;
        }

        if (StringUtils::nullOrEmpty($line[self::_EMAIL_INDEX])){
            $error = 'No email provided';
            $errors[] = $this->addError($error, $lineIndex, self::_EMAIL_INDEX);
            return;
        }

        if (StringUtils::nullOrEmpty($line[self::_COMPANY_INDEX])){
            $error = 'No company provided';
            $errors[] = $this->addError($error, $lineIndex, self::_COMPANY_INDEX);
            return;
        }

        //get employee from DB
        $employee = $this->entityManager
            ->getRepository(Employee::class)
            ->findOneBy(['uuid' => $line[self::_ID_INDEX]]);

        if (!($employee instanceof Employee)){
            $error = 'No employee found with id ' . ((string)$line[self::_ID_INDEX]);
            $errors[] = $this->addError($error, $lineIndex, self::_ID_INDEX);
            return;
        }

        //check for non-editable fields
        if ($employee->getEmail() !== $line[self::_EMAIL_INDEX]){
            $error = 'Database email does not match with provided email';
            $errors[] = $this->addError($error, $lineIndex, self::_EMAIL_INDEX);
            return;
        }

        //first name
        if (!StringUtils::nullOrEmpty($line[self::_FIRST_NAME_INDEX])){
            $employee->setFirstName($line[self::_FIRST_NAME_INDEX]);
        }

        //last name
        if (!StringUtils::nullOrEmpty($line[self::_LAST_NAME_INDEX])){
            $employee->setLastName($line[self::_LAST_NAME_INDEX]);
        }

        //salary
        if (!StringUtils::nullOrEmpty($line[self::_SALARY_INDEX])){
            $floatVal = $this->parseFloat($line[self::_SALARY_INDEX]);
            $employee->setSalary($floatVal);
        }

        //bonus
        if (!StringUtils::nullOrEmpty($line[self::_BONUS_INDEX])){
            $floatVal = $this->parseFloat($line[self::_BONUS_INDEX]);
            $employee->setBonus($floatVal);
        }

        //insurance
        if (!StringUtils::nullOrEmpty($line[self::_INSURANCE_INDEX])){
            $floatVal = $this->parseFloat($line[self::_INSURANCE_INDEX]);
            $employee->setInsuranceAmount($floatVal);
        }

        //employment start date
        if (!StringUtils::nullOrEmpty($line[self::_EMPLOYMENT_START_INDEX])){
            $newDate = $this->parseDate($line[self::_EMPLOYMENT_START_INDEX],
                                        $errors,
                                        $lineIndex,
                                        self::_EMPLOYMENT_START_INDEX);

            if ($newDate !== null){
                $employee->setEmploymentStartDate($newDate);
            }
        }

        //employment end date
        if (!StringUtils::nullOrEmpty($line[self::_EMPLOYMENT_END_INDEX])){
            $newDate = $this->parseDate($line[self::_EMPLOYMENT_END_INDEX],
                                        $errors,
                                        $lineIndex,
                                        self::_EMPLOYMENT_END_INDEX);

            if ($newDate !== null){
                $employee->setEmploymentEndDate($newDate);
            }
        }

        //check the new Data is correct according to our validation rules
        $validationErrors = $this->validator->validate($employee);

        if (count($validationErrors) > 0){
            //we dont want the changes we made to accidentally be persisted in the next flush, detach the entity
            $this->entityManager->clear(Employee::class);
            $errorsString = (string) $validationErrors;
            $errorsString = 'Validator failed with given data from line ' .
                ((string) $lineIndex) .
                ': ' . $errorsString;
            $errors[] = $this->addError($errorsString, $lineIndex, 0);
            return;
        }

        //update projects
        $employeeProjects = $this->getProjects($line[self::_PROJECT_INDEX], $errors, $lineIndex);
        $this->updateEmployeeProjectRelations($employee, $employeeProjects);

    }

    protected function getProjects(string $projects, array &$errors, int $lineIndex): array
    {
        if (StringUtils::nullOrEmpty($projects)){
            return [];
        }

        //take out spaces
        $projectNames = str_replace(' ', '', $projects);
        $projectNames = explode(',', $projectNames);
        $outProjects = [];
        $projectRepo = $this->entityManager->getRepository(Project::class);

        foreach ($projectNames as $projectName){
            $project = $projectRepo->findOneBy(['name' => $projectName]);

            if ($project !== null) {
                $outProjects[] = $project;

            } else {
                $error = 'Project ' . $projectName . ' not found';
                $errors[] = $this->addError($error, $lineIndex, self::_PROJECT_INDEX);
            }
        }

        return $outProjects;
    }

    protected function updateEmployeeProjectRelations(Employee $employee, array $projects)
    {
        $employeeProjectRelationRepo = $this->entityManager->getRepository(EmployeeProjectRelation::class);
        $employeeProjectRelations = $employee->getProjectRelations();

        //new project list supplied, we need to take out old project relations that don't exist anymore
        foreach ($employeeProjectRelations as $existingRelation){
            $found = false;

            foreach ($projects as $project){
                if ($project->getId() === $existingRelation->getProject()->getId()){
                    $found = true;
                    break;
                }
            }

            if (!$found){
                //this project is not in the new project list, it has to be removed
                $this->entityManager->remove($existingRelation);
            }
        }

        //confirm delete relations
        $this->entityManager->flush();

        //check for the rest of projects if new relations need to be made
        foreach ($projects as $project){
            if ($project instanceof Project){
                $dbRelation = $employeeProjectRelationRepo
                    ->findOneBy(['project' => $project, 'employee' => $employee]);

                if ($dbRelation === null){
                    //relation does not exist, create a new one
                    $this->employeeProjectRelationManager->create(
                        $employee,
                        $project,
                        null,
                        true
                    );
                }
            }
        }
    }

    protected function addError(string $error, int $line, string $column): array
    {
        return [
            'error' => $error,
            'line' => $line,
            'column' => $column
        ];
    }

    protected function parseFloat(string $floatStr): float
    {
        $outFloat = (float) str_replace(',', '.', $floatStr);
        return $outFloat;
    }

    protected function parseDate(string $dateStr, array &$errors, int $lineIndex, string $columnIndex): ?\DateTime
    {
        $dateStr = str_replace('.', '/', $dateStr);
        $outDate = \DateTime::createFromFormat('Y/m/d', $dateStr);

        if (!$outDate instanceof \DateTime){
            $errors[] = $this->addError('date format not valid', $lineIndex, $columnIndex);
            $outDate = null;

        } else if (DateTimeUtils::isWeekendDay($outDate)){
            $errors[] = $this->addError('weekend days are not allowed', $lineIndex, $columnIndex);
            $outDate = null;
        }

        return $outDate;
    }
}