<?php

namespace AppBundle\Command;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Faker\Generator;
use GuzzleHttp\Exception\ClientException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use GuzzleHttp\Client;
use Faker\Factory;

class JournalGenerateCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $apikey;

    /**
     * @var string
     */
    private $apiBaseUri;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Generator
     */
    private $faker;

    /**
     * @var array
     */
    private $periods = [1,2,3];

    /**
     * @var array
     */
    private $subjects = [1,2,3];

    /**
     * @var array
     */
    private $languages = [1,2];

    protected function configure()
    {
        $this
            ->setName('ojs:api:generate:journal')
            ->setDescription('Generate sample journal, issue, section, article, file and citation via OJS API.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io               = new SymfonyStyle($input, $output);
        $this->container        = $this->getContainer();
        $this->em               = $this->container->get('doctrine')->getManager();
        $this->apiBaseUri       = $this->container->getParameter('api_base_uri');
        $this->apikey           = $this->container->getParameter('apikey');
        $this->client           = new Client();
        $this->faker            = Factory::create();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io->title($this->getDescription());
        $this->createJournal();

    }

    private function createJournal()
    {
        $journal = [
            'translations' => [
                'tr' => [
                    'title' => $this->faker->text(70),
                    'subtitle' => $this->faker->text(70),
                    'description' => $this->faker->text(250),
                    'titleAbbr' => $this->faker->text(70),
                ]
            ],
            'titleTransliterated' => $this->faker->text(50),
            'accessModal' => 1,
            'publisher' => 1,
            'mandatoryLang' => 1,
            'languages' => $this->languages,
            'periods' => $this->periods,
            'subjects' => $this->subjects,
            'status' => 1,
            'domain' => $this->faker->domainName,
            'issn' => '',
            'eissn' => '',
            'founded' => 2014,
            'googleAnalyticsId' => 'Google Ana. ID',
            'country' => 2,
            'footer_text' => $this->faker->text(30),
            'slug' => $this->faker->slug,
            'tags' => ['journal'],
        ];
        try{
            $response = $this->client->post($this->apiBaseUri.'journals.json?apikey='.$this->apikey, [
                'json' => $journal,
                'headers' => [
                    'Content-Type'     => 'application/json',
                ]
            ]);
            $location = $response->getHeader('Location');
            $journalId = explode('=', $location[0])[1];

            return (int)$journalId;
        }catch(ClientException $e){
            return false;
        }
    }
}
