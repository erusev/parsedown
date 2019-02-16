<?php

namespace Erusev\Parsedown\Tests\Components\Inlines;

use Erusev\Parsedown\Components\Inlines\Strikethrough;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class StrikethroughTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testStrikethroughAPI()
    {
        $Strikethrough = Strikethrough::build(new Excerpt('~~foo~~', 0), new State);

        $this->assertSame('foo', $Strikethrough->text());
    }
}
