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

    /** @return bool */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /** @return self */
    public static function enabled()
    {
        return new self(true);
    }

    /** @return self */
    public static function initial()
    {
        return new self(false);
    }
}
