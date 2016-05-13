<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use Faker\Generator;
use GuzzleHttp\Exception\ClientException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
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

    /**
     * @var array
     */
    private $sectionIds = [];

    private $sampleIssueFile = 'http://www.cbu.edu.zm/downloads/pdf-sample.pdf';
    private $sampleIssueCover = 'http://lorempixel.com/200/300/';
    private $sampleIssueHeader = 'http://lorempixel.com/960/200/';
    private $sampleFileEncoded;
    private $sampleIssueCoverEncoded;
    private $sampleIssueHeaderEncoded;
    private $sampleArticleHeader = 'http://lorempixel.com/960/200/';
    private $sampleArticleHeaderEncoded;

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
        $cacheDir = $this->container->get('kernel')->getCacheDir().'/';
        $issueCacheDir = $cacheDir.'api_issue/';
        $articleCacheDir = $cacheDir.'api_article/';
        if(!is_dir($cacheDir) || !is_dir($issueCacheDir)){
            mkdir($issueCacheDir, 0775, true);
        }
        if(!is_dir($cacheDir) || !is_dir($articleCacheDir)){
            mkdir($articleCacheDir, 0775, true);
        }
        if(!file_exists($issueCacheDir.'sampleFile')){
            file_put_contents($issueCacheDir.'sampleIssueFile', file_get_contents($this->sampleIssueFile));
        }
        if(!file_exists($issueCacheDir.'sampleIssueCover')){
            file_put_contents($issueCacheDir.'sampleIssueCover', file_get_contents($this->sampleIssueCover));
        }
        if(!file_exists($issueCacheDir.'sampleIssueHeader')){
            file_put_contents($issueCacheDir.'sampleIssueHeader', file_get_contents($this->sampleIssueHeader));
        }
        if(!file_exists($articleCacheDir.'sampleArticleHeader')){
            file_put_contents($articleCacheDir.'sampleArticleHeader', file_get_contents($this->sampleArticleHeader));
        }
        $this->sampleFileEncoded = base64_encode(file_get_contents($issueCacheDir.'sampleIssueFile'));
        $this->sampleIssueCoverEncoded = base64_encode(file_get_contents($issueCacheDir.'sampleIssueCover'));
        $this->sampleIssueHeaderEncoded = base64_encode(file_get_contents($issueCacheDir.'sampleIssueHeader'));
        $this->sampleArticleHeaderEncoded = base64_encode(file_get_contents($articleCacheDir.'sampleArticleHeader'));
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
        $this->startGenerateProcess();
    }

    private function startGenerateProcess()
    {
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
            $this->io->writeln('Journal Created -> '.$journal['translations']['tr']['title']);

            $this->createSections((int)$journalId);
            $this->createIssues((int)$journalId);
        }catch(ClientException $e){
            return false;
        }
    }

    private function createSections($journalId)
    {
        foreach(range(1,4) as $number){
            $section = [
                'translations' => [
                    'tr' => [
                        'title' => $this->faker->text(50),
                    ]
                ],
                'allowIndex' => 1,
                'hideTitle' => 1,
                'sectionOrder' => $number,
            ];
            try{
                $response = $this->client->post($this->apiBaseUri.'journal/'.$journalId.'/sections.json?apikey='.$this->apikey, [
                    'json' => $section,
                    'headers' => [
                        'Content-Type'     => 'application/json',
                    ]
                ]);
                $location = $response->getHeader('Location');
                $sectionId = explode('=', $location[0])[1];


                $this->io->writeln('Journal Section Created -> '.$section['translations']['tr']['title'].' -> '.$sectionId);
                $this->sectionIds[] = $sectionId;
            }catch(\Exception $e){
                echo $e->getMessage();

                return false;
            }
        }
    }

    private function createIssues($journalId)
    {
        foreach(range(1,2) as $number){
            $issue = [
                'translations' => [
                    'tr' => [
                        'title' => $this->faker->text(60),
                        'description' => $this->faker->text(200)
                    ]
                ],
                'volume' => rand(1, 5),
                'number' => rand(1,5),
                'special' => 1,
                'supplement' => 1,
                'year' => 2012,
                'datePublished' => '27-09-1994',
                'tags' => ['consume', 'api'],
                'published' => 1,
                'full_file' => [
                    'filename' => 'samplefile.pdf',
                    'encoded_content' => $this->sampleFileEncoded
                ],
                'cover' => [
                    'filename' => 'sampleIssueCover.jpg',
                    'encoded_content' => $this->sampleIssueCoverEncoded,
                ],
                'header' => [
                    'filename' => 'sampleIssueHeader.jpg',
                    'encoded_content' => $this->sampleIssueHeaderEncoded,
                ],
            ];
            try{
                $response = $this->client->post($this->apiBaseUri.'journal/'.$journalId.'/issues.json?apikey='.$this->apikey, [
                    'json' => $issue,
                    'headers' => [
                        'Content-Type'     => 'application/json',
                    ]
                ]);
                $location = $response->getHeader('Location');
                $issueId = explode('=', $location[0])[1];

                $this->io->writeln('Journal Issue Created -> '.$issue['translations']['tr']['title'].' -> '.$issueId);
                $this->createArticles($journalId, $issueId);
            }catch(\Exception $e){
                return false;
            }
        }
    }

    private function createArticles($journalId, $issueId)
    {
        foreach(range(1,4) as $number){
            $article = [
                'translations' => [
                    'tr' => [
                        'title' => $this->faker->text(70),
                        'keywords' => [$this->faker->text(10), $this->faker->text(10)],
                        'abstract' => $this->faker->text(200),
                    ]
                ],
                'titleTransliterated' => $this->faker->text(200),
                'status' => 1,
                'doi' => $this->faker->text(10),
                'otherId' => '2341234',
                'anonymous' => 1,
                'pubdate' => '29-10-2015',
                'pubdateSeason' => 8,
                'firstPage' => 10,
                'lastPage' => 20,
                'uri' => 'http://behram.com',
                'abstractTransliterated' => $this->faker->text(150),
                'articleType' => 2,
                'orderNum' => $number,
                'submissionDate' => '22-10-2015',
                'header' => [
                    'filename' => 'sampleArticleHeader.jpg',
                    'encoded_content' => $this->sampleArticleHeaderEncoded,
                ],
            ];
            try{
                $response = $this->client->post($this->apiBaseUri.'journal/'.$journalId.'/articles.json?apikey='.$this->apikey, [
                    'json' => $article,
                    'headers' => [
                        'Content-Type'     => 'application/json',
                    ]
                ]);
                $location = $response->getHeader('Location');
                $explode = explode('/', $location[0]);
                $articleId = end($explode);

                $this->io->writeln('Journal Article Created -> '.$article['translations']['tr']['title']);
                $this->articleToIssue($journalId, $issueId, $articleId, $this->sectionIds[$number-1]);
                $this->createFile($journalId, $articleId);
                $this->createAuthors($journalId, $articleId);
                $this->createCitations($journalId, $articleId);
            }catch(\Exception $e){
                echo $e->getMessage();
                return false;
            }
        }
    }

    private function articleToIssue($journalId, $issueId, $articleId, $sectionId)
    {
        try{
            $this->client->get(
                $this->apiBaseUri.'journal/'.$journalId.'/issues/'.$issueId.'/add/article/'.$articleId.'/section/'.$sectionId.'.json?apikey='.$this->apikey, [
                'headers' => [
                    'Content-Type'     => 'application/json',
                ]
            ]);
            $this->io->writeln('Article added to issue -> '. implode(' ,', [$journalId, $issueId, $articleId, $sectionId]));
            return true;
        }catch(\Exception $e){
            echo $e->getMessage();
            return false;
        }
    }

    private function createFile($journalId, $articleId)
    {

    }

    private function createAuthors($journalId, $articleId)
    {

    }

    private function createCitations($journalId, $articleId)
    {

    }
}
