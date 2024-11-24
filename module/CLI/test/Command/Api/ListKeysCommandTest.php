<?php

declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Api;

use Cake\Chronos\Chronos;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shlinkio\Shlink\CLI\Command\Api\ListKeysCommand;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Rest\ApiKey\Model\ApiKeyMeta;
use Shlinkio\Shlink\Rest\ApiKey\Model\RoleDefinition;
use Shlinkio\Shlink\Rest\Entity\ApiKey;
use Shlinkio\Shlink\Rest\Service\ApiKeyServiceInterface;
use ShlinkioTest\Shlink\CLI\Util\CliTestUtils;
use Symfony\Component\Console\Tester\CommandTester;

class ListKeysCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MockObject & ApiKeyServiceInterface $apiKeyService;

    protected function setUp(): void
    {
        $this->apiKeyService = $this->createMock(ApiKeyServiceInterface::class);
        $this->commandTester = CliTestUtils::testerForCommand(new ListKeysCommand($this->apiKeyService));
    }

    #[Test, DataProvider('provideKeysAndOutputs')]
    public function returnsExpectedOutput(array $keys, bool $enabledOnly, string $expected): void
    {
        $this->apiKeyService->expects($this->once())->method('listKeys')->with($enabledOnly)->willReturn($keys);

        $this->commandTester->execute(['--enabled-only' => $enabledOnly]);
        $output = $this->commandTester->getDisplay();

        self::assertEquals($expected, $output);
    }

    public static function provideKeysAndOutputs(): iterable
    {
        $dateInThePast = Chronos::createFromFormat('Y-m-d H:i:s', '2020-01-01 00:00:00');

        yield 'all keys' => [
            [
                $apiKey1 = ApiKey::create()->disable(),
                $apiKey2 = ApiKey::fromMeta(ApiKeyMeta::fromParams(expirationDate: $dateInThePast)),
                $apiKey3 = ApiKey::create(),
            ],
            false,
            <<<OUTPUT
            +--------------------------------------+------------+---------------------------+-------+
            | Name                                 | Is enabled | Expiration date           | Roles |
            +--------------------------------------+------------+---------------------------+-------+
            | {$apiKey1->name} | ---        | -                         | Admin |
            +--------------------------------------+------------+---------------------------+-------+
            | {$apiKey2->name} | ---        | 2020-01-01T00:00:00+00:00 | Admin |
            +--------------------------------------+------------+---------------------------+-------+
            | {$apiKey3->name} | +++        | -                         | Admin |
            +--------------------------------------+------------+---------------------------+-------+

            OUTPUT,
        ];
        yield 'enabled keys' => [
            [$apiKey1 = ApiKey::create()->disable(), $apiKey2 = ApiKey::create()],
            true,
            <<<OUTPUT
            +--------------------------------------+-----------------+-------+
            | Name                                 | Expiration date | Roles |
            +--------------------------------------+-----------------+-------+
            | {$apiKey1->name} | -               | Admin |
            +--------------------------------------+-----------------+-------+
            | {$apiKey2->name} | -               | Admin |
            +--------------------------------------+-----------------+-------+

            OUTPUT,
        ];
        yield 'with roles' => [
            [
                $apiKey1 = ApiKey::create(),
                $apiKey2 = self::apiKeyWithRoles([RoleDefinition::forAuthoredShortUrls()]),
                $apiKey3 = self::apiKeyWithRoles(
                    [RoleDefinition::forDomain(self::domainWithId(Domain::withAuthority('example.com')))],
                ),
                $apiKey4 = ApiKey::create(),
                $apiKey5 = self::apiKeyWithRoles([
                    RoleDefinition::forAuthoredShortUrls(),
                    RoleDefinition::forDomain(self::domainWithId(Domain::withAuthority('example.com'))),
                ]),
                $apiKey6 = ApiKey::create(),
            ],
            true,
            <<<OUTPUT
            +--------------------------------------+-----------------+--------------------------+
            | Name                                 | Expiration date | Roles                    |
            +--------------------------------------+-----------------+--------------------------+
            | {$apiKey1->name} | -               | Admin                    |
            +--------------------------------------+-----------------+--------------------------+
            | {$apiKey2->name} | -               | Author only              |
            +--------------------------------------+-----------------+--------------------------+
            | {$apiKey3->name} | -               | Domain only: example.com |
            +--------------------------------------+-----------------+--------------------------+
            | {$apiKey4->name} | -               | Admin                    |
            +--------------------------------------+-----------------+--------------------------+
            | {$apiKey5->name} | -               | Author only              |
            |                                      |                 | Domain only: example.com |
            +--------------------------------------+-----------------+--------------------------+
            | {$apiKey6->name} | -               | Admin                    |
            +--------------------------------------+-----------------+--------------------------+

            OUTPUT,
        ];
        yield 'with names' => [
            [
                ApiKey::fromMeta(ApiKeyMeta::fromParams(name: 'Alice')),
                ApiKey::fromMeta(ApiKeyMeta::fromParams(name: 'Alice and Bob')),
                $apiKey3 = ApiKey::fromMeta(ApiKeyMeta::fromParams(name: '')),
                $apiKey4 = ApiKey::create(),
            ],
            true,
            <<<OUTPUT
            +--------------------------------------+-----------------+-------+
            | Name                                 | Expiration date | Roles |
            +--------------------------------------+-----------------+-------+
            | Alice                                | -               | Admin |
            +--------------------------------------+-----------------+-------+
            | Alice and Bob                        | -               | Admin |
            +--------------------------------------+-----------------+-------+
            | {$apiKey3->name} | -               | Admin |
            +--------------------------------------+-----------------+-------+
            | {$apiKey4->name} | -               | Admin |
            +--------------------------------------+-----------------+-------+

            OUTPUT,
        ];
    }

    private static function apiKeyWithRoles(array $roles): ApiKey
    {
        $apiKey = ApiKey::create();
        foreach ($roles as $role) {
            $apiKey->registerRole($role);
        }

        return $apiKey;
    }

    private static function domainWithId(Domain $domain): Domain
    {
        $domain->setId('1');
        return $domain;
    }
}
