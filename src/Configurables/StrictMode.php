<?php

namespace Erusev\Parsedown\Configurables;

use Erusev\Parsedown\Configurable;

final class StrictMode implements Configurable
{
    /** @var bool */
    private $enabled = false;

    /**
     * @param bool $enabled
     */
    public function __construct($enabled)
    {
        $this->enabled = $enabled;
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public static function initial(): self
    {
        return new self(false);
    }
}
