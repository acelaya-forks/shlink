<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Domain\Repository;

use Doctrine\Persistence\ObjectRepository;
use Happyr\DoctrineSpecification\Repository\EntitySpecificationRepositoryInterface;
use Shlinkio\Shlink\Core\Domain\Entity\Domain;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

/** @extends ObjectRepository<Domain> */
interface DomainRepositoryInterface extends ObjectRepository, EntitySpecificationRepositoryInterface
{
    /**
     * @return Domain[]
     */
    public function findDomains(?ApiKey $apiKey = null): array;

    public function findOneByAuthority(string $authority, ?ApiKey $apiKey = null): ?Domain;

    public function domainExists(string $authority, ?ApiKey $apiKey = null): bool;
}
