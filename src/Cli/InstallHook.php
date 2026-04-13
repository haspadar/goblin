<?php

declare(strict_types=1);

namespace Goblin\Cli;

/**
 * Git hooks managed by the install command.
 */
enum InstallHook: string
{
    case CommitMsg = 'commit-msg';
    case PrePush = 'pre-push';
    case PostCheckout = 'post-checkout';
}
