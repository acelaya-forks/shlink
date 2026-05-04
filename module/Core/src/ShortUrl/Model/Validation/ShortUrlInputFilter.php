<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\ShortUrl\Model\Validation;

use Laminas\Validator;

use function is_string;
use function preg_match;

use const Shlinkio\Shlink\LOOSE_URI_MATCHER;

/**
 * @deprecated
 */
class ShortUrlInputFilter
{
    /**
     * @todo Extract to its own validator class
     * @deprecated
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
