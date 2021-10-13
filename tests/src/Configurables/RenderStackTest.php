<?php

namespace Erusev\Parsedown\Tests\Configurables;

use Erusev\Parsedown\Configurables\RenderStack;
use Erusev\Parsedown\Html\Renderable;
use Erusev\Parsedown\Html\Renderables\Text;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class RenderStackTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testRenderStack()
    {
        $State = new State;
        $RenderStack = $State->get(RenderStack::class)
            ->push(
                /**
                 * @param Renderable[] $Rs
                 * @param State $_
                 * @return Renderable[]
                 */
                function ($Rs, $_) { return \array_merge($Rs, [new Text('baz')]); }
            )
            ->push(
                /**
                 * @param Renderable[] $Rs
                 * @param State $_
                 * @return Renderable[]
                 */
                function ($Rs, $_) { return \array_merge($Rs, [new Text('bar')]); }
            )
        ;

        $State = $State->setting($RenderStack);

        $this->assertSame(
            "<p>foo</p>\nbar\nbaz",
            (new Parsedown($State))->toHtml('foo')
        );
    }
}
