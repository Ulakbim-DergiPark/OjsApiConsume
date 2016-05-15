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

class JournalGenerateSDKCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ojs:api:generate:journal:sdk')
            ->setDescription('Generate sample journal, issue, section, article, file and citation via OJS API.')
        ;
    }
}