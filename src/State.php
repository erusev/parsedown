<?php

namespace Erusev\Parsedown;

use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Html\Renderable;

final class State
{
    /**
     * @var array<class-string<Configurable>, Configurable>
     * */
    private $state;

    /**
     * @param Configurable[] $Configurables
     */
    public function __construct(array $Configurables = [])
    {
        $this->state = \array_combine(
            \array_map(
                /** @return class-string */
                function (Configurable $C) { return \get_class($C); },
                $Configurables
            ),
            $Configurables
        );
    }

    /**
     * @return self
     */
    public function setting(Configurable $C)
    {
        return new self([\get_class($C) => $C] + $this->state);
    }

    /**
     * @return self
     */
    public function mergingWith(State $State)
    {
        return new self($State->state + $this->state);
    }

    /**
     * @template T as Configurable
     * @template-typeof T $configurableClass
     * @param class-string<Configurable> $configurableClass
     * @return T|null
     * */
    public function get($configurableClass)
    {
        return (isset($this->state[$configurableClass])
            ? $this->state[$configurableClass]
            : null
        );
    }

    /**
     * @template T as Configurable
     * @template-typeof T $configurableClass
     * @param class-string<Configurable> $configurableClass
     * @return T
     * */
    public function getOrDefault($configurableClass)
    {
        return (isset($this->state[$configurableClass])
            ? $this->state[$configurableClass]
            : $configurableClass::initial()
        );
    }

    public function __clone()
    {
        $this->state = \array_map(
            /** @return Configurable */
            function (Configurable $C) { return clone($C); },
            $this->state
        );
    }

    /**
     * @param StateRenderable[] $StateRenderables
     * @return Renderable[]
     */
    public function applyTo(array $StateRenderables)
    {
        return \array_map(
            /** @return Renderable */
            function (StateRenderable $SR) { return $SR->renderable($this); },
            $StateRenderables
        );
    }
}
