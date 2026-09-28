<?php

namespace ItkEnter\DataModels\Command;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Generator\RepositoryGenerator;
use ItkEnter\DataModels\Model\ModelFinder;
use ItkEnter\DataModels\Validation\ModelValidator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Phase 5, point 2: reads the version from the schema, checks the tree is
 * clean and generated files are current, then creates `<Model>/v<version>`
 * — locally only. This normally runs inside the phpfpm container (via
 * `task release`), which has no access to the host's git credentials, so
 * pushing the tag is the Taskfile's job, run on the host straight after —
 * see the `release` task. On success this prints nothing but the bare tag
 * name, so the Taskfile can capture it.
 */
#[AsCommand(name: 'release', description: 'Tag a model release locally: <Model>/v<version>')]
final class ReleaseCommand extends Command
{
    public function __construct(private readonly string $repoRoot)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('model', InputArgument::REQUIRED, 'The model to release');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $modelName = $input->getArgument('model');
        $config = Config::fromFile($this->repoRoot.'/config.yaml');
        $folder = (new ModelFinder($this->repoRoot))->find($modelName);

        if (null === $folder) {
            $output->writeln("<error>No such model: {$modelName}</error>");

            return Command::FAILURE;
        }

        if (!$this->treeIsClean()) {
            $output->writeln('<error>Working tree is not clean. Commit or stash your changes first.</error>');

            return Command::FAILURE;
        }

        $errors = (new ModelValidator($this->repoRoot))->validate($folder, $config);
        foreach ($errors as $error) {
            $output->writeln("<error>{$error}</error>");
        }
        if ([] !== $errors) {
            return Command::FAILURE;
        }

        (new RepositoryGenerator($this->repoRoot))->generate($modelName);
        if (!$this->treeIsClean()) {
            $output->writeln('<error>Generated files were not up to date — they have now been regenerated. Commit them and try again.</error>');

            return Command::FAILURE;
        }

        $version = (string) ($folder->schema['x-version'] ?? '');
        if ('' === $version) {
            $output->writeln('<error>Could not determine the model version (x-version is missing).</error>');

            return Command::FAILURE;
        }

        $tag = "{$modelName}/v{$version}";
        if ([] !== array_filter(explode("\n", $this->git(['tag', '--list', $tag])))) {
            $output->writeln("<error>Tag {$tag} already exists.</error>");

            return Command::FAILURE;
        }

        $this->git(['tag', $tag]);
        $output->writeln($tag);

        return Command::SUCCESS;
    }

    /**
     * @phpstan-impure reads live git state, not just $this
     */
    private function treeIsClean(): bool
    {
        return '' === trim($this->git(['status', '--porcelain']));
    }

    /**
     * @param string[] $args
     */
    private function git(array $args): string
    {
        $process = new Process(['git', ...$args], $this->repoRoot);
        $process->mustRun();

        return $process->getOutput();
    }
}
