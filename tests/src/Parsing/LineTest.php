<?php

namespace Erusev\Parsedown\Tests\Parsing;

use Erusev\Parsedown\Parsing\Line;
use PHPUnit\Framework\TestCase;

final class LineTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testLTrimmingBody()
    {
        $Line = new Line("  \t \t  \t   \tfoo", 1);

        // ltrim only acts on the indent string
        $this->assertSame('foo', $Line->ltrimBodyUpto(100));


        $this->assertSame(" \t \t  \t   \tfoo", $Line->ltrimBodyUpto(1));
        $this->assertSame("\t \t  \t   \tfoo", $Line->ltrimBodyUpto(2));
        $this->assertSame(" \t  \t   \tfoo", $Line->ltrimBodyUpto(3));
        $this->assertSame("\t  \t   \tfoo", $Line->ltrimBodyUpto(4));
        $this->assertSame("    \t   \tfoo", $Line->ltrimBodyUpto(5));
        $this->assertSame("   \t   \tfoo", $Line->ltrimBodyUpto(6));
        $this->assertSame("  \t   \tfoo", $Line->ltrimBodyUpto(7));
        $this->assertSame(" \t   \tfoo", $Line->ltrimBodyUpto(8));
        $this->assertSame("\t   \tfoo", $Line->ltrimBodyUpto(9));
        $this->assertSame("    \tfoo", $Line->ltrimBodyUpto(10));
        $this->assertSame("   \tfoo", $Line->ltrimBodyUpto(11));
        $this->assertSame("  \tfoo", $Line->ltrimBodyUpto(12));
        $this->assertSame(" \tfoo", $Line->ltrimBodyUpto(13));
        $this->assertSame("\tfoo", $Line->ltrimBodyUpto(14));
        $this->assertSame('foo', $Line->ltrimBodyUpto(15));
        $this->assertSame('foo', $Line->ltrimBodyUpto(16));
    }
}
