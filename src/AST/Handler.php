<?php

namespace Erusev\Parsedown\AST;

use Erusev\Parsedown\Html\Renderable;
use Erusev\Parsedown\State;

/**
 * @template T as Renderable
 */
final class Handler implements StateRenderable
{
    /** @var callable(State):T */
    private $closure;

    /**
     * @param callable(State):T $closure
     */
    public function __construct(callable $closure)
    {
        $this->closure = $closure;
    }

    /**
     * @param State $State
     * @return T&Renderable
     */
    public function renderable(State $State)
    {
        $closure = $this->closure;

        return $closure($State);
    }
}
