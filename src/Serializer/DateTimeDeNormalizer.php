<?php


namespace App\Serializer;

use App\Entity\Employee;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;


class DateTimeDeNormalizer implements ContextAwareDenormalizerInterface
{
    protected ObjectNormalizer $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function supportsDenormalization($data, string $type, string $format = null, array $context = [])
    {
        return ($type === Employee::class);
    }

    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $denormalizedData = $this->normalizer->denormalize($data, $type, $format, $context);

        if ($denormalizedData instanceof Employee){
            $employmentStartDate = array_key_exists('employmentStartDate', $data) ?
                $this->parseDate($data['employmentStartDate']) :
                null;

            $employmentEndDate = array_key_exists('employmentEndDate', $data) ?
                $this->parseDate($data['employmentEndDate']) :
                null;

            $denormalizedData->setEmploymentStartDate($employmentStartDate);
            $denormalizedData->setEmploymentEndDate($employmentEndDate);
        }

        return $denormalizedData;
    }

    private function parseDate(?string $dateStr): ?\DateTime
    {
        if ($dateStr === null){
            return null;
        }

        $outValue = \DateTime::createFromFormat('Y/m/d', $dateStr);
        $outValue = ($outValue instanceof \DateTime) ? $outValue : null;

        return $outValue;
    }
}