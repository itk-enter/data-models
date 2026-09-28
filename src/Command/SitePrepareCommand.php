<?php

namespace ItkEnter\DataModels\Command;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Generator\TwigFactory;
use ItkEnter\DataModels\Site\GitTags;
use ItkEnter\DataModels\Site\SiteBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'site:prepare', description: 'Write a MkDocs source tree to build/docs/')]
final class SitePrepareCommand extends Command
{
    public function __construct(private readonly string $repoRoot)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = Config::fromFile($this->repoRoot.'/config.yaml');
        $twig = TwigFactory::create($this->repoRoot);
        $gitTags = new GitTags($this->repoRoot);

        (new SiteBuilder($this->repoRoot, $config, $twig, $gitTags))->build();

        $output->writeln('<info>Wrote build/docs/</info>');

        return Command::SUCCESS;
    }
}
