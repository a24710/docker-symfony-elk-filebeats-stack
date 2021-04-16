<?php


namespace App\Services;


use App\Entity\BaseEntity;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class ElasticSearchManager
{
    public const _SERIALIZATION_GROUP_WRITE = 'elastic:write';

    protected Client $client;
    protected SerializerInterface $serializer;
    protected ObjectNormalizer $normalizer;

    public function __construct(SerializerInterface $serializer, ObjectNormalizer $normalizer, Client $elasticClient)
    {
        $this->client = $elasticClient;
        $this->serializer = $serializer;
        $this->normalizer = $normalizer;
    }

    public function searchFuzzy(?string $className, array $searchParams): array
    {
        return $this->search($className, 'fuzzy', $searchParams);
    }

    public function searchMatch(?string $className, array $searchParams): array
    {
        return $this->search($className, 'match', $searchParams);
    }

    public function searchPrefix(?string $className, array $searchParams): array
    {
        return $this->search($className, 'prefix', $searchParams);
    }

    private function search(?string $className, string $queryType, array $searchParams): array
    {
        //all values to lowercase
        foreach ($searchParams as $key => &$value){
            $value = strtolower($value);
        }

        unset($value);

        //mount query
        $index = ($className !== null) ?
            $this->resolveIndexName($className) :
            null;

        $params = [
            'body'  => [
                'query' => [
                    $queryType => $searchParams
                ],
            ]
        ];

        if ($index !== null){
            $params['index'] = $index;
        }

        //launch query
        $results = $this->client->search($params);

        if (is_array($results)){
            $results = $results['hits']['hits'] ?? null;
        }

        return $results;
    }

    public function indexEntity(BaseEntity $entity)
    {
        $id = $entity->getId();
        $normalizedData = $this->normalizer->normalize($entity, null, ['groups' => 'elastic:write']);
        $index = $this->resolveIndexName(get_class($entity));

        if (count($normalizedData) > 0){
            $this->indexDocument($index, $id, $normalizedData);
        }
    }

    protected function indexDocument(string $index, string $id, array $normalizedData): bool
    {
        $params = [
            'id' => $id,
            'index' => $index,
            'body' => $normalizedData
        ];

        $response = $this->client->index($params);

        $result = $response['result'] ?? null;
        $result = ($result === 'created');

        return $result;
    }

    public function deleteIndex(string $className): bool
    {
        $indexName = $this->resolveIndexName($className);

        $deleteParams = [
            'index' => $indexName
        ];

        $result = true;

        try{
            $response = $this->client->indices()->delete($deleteParams);

        } catch (\Exception $exception){
            $result = false;
        }

        return $result;
    }

    protected function resolveIndexName(string $className): string
    {
        $index = strtolower($className);
        $prefixToRemove = "app\\entity\\";

        //remove unnecessary characters
        if (str_starts_with($index, $prefixToRemove)){
            $index = substr($index, strlen($prefixToRemove));
        }

        return $index;
    }
}

