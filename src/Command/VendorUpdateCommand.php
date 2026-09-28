<?php

namespace ItkEnter\DataModels\Command;

use ItkEnter\DataModels\Config;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'vendor:update', description: 'Refresh vendor-assets/ at the versions pinned in config.yaml')]
final class VendorUpdateCommand extends Command
{
    private const SWAGGER_UI_FILES = ['swagger-ui-bundle.js', 'swagger-ui.css', 'LICENSE'];

    public function __construct(private readonly string $repoRoot)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = Config::fromFile($this->repoRoot.'/config.yaml');

        if (!$this->updateCommonSchema($config, $output)) {
            return Command::FAILURE;
        }

        if (!$this->updateSwaggerUi($config, $output)) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function updateCommonSchema(Config $config, OutputInterface $output): bool
    {
        $url = $config->commonSchemaFetchUrl();
        $contents = $this->fetch($url);

        if (null === $contents) {
            $output->writeln("<error>Could not fetch {$url}</error>");

            return false;
        }

        // Fail fast if it's not valid JSON, rather than vendoring garbage.
        json_decode($contents, flags: JSON_THROW_ON_ERROR);

        $this->write('vendor-assets/common-schema.json', $contents);
        $output->writeln("<info>Wrote vendor-assets/common-schema.json from {$url}</info>");

        return true;
    }

    /**
     * Fetches the npm tarball for the pinned swagger-ui-dist version and
     * vendors only what the docs site embeds (phase 6): the bundle JS,
     * its CSS, and the licence.
     */
    private function updateSwaggerUi(Config $config, OutputInterface $output): bool
    {
        $version = $config->swaggerUiVersion;
        $url = "https://registry.npmjs.org/swagger-ui-dist/-/swagger-ui-dist-{$version}.tgz";
        $tarball = $this->fetch($url);

        if (null === $tarball) {
            $output->writeln("<error>Could not fetch {$url}</error>");

            return false;
        }

        $tmpDir = tempnam(sys_get_temp_dir(), 'swagger-ui-');
        unlink($tmpDir);
        mkdir($tmpDir);
        $tarballPath = "{$tmpDir}/swagger-ui-dist.tgz";
        file_put_contents($tarballPath, $tarball);

        try {
            (new Process(['tar', 'xzf', $tarballPath, '-C', $tmpDir]))->mustRun();

            $checksums = '';
            foreach (self::SWAGGER_UI_FILES as $file) {
                $source = "{$tmpDir}/package/{$file}";
                if (!is_file($source)) {
                    $output->writeln("<error>swagger-ui-dist {$version} has no {$file}</error>");

                    return false;
                }

                $contents = file_get_contents($source);
                $this->write("vendor-assets/swagger-ui/{$file}", $contents);
                if ('LICENSE' !== $file) {
                    $checksums .= hash('sha256', $contents)."  {$file}\n";
                }
            }

            $this->write('vendor-assets/swagger-ui/VERSION', $version."\n");
            $this->write('vendor-assets/swagger-ui/checksums.sha256', $checksums);
        } finally {
            (new Process(['rm', '-rf', $tmpDir]))->run();
        }

        $output->writeln("<info>Wrote vendor-assets/swagger-ui/ at version {$version}</info>");

        return true;
    }

    private function fetch(string $url): ?string
    {
        $context = stream_context_create(['http' => ['header' => "User-Agent: itk-enter/data-models\r\n"]]);
        $contents = @file_get_contents($url, context: $context);

        return false === $contents ? null : $contents;
    }

    private function write(string $relativePath, string $contents): void
    {
        $path = "{$this->repoRoot}/{$relativePath}";
        if (!is_dir(\dirname($path))) {
            mkdir(\dirname($path), recursive: true);
        }

        file_put_contents($path, $contents);
    }
}
