<?php

namespace App\DataFixtures;

use App\Entity\Company;
use App\Entity\Employee;
use App\Entity\EmployeeProjectRelation;
use App\Entity\Project;
use App\Services\BaseEntityManager;
use App\Services\CompanyManager;
use App\Services\ElasticSearchManager;
use App\Services\EmployeeManager;
use App\Utils\DateTimeUtils;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;


class AppFixtures extends Fixture
{
    private const _EMPLOYEE_COUNT = 100;

    protected BaseEntityManager $baseEntityManager;
    protected ElasticSearchManager $elasticSearchManager;

    public function __construct(BaseEntityManager $baseEntityManager,
        ElasticSearchManager $elasticSearchManager)
    {
        $this->baseEntityManager = $baseEntityManager;
        $this->elasticSearchManager = $elasticSearchManager;
    }

    public function load(ObjectManager $manager)
    {
        $this->elasticSearchManager->deleteIndex(Company::class);
        $companies = $this->loadCompanies($manager);

        $this->elasticSearchManager->deleteIndex(Project::class);
        $projects = $this->loadProjects($manager);

        $this->elasticSearchManager->deleteIndex(Employee::class);
        $employees = $this->loadEmployees($manager, $companies);

        $this->loadEmployeeProjectRelations($employees, $projects);
    }

    protected function loadCompanies(ObjectManager $manager): array
    {
        $names = ['NVIDIA', 'Coca Cola', 'Google', 'Amazon', 'AMD'];
        $companies = [];

        foreach ($names as $name) {
            $company = new Company();
            $company->setName($name);
            $manager->persist($company);

            $company = $this->baseEntityManager->store($company);

            if ($company !== null){
                $companies[] = $company;
            }
        }

        return $companies;
    }

    protected function loadProjects(ObjectManager $manager): array
    {
        $names = ['Shop', 'Payrolls', 'MobileApp'];
        $projects = [];

        foreach ($names as $name) {
            $project = new Project();
            $project->setName($name);
            $manager->persist($project);

            $project = $this->baseEntityManager->store($project);

            if ($project){
                $projects[] = $project;
            }
        }

        return $projects;
    }

    protected function loadEmployees(ObjectManager $manager, array $companies): array
    {
        $companiesCount = count($companies);

        if ($companiesCount === 0) {
            return [];
        }

        $employees = [];
        $faker = Factory::create();
        $positions = Employee::availablePositions();
        $positionCount = count($positions);
        $employeeRepo = $manager->getRepository(Employee::class);

        for ($i = 0; $i < self::_EMPLOYEE_COUNT; $i++) {
            $email = $faker->unique()->safeEmail;
            $employee = $employeeRepo->findOneBy(['email' => $email]);

            if ($employee === null) {
                $firstName = $faker->firstName;
                $lastName = $faker->lastName;
                $salary = (float)random_int(30, 60) * 1000;
                $bonus = (float)random_int(30, 60) * 1000;
                $insuranceAmount = (float)random_int(30, 60) * 1000;
                $companyIndex = random_int(0, $companiesCount - 1);
                $positionIndex = random_int(0, $positionCount - 1);
                $startDate = $faker->dateTimeBetween('-10 years', 'now');

                while (DateTimeUtils::isWeekendDay($startDate)) {
                    $startDate = $faker->dateTimeBetween('-10 years', 'now');
                }

                $endDate = $faker->optional()->dateTimeBetween('-10 years', '-1 month');

                while (DateTimeUtils::isWeekendDay($endDate)) {
                    $endDate = $faker->optional()->dateTimeBetween('-10 years', '-1 month');
                }

                $employee = new Employee();
                $employee->setFirstName($firstName)
                    ->setLastName($lastName)
                    ->setEmail($email)
                    ->setSalary($salary)
                    ->setBonus($bonus)
                    ->setInsuranceAmount($insuranceAmount)
                    ->setPositionInCompany($positions[$positionIndex])
                    ->setCompany($companies[$companyIndex])
                    ->setEmploymentStartDate($startDate)
                    ->setEmploymentEndDate($endDate);

                $employee = $this->baseEntityManager->store($employee);

                if ($employee !== null){
                    $employees[] = $employee;
                }
            }
        }

        return $employees;
    }

    protected function loadEmployeeProjectRelations(
        array $employees,
        array $projects
    ): array {
        $employeeCount = count($employees);
        $projectCount = count($projects);

        if ($employeeCount === 0 ||
            $projectCount === 0) {
            return [];
        }

        $relations = [];
        $availableRoles = EmployeeProjectRelation::availableRoles();
        $availableRoleCount = count($availableRoles);

        foreach ($employees as $employee) {
            if ($employee instanceof Employee) {
                $relationCount = random_int(0, 2); //how many relations to persist
                $projectsIncluded = [];

                for ($i = 0; $i < $relationCount; $i++) {
                    $projectIndex = random_int(0, $projectCount - 1);

                    if (!in_array($projectIndex, $projectsIncluded, true)) {
                        $projectsIncluded[] = $projectIndex;
                        $roleIndex = random_int(0, $availableRoleCount);
                        $project = $projects[$projectIndex];
                        $role = $availableRoles[$roleIndex] ?? null;
                        $relation = new EmployeeProjectRelation();
                        $relation->setEmployee($employee);
                        $relation->setProject($project);
                        $relation->setRole($role);

                        $this->baseEntityManager->store($relation);
                    }
                }
            }
        }

        return $relations;
    }
}
