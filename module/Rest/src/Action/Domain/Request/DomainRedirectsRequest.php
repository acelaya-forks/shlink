<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Domain\Request;

use Shlinkio\Shlink\Common\ObjectMapper\HostAndPortConverter;
use Shlinkio\Shlink\Core\Config\NotFoundRedirectConfigInterface;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;

final readonly class DomainRedirectsRequest
{
    public string $authority;

    public function __construct(
        #[HostAndPortConverter]
        string $domain,
        private string|null $baseUrlRedirect = null,
        private bool $baseUrlRedirectWasProvided = false,
        private string|null $regular404Redirect = null,
        private bool $regular404RedirectWasProvided = false,
        private string|null $invalidShortUrlRedirect = null,
        private bool $invalidShortUrlRedirectWasProvided = false,
    ) {
        $this->authority = $domain;
    }

    public function toNotFoundRedirects(NotFoundRedirectConfigInterface|null $defaults = null): NotFoundRedirects
    {
        return NotFoundRedirects::withRedirects(
            $this->baseUrlRedirectWasProvided ? $this->baseUrlRedirect : $defaults?->baseUrlRedirect,
            $this->regular404RedirectWasProvided ? $this->regular404Redirect : $defaults?->regular404Redirect,
            $this->invalidShortUrlRedirectWasProvided
                ? $this->invalidShortUrlRedirect
                : $defaults?->invalidShortUrlRedirect,
        );
    }
}
