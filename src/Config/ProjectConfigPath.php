<?php

declare(strict_types=1);

namespace Goblin\Config;

/**
 * Resolves a local overlay config path from a CLI override or the current directory.
 */
final readonly class ProjectConfigPath
{
    private const string FILENAME = '.goblin.php';

    /**
     * Stores the CLI override (empty when absent) and the current working directory.
     */
    public function __construct(private string $override, private string $cwd) {}

    /**
     * Returns the override verbatim, the cwd-scoped config path when it exists, or null.
     */
    public function value(): ?string
    {
        if ($this->override !== '') {
            return $this->override;
        }

        $candidate = rtrim($this->cwd, '/') . '/' . self::FILENAME;

        return is_file($candidate)
            ? $candidate
            : null;
    }
}
