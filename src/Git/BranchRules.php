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
    public function branchFor(string $release): BranchTarget
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
     * Builds release to BranchTarget mapping.
     *
     * @throws GoblinException
     * @return array<string, BranchTarget>
     */
    private function buildMap(): array
    {
        $map = [];
        $assigned = [];
        $vars = [];

        foreach ($this->rules as $branch => $rule) {
            if ($branch === 'default' || !is_array($rule)) {
                continue;
            }

            $regex = $this->ruleRegex($rule);
            $matched = $regex->match($this->releases, $assigned, $vars);

            if ($matched !== null) {
                $map[$matched] = new BranchTarget($branch, (new BaseList($rule, $branch))->toList());
                $assigned[$matched] = $branch;
                $vars = array_merge($vars, $regex->vars($matched, $vars));
            }
        }

        $default = $this->defaultBranch();
        $fallback = new BranchTarget($default, [$default]);

        foreach ($this->releases as $release) {
            if (!array_key_exists($release, $map)) {
                $map[$release] = $fallback;
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
