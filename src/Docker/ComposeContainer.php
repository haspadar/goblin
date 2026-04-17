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
        $split = preg_split('/\R/', (string) file_get_contents($path));

        if ($split === false) {
            throw new GoblinException("Cannot parse compose file: {$path}");
        }

        return $this->readContainerName($this->significant($split), $path);
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
        $afterServices = $this->sliceAfter($lines, '/^services:\s*$/')
            ?? throw new GoblinException("No 'services:' block in {$path}");

        $servicePattern = '/^(\s+)' . preg_quote($this->service, '/') . ':\s*$/';
        $serviceTail = $this->sliceAfter($afterServices, $servicePattern)
            ?? throw new GoblinException("Service '{$this->service}' not found in {$path}");

        $indent = $serviceTail === []
            ? 0
            : $this->indent($serviceTail[0]);

        foreach ($serviceTail as $line) {
            if ($this->indent($line) < $indent) {
                break;
            }

            if (preg_match('/^\s+container_name:\s*(.+)$/', $line, $match) === 1) {
                return trim($match[1], " \t\"'");
            }
        }

        throw new GoblinException("Service '{$this->service}' has no container_name in {$path}");
    }

    /**
     * Returns lines after the first match of $pattern, or null if no match.
     *
     * @param list<string> $lines
     * @param non-empty-string $pattern
     * @return list<string>|null
     */
    private function sliceAfter(array $lines, string $pattern): ?array
    {
        foreach ($lines as $i => $line) {
            if (preg_match($pattern, $line) === 1) {
                return array_slice($lines, $i + 1);
            }
        }

        return null;
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
