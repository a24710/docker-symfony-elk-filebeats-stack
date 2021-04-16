<?php

namespace App\Controller;

use App\Entity\Employee;
use App\Services\ElasticSearchManager;
use App\Services\EmployeeManager;
use App\Utils\StringUtils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class EmployeeController extends AbstractController
{
    /**
     * @Route("/api/employees_raw", methods={"GET"})
     */
    public function listEmployees(EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        LoggerInterface $logger)
    {
        $logger->info('call to list employees');
        $employees = $entityManager
            ->getRepository(Employee::class)
            ->findAll();

        $serializedData = $serializer->serialize($employees, 'json', ['groups' => 'employee:read']);

        return new JsonResponse($serializedData, Response::HTTP_OK, [], true);
    }

    /**
     * @Route("/api/employees/csv_export", methods={"GET"})
     */
    public function getEmployeesCSV(EmployeeManager $employeeManager, LoggerInterface $logger)
    {
        $logger->info('call to export csv');
        $csvData = $employeeManager->getAllEmployeesInCSVFormat();

        //prepare to return a file
        $response = new Response($csvData);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            'employees.csv'
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @Route("/api/employees/csv_import", methods={"POST"})
     */
    public function postEmployeesCSV(Request $request,
        EmployeeManager $employeeManager,
        LoggerInterface $logger)
    {
        $file = $request->files->get('file');

        if (!($file instanceof File)){
            return new JsonResponse(['error' => 'file field not found'], Response::HTTP_BAD_REQUEST);
        }

        $logger->info('call to post CSV employee file');

        $errors = [];
        $response = ($employeeManager->importEmployeesFromCSVFile($file, $errors)) ?
            new JsonResponse() :
            new JsonResponse($errors, Response::HTTP_BAD_REQUEST);

        return $response;
    }

    /**
     * @Route("/api/employees/fuzzySearch", methods={"GET"})
     */
    public function fuzzySearch(Request $request,
        ElasticSearchManager $elasticSearchManager,
        LoggerInterface $logger)
    {
        $searchQuery = $request->query->get('searchQuery');

        if (StringUtils::nullOrEmpty($searchQuery)){
            return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
        }

        $logger->info('call to fuzzy search', ['searchQuery' => $searchQuery]);
        $response = $elasticSearchManager->searchFuzzy(Employee::class, ['firstName' => $searchQuery]);

        return new JsonResponse($response);
    }
}