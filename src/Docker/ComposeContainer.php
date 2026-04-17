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

        return $this->readContainerName(new ComposeLines($this->significant($contents)), $path);
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
     * Reads container_name from the body of the configured service.
     *
     * @throws GoblinException
     */
    private function readContainerName(ComposeLines $lines, string $path): string
    {
        $services = $lines->sliceAfter('/^services:\s*$/')
            ?? throw new GoblinException("No 'services:' block in {$path}");

        $servicePattern = '/^(\s+)' . preg_quote($this->service, '/') . ':\s*$/';
        $serviceIndent = $services->takeNested(0)->firstCapturedIndent($servicePattern)
            ?? throw new GoblinException("Service '{$this->service}' not found in {$path}");

        $body = ($services->sliceAfter($servicePattern) ?? new ComposeLines([]))
            ->takeNested($serviceIndent)
            ->atFirstIndent();

        foreach ($body->all() as $line) {
            $match = [];

            if (preg_match('/^\s+container_name:\s*(.+)$/', $line, $match) === 1) {
                return $this->cleanValue($match[1]);
            }
        }

        throw new GoblinException("Service '{$this->service}' has no container_name in {$path}");
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
     * Splits file contents into lines and drops blank and comment-only lines.
     *
     * @return list<string>
     */
    private function significant(string $contents): array
    {
        $split = preg_split('/\R/', $contents);

        if ($split === false) {
            return [];
        }

        $out = [];

        foreach ($split as $line) {
            if (trim($line) !== '' && !str_starts_with(ltrim($line), '#')) {
                $out[] = $line;
            }
        }

        return $out;
    }
}
