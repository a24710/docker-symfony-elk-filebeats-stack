<?php


namespace App\Tests\Controller;


use App\Entity\Company;
use App\Entity\Employee;
use App\Utils\EmployeeCSVExporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class EmployeeControllerTest extends WebTestCase
{
    public function testEmployeesRawGet()
    {
        $client = self::createClient();
        $client->request('GET', '/api/employees_raw');
        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());

        //check the endpoint the same item count that exist in the db
        $responseData = $response->getContent();
        $responseData = json_decode($responseData, true);
        $responseEmployeeCount = count($responseData);

        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $dbEmployeeCount = $entityManager->getRepository(Employee::class)->getEmployeeCount();

        self::assertEquals($responseEmployeeCount, $dbEmployeeCount);
    }

    //test api platform get employees
    public function testEmployeesGet()
    {
        $client = self::createClient();
        $client->request('GET', '/api/employees');
        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);
        $responseEmployees = $decodedResponse['hydra:member'] ?? null;

        self::assertNotNull($responseEmployees);
        self::assertGreaterThan(0, count($responseEmployees));
    }

    //test creating valid employees
    public function testValidEmployeesPost()
    {
        $client = self::createClient();
        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $jsons = $this->generateValidEmployeeJsons($entityManager);

        foreach ($jsons as $json){
            $client->request('POST','/api/employees', [], [], ['CONTENT_TYPE' => 'application/json'], $json);
            $response = $client->getResponse();
            self::assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        }
    }

    //test creating invalid employees
    public function testInvalidEmployeesPost()
    {
        $client = self::createClient();
        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $jsons = $this->generateInvalidEmployeeJsons($entityManager);

        foreach ($jsons as $json){
            $client->request('POST','/api/employees', [], [], ['CONTENT_TYPE' => 'application/json'], $json);
            $response = $client->getResponse();
            self::assertNotEquals(Response::HTTP_CREATED, $response->getStatusCode());
        }
    }

    //test downloading the employees CSV file
    public function testDownloadEmployeesCSVFile()
    {
        $client = self::createClient();
        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $client->request('GET','/api/employees/csv_export', [], [], []);
        $response = $client->getResponse();

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $validCsvContent = $response->getContent();
        $this->testCSVResponseHasAttachment($response);
        $this->testCSVEmployeesMatchDatabase($response, $entityManager);
        $this->testUploadingValidCSVFileFromValidResponse($validCsvContent, $client);
    }

    public function testUploadEmployeesCSVFile()
    {
        $client = self::createClient();
        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $employeeCSVExporter = $client->getContainer()->get(EmployeeCSVExporter::class);
        $employeeRepo = $entityManager->getRepository(Employee::class);
        $dbEmployee = $employeeRepo->findOneBy([]);

        //call without an attached file
        $client->request('POST', '/api/employees/csv_import');
        $response = $client->getResponse();
        self::assertNotEquals(Response::HTTP_OK, $response->getStatusCode());

        //upload a csv file with a single unmodified employee
        $this->createTestAndUploadEmployeesCSVFile([$dbEmployee], $client, $employeeCSVExporter, false);

        //upload a csv file with an employee modified with a wrong email
        $dbEmployee = $employeeRepo->findOneBy([]);
        $dbEmployee->setEmail('changedEmail@hello.es');
        $this->createTestAndUploadEmployeesCSVFile([$dbEmployee], $client, $employeeCSVExporter, true);

        //change user name and check with DB if it worked
        $entityManager->clear();
        $dbEmployee = $employeeRepo->findOneBy([]);
        $employeeId = $dbEmployee->getId();
        $newName = 'newName';
        $dbEmployee->setFirstName($newName);
        $this->createTestAndUploadEmployeesCSVFile([$dbEmployee], $client, $employeeCSVExporter, false);
        $entityManager->clear();
        $dbEmployee = $employeeRepo->find($employeeId);
        self::assertEquals($newName, $dbEmployee->getFirstName());

        //remove user company and check if it can be uploaded
        $dbEmployee = $employeeRepo->findOneBy([]);
        $dbEmployee->setCompany(null);
        $this->createTestAndUploadEmployeesCSVFile([$dbEmployee], $client, $employeeCSVExporter, true);

        //TODO: add more upload cases
    }

    private function createTestAndUploadEmployeesCSVFile(array $employees,
        KernelBrowser $client,
        EmployeeCSVExporter $employeeCSVExporter,
        bool $testForFail)
    {
        $csv = $employeeCSVExporter->getCSVFromEmployees($employees);

        //create a temp file with the content received
        $filename = sys_get_temp_dir() . '/employees.csv';
        $result = file_put_contents($filename, $csv);
        $fileWriteSuccess = ($result !== false);

        self::assertTrue($fileWriteSuccess);

        $uploadFile = new UploadedFile(
            $filename,
            'employee.csv',
            'text/csv',
            null
        );

        $client->request('POST', '/api/employees/csv_import', [], ['file' => $uploadFile]);
        $response = $client->getResponse();

        if ($testForFail){
            self::assertNotEquals(Response::HTTP_OK, $response->getStatusCode());

        } else {
            self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        }

        unlink($filename);
    }

    private function testCSVResponseHasAttachment(Response $response): void
    {
        //check that content has an attachment
        $contentDisposition = $response->headers->get('content-disposition');
        $contentIsAttachment = false;

        if ($contentDisposition !== null){
            $contentIsAttachment = strpos($contentDisposition, 'attachment') !== false;
        }

        self::assertTrue($contentIsAttachment);
    }

    private function testUploadingValidCSVFileFromValidResponse(string $content, KernelBrowser $client): void
    {
        //create a temp file with the content received
        $filename = sys_get_temp_dir() . '/valid.csv';
        $result = file_put_contents($filename, $content);
        $fileWriteSuccess = ($result !== false);

        self::assertTrue($fileWriteSuccess);

        $uploadFile = new UploadedFile(
            $filename,
            'valid.csv',
            'text/csv',
            null
        );

        $client->request('POST', '/api/employees/csv_import', [], ['file' => $uploadFile]);
        $response = $client->getResponse();
        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());

        unlink($filename);
    }

    private function testCSVEmployeesMatchDatabase(Response $response, EntityManagerInterface $entityManager): void
    {
        //db count check
        $dbEmployeeCount = $entityManager->getRepository(Employee::class)->getEmployeeCount();

        //csv count check
        $responseData = $response->getContent();
        $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
        $csvLines = $serializer->decode($responseData, 'csv');
        $csvLineCount = count($csvLines) - 1; //ignore the last line

        self::assertEquals($dbEmployeeCount, $csvLineCount);

        //TODO::check that every employee in the CSV matches with the employee in the DB, not just the count
    }

    private function generateValidEmployeeJsons(EntityManagerInterface $entityManager): array
    {
        //get and existing random one
        $company = $entityManager
            ->getRepository(Company::class)
            ->findOneBy([]);

        self::assertNotNull($company);

        if ($company === null){
            return [];
        }

        //cant do this in a data provider cause the entityManager is not available there
        $companyId = $company->getUuid();
        $jsons = [];

        //valid data
        $jsons[] = json_encode($this->provideBaseEmployeeData($companyId));

        //valid data, end date = null
        $employeeData = $this->provideBaseEmployeeData($companyId);
        $employeeData['employmentEndDate'] = null;
        $employeeData['email'] = 'another_email@example.org';
        $jsons[] = json_encode($employeeData);

        //TODO:: continue adding more jsons that are should be accepted by the post employee method

        return $jsons;
    }

    private function generateInvalidEmployeeJsons(EntityManagerInterface $entityManager): array
    {
        //get an existing random one
        $company = $entityManager
            ->getRepository(Company::class)
            ->findOneBy([]);

        $employee = $entityManager
            ->getRepository(Employee::class)
            ->findOneBy([]);

        self::assertNotNull($company);
        self::assertNotNull($employee);

        if ($company === null ||
            $employee === null)
        {
            return [];
        }

        //cant do this in a data provider cause the entityManager is not available there without doing extra tricks
        $validEmail = $employee->getEmail();
        $companyId = $company->getId();
        $jsons = [];

        //invalid employees

        //existing email
        $data = $this->provideBaseEmployeeData($companyId);
        $data['email'] = $validEmail;
        $jsons[] = json_encode($data);

        //wrong email format
        $data = $this->provideBaseEmployeeData($companyId);
        $data['email'] = 'wrongEmailFormat@';
        $jsons[] = json_encode($data);

        //null email
        $data = $this->provideBaseEmployeeData($companyId);
        $data['email'] = null;
        $jsons[] = json_encode($data);

        //TODO:: continue adding more jsons that are going to be rejected in the post employee method

        return $jsons;
    }

    private function provideBaseEmployeeData(string $companyId)
    {
        return [
            "firstName" => "John",
            "lastName" => "Doe",
            "email" => "john.doeeee@example.org",
            "salary" => 43000,
            "bonus" => 42000,
            "insuranceAmount" => 41000,
            "company" => "/api/companies/" . $companyId,
            "employmentStartDate" => "2020/01/23",
            "employmentEndDate" => "2020/01/24",
            "positionInCompany" => "manager"
        ];
    }
}