<?php

namespace Erusev\Parsedown\Tests\Components\Blocks;

use Erusev\Parsedown\Components\Blocks\BlockQuote;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\Parsing\Line;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class BlockQuoteTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testBlockQuoteAPI()
    {
        $BlockQuote = BlockQuote::build(new Context(new Line('> foo'), ''), new State);

        $this->assertSame('foo', $BlockQuote->contents(new State)[0][0]->text());
    }
}
