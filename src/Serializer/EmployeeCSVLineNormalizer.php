<?php


namespace App\Serializer;


use App\Entity\Employee;
use App\Entity\EmployeeProjectRelation;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

class EmployeeCSVLineNormalizer implements ContextAwareNormalizerInterface
{
    //this normalizer is used for creating a csv line while exporting employees
    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        $result = false;

        if ($data instanceof Employee){
            $groups = $context['groups'] ?? null;

            if ((is_array($groups) && in_array('employee:csv', $groups)) ||
                (is_string($groups) && ($groups === 'employee:csv')))
            {
                $result = true;
            }
        }

        return $result;
    }

    public function normalize($employee, string $format = null, array $context = [])
    {
        $outValue = [];
        $outValue[] = $employee->getUuid();
        $outValue[] = $employee->getFirstName();
        $outValue[] = $employee->getLastName();
        $outValue[] = $employee->getEmail();
        $outValue[] = $employee->getCompany()?->getName();
        $outValue[] = $employee->getPositionInCompany();

        //include projects
        $projects = '';
        $separatorString = ', ';

        foreach ($employee->getProjectRelations() as $projectRelation){
            if ($projectRelation instanceof EmployeeProjectRelation){
                $projects .= $projectRelation->getProject()->getName() . $separatorString;
            }
        }

        if (str_ends_with($projects, $separatorString)){
            $projects = substr($projects, 0, strlen($projects) - strlen($separatorString));
        }

        $outValue[] = $projects;

        //Salary
        $outValue[] = $this->formatNumber($employee->getSalary());
        $outValue[] = $this->formatNumber($employee->getBonus());
        $outValue[] = $this->formatNumber($employee->getInsuranceAmount());
        $totalCost = $employee->getSalary() + $employee->getBonus() + $employee->getInsuranceAmount();
        $outValue[] = $this->formatNumber($totalCost);

        //employment start
        $outValue[] = ($employee->getEmploymentStartDate() !== null) ?
            date_format($employee->getEmploymentStartDate(), 'Y/m/d') :
            '';

        //employment end
        $outValue[] = ($employee->getEmploymentEndDate() !== null) ?
            date_format($employee->getEmploymentEndDate(), 'Y/m/d') :
            '';

        return $outValue;
    }

    protected function formatNumber(?float $number): string
    {
        $outValue = '';

        if ($number !== null){
            $outValue = number_format($number, 2, ',', '');
        }

        return $outValue;
    }
}