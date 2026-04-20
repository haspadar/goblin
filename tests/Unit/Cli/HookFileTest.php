<?php

declare(strict_types=1);

namespace Goblin\Tests\Unit\Cli;

use Goblin\Cli\HookAction;
use Goblin\Cli\HookFile;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HookFileTest extends TestCase
{
    #[Test]
    public function installsFreshHookWhenFileMissing(): void
    {
        $path = self::tempPath('fresh-commit-msg');
        $block = "# BEGIN goblin\necho draft-commit-msg\n# END goblin\n";

        $action = (new HookFile($path, $block))->install();

        self::assertSame(HookAction::Installed, $action, 'missing file must result in Installed');
    }

    #[Test]
    public function writesShebangAtTopOfFreshHook(): void
    {
        $path = self::tempPath('shebang-pre-push');
        $block = "# BEGIN goblin\necho shebang-check\n# END goblin\n";

        (new HookFile($path, $block))->install();

        self::assertStringStartsWith("#!/bin/sh\n", (string) file_get_contents($path), 'fresh hook must start with shebang');
    }

    #[Test]
    public function flagsFreshHookExecutable(): void
    {
        $path = self::tempPath('chmod-post-checkout');
        $block = "# BEGIN goblin\necho chmod-check\n# END goblin\n";

        (new HookFile($path, $block))->install();

        self::assertSame(0o755, fileperms($path) & 0o777, 'fresh hook must be executable by owner');
    }

    #[Test]
    public function skipsHookAlreadyContainingMarker(): void
    {
        $path = self::tempPath('skip-commit-msg');
        file_put_contents($path, "#!/bin/sh\n# BEGIN goblin\necho already-there\n# END goblin\n");
        $block = "# BEGIN goblin\necho skip-payload\n# END goblin\n";

        $action = (new HookFile($path, $block))->install();

        self::assertSame(HookAction::Skipped, $action, 'marker present must short-circuit install');
    }

    #[Test]
    public function leavesHookUntouchedWhenMarkerPresent(): void
    {
        $path = self::tempPath('untouched-pre-push');
        $original = "#!/bin/sh\n# BEGIN goblin\necho keep-me-intact\n# END goblin\n";
        file_put_contents($path, $original);
        $block = "# BEGIN goblin\necho replacement-payload\n# END goblin\n";

        (new HookFile($path, $block))->install();

        self::assertSame($original, (string) file_get_contents($path), 'skip must not rewrite file');
    }

    #[Test]
    public function appendsBlockWhenForeignHookExists(): void
    {
        $path = self::tempPath('append-post-checkout');
        file_put_contents($path, "#!/bin/sh\nexec sh \"\$ROOT/../pilot/hooks/post-checkout\" \"\$@\"\n");
        $block = "# BEGIN goblin\necho appended-payload\n# END goblin\n";

        $action = (new HookFile($path, $block))->install();

        self::assertSame(HookAction::Appended, $action, 'foreign hook must trigger append');
    }

    #[Test]
    public function keepsForeignContentAbovePastedBlock(): void
    {
        $path = self::tempPath('preserve-pre-push');
        $pilot = "#!/bin/sh\nexec sh \"\$ROOT/../pilot/hooks/pre-push\" \"\$@\"\n";
        file_put_contents($path, $pilot);
        $block = "# BEGIN goblin\necho goblin-pre-push\n# END goblin\n";

        (new HookFile($path, $block))->install();

        self::assertStringStartsWith($pilot, (string) file_get_contents($path), 'append must preserve existing content at the top');
    }

    #[Test]
    public function skipsOnSecondInstallAfterAppend(): void
    {
        $path = self::tempPath('reinstall-commit-msg');
        file_put_contents($path, "#!/bin/sh\necho foreign-hook\n");
        $block = "# BEGIN goblin\necho reinstall-payload\n# END goblin\n";
        (new HookFile($path, $block))->install();

        $action = (new HookFile($path, $block))->install();

        self::assertSame(HookAction::Skipped, $action, 'second install after append must skip');
    }

    #[Test]
    public function ignoresMarkerPhraseInsideForeignLine(): void
    {
        $path = self::tempPath('inline-marker-pre-push');
        file_put_contents($path, "#!/bin/sh\necho \"see # BEGIN goblin in a sentence\"\n");
        $block = "# BEGIN goblin\necho real-block\n# END goblin\n";

        $action = (new HookFile($path, $block))->install();

        self::assertSame(HookAction::Appended, $action, 'marker not at line start must not count as installed');
    }

    #[Test]
    public function appendsBlockAtEndOfFile(): void
    {
        $path = self::tempPath('tail-commit-msg');
        file_put_contents($path, "#!/bin/sh\nexec sh \"\$ROOT/../pilot/hooks/commit-msg\" \"\$@\"\n");
        $block = "# BEGIN goblin\necho tail-payload\n# END goblin\n";

        (new HookFile($path, $block))->install();

        self::assertStringEndsWith($block, (string) file_get_contents($path), 'appended block must land at the end');
    }

    #[Test]
    public function treatsEmptyExistingFileAsFreshInstall(): void
    {
        $path = self::tempPath('empty-pre-push');
        file_put_contents($path, '');
        $block = "# BEGIN goblin\necho empty-payload\n# END goblin\n";

        (new HookFile($path, $block))->install();

        self::assertStringStartsWith("#!/bin/sh\n", (string) file_get_contents($path), 'empty file must be seeded with shebang');
    }

    #[Test]
    public function stripsCarriageReturnBeforeAppendedBlock(): void
    {
        $path = self::tempPath('crlf-post-checkout');
        file_put_contents($path, "#!/bin/sh\r\necho crlf-foreign\r\n");
        $block = "# BEGIN goblin\necho crlf-payload\n# END goblin\n";

        (new HookFile($path, $block))->install();

        self::assertStringNotContainsString("\r\n\n# BEGIN goblin", (string) file_get_contents($path), 'append must not leave orphan CR before block');
    }

    private static function tempPath(string $suffix): string
    {
        $path = sys_get_temp_dir() . '/goblin-hookfile-' . $suffix . '-' . bin2hex(random_bytes(4));
        register_shutdown_function(static fn() => is_file($path) && unlink($path));

        return $path;
    }
}
