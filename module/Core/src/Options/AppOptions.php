<?php

declare(strict_types=1);

namespace Shlinkio\Shlink\Core\Options;

use Laminas\Stdlib\AbstractOptions;

use function sprintf;

class AppOptions extends AbstractOptions
{
    private string $name = '';
    private string $version = '1.0';

    public function getName(): string
    {
        return $this->name;
    }

    protected function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    protected function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    /** @deprecated  */
    protected function setDisableTrackParam(?string $disableTrackParam): self
    {
        // Keep just for backwards compatibility during hydration
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s:v%s', $this->name, $this->version);
    }
}
