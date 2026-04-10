<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Issue;

use Goblin\Issue\DescriptionFields;
use Goblin\Tests\Fake\FakeHttp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DescriptionFieldsTest extends TestCase
{
    #[Test]
    public function alwaysIncludesDescription(): void
    {
        $fields = new DescriptionFields(
            new FakeHttp(['GET /rest/api/3/field' => []]),
        );

        self::assertSame(
            ['description'],
            $fields->names(),
            'description must always be first in the list',
        );
    }

    #[Test]
    public function includesTextareaCustomFields(): void
    {
        $fields = new DescriptionFields(
            new FakeHttp([
                'GET /rest/api/3/field' => [
                    [
                        'id' => 'customfield_11961',
                        'name' => 'Acceptance Criteria',
                        'schema' => [
                            'custom' => 'com.atlassian.jira.plugin.system.customfieldtypes:textarea',
                        ],
                    ],
                    [
                        'id' => 'customfield_10020',
                        'name' => 'Story Points',
                        'schema' => [
                            'custom' => 'com.atlassian.jira.plugin.system.customfieldtypes:float',
                        ],
                    ],
                ],
            ]),
        );

        self::assertSame(
            ['description', 'customfield_11961'],
            $fields->names(),
            'textarea custom fields must be included after description',
        );
    }

    #[Test]
    public function skipsDescriptionFieldId(): void
    {
        $fields = new DescriptionFields(
            new FakeHttp([
                'GET /rest/api/3/field' => [
                    [
                        'id' => 'description',
                        'name' => 'Description',
                        'schema' => [
                            'custom' => 'com.atlassian.jira.plugin.system.customfieldtypes:textarea',
                        ],
                    ],
                ],
            ]),
        );

        self::assertSame(
            ['description'],
            $fields->names(),
            'description must not be duplicated',
        );
    }

    #[Test]
    public function skipsNonArrayFields(): void
    {
        $fields = new DescriptionFields(
            new FakeHttp([
                'GET /rest/api/3/field' => [
                    'not-an-array',
                    42,
                ],
            ]),
        );

        self::assertSame(
            ['description'],
            $fields->names(),
            'non-array entries must be skipped',
        );
    }
}
