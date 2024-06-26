<?php

namespace Shlinkio\Shlink\Core\Util;

use Fig\Http\Message\RequestMethodInterface;
use Mezzio\Router\Route;

use function Shlinkio\Shlink\Core\ArrayUtils\contains;

enum RedirectStatus: int
{
    case STATUS_301 = 301; // StatusCodeInterface::STATUS_MOVED_PERMANENTLY;
    case STATUS_302 = 302; // StatusCodeInterface::STATUS_FOUND;
    case STATUS_307 = 307; // StatusCodeInterface::STATUS_TEMPORARY_REDIRECT;
    case STATUS_308 = 308; // StatusCodeInterface::STATUS_PERMANENT_REDIRECT;

    public function allowsCache(): bool
    {
        return contains($this, [self::STATUS_301, self::STATUS_308]);
    }

    /**
     * @return array<RequestMethodInterface::METHOD_*>|Route::HTTP_METHOD_ANY
     */
    public function allowedHttpMethods(): array|null
    {
        return contains($this, [self::STATUS_301, self::STATUS_302])
            ? [RequestMethodInterface::METHOD_GET]
            : Route::HTTP_METHOD_ANY;
    }
}
