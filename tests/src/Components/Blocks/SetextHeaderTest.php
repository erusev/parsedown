<?php

namespace Erusev\Parsedown\Tests\Components\Blocks;

use Erusev\Parsedown\Components\Blocks\Paragraph;
use Erusev\Parsedown\Components\Blocks\SetextHeader;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\Parsing\Line;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class SetextHeaderTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testSetextHeaderAPI()
    {
        $Paragraph = Paragraph::build(new Context(new Line('foo'), ''), new State);
        $SetextHeader = SetextHeader::build(new Context(new Line('==='), ''), new State, $Paragraph);

        $this->assertSame('foo', $SetextHeader->text());
        $this->assertSame(1, $SetextHeader->level());
    }
}
