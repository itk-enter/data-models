<?php

namespace ItkEnter\DataModels\Command;

use ItkEnter\DataModels\Config;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'vendor:update', description: 'Refresh vendor-assets/ at the versions pinned in config.yaml')]
final class VendorUpdateCommand extends Command
{
    public function __construct(private readonly string $repoRoot)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = Config::fromFile($this->repoRoot.'/config.yaml');

        $url = $config->commonSchemaFetchUrl();
        $context = stream_context_create(['http' => ['header' => "User-Agent: itk-enter/data-models\r\n"]]);
        $contents = @file_get_contents($url, context: $context);

        if (false === $contents) {
            $output->writeln("<error>Could not fetch {$url}</error>");

            return Command::FAILURE;
        }

        // Fail fast if it's not valid JSON, rather than vendoring garbage.
        json_decode($contents, flags: JSON_THROW_ON_ERROR);

        $vendorAssetsDir = $this->repoRoot.'/vendor-assets';
        if (!is_dir($vendorAssetsDir)) {
            mkdir($vendorAssetsDir, recursive: true);
        }

        file_put_contents($vendorAssetsDir.'/common-schema.json', $contents);
        $output->writeln("<info>Wrote vendor-assets/common-schema.json from {$url}</info>");

        return Command::SUCCESS;
    }
}
