<?php

declare(strict_types=1);

namespace Goblin\Git;

/**
 * Matches releases by regex pattern with variable substitution.
 */
final readonly class RegexRule
{
    /**
     * Stores regex pattern and sort direction.
     */
    public function __construct(private string $pattern, private string $sort) {}

    /**
     * Returns the selected release from matches, or null.
     *
     * @param list<string> $releases
     * @param array<string, string> $assigned
     * @param array<string, string> $vars
     */
    public function match(array $releases, array $assigned, array $vars): ?string
    {
        $regex = $this->interpolate($vars);

        if ($regex === '') {
            return null;
        }

        $matched = [];

        foreach ($releases as $release) {
            if (!array_key_exists($release, $assigned) && @preg_match($regex, $release) === 1) {
                $matched[] = $release;
            }
        }

        if ($matched === []) {
            return null;
        }

        usort($matched, static fn(string $a, string $b): int => version_compare($a, $b));

        return $this->sort === 'asc' ? $matched[0] : $matched[count($matched) - 1];
    }

    /**
     * Returns named groups captured from release.
     *
     * @param array<string, string> $vars
     * @return array<string, string>
     */
    public function vars(string $release, array $vars): array
    {
        $regex = $this->interpolate($vars);

        if ($regex === '' || @preg_match($regex, $release, $m) !== 1) {
            return [];
        }

        $result = [];

        foreach ($m as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Replaces {var} and {var+N} placeholders with values.
     *
     * @param array<string, string> $vars
     */
    private function interpolate(array $vars): string
    {
        if ($vars === []) {
            return $this->pattern;
        }

        return (string) preg_replace_callback(
            '/\{(\w+)(?:\s*\+\s*(\d+))?\}/',
            static function (array $m) use ($vars): string {
                $name = $m[1];

                if (!array_key_exists($name, $vars)) {
                    return $m[0];
                }

                $value = (int) $vars[$name];

                if (array_key_exists(2, $m)) {
                    $value += (int) $m[2];
                }

                return (string) $value;
            },
            $this->pattern,
        );
    }
}
