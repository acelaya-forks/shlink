<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\CLI\Command\ShortUrl\Input;

use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlEdition;
use Symfony\Component\Console\Attribute\Option;

use function array_unique;

/**
 * Common input used for short URL creation and edition
 */
final class ShortUrlDataInput
{
    /** @var string[]|null */
    #[Option('Tags to apply to the short URL', name: 'tag', shortcut: 't')]
    public array|null $tags = null;

    #[Option(
        'The date from which this short URL will be valid. '
        . 'If someone tries to access it before this date, it will not be found',
        shortcut: 's',
    )]
    public string|null $validSince = null;

    #[Option(
        'The date until which this short URL will be valid. '
        . 'If someone tries to access it after this date, it will not be found',
        shortcut: 'u',
    )]
    public string|null $validUntil = null;

    #[Option('This will limit the number of visits for this short URL', shortcut: 'm')]
    public int|null $maxVisits = null;

    #[Option('A descriptive title for the short URL')]
    public string|null $title = null;

    #[Option('Tells if this short URL will be included as "Allow" in Shlink\'s robots.txt', shortcut: 'r')]
    public bool|null $crawlable = null;

    #[Option(
        'Disables the forwarding of the query string to the long URL, when the short URL is visited',
        shortcut: 'w',
    )]
    public bool|null $noForwardQuery = null;

    public function toArray(): array
    {
        $data = [];

        // Avoid setting arguments that were not explicitly provided.
        // This is important when editing short URLs and should not make a difference when creating.
        if ($this->validSince !== null) {
            $data['validSince'] = $this->validSince;
        }
        if ($this->validUntil !== null) {
            $data['validUntil'] = $this->validUntil;
        }
        if ($this->maxVisits !== null) {
            $data['maxVisits'] = $this->maxVisits;
        }
        if ($this->tags !== null) {
            $data['tags'] = array_unique($this->tags);
        }
        if ($this->title !== null) {
            $data['title'] = $this->title;
        }
        if ($this->crawlable !== null) {
            $data['crawlable'] = $this->crawlable;
        }
        if ($this->noForwardQuery !== null) {
            $data['forwardQuery'] = !$this->noForwardQuery;
        }

        return $data;
    }

    public function toShortUrlEdition(string|null $longUrl): ShortUrlEdition
    {
        return new ShortUrlEdition(
            longUrlWasProvided: $longUrl !== null,
            longUrl: $longUrl,
            validSinceWasProvided: $this->validSince !== null,
            validSince: $this->validSince,
            validUntilWasProvided: $this->validUntil !== null,
            validUntil: $this->validUntil,
            maxVisitsWasProvided: $this->maxVisits !== null,
            maxVisits: $this->maxVisits,
            tagsWereProvided: $this->tags !== null,
            tags: $this->tags ?? [],
            titleWasProvided: $this->title !== null,
            title: $this->title,
            crawlableWasProvided: $this->crawlable !== null,
            crawlable: $this->crawlable ?? false,
            forwardQueryWasProvided: $this->noForwardQuery !== null,
            forwardQuery: $this->noForwardQuery !== null && ! $this->noForwardQuery,
        );
    }
}
