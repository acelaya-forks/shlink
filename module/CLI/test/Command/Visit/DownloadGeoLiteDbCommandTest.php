<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Visit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Visit\DownloadGeoLiteDbCommand;
use Shlinkio\Shlink\CLI\Util\ExitCode;
use Shlinkio\Shlink\Core\Exception\GeolocationDbUpdateFailedException;
use Shlinkio\Shlink\Core\Geolocation\GeolocationDbUpdaterInterface;
use Shlinkio\Shlink\Core\Geolocation\GeolocationDownloadProgressHandlerInterface;
use Shlinkio\Shlink\Core\Geolocation\GeolocationResult;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Tester\CommandTester;

use function sprintf;

class DownloadGeoLiteDbCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & GeolocationDbUpdaterInterface $dbUpdater;

    protected function setUp(): void
    {
        $this->dbUpdater = $this->createMock(GeolocationDbUpdaterInterface::class);
        $this->commandTester = CliTestUtils::testerForCommand(new DownloadGeoLiteDbCommand($this->dbUpdater));
    }

    #[Test, DataProvider('provideFailureParams')]
    public function showsProperMessageWhenGeoLiteUpdateFails(
        bool $olderDbExists,
        string $expectedMessage,
        int $expectedExitCode,
    ): void {
        $this->dbUpdater->expects($this->once())->method('checkDbUpdate')->withAnyParameters()->willReturnCallback(
            function (GeolocationDownloadProgressHandlerInterface $handler) use ($olderDbExists): void {
                $handler->beforeDownload($olderDbExists);
                $handler->handleProgress(100, 50, $olderDbExists);

                throw $olderDbExists
                    ? GeolocationDbUpdateFailedException::withOlderDb()
                    : GeolocationDbUpdateFailedException::withoutOlderDb();
            },
        );

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();
        $exitCode = $this->commandTester->getStatusCode();

        self::assertStringContainsString(
            sprintf('%s GeoLite2 db file...', $olderDbExists ? 'Updating' : 'Downloading'),
            $output,
        );
        self::assertStringContainsString($expectedMessage, $output);
        self::assertSame($expectedExitCode, $exitCode);
    }

    public static function provideFailureParams(): iterable
    {
        yield 'existing db' => [
            true,
            '[WARNING] GeoLite2 db file update failed. Visits will continue to be located',
            ExitCode::EXIT_WARNING,
        ];
        yield 'not existing db' => [
            false,
            '[ERROR] GeoLite2 db file download failed. It will not be possible to locate',
            ExitCode::EXIT_FAILURE,
        ];
    }

    #[Test]
    #[TestWith([GeolocationResult::LICENSE_MISSING, 'It was not possible to download GeoLite2 db'])]
    #[TestWith([GeolocationResult::MAX_ERRORS_REACHED, 'Max consecutive errors reached'])]
    #[TestWith([GeolocationResult::UPDATE_IN_PROGRESS, 'A geolocation db is already being downloaded'])]
    public function warningIsPrintedForSomeResults(GeolocationResult $result, string $expectedWarningMessage): void
    {
        $this->dbUpdater->expects($this->once())->method('checkDbUpdate')->withAnyParameters()->willReturn($result);

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();
        $exitCode = $this->commandTester->getStatusCode();

        self::assertStringContainsString('[WARNING] ' . $expectedWarningMessage, $output);
        self::assertSame(ExitCode::EXIT_WARNING, $exitCode);
    }

    #[Test, DataProvider('provideSuccessParams')]
    public function printsExpectedMessageWhenNoErrorOccurs(callable $checkUpdateBehavior, string $expectedMessage): void
    {
        $this->dbUpdater->expects($this->once())->method('checkDbUpdate')->withAnyParameters()->willReturnCallback(
            $checkUpdateBehavior,
        );

        $this->commandTester->execute([]);
        $output = $this->commandTester->getDisplay();
        $exitCode = $this->commandTester->getStatusCode();

        self::assertStringContainsString($expectedMessage, $output);
        self::assertSame(ExitCode::EXIT_SUCCESS, $exitCode);
    }

    public static function provideSuccessParams(): iterable
    {
        yield 'up to date db' => [fn () => GeolocationResult::CHECK_SKIPPED, '[INFO] GeoLite2 db file is up to date.'];
        yield 'outdated db' => [function (GeolocationDownloadProgressHandlerInterface $handler): GeolocationResult {
            $handler->beforeDownload(true);
            return GeolocationResult::DB_CREATED;
        }, '[OK] GeoLite2 db file properly downloaded.'];
    }
}
