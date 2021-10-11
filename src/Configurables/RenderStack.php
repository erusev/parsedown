<?php

namespace Erusev\Parsedown\Configurables;

use Erusev\Parsedown\Configurable;
use Erusev\Parsedown\Html\Renderable;

final class RenderStack implements Configurable
{
    /** @var list<\Closure(Renderable[]):Renderable[]> */
    private $stack;

    /**
     * @param list<\Closure(Renderable[]):Renderable[]> $stack
     */
    private function __construct($stack = [])
    {
        $this->stack = $stack;
    }

    /** @return self */
    public static function initial()
    {
        return new self;
    }

    /**
     * @param \Closure(Renderable[]):Renderable[] $RenderMap
     * @return self
     */
    public function push(\Closure $RenderMap): self
    {
        return new self(\array_merge($this->stack, [$RenderMap]));
    }

    /** @return list<\Closure(Renderable[]):Renderable[]> */
    public function getStack(): array
    {
        return $this->stack;
    }
}
