<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\ShortUrl;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\ShortUrl\DeleteShortUrlCommand;
use Shlinkio\Shlink\Core\Exception;
use Shlinkio\Shlink\Core\ShortUrl\DeleteShortUrlServiceInterface;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Tester\CommandTester;

use function sprintf;

use const PHP_EOL;

class DeleteShortUrlCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & DeleteShortUrlServiceInterface $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(DeleteShortUrlServiceInterface::class);
        $this->commandTester = CliTestUtils::testerForCommand(new DeleteShortUrlCommand($this->service));
    }

    #[Test]
    public function successMessageIsPrintedIfUrlIsProperlyDeleted(): void
    {
        $shortCode = 'abc123';
        $this->service->expects($this->once())->method('deleteByShortCode')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            $this->isFalse(),
        );

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString(
            sprintf('Short URL with short code "%s" successfully deleted.', $shortCode),
            $output,
        );
    }

    #[Test]
    public function invalidShortCodePrintsMessage(): void
    {
        $shortCode = 'abc123';
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($shortCode);
        $this->service->expects($this->once())->method('deleteByShortCode')->with(
            $identifier,
            $this->isFalse(),
        )->willThrowException(Exception\ShortUrlNotFoundException::fromNotFound($identifier));

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString(sprintf('No URL found with short code "%s"', $shortCode), $output);
    }

    #[Test, DataProvider('provideRetryDeleteAnswers')]
    public function deleteIsRetriedWhenThresholdIsReachedAndQuestionIsAccepted(
        array $retryAnswer,
        int $expectedDeleteCalls,
        string $expectedMessage,
    ): void {
        $shortCode = 'abc123';
        $identifier = ShortUrlIdentifier::fromShortCodeAndDomain($shortCode);
        $this->service->expects($this->exactly($expectedDeleteCalls))->method('deleteByShortCode')->with(
            $identifier,
            $this->isBool(),
        )->willReturnCallback(function ($_, bool $ignoreThreshold) use ($shortCode): void {
            if (!$ignoreThreshold) {
                throw Exception\DeleteShortUrlException::fromVisitsThreshold(
                    10,
                    ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
                );
            }
        });
        $this->commandTester->setInputs($retryAnswer);

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString(sprintf(
            'Impossible to delete short URL with short code "%s", since it has more than "10" visits.',
            $shortCode,
        ), $output);
        self::assertStringContainsString($expectedMessage, $output);
    }

    public static function provideRetryDeleteAnswers(): iterable
    {
        yield 'answering yes to retry' => [['yes'], 2, 'Short URL with short code "abc123" successfully deleted.'];
        yield 'answering no to retry' => [['no'], 1, 'Short URL was not deleted.'];
        yield 'answering default to retry' => [[PHP_EOL], 1, 'Short URL was not deleted.'];
    }

    #[Test]
    public function deleteIsNotRetriedWhenThresholdIsReachedAndQuestionIsDeclined(): void
    {
        $shortCode = 'abc123';
        $this->service->expects($this->once())->method('deleteByShortCode')->with(
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
            $this->isFalse(),
        )->willThrowException(Exception\DeleteShortUrlException::fromVisitsThreshold(
            10,
            ShortUrlIdentifier::fromShortCodeAndDomain($shortCode),
        ));
        $this->commandTester->setInputs(['no']);

        $this->commandTester->execute(['shortCode' => $shortCode]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString(sprintf(
            'Impossible to delete short URL with short code "%s", since it has more than "10" visits.',
            $shortCode,
        ), $output);
        self::assertStringContainsString('Short URL was not deleted.', $output);
    }
}
