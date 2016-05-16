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

class JournalGenerateBundleCommand extends ContainerAwareCommand
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
     * @var int
     */
    private $language = 1;

    /**
     * @var array
     */
    private $languages = [1,2];

    /**
     * @var int
     */
    private $publisher = 1;

    /**
     * @var int
     */
    private $country = 255;

    /**
     * @var int
     */
    private $articleType = 1;

    /**
     * @var int
     */
    private $title = 1;

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
    private $sampleArticleFile = 'http://www.cbu.edu.zm/downloads/pdf-sample.pdf';
    private $sampleArticleFileEncoded;
    private $sampleJournalHeader = 'http://lorempixel.com/960/200/';
    private $sampleJournalHeaderEncoded;
    private $sampleJournalImage = 'http://lorempixel.com/200/300/';
    private $sampleJournalImageEncoded;
    private $sampleJournalLogo = 'http://lorempixel.com/200/200/';
    private $sampleJournalLogoEncoded;

    protected function configure()
    {
        $this
            ->setName('ojs:api:generate:journal:bundle')
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
        $this->faker            = Factory::create('tr_TR');
        $this->periods          = $this->container->getParameter('periods');
        $this->subjects         = $this->container->getParameter('subjects');
        $this->language         = $this->container->getParameter('language');
        $this->languages        = $this->container->getParameter('languages');
        $this->publisher        = $this->container->getParameter('publisher');
        $this->country          = $this->container->getParameter('country');
        $this->articleType      = $this->container->getParameter('article_type');
        $this->title            = $this->container->getParameter('title');
        $this->fileStructureSetup();
    }

    private function fileStructureSetup()
    {
        $cacheDir = $this->container->get('kernel')->getCacheDir().'/';
        $issueCacheDir = $cacheDir.'api_issue/';
        $articleCacheDir = $cacheDir.'api_article/';
        $articleFileCacheDir = $cacheDir.'api_article_file/';
        $journalCacheDir = $cacheDir.'api_journal/';
        if(!is_dir($cacheDir) || !is_dir($issueCacheDir)){
            mkdir($issueCacheDir, 0775, true);
        }
        if(!is_dir($cacheDir) || !is_dir($articleCacheDir)){
            mkdir($articleCacheDir, 0775, true);
        }
        if(!is_dir($cacheDir) || !is_dir($articleFileCacheDir)){
            mkdir($articleFileCacheDir, 0775, true);
        }
        if(!is_dir($cacheDir) || !is_dir($journalCacheDir)){
            mkdir($journalCacheDir, 0775, true);
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
        if(!file_exists($articleFileCacheDir.'sampleArticleFile')){
            file_put_contents($articleFileCacheDir.'sampleArticleFile', file_get_contents($this->sampleArticleFile));
        }
        if(!file_exists($journalCacheDir.'sampleJournalHeader')){
            file_put_contents($journalCacheDir.'sampleJournalHeader', file_get_contents($this->sampleJournalHeader));
        }
        if(!file_exists($journalCacheDir.'sampleJournalLogo')){
            file_put_contents($journalCacheDir.'sampleJournalLogo', file_get_contents($this->sampleJournalLogo));
        }
        if(!file_exists($journalCacheDir.'sampleJournalImage')){
            file_put_contents($journalCacheDir.'sampleJournalImage', file_get_contents($this->sampleJournalImage));
        }
        $this->sampleArticleFileEncoded = base64_encode(file_get_contents($articleFileCacheDir.'sampleArticleFile'));
        $this->sampleFileEncoded = base64_encode(file_get_contents($issueCacheDir.'sampleIssueFile'));
        $this->sampleIssueCoverEncoded = base64_encode(file_get_contents($issueCacheDir.'sampleIssueCover'));
        $this->sampleIssueHeaderEncoded = base64_encode(file_get_contents($issueCacheDir.'sampleIssueHeader'));
        $this->sampleArticleHeaderEncoded = base64_encode(file_get_contents($articleCacheDir.'sampleArticleHeader'));
        $this->sampleJournalHeaderEncoded = base64_encode(file_get_contents($journalCacheDir.'sampleJournalHeader'));
        $this->sampleJournalImageEncoded = base64_encode(file_get_contents($journalCacheDir.'sampleJournalImage'));
        $this->sampleJournalLogoEncoded = base64_encode(file_get_contents($journalCacheDir.'sampleJournalLogo'));
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
        $this->io->success('Journal and related items created successfully!');
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
                    'title' => $this->faker->text(40).' - '.date("H:i:s"),
                    'subtitle' => $this->faker->text(70),
                    'description' => $this->faker->text(1500),
                    'titleAbbr' => $this->faker->text(70),
                ]
            ],
            'titleTransliterated' => $this->faker->text(50),
            'accessModal' => 1,
            'publisher' => $this->publisher,
            'mandatoryLang' => $this->language,
            'languages' => $this->languages,
            'periods' => $this->periods,
            'subjects' => $this->subjects,
            'status' => 1,
            'domain' => $this->faker->domainName,
            'issn' => '',
            'eissn' => '',
            'founded' => rand(2010, 2014),
            'googleAnalyticsId' => 'Google Ana. ID',
            'country' => $this->country,
            'footer_text' => $this->faker->text(30),
            'slug' => $this->faker->slug,
            'tags' => ['journal'],
            'header' => [
                'filename' => 'sampleJournalCover.jpg',
                'encoded_content' => $this->sampleJournalHeaderEncoded,
            ],
            'image' => [
                'filename' => 'sampleJournalImage.jpg',
                'encoded_content' => $this->sampleJournalImageEncoded,
            ],
            'logo' => [
                'filename' => 'sampleJournalLogo.jpg',
                'encoded_content' => $this->sampleJournalLogoEncoded,
            ],
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
        }catch(\Exception $e){
            $this->io->error($e->getResponse()->getBody());
            return false;
        }
    }

    private function createSections($journalId)
    {
        foreach(range(1,3) as $number){
            $section = [
                'translations' => [
                    'tr' => [
                        'title' => $this->faker->text(rand(30,70)),
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
                $this->io->error($e->getResponse()->getBody());
                return false;
            }
        }
    }

    private function createIssues($journalId)
    {
        foreach(range(1,rand(3,6)) as $number){
            $issue = [
                'translations' => [
                    'tr' => [
                        'title' => $this->faker->text(60),
                        'description' => $this->faker->text(200)
                    ]
                ],
                'volume' => rand(1, 5),
                'number' => rand(1,5),
                'supplement' => 1,
                'year' => rand(2010,2016),
                'datePublished' => '27-09-1994',
                'tags' => ['consume', 'api'],
                'visibility' => 1,
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
            if(rand(0,1)){
                $issue['special'] = '';
            }
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
                $this->io->error($e->getResponse()->getBody());
                return false;
            }
        }
    }

    private function createArticles($journalId, $issueId)
    {
        foreach(range(1,rand(10,20)) as $number){
            $article = [
                'translations' => [
                    'tr' => [
                        'title' => $this->faker->text(70),
                        'keywords' => [$this->faker->text(10), $this->faker->text(10)],
                        'abstract' => $this->faker->text(1500),
                    ]
                ],
                'titleTransliterated' => $this->faker->text(200),
                'status' => 1,
                'doi' => $this->faker->text(10),
                'otherId' => '2341234',
                'anonymous' => 1,
                'pubdate' => '29-10-2015',
                'pubdateSeason' => 8,
                'firstPage' => ($number-1)*10,
                'lastPage' => ($number-1)*10+10,
                'uri' => 'http://behram.com',
                'abstractTransliterated' => $this->faker->text(150),
                'articleType' => $this->articleType,
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
                $this->articleToIssue($journalId, $issueId, $articleId, $this->sectionIds[array_rand($this->sectionIds, 1)]);
                $this->createFile($journalId, $articleId);
                $this->createAuthors($journalId, $articleId);
                $this->createCitations($journalId, $articleId);
            }catch(\Exception $e){
                $this->io->error($e->getResponse()->getBody());
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
            $this->io->error($e->getResponse()->getBody());
            return false;
        }
    }

    private function createFile($journalId, $articleId)
    {
        foreach(range(1, rand(1,3)) as $number) {
            $articleFile = [
                'file' => [
                    'filename' => 'sampleArticleFile.pdf',
                    'encoded_content' => $this->sampleArticleFileEncoded,
                ],
                'type' => 2,
                'langCode' => 1,
                'title' => $this->faker->text(5),
                'description' => $this->faker->text(50),
            ];
            try {
                $this->client->post($this->apiBaseUri . 'journal/' . $journalId . '/article/' . $articleId . '/files.json?apikey=' . $this->apikey, [
                    'json' => $articleFile,
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ]
                ]);
                $this->io->writeln('Journal Article File Created -> ' . $articleFile['title']);
            } catch (\Exception $e) {
                $this->io->error($e->getResponse()->getBody());
                return false;
            }
        }
    }

    private function createAuthors($journalId, $articleId)
    {
        foreach(range(1,rand(2,5)) as $number){
            $articleAuthor = [
                'author' => [
                    'orcid' => 'orcid-id',
                    'translations' => [
                        'tr' => [
                            'biography' => $this->faker->text(80),
                        ]
                    ],
                    'title' => $this->title,
                    'firstName' => $this->faker->firstName,
                    'lastName' => $this->faker->firstNameMale,
                    'middleName' => $this->faker->firstName,
                    'phone' => $this->faker->phoneNumber,
                    'firstNameTransliterated' => '',
                    'middleNameTransliterated' => '',
                    'lastNameTransliterated' => '',
                    'initials' => $this->faker->title,
                    'email' => $this->faker->email,
                    'address' => $this->faker->address,
                    'institution' => null,
                    'country' => $this->country,
                    'authorDetails' => $this->faker->text(200),
                ],
                'authorOrder' => $number,
            ];
            try{
                $this->client->post($this->apiBaseUri.'journal/'.$journalId.'/article/'.$articleId.'/authors.json?apikey='.$this->apikey, [
                    'json' => $articleAuthor,
                    'headers' => [
                        'Content-Type'     => 'application/json',
                    ]
                ]);

                $this->io->writeln('Journal Article Author Created -> '.$articleAuthor['author']['firstName']);

            }catch(\Exception $e){
                $this->io->error($e->getResponse()->getBody());
                return false;
            }
        }
    }

    private function createCitations($journalId, $articleId)
    {
        foreach(range(1, rand(10,16)) as $number){
            $articleCitation = [
                'raw' => $this->faker->text(rand(100, 150)),
                'type' => 2,
                'orderNum' => $number,
            ];
            try{
                $this->client->post($this->apiBaseUri.'journal/'.$journalId.'/article/'.$articleId.'/citations.json?apikey='.$this->apikey, [
                    'json' => $articleCitation,
                    'headers' => [
                        'Content-Type'     => 'application/json',
                    ]
                ]);

                $this->io->writeln('Journal Article Citation Created -> '.$articleCitation['raw']);

            }catch(\Exception $e){
                $this->io->error($e->getResponse()->getBody());
                return false;
            }
        }
    }
}