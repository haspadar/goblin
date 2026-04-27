<?php

declare(strict_types=1);

namespace Goblin\Cli\Command;

use Goblin\Cli\Arguments;
use Goblin\Config\Config;
use Goblin\Output\Output;
use Goblin\Shell\Shell;
use Override;

/**
 * Runs tests on the local host using the `test-command` config key.
 */
final readonly class LocalTestCommand implements Command
{
    /**
     * Stores shell, configuration, and output.
     */
    public function __construct(
        private Shell $shell,
        private Config $config,
        private Output $output,
    ) {}

    #[Override]
    public function run(Arguments $args): int
    {
        $command = $this->config->has('test-command')
            ? $this->config->value('test-command')
            : 'php artisan test';

        $this->output->muted("Running tests: {$command}");
        $code = $this->shell->run($command);

        if ($code !== 0) {
            $this->output->error('Tests failed.');

            return 1;
        }

        $this->output->success('Tests passed.');

        return 0;
    }
}
