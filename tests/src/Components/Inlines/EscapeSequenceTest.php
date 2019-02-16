<?php

namespace Erusev\Parsedown\Tests\Components\Inlines;

use Erusev\Parsedown\Components\Inlines\EscapeSequence;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class EscapeSequenceTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testEscapeSequenceAPI()
    {
        $EscapeSequence = EscapeSequence::build(new Excerpt('\`', 0), new State);

        $this->assertSame('`', $EscapeSequence->char());
    }
}
