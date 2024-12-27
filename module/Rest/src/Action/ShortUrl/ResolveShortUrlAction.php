<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\ShortUrl;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Shlinkio\Shlink\Core\ShortUrl\Model\ShortUrlIdentifier;
use Shlinkio\Shlink\Core\ShortUrl\ShortUrlResolverInterface;
use Shlinkio\Shlink\Core\ShortUrl\Transformer\ShortUrlDataTransformerInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class ResolveShortUrlAction extends AbstractRestAction
{
    protected const string ROUTE_PATH = '/short-urls/{shortCode}';
    protected const array ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    public function __construct(
        private readonly ShortUrlResolverInterface $urlResolver,
        private readonly ShortUrlDataTransformerInterface $transformer,
    ) {
    }

    public function handle(Request $request): Response
    {
        $url = $this->urlResolver->resolveShortUrl(
            ShortUrlIdentifier::fromApiRequest($request),
            AuthenticationMiddleware::apiKeyFromRequest($request),
        );

        return new JsonResponse($this->transformer->transform($url));
    }
}
