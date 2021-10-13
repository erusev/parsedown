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
    public function __construct($Contents = [])
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

    public function adding(Renderable $Renderable): Container
    {
        return new Container(\array_merge($this->Contents, [$Renderable]));
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
     * @param \Closure(string):TransformableRenderable $Transform
     * @return TransformableRenderable
     */
    public function transformingContent(\Closure $Transform): TransformableRenderable
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

    public function replacingAll(string $search, TransformableRenderable $Replacement): TransformableRenderable
    {
        return new Container(\array_map(
            function (Renderable $R) use ($search, $Replacement): Renderable {
                if (! $R instanceof TransformableRenderable) {
                    return $R;
                }

                return $R->replacingAll($search, $Replacement);
            },
            $this->Contents
        ));
    }
}
