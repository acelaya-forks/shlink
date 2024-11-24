<?php

declare(strict_types=1);

namespace ShlinkioApiTest\Shlink\Rest\Fixtures;

use Cake\Chronos\Chronos;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Shlinkio\Shlink\Core\ShortUrl\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\Visitor;

class VisitsFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [ShortUrlsFixture::class];
    }

    public function load(ObjectManager $manager): void
    {
        /** @var ShortUrl $abcShortUrl */
        $abcShortUrl = $this->getReference('abc123_short_url');
        $manager->persist(Visit::forValidShortUrl(
            $abcShortUrl,
            Visitor::fromParams(userAgent: 'shlink-tests-agent', remoteAddress: '44.55.66.77'),
        ));
        $manager->persist(Visit::forValidShortUrl(
            $abcShortUrl,
            Visitor::fromParams('shlink-tests-agent', 'https://google.com', '4.5.6.7'),
        ));
        $manager->persist(Visit::forValidShortUrl(
            $abcShortUrl,
            Visitor::fromParams(userAgent: 'shlink-tests-agent', remoteAddress: '1.2.3.4'),
        ));

        /** @var ShortUrl $defShortUrl */
        $defShortUrl = $this->getReference('def456_short_url');
        $manager->persist(Visit::forValidShortUrl(
            $defShortUrl,
            Visitor::fromParams(userAgent: 'cf-facebook', remoteAddress: '127.0.0.1'),
        ));
        $manager->persist(Visit::forValidShortUrl(
            $defShortUrl,
            Visitor::fromParams('shlink-tests-agent', 'https://app.shlink.io', ''),
        ));

        /** @var ShortUrl $ghiShortUrl */
        $ghiShortUrl = $this->getReference('ghi789_short_url');
        $manager->persist(Visit::forValidShortUrl(
            $ghiShortUrl,
            Visitor::fromParams(userAgent: 'shlink-tests-agent', remoteAddress: '1.2.3.4'),
        ));
        $manager->persist(Visit::forValidShortUrl(
            $ghiShortUrl,
            Visitor::fromParams('shlink-tests-agent', 'https://app.shlink.io', ''),
        ));

        $manager->persist($this->setVisitDate(
            fn () => Visit::forBasePath(Visitor::fromParams('shlink-tests-agent', 'https://s.test', '1.2.3.4')),
            '2020-01-01',
        ));
        $manager->persist($this->setVisitDate(
            fn () => Visit::forRegularNotFound(
                Visitor::fromParams('shlink-tests-agent', 'https://s.test/foo/bar', '1.2.3.4'),
            ),
            '2020-02-01',
        ));
        $manager->persist($this->setVisitDate(
            fn () => Visit::forInvalidShortUrl(
                Visitor::fromParams('cf-facebook', 'https://s.test/foo', '1.2.3.4', 'foo.com'),
            ),
            '2020-03-01',
        ));

        $manager->flush();
    }

    /**
     * @param callable(): Visit $createVisit
     */
    private function setVisitDate(callable $createVisit, string $date): Visit
    {
        Chronos::setTestNow($date);
        $visit = $createVisit();
        Chronos::setTestNow();

        return $visit;
    }
}
