<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Config;

use Goblin\Config\MergedConfigFile;
use Goblin\GoblinException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MergedConfigFileTest extends TestCase
{
    #[Test]
    public function returnsBaseWhenOverlayPathIsNull(): void
    {
        $base = self::writeConfig('base-null', ['jira-url' => 'https://base.example']);

        $data = (new MergedConfigFile($base))->data();

        self::assertSame(['jira-url' => 'https://base.example'], $data, 'base is returned verbatim when no overlay is configured');
    }

    #[Test]
    public function returnsBaseWhenOverlayFileIsMissing(): void
    {
        $base = self::writeConfig('base-missing-overlay', ['jira-url' => 'https://base.example']);
        $absent = sys_get_temp_dir() . '/goblin-merged-missing-' . bin2hex(random_bytes(4)) . '.php';

        $data = (new MergedConfigFile($base, $absent))->data();

        self::assertSame(['jira-url' => 'https://base.example'], $data, 'missing overlay file is ignored without error');
    }

    #[Test]
    public function overlayKeysReplaceBaseKeys(): void
    {
        $base = self::writeConfig('overlay-replace-base', ['jira-url' => 'https://base.example', 'protected-branches' => ['master']]);
        $overlay = self::writeConfig('overlay-replace-local', ['protected-branches' => ['dev']]);

        $data = (new MergedConfigFile($base, $overlay))->data();

        self::assertSame(['jira-url' => 'https://base.example', 'protected-branches' => ['dev']], $data, 'overlay replaces base keys at the top level while keeping unrelated base keys');
    }

    #[Test]
    public function overlayMapReplacesBaseMapWholesale(): void
    {
        $base = self::writeConfig('overlay-map-base', ['branch-rules' => ['stage' => ['match' => '/stage/'], 'default' => 'dev']]);
        $overlay = self::writeConfig('overlay-map-local', ['branch-rules' => ['default' => 'master']]);

        $data = (new MergedConfigFile($base, $overlay))->data();

        self::assertSame(['branch-rules' => ['default' => 'master']], $data, 'overlay nested map fully replaces base map, no recursive merge');
    }

    #[Test]
    public function skipsOverlayWhenItResolvesToBasePath(): void
    {
        $base = self::writeConfig('overlay-same-path', ['jira-url' => 'https://base.example']);

        $data = (new MergedConfigFile($base, $base))->data();

        self::assertSame(['jira-url' => 'https://base.example'], $data, 'overlay equal to base is treated as no-overlay to avoid double merge');
    }

    #[Test]
    public function throwsWhenBaseFileMissing(): void
    {
        $missing = sys_get_temp_dir() . '/goblin-merged-no-base-' . bin2hex(random_bytes(4)) . '.php';

        $this->expectException(GoblinException::class);

        (new MergedConfigFile($missing))->data();
    }

    private static function writeConfig(string $suffix, array $data): string
    {
        $path = sys_get_temp_dir() . '/goblin-merged-' . $suffix . '-' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($path, '<?php return ' . var_export($data, true) . ';');
        register_shutdown_function(static fn() => is_file($path) && unlink($path));

        return $path;
    }
}
