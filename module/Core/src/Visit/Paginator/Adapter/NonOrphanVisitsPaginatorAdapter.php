<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Visit\Paginator\Adapter;

use Shlinkio\Shlink\Core\Paginator\Adapter\AbstractCacheableCountPaginatorAdapter;
use Shlinkio\Shlink\Core\Visit\Entity\Visit;
use Shlinkio\Shlink\Core\Visit\Model\VisitsParams;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsCountFiltering;
use Shlinkio\Shlink\Core\Visit\Persistence\VisitsListFiltering;
use Shlinkio\Shlink\Core\Visit\Repository\VisitRepositoryInterface;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

/** @extends AbstractCacheableCountPaginatorAdapter<Visit> */
class NonOrphanVisitsPaginatorAdapter extends AbstractCacheableCountPaginatorAdapter
{
    public function __construct(
        private readonly VisitRepositoryInterface $repo,
        private readonly VisitsParams $params,
        private readonly ?ApiKey $apiKey,
    ) {
    }

    protected function doCount(): int
    {
        return $this->repo->countNonOrphanVisits(new VisitsCountFiltering(
            $this->params->dateRange,
            $this->params->excludeBots,
            $this->apiKey,
        ));
    }

    public function getSlice(int $offset, int $length): iterable
    {
        return $this->repo->findNonOrphanVisits(new VisitsListFiltering(
            $this->params->dateRange,
            $this->params->excludeBots,
            $this->apiKey,
            $length,
            $offset,
        ));
    }
}
