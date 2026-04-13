<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Cli;

use Goblin\Cli\Arguments;
use Goblin\Cli\Command\IssueCommand;
use Goblin\Tests\Fake\FakeConfig;
use Goblin\Tests\Fake\FakeGit;
use Goblin\Tests\Fake\FakeHttp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IssueCommandTest extends TestCase
{
    #[Test]
    public function returnsZeroExitCode(): void
    {
        $cmd = new IssueCommand(
            $this->http('PROJ-42'),
            new FakeGit('PROJ-42-fix-login'),
            new FakeConfig(['project-regex' => '/^([A-Z]+)-\d+/']),
        );

        ob_start();
        $code = $cmd->run(new Arguments('issue', [], ['PROJ-42']));
        ob_end_clean();

        self::assertSame(
            0,
            $code,
            'issue command must return zero exit code',
        );
    }

    #[Test]
    public function outputsDescriptionAsPlainText(): void
    {
        $cmd = new IssueCommand(
            new FakeHttp([
                'GET /rest/api/3/issue/PROJ-42' => [
                    'fields' => [
                        'description' => [
                            'type' => 'doc',
                            'content' => [
                                [
                                    'type' => 'paragraph',
                                    'content' => [
                                        ['type' => 'text', 'text' => 'Hello world'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'GET /rest/api/3/field' => [],
            ]),
            new FakeGit('PROJ-42-fix-login'),
            new FakeConfig(['project-regex' => '/^([A-Z]+)-\d+/']),
        );

        ob_start();
        $cmd->run(new Arguments('issue', [], ['PROJ-42', 'description']));
        $output = (string) ob_get_clean();

        self::assertStringContainsString(
            'Hello world',
            $output,
            'description mode must output plain text',
        );
    }

    #[Test]
    public function outputsRawAsJson(): void
    {
        $cmd = new IssueCommand(
            new FakeHttp([
                'GET /rest/api/3/issue/PROJ-42' => [
                    'key' => 'PROJ-42',
                    'fields' => ['summary' => 'Raw test'],
                ],
                'GET /rest/api/3/field' => [],
            ]),
            new FakeGit('PROJ-42-fix-login'),
            new FakeConfig(['project-regex' => '/^([A-Z]+)-\d+/']),
        );

        ob_start();
        $cmd->run(new Arguments('issue', [], ['PROJ-42', 'raw']));
        $output = (string) ob_get_clean();

        self::assertStringContainsString(
            'PROJ-42',
            $output,
            'raw mode must output JSON with issue key',
        );
    }

    #[Test]
    public function resolvesNumericKeyWithProject(): void
    {
        $cmd = new IssueCommand(
            new FakeHttp([
                'GET /rest/api/3/issue/PROJ-99' => [
                    'key' => 'PROJ-99',
                    'fields' => ['summary' => 'Numeric'],
                ],
                'GET /rest/api/3/field' => [],
            ]),
            new FakeGit('PROJ-99-numeric-test'),
            new FakeConfig(['project-regex' => '/^([A-Z]+)-\d+/']),
        );

        ob_start();
        $cmd->run(new Arguments('issue', [], ['99', 'raw']));
        $output = (string) ob_get_clean();

        self::assertStringContainsString(
            'PROJ-99',
            $output,
            'numeric key must be resolved with project prefix',
        );
    }

    #[Test]
    public function worksOnBranchWithoutProjectPrefix(): void
    {
        $cmd = new IssueCommand(
            new FakeHttp([
                'GET /rest/api/3/issue/PROJ-10' => [
                    'key' => 'PROJ-10',
                    'fields' => ['summary' => 'No prefix branch'],
                ],
                'GET /rest/api/3/field' => [],
            ]),
            new FakeGit('dev'),
            new FakeConfig(['project-regex' => '/^([A-Z]+)-\d+/']),
        );

        ob_start();
        $code = $cmd->run(new Arguments('issue', [], ['PROJ-10', 'raw']));
        ob_end_clean();

        self::assertSame(
            0,
            $code,
            'must work with full key when branch has no project prefix',
        );
    }

    /**
     * @return FakeHttp
     */
    private function http(string $key): FakeHttp
    {
        return new FakeHttp([
            "GET /rest/api/3/issue/{$key}" => [
                'key' => $key,
                'fields' => [
                    'summary' => 'Test issue',
                    'status' => ['name' => 'Open'],
                ],
            ],
            "GET /rest/api/3/issue/{$key}/comment?startAt=0&maxResults=100" => [
                'comments' => [],
                'total' => 0,
                'startAt' => 0,
                'maxResults' => 100,
            ],
            'GET /rest/api/3/field' => [],
        ]);
    }
}
