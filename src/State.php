<?php

namespace Erusev\Parsedown;

use Erusev\Parsedown\AST\StateRenderable;
use Erusev\Parsedown\Html\Renderable;

final class State implements StateBearer
{
    /**
     * @var array<class-string<Configurable>, Configurable>
     */
    private $state;

    /**
     * @var array<class-string<Configurable>, Configurable>
     */
    private static $initialCache = [];

    /**
     * @param Configurable[] $Configurables
     */
    public function __construct(array $Configurables = [])
    {
        $this->state = \array_combine(
            \array_map(
                /** @return class-string<Configurable> */
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
     * @template-typeof T $className
     * @param class-string<T> $className
     * @return T
     */
    public function get($className)
    {
        if (
            ! isset($this->state[$className])
            && \is_subclass_of($className, MutableConfigurable::class, true)
        ) {
            if (! isset(self::$initialCache[$className])) {
                /** @var T */
                self::$initialCache[$className] = $className::initial();
            }

            /**
             * @var T
             * @psalm-suppress PossiblyUndefinedMethod
             */
            $this->state[$className] = self::$initialCache[$className]->isolatedCopy();
        }

        /** @var T */
        return (
            $this->state[$className]
            ?? self::$initialCache[$className]
            ?? self::$initialCache[$className] = $className::initial()
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

    public function state(): State
    {
        return $this;
    }

    /** @return self */
    public static function from(StateBearer $StateBearer)
    {
        return $StateBearer->state();
    }

    public function isolatedCopy(): self
    {
        return new self(\array_map(
            function ($C) {
                return $C instanceof MutableConfigurable ? $C->isolatedCopy() : $C;
            },
            $this->state
        ));
    }
}
