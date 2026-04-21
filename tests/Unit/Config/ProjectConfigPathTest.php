<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Config;

use Goblin\Config\ProjectConfigPath;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProjectConfigPathTest extends TestCase
{
    #[Test]
    public function returnsOverrideVerbatimWhenProvided(): void
    {
        $override = '/explicit/path/to/overlay.php';

        $value = (new ProjectConfigPath($override, sys_get_temp_dir()))->value();

        self::assertSame($override, $value, 'explicit --config path wins over cwd discovery');
    }

    #[Test]
    public function returnsCwdConfigWhenFileExists(): void
    {
        $dir = self::tempDir('cwd-present');
        $expected = $dir . '/.goblin.php';
        file_put_contents($expected, '<?php return [];');

        $value = (new ProjectConfigPath('', $dir))->value();

        self::assertSame($expected, $value, 'cwd-local .goblin.php is auto-discovered when present');
    }

    #[Test]
    public function returnsNullWhenCwdHasNoConfig(): void
    {
        $dir = self::tempDir('cwd-absent');

        $value = (new ProjectConfigPath('', $dir))->value();

        self::assertNull($value, 'no override and no cwd file must yield null overlay');
    }

    #[Test]
    public function trimsTrailingSlashFromCwdBeforeAppendingFilename(): void
    {
        $dir = self::tempDir('cwd-trailing-slash');
        $expected = $dir . '/.goblin.php';
        file_put_contents($expected, '<?php return [];');

        $value = (new ProjectConfigPath('', $dir . '/'))->value();

        self::assertSame($expected, $value, 'cwd with trailing slash must not produce a double separator');
    }

    private static function tempDir(string $suffix): string
    {
        $dir = sys_get_temp_dir() . '/goblin-project-config-' . $suffix . '-' . bin2hex(random_bytes(4));
        mkdir($dir);
        register_shutdown_function(static function () use ($dir): void {
            array_map(unlink(...), glob($dir . '/{,.}*.php', GLOB_BRACE) ?: []);
            is_dir($dir) && rmdir($dir);
        });

        return $dir;
    }
}
