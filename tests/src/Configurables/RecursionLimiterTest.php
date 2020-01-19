<?php

namespace Erusev\Parsedown\Tests\Configurables;

use Erusev\Parsedown\Configurables\RecursionLimiter;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class RecursionLimiterTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testDepthLimit()
    {
        $State = new State([RecursionLimiter::maxDepth(3)]);

        $Parsedown = new Parsedown($State);

        $borderline = '>>> foo';
        $exceeded = '>>>> foo';
        $exceededByInline = '>>> fo*o*';

        $this->assertSame(
            (
                \str_repeat("<blockquote>\n", 3)
                . '<p>foo</p>'
                . \str_repeat("\n</blockquote>", 3)
            ),
            $Parsedown->toHtml($borderline)
        );

        $this->assertSame(
            (
                \str_repeat("<blockquote>\n", 3)
                . '<p>&gt; foo</p>'
                . \str_repeat("\n</blockquote>", 3)
            ),
            $Parsedown->toHtml($exceeded)
        );

        $this->assertSame(
            (
                \str_repeat("<blockquote>\n", 3)
                . '<p>fo*o*</p>'
                . \str_repeat("\n</blockquote>", 3)
            ),
            $Parsedown->toHtml($exceededByInline)
        );
    }
}
