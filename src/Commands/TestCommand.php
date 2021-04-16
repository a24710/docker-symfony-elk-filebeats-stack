<?php


namespace App\Commands;



use App\Entity\Employee;
use App\Services\ElasticSearchManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class TestCommand extends Command
{
    protected static $defaultName = 'app:test';
    protected EntityManagerInterface $entityManager;
    protected SerializerInterface $serializer;
    protected ElasticSearchManager $elasticSearchManager;

    public function __construct(EntityManagerInterface $entityManager,
        SerializerInterface $serializer, ElasticSearchManager $elasticSearchManager)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->elasticSearchManager = $elasticSearchManager;
    }

    protected function configure()
    {
        parent::configure();
        $this->setDescription('Test code command');
    }

    public function run(InputInterface $input, OutputInterface $output)
    {
        $result = $this->elasticSearchManager->searchMatch(Employee::class, ['firstName' => 'Emie']);
        $output->writeln('match ' . json_encode($result));

        $result = $this->elasticSearchManager->searchPrefix(Employee::class, ['firstName' => 'Emi']);
        $output->writeln('prefix ' . json_encode($result));

        return Command::SUCCESS;
    }
}

