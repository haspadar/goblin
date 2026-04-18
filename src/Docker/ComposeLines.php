<?php

declare(strict_types=1);

namespace Goblin\Docker;

/**
 * Operations over a list of YAML lines from a docker-compose file.
 */
final readonly class ComposeLines
{
    /**
     * Stores the list of significant (non-blank, non-comment) lines.
     *
     * @param list<string> $lines
     */
    public function __construct(private array $lines) {}

    /**
     * Returns the underlying list of YAML lines.
     *
     * @return list<string>
     */
    public function all(): array
    {
        return $this->lines;
    }

    /**
     * Returns a new ComposeLines containing all lines after the first match of $pattern, or null if no match.
     *
     * @param non-empty-string $pattern
     */
    public function sliceAfter(string $pattern): ?self
    {
        foreach ($this->lines as $i => $line) {
            if (preg_match($pattern, $line) === 1) {
                return new self(array_slice($this->lines, $i + 1));
            }
        }

        return null;
    }

    /**
     * Returns a new ComposeLines containing the prefix whose indent is strictly greater than $parentIndent.
     */
    public function takeNested(int $parentIndent): self
    {
        $out = [];

        foreach ($this->lines as $line) {
            if ($this->indentOf($line) <= $parentIndent) {
                break;
            }

            $out[] = $line;
        }

        return new self($out);
    }

    /**
     * Returns a new ComposeLines with only the lines at the indent of the first line.
     */
    public function atFirstIndent(): self
    {
        if ($this->lines === []) {
            return $this;
        }

        $target = $this->indentOf($this->lines[0]);
        $out = [];

        foreach ($this->lines as $line) {
            if ($this->indentOf($line) === $target) {
                $out[] = $line;
            }
        }

        return new self($out);
    }

    /**
     * Returns the leading-whitespace capture (group 1) of the first matching line, or null.
     *
     * @param non-empty-string $pattern
     */
    public function firstCapturedIndent(string $pattern): ?int
    {
        $match = [];

        foreach ($this->lines as $line) {
            if (preg_match($pattern, $line, $match) === 1) {
                return strlen($match[1]);
            }
        }

        return null;
    }

    private function indentOf(string $line): int
    {
        return strlen($line) - strlen(ltrim($line, ' '));
    }
}
