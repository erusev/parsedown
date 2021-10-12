<?php

namespace Erusev\Parsedown\Html\Renderables;

use Erusev\Parsedown\Html\Renderable;
use Erusev\Parsedown\Html\TransformableRenderable;

final class Container implements TransformableRenderable
{
    use CanonicalStateRenderable;

    /** @var Renderable[] */
    private $Contents;

    /**
     * @param Renderable[] $Contents
     */
    public function __construct($Contents)
    {
        $this->Contents = $Contents;
    }

    /**
     * @return Renderable[]
     */
    public function contents()
    {
        return $this->Contents;
    }


    /** @return string */
    public function getHtml()
    {
        return \array_reduce(
            $this->Contents,
            /**
             * @param string $html
             * @param Renderable $Renderable
             * @return string
             */
            function ($html, Renderable $Renderable) {
                return $html . $Renderable->getHtml();
            },
            ''
        );
    }

    /**
     * @param \Closure(string):Renderable $Transform
     * @return Renderable
     */
    public function transformingContent(\Closure $Transform): Renderable
    {
        return new Container(\array_map(
            function (Renderable $R) use ($Transform): Renderable {
                if (! $R instanceof TransformableRenderable) {
                    return $R;
                }

                return $R->transformingContent($Transform);
            },
            $this->Contents
        ));
    }
}
