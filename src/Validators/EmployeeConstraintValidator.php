<?php


namespace App\Validators;


use App\Entity\Employee;
use App\Utils\DateTimeUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EmployeeConstraintValidator extends ConstraintValidator
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function validate($value, Constraint $constraint)
    {
        $this->checkCompany($value);
        $this->checkPosition($value);
        $this->checkEmail($value);
        $this->checkDates($value);
    }

    private function checkCompany(Employee $employee)
    {
        if ($employee->getCompany() === null ||
            !$employee->getCompany()->isPersisted())
        {
            $this->context->buildViolation('An existing company must be provided')
                ->atPath('company')
                ->addViolation();
        }
    }

    private function checkPosition(Employee $employee)
    {
        $availablePositions = Employee::availablePositions();
        $position = $employee->getPositionInCompany();

        if ($position === null ||
            !in_array($position, $availablePositions))
        {
            $this->context->buildViolation('A valid position must be provided')
                ->atPath('position')
                ->addViolation();
        }
    }

    private function checkEmail(Employee $employee)
    {
        $email = $employee->getEmail();

        if ($email === null ||
            strlen(trim($email)) === 0)
        {
            $this->context->buildViolation('An email address has to be provided')
                ->atPath('email')
                ->addViolation();

            return;
        }

        $dbEmployee = $this->entityManager
            ->getRepository(Employee::class)
            ->findOneBy(['email' => $email]);

        if ($employee->isPersisted()){
            //PATCH
            if ($dbEmployee->getId() !== $employee->getId()){
                $this->context->buildViolation('You can not change the email field')
                    ->atPath('email')
                    ->addViolation();
            }

        } else {
            //POST
            if ($dbEmployee !== null){
                $this->context->buildViolation('An existing employee already has this email')
                    ->atPath('email')
                    ->addViolation();
            }
        }
    }

    private function checkDates(Employee $employee)
    {
        $endDate = $employee->getEmploymentEndDate();
        $startDate = $employee->getEmploymentStartDate();

        if ($endDate !== null &&
            DateTimeUtils::isWeekendDay($endDate))
        {
            $this->context->buildViolation('employment end date can not be on weekend')
                ->atPath('employmentEndDate')
                ->addViolation();
        }

        if ($startDate !== null &&
            DateTimeUtils::isWeekendDay($startDate))
        {
            $this->context->buildViolation('employment start date can not be on weekend')
                ->atPath('employmentStartDate')
                ->addViolation();
        }
    }
}

