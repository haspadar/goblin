<?php

declare(strict_types=1);

namespace Goblin\Cli\Command;

use Goblin\Cli\Arguments;
use Goblin\Config\Config;
use Goblin\Docker\Docker;
use Goblin\Output\Output;
use Override;

/**
 * Runs tests inside a Docker container.
 */
final readonly class TestCommand implements Command
{
    /**
     * Stores Docker client, configuration, and output.
     */
    public function __construct(
        private Docker $docker,
        private Config $config,
        private Output $output,
    ) {}

    #[Override]
    public function run(Arguments $args): int
    {
        $fromFlag = $args->option('container');
        $container = $fromFlag !== ''
            ? $fromFlag
            : $this->config->value('container');

        if (!$this->docker->isRunning($container)) {
            $this->output->muted("Container '{$container}' is not running. Tests skipped.");

            return 0;
        }

        $command = $this->config->has('test-command')
            ? $this->config->value('test-command')
            : 'php artisan test';

        $this->output->muted("Running tests in '{$container}'...");
        $code = $this->docker->exec($container, $command);

        if ($code !== 0) {
            $this->output->error('Tests failed.');

            return 1;
        }

        $this->output->success('Tests passed.');

        return 0;
    }
}
