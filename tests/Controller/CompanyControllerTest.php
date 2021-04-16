<?php


namespace App\Tests\Controller;


use App\Entity\Company;
use App\Entity\Project;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CompanyControllerTest extends WebTestCase
{
    public function testGetCompanies()
    {
        $client = self::createClient();
        $client->request('GET', '/api/companies');
        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);
        $responseCompanies = $decodedResponse['hydra:member'] ?? null;

        self::assertNotNull($responseCompanies);
        self::assertTrue(is_array($responseCompanies));
        self::assertGreaterThan(0, count($responseCompanies));
    }

    public function testGetSingleCompany()
    {
        $client = self::createClient();
        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $company = $entityManager->getRepository(Company::class)->findOneBy([]);
        $companyName = $company->getName();
        $url = '/api/companies/' . $company->getUuid();
        $client->request('GET', $url);
        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);

        self::assertNotNull($decodedResponse);
        self::assertTrue(is_array($decodedResponse));
        self::assertTrue(array_key_exists('name', $decodedResponse));
        self::assertEquals($companyName, $decodedResponse['name']);
    }

    public function testGetNonExistingCompany()
    {
        $client = self::createClient();
        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $companyRepo = $entityManager->getRepository(Company::class);
        $id = 1000;
        $nonExistingCompany = $companyRepo->find($id);

        while ($nonExistingCompany !== null){
            $id++;
            $nonExistingCompany = $companyRepo->find($id);
        }

        $url = '/api/companies/' . $id;
        $client->request('GET', $url);
        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

}