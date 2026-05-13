<?php

declare(strict_types=1);

namespace Goblin\Issue;

/**
 * Renders Jira ADF node tree into plain text.
 */
final readonly class AdfText
{
    /**
     * Stores the ADF node to render.
     *
     * @param array<string, mixed> $node ADF node.
     */
    public function __construct(private array $node) {}

    /**
     * Returns plain-text representation of the ADF node.
     */
    public function text(): string
    {
        return $this->render($this->node);
    }

    /**
     * Recursively renders a single ADF node.
     *
     * @param array<string, mixed> $adf ADF node.
     */
    private function render(array $adf): string
    {
        $type = $this->string($adf, 'type');

        return match ($type) {
            'text' => $this->string($adf, 'text'),
            'hardBreak' => "\n",
            'mention' => $this->string($this->attrs($adf), 'text'),
            'inlineCard', 'embedCard' => $this->string($this->attrs($adf), 'url'),
            'paragraph', 'heading' => sprintf("%s\n\n", $this->children($adf)),
            'listItem' => sprintf("- %s\n", trim($this->children($adf))),
            default => $this->children($adf),
        };
    }

    /**
     * Joins rendered children of a container node.
     *
     * @param array<string, mixed> $adf ADF node.
     */
    private function children(array $adf): string
    {
        /** @psalm-var mixed $content */
        $content = $adf['content'] ?? [];
        $parts = [];

        if (is_array($content)) {
            /** @psalm-var mixed $child */
            foreach ($content as $child) {
                if (is_array($child)) {
                    /** @phpstan-var array<string, mixed> $child */
                    $parts[] = $this->render($child);
                }
            }
        }

        return implode('', $parts);
    }

    /**
     * Extracts a string value from an array by key.
     *
     * @param array<string, mixed> $data Raw data array.
     */
    private function string(array $data, string $key): string
    {
        /** @psalm-var mixed $value */
        $value = $data[$key] ?? '';

        return is_string($value)
            ? $value
            : '';
    }

    /**
     * Extracts attrs sub-array from a node.
     *
     * @param array<string, mixed> $adf ADF node.
     * @return array<string, mixed>
     */
    private function attrs(array $adf): array
    {
        $attrs = $adf['attrs'] ?? [];

        if (!is_array($attrs)) {
            return [];
        }

        /** @phpstan-var array<string, mixed> $attrs */
        return $attrs;
    }
}
