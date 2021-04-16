<?php


namespace App\Tests\Controller;


use App\Entity\Company;
use App\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProjectControllerTest extends WebTestCase
{
    public function testGetProjects()
    {
        $client = self::createClient();
        $client->request('GET', '/api/projects');
        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);
        $responseProjects = $decodedResponse['hydra:member'] ?? null;

        self::assertNotNull($responseProjects);
        self::assertGreaterThan(0, count($responseProjects));
    }

    public function testGetSingleProject()
    {
        $client = self::createClient();
        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $project = $entityManager->getRepository(Project::class)->findOneBy([]);
        $projectName = $project->getName();
        $url = '/api/projects/' . $project->getUuid();
        $client->request('GET', $url);
        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $decodedResponse = json_decode($response->getContent(), true);

        self::assertNotNull($decodedResponse);
        self::assertTrue(is_array($decodedResponse));
        self::assertTrue(array_key_exists('name', $decodedResponse));
        self::assertEquals($projectName, $decodedResponse['name']);
    }

    public function testGetNonExistingProject()
    {
        $client = self::createClient();
        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $projectRepo = $entityManager->getRepository(Project::class);
        $id = 1000;
        $nonExistingProject = $projectRepo->find($id);

        while ($nonExistingProject !== null){
            $id++;
            $nonExistingProject = $projectRepo->find($id);
        }

        $url = '/api/projects/' . $id;
        $client->request('GET', $url);
        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}

