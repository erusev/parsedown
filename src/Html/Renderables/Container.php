<?php

namespace Erusev\Parsedown\Html\Renderables;

use Erusev\Parsedown\Html\Renderable;

final class Container implements Renderable
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
}
