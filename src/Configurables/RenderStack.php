<?php

namespace Erusev\Parsedown\Configurables;

use Erusev\Parsedown\Configurable;
use Erusev\Parsedown\Html\Renderable;
use Erusev\Parsedown\State;

final class RenderStack implements Configurable
{
    /** @var list<\Closure(Renderable[],State):Renderable[]> */
    private $stack;

    /**
     * @param list<\Closure(Renderable[],State):Renderable[]> $stack
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
     * @param \Closure(Renderable[],State):Renderable[] $RenderMap
     * @return self
     */
    public function push(\Closure $RenderMap): self
    {
        return new self(\array_merge($this->stack, [$RenderMap]));
    }

    /** @return list<\Closure(Renderable[],State):Renderable[]> */
    public function getStack(): array
    {
        return $this->stack;
    }
}
