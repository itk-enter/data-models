<?php

namespace ItkEnter\DataModels\Command;

use ItkEnter\DataModels\Generator\RepositoryGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'generate', description: "Generate a model's files, or all models if none given")]
final class GenerateCommand extends Command
{
    public function __construct(private readonly string $repoRoot)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('model', InputArgument::OPTIONAL, 'The model to generate; all models if omitted');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $generated = (new RepositoryGenerator($this->repoRoot))->generate($input->getArgument('model'));
        } catch (\RuntimeException $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return Command::FAILURE;
        }

        foreach ($generated as $modelName) {
            $output->writeln("<info>{$modelName}: generated</info>");
        }

        return Command::SUCCESS;
    }
}
