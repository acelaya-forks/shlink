<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model\Validation;

use DateTimeInterface;
use Laminas\Filter;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator;
use Shlinkio\Shlink\Common\Validation\InputFactory;
use Shlinkio\Shlink\Rest\Entity\ApiKey;

use function is_string;
use function preg_match;
use function substr;

use const Shlinkio\Shlink\LOOSE_URI_MATCHER;

/**
 * @extends InputFilter<mixed>
 * @deprecated
 */
class ShortUrlInputFilter extends InputFilter
{
    // Fields for creation only
    public const string SHORT_CODE_LENGTH = 'shortCodeLength';
    public const string DOMAIN = 'domain';

    // Fields for creation and edition
    public const string LONG_URL = 'longUrl';
    public const string VALID_SINCE = 'validSince';
    public const string VALID_UNTIL = 'validUntil';
    public const string MAX_VISITS = 'maxVisits';
    public const string TITLE = 'title';
    public const string TAGS = 'tags';
    public const string CRAWLABLE = 'crawlable';
    public const string FORWARD_QUERY = 'forwardQuery';
    public const string API_KEY = 'apiKey';

    public static function forEdition(array $data): self
    {
        $instance = new self();
        $instance->initializeForEdition();
        $instance->setData($data);

        return $instance;
    }

    private function initializeForEdition(bool $requireLongUrl = false): void
    {
        $longUrlInput = InputFactory::basic(self::LONG_URL, required: $requireLongUrl);
        $longUrlInput->getValidatorChain()->merge(self::longUrlValidators(allowNull: ! $requireLongUrl));
        $this->add($longUrlInput);

        $validSince = InputFactory::basic(self::VALID_SINCE);
        $validSince->getValidatorChain()->attach(new Validator\Date(['format' => DateTimeInterface::ATOM]));
        $this->add($validSince);

        $validUntil = InputFactory::basic(self::VALID_UNTIL);
        $validUntil->getValidatorChain()->attach(new Validator\Date(['format' => DateTimeInterface::ATOM]));
        $this->add($validUntil);

        $this->add(InputFactory::numeric(self::MAX_VISITS));

        $title = InputFactory::basic(self::TITLE);
        $title->getFilterChain()->attach(new Filter\Callback(
            static fn (string|null $value) => $value === null ? $value : substr($value, 0, 512),
        ));
        $this->add($title);

        $this->add(InputFactory::tags(self::TAGS));
        $this->add(InputFactory::boolean(self::CRAWLABLE));

        // This cannot be defined as a boolean inputs, because it can actually have 3 values: true, false and null.
        // Defining them as boolean will make null fall back to false, which is not the desired behavior.
        $this->add(InputFactory::basic(self::FORWARD_QUERY));

        $apiKeyInput = InputFactory::basic(self::API_KEY);
        $apiKeyInput->getValidatorChain()->attach(new Validator\IsInstanceOf(['className' => ApiKey::class]));
        $this->add($apiKeyInput);
    }

    /**
     * @todo Extract to its own validator class
     */
    public static function longUrlValidators(bool $allowNull = false): Validator\ValidatorChain
    {
        $emptyModifiers = [
            Validator\NotEmpty::OBJECT,
            Validator\NotEmpty::SPACE,
            Validator\NotEmpty::EMPTY_ARRAY,
            Validator\NotEmpty::BOOLEAN,
            Validator\NotEmpty::STRING,
        ];
        if (! $allowNull) {
            $emptyModifiers[] = Validator\NotEmpty::NULL;
        }

        return (new Validator\ValidatorChain())
            ->attach(new Validator\NotEmpty($emptyModifiers))
            ->attach(new Validator\Callback(
                // Non-strings is always allowed. Other validators will take care of those
                static fn (mixed $value) => ! is_string($value) || preg_match(LOOSE_URI_MATCHER, $value) === 1,
            ));
    }
}
