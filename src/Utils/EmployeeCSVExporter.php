<?php


namespace App\Utils;


use App\Entity\Employee;
use App\Entity\EmployeeProjectRelation;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class EmployeeCSVExporter
{
    protected NormalizerInterface $normalizer;

    public function __construct(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function getCSVFromEmployees(array $employees): string
    {
        $lines = [];

        //first line (headers)
        $lines[] = $this->getHeadersLine();
        $employeeLines = [];

        //serialize all employees
        try {
            $employeeLines = $this->normalizer->normalize($employees, null, ['groups' => 'employee:csv']);
        } catch (ExceptionInterface $e) {
        }

        //append them to the lines array
        $lines = array_merge($lines, $employeeLines);

        $totalSalary = $totalBonus = $totalInsurance = 0.0;

        //calculate totals
        foreach ($employees as $employee){
            if ($employee instanceof Employee){
                $totalSalary += $employee->getSalary() ?? 0.0;
                $totalBonus += $employee->getBonus() ?? 0.0;
                $totalInsurance += $employee->getInsuranceAmount() ?? 0.0;
            }
        }

        //last line
        $lines[] = $this->getSummaryLine($totalSalary, $totalBonus, $totalInsurance);

        //encode and return
        $options = ['no_headers' => true];
        $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
        $csvData = $serializer->encode($lines, 'csv', $options);

        return $csvData;
    }

    protected function getHeadersLine(): array
    {
        return ['ID',
            'First Name',
            'Last Name',
            'Email',
            'Company',
            'Position',
            'Project',
            'Salary',
            'Bonus',
            'Insurance Amount',
            'Total Cost',
            'Employment Start',
            'Employment End'
        ];
    }

    protected function getSummaryLine(float $totalSalary, float $totalBonus, float $totalInsurance): array
    {
        return [
            '',
            'Total',
            '',
            '',
            '',
            '',
            '',
            number_format($totalSalary, 2, ',', ''),
            number_format($totalBonus, 2, ',', ''),
            number_format($totalInsurance, 2, ',', ''),
            number_format($totalSalary + $totalBonus + $totalInsurance, 2, ',', ''),
            '',
            ''
        ];
    }
}