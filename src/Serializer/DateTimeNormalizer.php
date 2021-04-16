<?php


namespace App\Serializer;


use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

class DateTimeNormalizer implements ContextAwareNormalizerInterface
{
    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return ($data instanceof \DateTimeInterface);
    }

    public function normalize($object, string $format = null, array $context = [])
    {
        $outValue = $object->format('Y/m/d');
        return $outValue;
    }
}