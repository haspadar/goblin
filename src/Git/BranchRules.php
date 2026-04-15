<?php

declare(strict_types=1);

namespace Goblin\Git;

use Goblin\GoblinException;

/**
 * Maps Jira Fix Versions to target branches using branch-rules config.
 */
final readonly class BranchRules
{
    /**
     * Stores active releases and branch rules.
     *
     * @param list<string> $releases
     * @param array<string, mixed> $rules
     */
    public function __construct(private array $releases, private array $rules) {}

    /**
     * Returns target branch for a Fix Version.
     *
     * @throws GoblinException
     */
    public function branchFor(string $release): string
    {
        $map = $this->buildMap();

        if (!array_key_exists($release, $map)) {
            throw new GoblinException(
                "Fix Version '{$release}' not found among active releases",
            );
        }

        return $map[$release];
    }

    /**
     * Builds release to branch mapping.
     *
     * @throws GoblinException
     * @return array<string, string>
     */
    private function buildMap(): array
    {
        $map = [];
        $vars = [];

        foreach ($this->rules as $branch => $rule) {
            if ($branch === 'default' || !is_array($rule)) {
                continue;
            }

            $regex = $this->ruleRegex($rule);
            $matched = $regex->match($this->releases, $map, $vars);

            if ($matched !== null) {
                $map[$matched] = $branch;
                $vars = array_merge($vars, $regex->vars($matched, $vars));
            }
        }

        $default = $this->defaultBranch();

        foreach ($this->releases as $release) {
            if (!array_key_exists($release, $map)) {
                $map[$release] = $default;
            }
        }

        return $map;
    }

    /**
     * Creates RegexRule from a rule config entry.
     *
     * @param array<array-key, mixed> $rule
     */
    private function ruleRegex(array $rule): RegexRule
    {
        /** @psalm-var mixed $pattern */
        $pattern = $rule['match'] ?? '';
        /** @psalm-var mixed $sort */
        $sort = $rule['sort'] ?? 'desc';

        return new RegexRule(
            is_string($pattern) ? $pattern : '',
            is_string($sort) ? $sort : 'desc',
        );
    }

    /**
     * Returns default branch name from rules.
     */
    private function defaultBranch(): string
    {
        /** @psalm-var mixed $default */
        $default = $this->rules['default'] ?? 'dev';

        return is_string($default) ? $default : 'dev';
    }
}
