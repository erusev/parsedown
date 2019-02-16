<?php

namespace Erusev\Parsedown\Tests\Components\Blocks;

use Erusev\Parsedown\Components\Blocks\FencedCode;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\Parsing\Line;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class FencedCodeTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testFencedCodeAPI()
    {
        $FencedCode = FencedCode::build(new Context(new Line('```foo'), ''), new State);
        $FencedCode = $FencedCode->advance(new Context(new Line('bar'), ''), new State);
        $FencedCode = $FencedCode->advance(new Context(new Line('```'), ''), new State);

        $this->assertSame('foo', $FencedCode->infostring());
        $this->assertSame("bar\n", $FencedCode->code());
    }
}
