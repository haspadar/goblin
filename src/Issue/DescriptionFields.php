<?php

declare(strict_types=1);

namespace Goblin\Issue;

use Goblin\GoblinException;
use Goblin\Http\Http;

/**
 * Discovers ADF text fields from Jira field metadata.
 */
final readonly class DescriptionFields
{
    /**
     * Stores HTTP client for Jira API requests.
     */
    public function __construct(private Http $http) {}

    /**
     * Returns field IDs that contain ADF text.
     *
     * @throws GoblinException
     * @return list<string>
     */
    public function names(): array
    {
        $fields = $this->http->json('GET', '/rest/api/3/field');
        $result = ['description'];

        /** @psalm-var mixed $field */
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            /** @psalm-var array<string, mixed> $field */
            $id = $this->textareaId($field);

            if ($id !== '') {
                $result[] = $id;
            }
        }

        return $result;
    }

    /**
     * Extracts field ID if it is a textarea custom field.
     *
     * @param array<string, mixed> $field
     */
    private function textareaId(array $field): string
    {
        $id = $field['id'] ?? '';
        $schema = $field['schema'] ?? [];

        if (!is_string($id) || $id === 'description' || !is_array($schema)) {
            return '';
        }

        /** @psalm-var mixed $custom */
        $custom = $schema['custom'] ?? '';

        return $custom === 'com.atlassian.jira.plugin.system.customfieldtypes:textarea'
            ? $id
            : '';
    }
}
