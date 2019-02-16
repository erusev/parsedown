<?php

namespace Erusev\Parsedown\Tests\Components\Blocks;

use Erusev\Parsedown\Components\Blocks\Paragraph;
use Erusev\Parsedown\Components\Blocks\Table;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\Parsing\Line;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class TableTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testTableAPI()
    {
        $Paragraph = Paragraph::build(new Context(new Line('foo | bar'), ''), new State);
        $Table = Table::build(new Context(new Line('--- | :---'), ''), new State, $Paragraph);
        $Table = $Table->advance(new Context(new Line('baz | boo'), ''), new State);

        $this->assertSame('foo', $Table->headerRow(new State)[0][0]->text());
        $this->assertSame('bar', $Table->headerRow(new State)[1][0]->text());

        $this->assertSame('baz', $Table->rows(new State)[0][0][0]->text());
        $this->assertSame('boo', $Table->rows(new State)[0][1][0]->text());

        $this->assertSame(null, $Table->alignments()[0]);
        $this->assertSame('left', $Table->alignments()[1]);
    }
}
