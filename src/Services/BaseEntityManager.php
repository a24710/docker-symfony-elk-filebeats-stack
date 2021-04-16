<?php


namespace App\Services;


use App\Entity\BaseEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BaseEntityManager
{
    protected ElasticSearchManager $elasticSearchManager;
    protected EntityManagerInterface $entityManager;
    protected ValidatorInterface $validator;

    public function __construct(
        ElasticSearchManager $elasticSearchManager,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator)
    {
        $this->elasticSearchManager = $elasticSearchManager;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    public function store(BaseEntity $entity,
        bool $validate = true,
        bool $flush = true,
        bool $elasticIndex = true,
        ?array $validationGroups = null,
        ?array &$validationErrors = null): ?BaseEntity
    {
        $outValue = $entity;

        if ($validate){
            $outValue = $this->validate($entity, $validationGroups, $validationErrors) ?
                $entity :
                null;
        }

        //flush and elastic
        if ($flush &&
            $outValue !== null)
        {
            $isNewEntity = !$entity->isPersisted();

            //persist to DB
            $this->entityManager->persist($outValue);
            $this->entityManager->flush();

            if ($isNewEntity){
                $this->postCreationProcess($entity);

                if ($elasticIndex){
                    $this->elasticSearchManager->indexEntity($outValue);
                }
            }
        }

        return $outValue;
    }

    public function validate(BaseEntity $entity, ?array &$errors, ?array $validationGroups = null): bool
    {
        $constraints = $this->validator->validate($entity, null, $validationGroups);

        //populate errors
        if ($errors !== null){
            $errors = [];

            foreach ($constraints as $constraint){
                if ($constraint instanceof ConstraintViolationInterface){
                    $errors[] = [
                        'attribute' => $constraint->getPropertyPath(),
                        'error' => $constraint->getMessage()
                    ];
                }
            }
        }

        $result = ($constraints->count() === 0);

        return $result;
    }

    protected function postCreationProcess(BaseEntity $entity): void
    {
        //Optionally overridden in child classes
    }
}