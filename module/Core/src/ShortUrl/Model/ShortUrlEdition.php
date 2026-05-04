<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model;

use Cake\Chronos\Chronos;
use DateTimeInterface;
use Shlinkio\Shlink\Common\ObjectMapper\LooseUriConverter;
use Shlinkio\Shlink\Common\ObjectMapper\SubstringConverter;
use Shlinkio\Shlink\Common\ObjectMapper\TagsConverter;
use Shlinkio\Shlink\Core\ShortUrl\Helper\TitleResolutionModelInterface;

use function Shlinkio\Shlink\Common\normalizeOptionalDate;

final readonly class ShortUrlEdition implements TitleResolutionModelInterface
{
    public Chronos|null $validSince;
    public Chronos|null $validUntil;

    /**
     * @param string[] $tags
     */
    public function __construct(
        private bool $longUrlWasProvided = false,
        #[LooseUriConverter]
        public string|null $longUrl = null,
        public bool $validSinceWasProvided = false,
        DateTimeInterface|string|null $validSince = null,
        public bool $validUntilWasProvided = false,
        DateTimeInterface|string|null $validUntil = null,
        public bool $maxVisitsWasProvided = false,
        public int|null $maxVisits = null,
        public bool $tagsWereProvided = false,
        #[TagsConverter]
        public array $tags = [],
        public bool $titleWasProvided = false,
        #[SubstringConverter(512)]
        public string|null $title = null,
        public bool $titleWasAutoResolved = false,
        public bool $crawlableWasProvided = false,
        public bool $crawlable = false,
        public bool $forwardQueryWasProvided = false,
        public bool $forwardQuery = true,
    ) {
        $this->validSince = normalizeOptionalDate($validSince);
        $this->validUntil = normalizeOptionalDate($validUntil);
    }

    public function withResolvedTitle(string $title): static
    {
        // TODO Use clone with once PHP 8.4 is no longer supported
        // return clone($this, [
        //     'title' => $title,
        //     'titleWasAutoResolved' => true,
        // ]);

        return new self(
            longUrlWasProvided: $this->longUrlWasProvided,
            longUrl: $this->longUrl,
            validSinceWasProvided: $this->validSinceWasProvided,
            validSince: $this->validSince,
            validUntilWasProvided: $this->validUntilWasProvided,
            validUntil: $this->validUntil,
            maxVisitsWasProvided: $this->maxVisitsWasProvided,
            maxVisits: $this->maxVisits,
            tagsWereProvided: $this->tagsWereProvided,
            tags: $this->tags,
            titleWasProvided: $this->titleWasProvided,
            title: $title,
            titleWasAutoResolved: true,
            crawlableWasProvided: $this->crawlableWasProvided,
            crawlable: $this->crawlable,
            forwardQueryWasProvided: $this->forwardQueryWasProvided,
            forwardQuery: $this->forwardQuery,
        );
    }

    public function longUrlWasProvided(): bool
    {
        return $this->longUrlWasProvided && $this->longUrl !== null;
    }

    public function hasTitle(): bool
    {
        return $this->titleWasProvided;
    }
}
