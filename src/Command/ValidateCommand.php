<?php

namespace ItkEnter\DataModels\Command;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Model\ModelFinder;
use ItkEnter\DataModels\Validation\ModelValidator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'validate', description: 'Validate a model, or all models if none given')]
final class ValidateCommand extends Command
{
    public function __construct(private readonly string $repoRoot)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('model', InputArgument::OPTIONAL, 'The model to validate; all models if omitted');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = Config::fromFile($this->repoRoot.'/config.yaml');
        $finder = new ModelFinder($this->repoRoot);
        $validator = new ModelValidator($this->repoRoot);

        $modelName = $input->getArgument('model');
        if (null === $modelName) {
            $models = $finder->all();
        } else {
            $model = $finder->find($modelName);
            if (null === $model) {
                $output->writeln("<error>No such model: {$modelName}</error>");

                return Command::FAILURE;
            }
            $models = [$model];
        }

        $errorCount = 0;
        foreach ($models as $model) {
            $errors = $validator->validate($model, $config);
            if ([] === $errors) {
                $output->writeln("<info>{$model->name}: OK</info>");
                continue;
            }

            foreach ($errors as $error) {
                $output->writeln("<error>{$error}</error>");
                ++$errorCount;
            }
        }

        return 0 === $errorCount ? Command::SUCCESS : Command::FAILURE;
    }
}
