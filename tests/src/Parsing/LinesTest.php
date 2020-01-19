<?php

namespace Erusev\Parsedown\Tests\Parsing;

use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\Parsing\Line;
use Erusev\Parsedown\Parsing\Lines;
use PHPUnit\Framework\TestCase;

final class LinesTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testContainsBlankLines()
    {
        $Lines = Lines::fromTextLines('foo', 0);
        $this->assertFalse($Lines->containsBlankLines());

        $Lines = $Lines->appendingTextLines('bar', 0);
        $this->assertFalse($Lines->containsBlankLines());

        $Lines = $Lines->appendingTextLines("boo\nbaz", 0);
        $this->assertFalse($Lines->containsBlankLines());

        $Lines = $Lines->appendingTextLines("zoo\n\nzoom", 0);
        $this->assertTrue($Lines->containsBlankLines());

        $Lines = Lines::fromTextLines('foo', 0);
        $Lines = $Lines->appendingTextLines('', 0);
        $this->assertTrue($Lines->containsBlankLines());

        $Lines = Lines::fromTextLines('foo', 0);
        $Lines = $Lines->appendingTextLines("\n", 0);
        $this->assertTrue($Lines->containsBlankLines());
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testAppendContext()
    {
        $Lines = Lines::fromTextLines('foo', 0);

        $this->assertFalse($Lines->containsBlankLines());

        $Lines = $Lines->appendingContext(new Context(new Line('bar'), "\n  \n"));

        $this->assertTrue($Lines->containsBlankLines());
        $this->assertSame($Lines->trailingBlankLines(), 0);

        $Lines = $Lines->appendingContext(new Context(new Line('boo'), ''));
        $Lines = $Lines->appendingContext(new Context(new Line('baz'), ''));

        $this->assertTrue($Lines->containsBlankLines());
        $this->assertSame($Lines->trailingBlankLines(), 0);
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testTrailingBlankLines()
    {
        $Lines = Lines::fromTextLines('foo', 0);

        $this->assertSame($Lines->trailingBlankLines(), 0);

        $Lines = $Lines->appendingTextLines("bar\n", 0);

        $this->assertSame($Lines->trailingBlankLines(), 1);

        $Lines = $Lines->appendingTextLines("boo\nbaz\n\n", 0);

        $this->assertSame($Lines->trailingBlankLines(), 2);

        $Lines = $Lines->appendingTextLines("zoo\n\nzoom", 0);

        $this->assertSame($Lines->trailingBlankLines(), 0);
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testAppendNegativeBlankLines()
    {
        $Lines = Lines::fromTextLines('foo', 0);

        $this->assertSame($Lines->trailingBlankLines(), 0);

        $Lines = $Lines->appendingBlankLines(-1);

        $this->assertSame($Lines->trailingBlankLines(), 0);
    }
}
