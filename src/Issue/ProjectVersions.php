<?php

declare(strict_types=1);

namespace Goblin\Issue;

use Goblin\GoblinException;
use Goblin\Http\Http;

/**
 * Unreleased versions for a Jira project.
 */
final readonly class ProjectVersions
{
    /**
     * Stores HTTP client and project key.
     */
    public function __construct(private Http $http, private string $project) {}

    /**
     * Returns active unreleased versions sorted by semver.
     *
     * @throws GoblinException
     * @return list<string>
     */
    public function names(): array
    {
        $response = $this->http->json(
            'GET',
            "/rest/api/3/project/{$this->project}/version?status=unreleased&orderBy=name",
        );

        /** @psalm-var mixed $allVersions */
        $allVersions = $response['values'] ?? [];

        $result = [];
        $pattern = '/^' . preg_quote($this->project, '/') . '\s+\d+\.\d+\.\d+$/';

        /** @psalm-var mixed $version */
        foreach (is_array($allVersions) ? $allVersions : [] as $version) {
            if (!is_array($version)) {
                continue;
            }

            /** @psalm-var mixed $name */
            $name = $version['name'] ?? '';

            if (is_string($name) && preg_match($pattern, $name) === 1) {
                $result[] = $name;
            }
        }

        usort($result, static fn(string $a, string $b): int => version_compare($a, $b));

        return $result;
    }
}
