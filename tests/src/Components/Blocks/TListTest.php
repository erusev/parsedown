<?php

namespace Erusev\Parsedown\Tests\Components\Blocks;

use Erusev\Parsedown\Components\Blocks\TList;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\Parsing\Line;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class TListTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testTListAPI()
    {
        $List = TList::build(new Context(new Line('* foo'), ''), new State);

        $this->assertSame('foo', $List->items(new State)[0][0][0]->text());
        $this->assertSame('ul', $List->type());
        $this->assertSame(null, $List->listStart());

        $List = TList::build(new Context(new Line('00100.  foo'), ''), new State);

        $this->assertSame('foo', $List->items(new State)[0][0][0]->text());
        $this->assertSame('ol', $List->type());
        $this->assertSame(100, $List->listStart());
    }
}
