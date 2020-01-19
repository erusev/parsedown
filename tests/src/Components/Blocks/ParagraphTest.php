<?php

namespace Erusev\Parsedown\Tests\Components\Blocks;

use Erusev\Parsedown\Components\Blocks\Paragraph;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\Parsing\Line;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class ParagraphTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testParagraphAPI()
    {
        $Paragraph = Paragraph::build(new Context(new Line('foo'), ''), new State);

        $this->assertSame('foo', $Paragraph->text());
    }
}
