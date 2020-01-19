<?php

namespace Erusev\Parsedown\Configurables;

use Erusev\Parsedown\Configurable;

final class RecursionLimiter implements Configurable
{
    /** @var int */
    private $maxDepth;

    /** @var int */
    private $currentDepth;

    /**
     * @param int $maxDepth
     * @param int $currentDepth
     */
    private function __construct($maxDepth, $currentDepth)
    {
        $this->maxDepth = $maxDepth;
        $this->currentDepth = $currentDepth;
    }

    /** @return self */
    public static function initial()
    {
        return self::maxDepth(256);
    }

    /**
     * @param int $maxDepth
     * @return self
     */
    public static function maxDepth($maxDepth)
    {
        return new self($maxDepth, 0);
    }

    /** @return self */
    public function incremented()
    {
        return new self($this->maxDepth, $this->currentDepth + 1);
    }

    /** @return bool */
    public function isDepthExceeded()
    {
        return ($this->maxDepth < $this->currentDepth);
    }
}
