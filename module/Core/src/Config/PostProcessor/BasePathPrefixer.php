<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Config\PostProcessor;

use function array_map;

class BasePathPrefixer
{
    private const array ELEMENTS_WITH_PATH = ['routes', 'middleware_pipeline'];

    public function __invoke(array $config): array
    {
        $basePath = $config['router']['base_path'] ?? '';

        foreach (self::ELEMENTS_WITH_PATH as $configKey) {
            $config[$configKey] = $this->prefixPathsWithBasePath($configKey, $config, $basePath);
        }

        return $config;
    }

    private function prefixPathsWithBasePath(string $configKey, array $config, string $basePath): array
    {
        return array_map(function (array $element) use ($basePath) {
            if (! isset($element['path'])) {
                return $element;
            }

            $element['path'] = $basePath . $element['path'];
            return $element;
        }, $config[$configKey] ?? []);
    }
}
