<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Rest\Action\Domain;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shlinkio\Shlink\Core\Config\NotFoundRedirects;
use Shlinkio\Shlink\Core\Config\Options\NotFoundRedirectOptions;
use Shlinkio\Shlink\Core\Domain\DomainServiceInterface;
use Shlinkio\Shlink\Rest\Action\AbstractRestAction;
use Shlinkio\Shlink\Rest\Middleware\AuthenticationMiddleware;

class ListDomainsAction extends AbstractRestAction
{
    protected const string ROUTE_PATH = '/domains';
    protected const array ROUTE_ALLOWED_METHODS = [self::METHOD_GET];

    public function __construct(
        private readonly DomainServiceInterface $domainService,
        private readonly NotFoundRedirectOptions $options,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $apiKey = AuthenticationMiddleware::apiKeyFromRequest($request);
        $domainItems = $this->domainService->listDomains($apiKey);

        return new JsonResponse([
            'domains' => [
                'data' => $domainItems,
                'defaultRedirects' => NotFoundRedirects::fromConfig($this->options),
            ],
        ]);
    }
}
