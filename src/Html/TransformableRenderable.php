<?php

namespace Erusev\Parsedown\Html;

interface TransformableRenderable extends Renderable
{
    /**
     * Takes a closure $Transform which will provide a transformation of
     * a "contained text" into Renderables.
     *
     * In order for TransformableRenderable to make sense, a Renderable must
     * have:
     *   1. Some concept of "contained text". $Transform can be applied
     *      piece-wise if your container contains logically disjoint sections
     *      of text.
     *   2. A generic mechanism for containing other Renderables, or replacing
     *      the current renderable with a container.
     *
     * It is acceptable to only partially transform "contained text".
     *
     * @param \Closure(string):Renderable $Transform
     * @return Renderable
     */
    public function transformingContent(\Closure $Transform): Renderable;
}
