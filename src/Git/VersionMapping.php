<?php

declare(strict_types=1);

namespace Goblin\Git;

use Goblin\GoblinException;

/**
 * Maps Jira Fix Versions to target branches.
 */
final readonly class VersionMapping
{
    /**
     * Stores active project versions.
     *
     * @param list<string> $versions
     */
    public function __construct(private array $versions) {}

    /**
     * Returns target branch for a Fix Version.
     *
     * @throws GoblinException
     */
    public function branchFor(string $fixVersion): string
    {
        $map = $this->buildMap();

        if (!array_key_exists($fixVersion, $map)) {
            throw new GoblinException(
                "Fix Version '{$fixVersion}' not found among active releases",
            );
        }

        return $map[$fixVersion];
    }

    /**
     * Builds version to branch mapping.
     *
     * @return array<string, string>
     */
    private function buildMap(): array
    {
        $betas = $this->betas();
        $map = [];

        foreach ($betas as $beta) {
            $map[$beta] = 'beta';
        }

        if ($betas === []) {
            return $this->allToMaster();
        }

        if (count($betas) === 1) {
            return $this->singleBetaMap($map, $betas[0]);
        }

        return $this->multiBetaMap($map, $betas);
    }

    /**
     * Maps all versions to master when no betas exist.
     *
     * @return array<string, string>
     */
    private function allToMaster(): array
    {
        $map = [];

        foreach ($this->versions as $v) {
            $map[$v] = 'master';
        }

        return $map;
    }

    /**
     * Builds map with single beta: above beta → dev, below → master.
     *
     * @param array<string, string> $map
     * @return array<string, string>
     */
    private function singleBetaMap(array $map, string $beta): array
    {
        foreach ($this->versions as $v) {
            if (array_key_exists($v, $map)) {
                continue;
            }

            $map[$v] = version_compare($v, $beta, '>')
                ? 'dev'
                : 'master';
        }

        return $map;
    }

    /**
     * Builds map with multiple betas: stage, dev, master zones.
     *
     * @param array<string, string> $map
     * @param list<string> $betas
     * @return array<string, string>
     */
    private function multiBetaMap(array $map, array $betas): array
    {
        usort($betas, static fn(string $a, string $b): int => version_compare($a, $b));
        /** @psalm-var non-empty-list<string> $betas */
        $latestBeta = $betas[count($betas) - 1];
        $stage = preg_replace('/\.1$/', '.0', $latestBeta);

        if (!is_string($stage)) {
            return $map;
        }

        if (in_array($stage, $this->versions, true)) {
            $map[$stage] = 'stage';
        }

        foreach ($this->versions as $v) {
            if (array_key_exists($v, $map)) {
                continue;
            }

            $map[$v] = version_compare($v, $stage, '>')
                ? 'dev'
                : 'master';
        }

        return $map;
    }

    /**
     * Returns beta versions (pattern X.Y.1).
     *
     * @return list<string>
     */
    private function betas(): array
    {
        return array_values(
            array_filter(
                $this->versions,
                static fn(string $v): bool => preg_match('/\s\d+\.\d+\.1$/', $v) === 1,
            ),
        );
    }
}
