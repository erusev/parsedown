<?php

namespace Erusev\Parsedown\Configurables;

trait BooleanConfigurable
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

    /** @return static */
    public static function enabled()
    {
        return new self(true);
    }

    /** @return static */
    public static function initial()
    {
        return new self(false);
    }
}
