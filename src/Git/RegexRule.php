<?php

declare(strict_types=1);

namespace Goblin\Git;

use Goblin\GoblinException;

/**
 * Matches releases by regex pattern with variable substitution.
 */
final readonly class RegexRule
{
    private const int OFFSET_INDEX = 2;

    /**
     * Stores regex pattern and sort direction.
     *
     * @param string $pattern Regex pattern.
     * @param string $sort Sort weight.
     */
    public function __construct(private string $pattern, private string $sort) {}

    /**
     * Returns the selected release from matches, or an empty string when no release matches.
     *
     * @param list<string> $releases Active releases.
     * @param array<string, string> $assigned Already assigned versions.
     * @param array<string, string> $vars Substitution variables.
     * @throws GoblinException
     */
    public function match(array $releases, array $assigned, array $vars): string
    {
        $regex = $this->interpolate($vars);

        if ($regex === '') {
            return '';
        }

        $matched = [];

        $this->validate($regex);

        foreach ($releases as $release) {
            if (!array_key_exists($release, $assigned) && preg_match($regex, $release) === 1) {
                $matched[] = $release;
            }
        }

        if ($matched === []) {
            return '';
        }

        usort($matched, static fn(string $a, string $b): int => version_compare($a, $b));

        return $this->sort === 'asc' ? $matched[0] : $matched[count($matched) - 1];
    }

    /**
     * Returns named groups captured from release.
     *
     * @param string $release Release identifier.
     * @param array<string, string> $vars Substitution variables.
     * @throws GoblinException
     * @return array<string, string>
     */
    public function vars(string $release, array $vars): array
    {
        $regex = $this->interpolate($vars);

        if ($regex === '') {
            return [];
        }

        $this->validate($regex);

        if (preg_match($regex, $release, $m) !== 1) {
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
     * Throws when regex pattern is invalid.
     *
     * @param non-empty-string $regex Regex value.
     * @throws GoblinException
     */
    private function validate(string $regex): void
    {
        if (!is_int(@preg_match($regex, ''))) {
            throw new GoblinException("Invalid branch-rule regex: {$regex}");
        }
    }

    /**
     * Replaces {var} and {var+N} placeholders with values.
     *
     * @param array<string, string> $vars Substitution variables.
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

                if (array_key_exists(self::OFFSET_INDEX, $m)) {
                    $value += (int) $m[self::OFFSET_INDEX];
                }

                return (string) $value;
            },
            $this->pattern,
        );
    }
}
