<?php

declare(strict_types=1);

namespace Goblin\Docker;

use Goblin\GoblinException;

/**
 * Reads services.<service>.container_name from a docker-compose file.
 */
final readonly class ComposeContainer
{
    private const array FILES = ['docker-compose.yml', 'docker-compose.yaml', 'compose.yml', 'compose.yaml'];

    /**
     * Stores project root and compose service key to read.
     */
    public function __construct(private string $projectRoot, private string $service) {}

    /**
     * Returns container_name of the configured service.
     *
     * @throws GoblinException
     */
    public function name(): string
    {
        $path = $this->composePath();
        $contents = @file_get_contents($path);

        if ($contents === false) {
            throw new GoblinException("Cannot read compose file: {$path}");
        }

        $split = preg_split('/\R/', $contents);
        $lines = $split === false
            ? []
            : $this->significant($split);

        return $this->readContainerName($lines, $path);
    }

    /**
     * Finds an existing compose file in the project root.
     *
     * @throws GoblinException
     */
    private function composePath(): string
    {
        foreach (self::FILES as $file) {
            $path = $this->projectRoot . '/' . $file;

            if (is_file($path)) {
                return $path;
            }
        }

        throw new GoblinException("No compose file in {$this->projectRoot}");
    }

    /**
     * Locates the requested service body and reads container_name from it.
     *
     * @param list<string> $lines
     * @throws GoblinException
     */
    private function readContainerName(array $lines, string $path): string
    {
        $services = $this->servicesBlock($lines, $path);
        $body = $this->serviceBody($services, $path);

        foreach ($body as $line) {
            $match = [];

            if (preg_match('/^\s+container_name:\s*(.+)$/', $line, $match) === 1) {
                return $this->cleanValue($match[1]);
            }
        }

        throw new GoblinException("Service '{$this->service}' has no container_name in {$path}");
    }

    /**
     * Returns lines that belong to the top-level services: block.
     *
     * @param list<string> $lines
     * @throws GoblinException
     * @return list<string>
     */
    private function servicesBlock(array $lines, string $path): array
    {
        $after = $this->sliceAfterMatch($lines, '/^services:\s*$/');

        if ($after === null) {
            throw new GoblinException("No 'services:' block in {$path}");
        }

        return $this->takeWhileIndented($after, 0);
    }

    /**
     * Returns lines that form the body of the requested service, filtered to the shallowest child indent.
     *
     * @param list<string> $services
     * @throws GoblinException
     * @return list<string>
     */
    private function serviceBody(array $services, string $path): array
    {
        $pattern = '/^(\s+)' . preg_quote($this->service, '/') . ':\s*$/';
        $parent = $this->matchIndent($services, $pattern);

        if ($parent === null) {
            throw new GoblinException("Service '{$this->service}' not found in {$path}");
        }

        $after = $this->sliceAfterMatch($services, $pattern) ?? [];

        return $this->onlyAtIndent($this->takeWhileIndented($after, $parent));
    }

    /**
     * Returns lines that come after the first match of $pattern, or null if no match.
     *
     * @param list<string> $lines
     * @param non-empty-string $pattern
     * @return list<string>|null
     */
    private function sliceAfterMatch(array $lines, string $pattern): ?array
    {
        foreach ($lines as $i => $line) {
            if (preg_match($pattern, $line) === 1) {
                return array_slice($lines, $i + 1);
            }
        }

        return null;
    }

    /**
     * Returns the indent of the first line matching $pattern with a single captured leading-whitespace group, or null.
     *
     * @param list<string> $lines
     * @param non-empty-string $pattern
     */
    private function matchIndent(array $lines, string $pattern): ?int
    {
        $match = [];

        foreach ($lines as $line) {
            if (preg_match($pattern, $line, $match) === 1) {
                return strlen($match[1]);
            }
        }

        return null;
    }

    /**
     * Returns the prefix of $lines whose indent is strictly greater than $parentIndent.
     *
     * @param list<string> $lines
     * @return list<string>
     */
    private function takeWhileIndented(array $lines, int $parentIndent): array
    {
        $out = [];

        foreach ($lines as $line) {
            if ($this->indent($line) <= $parentIndent) {
                break;
            }

            $out[] = $line;
        }

        return $out;
    }

    /**
     * Keeps only lines at the shallowest indent of the given block.
     *
     * @param list<string> $lines
     * @return list<string>
     */
    private function onlyAtIndent(array $lines): array
    {
        if ($lines === []) {
            return [];
        }

        $target = $this->indent($lines[0]);
        $out = [];

        foreach ($lines as $line) {
            if ($this->indent($line) === $target) {
                $out[] = $line;
            }
        }

        return $out;
    }

    /**
     * Strips inline comment, surrounding quotes, and whitespace from a value.
     */
    private function cleanValue(string $raw): string
    {
        $value = preg_replace('/\s+#.*$/', '', $raw) ?? $raw;

        return trim($value, " \t\"'");
    }

    /**
     * Removes blank and comment lines from the input.
     *
     * @param list<string> $lines
     * @return list<string>
     */
    private function significant(array $lines): array
    {
        $out = [];

        foreach ($lines as $line) {
            if (trim($line) !== '' && !str_starts_with(ltrim($line), '#')) {
                $out[] = $line;
            }
        }

        return $out;
    }

    private function indent(string $line): int
    {
        return strlen($line) - strlen(ltrim($line, ' '));
    }
}
