<?php

namespace Erusev\Parsedown\Tests\Components\Inlines;

use Erusev\Parsedown\Components\Inlines\Emphasis;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class EmphasisTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testEmphasisAPI()
    {
        $Emphasis = Emphasis::build(new Excerpt('*foo*', 0), new State);

        $this->assertSame('foo', $Emphasis->text());

        $Emphasis = Emphasis::build(new Excerpt('**foo**', 0), new State);

        $this->assertSame('foo', $Emphasis->text());
    }
}
