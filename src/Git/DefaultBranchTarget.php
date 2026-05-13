<?php

declare(strict_types=1);

namespace Goblin\Git;

/**
 * Fallback BranchTarget derived from the rules' default entry.
 */
final readonly class DefaultBranchTarget
{
    /**
     * Stores the raw default entry from branch-rules config.
     *
     *
     * @param mixed $default Default branch name.
     */
    public function __construct(private mixed $default) {}

    /**
     * Returns the resolved BranchTarget for unmatched releases.
     */
    public function toBranchTarget(): BranchTarget
    {
        if (is_array($this->default)) {
            /** @psalm-var mixed $branch */
            $branch = $this->default['branch'] ?? 'dev';
            $name = is_string($branch) ? $branch : 'dev';

            /** @psalm-var mixed $transitive */
            $transitive = $this->default['transitive'] ?? false;
            $match = is_bool($transitive) && $transitive ? BaseMatch::Transitive : BaseMatch::Strict;

            return new BranchTarget($name, [$name], $match);
        }

        $name = is_string($this->default) ? $this->default : 'dev';

        return new BranchTarget($name, [$name]);
    }
}
