<?php

declare(strict_types=1);

namespace Goblin\Cli;

/**
 * Result of installing a single git hook file.
 */
enum HookAction: string
{
    case Installed = 'installed';
    case Prepended = 'prepended';
    case Skipped = 'skipped';
}
