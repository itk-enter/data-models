<?php

namespace ItkEnter\DataModels\Command;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Site\BuildChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The phase 6 build checks, run against build/site/ after `mkdocs build`.
 */
#[AsCommand(name: 'site:check', description: 'Check the built docs site for broken $ids, IRIs and $refs')]
final class SiteCheckCommand extends Command
{
    public function __construct(private readonly string $repoRoot)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = Config::fromFile($this->repoRoot.'/config.yaml');
        $errors = (new BuildChecker($this->repoRoot, $config))->check();

        foreach ($errors as $error) {
            $output->writeln("<error>{$error}</error>");
        }

        if ([] !== $errors) {
            return Command::FAILURE;
        }

        $output->writeln('<info>Docs site build checks passed</info>');

        return Command::SUCCESS;
    }
}
