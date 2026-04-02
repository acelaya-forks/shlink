<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Domain\Request;

use Shlinkio\Shlink\Core\Config\NotFoundRedirectConfigInterface;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;
use Shlinkio\Shlink\Core\Domain\Validation\DomainRedirectsInputFilter;
use Shlinkio\Shlink\Core\Exception\ValidationException;

use function array_key_exists;

final readonly class DomainRedirectsRequest
{
    private function __construct(
        public string $authority,
        private string|null $baseUrlRedirect = null,
        private bool $baseUrlRedirectWasProvided = false,
        private string|null $regular404Redirect = null,
        private bool $regular404RedirectWasProvided = false,
        private string|null $invalidShortUrlRedirect = null,
        private bool $invalidShortUrlRedirectWasProvided = false,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public static function fromRawData(array $payload): self
    {
        $inputFilter = DomainRedirectsInputFilter::withData($payload);
        if (! $inputFilter->isValid()) {
            throw ValidationException::fromInputFilter($inputFilter);
        }

        return new self(
            authority: $inputFilter->getValue(DomainRedirectsInputFilter::DOMAIN),
            baseUrlRedirect: $inputFilter->getValue(DomainRedirectsInputFilter::BASE_URL_REDIRECT),
            baseUrlRedirectWasProvided: array_key_exists(
                DomainRedirectsInputFilter::BASE_URL_REDIRECT,
                $payload,
            ),
            regular404Redirect: $inputFilter->getValue(DomainRedirectsInputFilter::REGULAR_404_REDIRECT),
            regular404RedirectWasProvided: array_key_exists(
                DomainRedirectsInputFilter::REGULAR_404_REDIRECT,
                $payload,
            ),
            invalidShortUrlRedirect: $inputFilter->getValue(DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT),
            invalidShortUrlRedirectWasProvided: array_key_exists(
                DomainRedirectsInputFilter::INVALID_SHORT_URL_REDIRECT,
                $payload,
            ),
        );
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
