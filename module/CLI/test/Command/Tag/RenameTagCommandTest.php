<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Tag;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Tag\RenameTagCommand;
use Shlinkio\Shlink\Core\Exception\TagNotFoundException;
use Shlinkio\Shlink\Core\Model\Renaming;
use Shlinkio\Shlink\Core\Tag\Entity\Tag;
use Shlinkio\Shlink\Core\Tag\TagServiceInterface;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Tester\CommandTester;

class RenameTagCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & TagServiceInterface $tagService;

    protected function setUp(): void
    {
        $this->tagService = $this->createMock(TagServiceInterface::class);
        $this->commandTester = CliTestUtils::testerForCommand(new RenameTagCommand($this->tagService));
    }

    #[Test]
    public function errorIsPrintedIfExceptionIsThrown(): void
    {
        $oldName = 'foo';
        $newName = 'bar';
        $this->tagService->expects($this->once())->method('renameTag')->with(
            Renaming::fromNames($oldName, $newName),
        )->willThrowException(TagNotFoundException::fromTag('foo'));

        $this->commandTester->execute([
            'oldName' => $oldName,
            'newName' => $newName,
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Tag with name "foo" could not be found', $output);
    }

    #[Test]
    public function successIsPrintedIfNoErrorOccurs(): void
    {
        $oldName = 'foo';
        $newName = 'bar';
        $this->tagService->expects($this->once())->method('renameTag')->with(
            Renaming::fromNames($oldName, $newName),
        )->willReturn(new Tag($newName));

        $this->commandTester->execute([
            'oldName' => $oldName,
            'newName' => $newName,
        ]);
        $output = $this->commandTester->getDisplay();

        self::assertStringContainsString('Tag properly renamed', $output);
    }
}
