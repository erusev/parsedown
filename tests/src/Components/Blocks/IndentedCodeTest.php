<?php

namespace Erusev\Parsedown\Tests\Components\Blocks;

use Erusev\Parsedown\Components\Blocks\IndentedCode;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\Parsing\Line;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class IndentedCodeTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testIndentedCodeAPI()
    {
        $IndentedCode = IndentedCode::build(new Context(new Line('    foo'), ''), new State);

        $this->assertSame("foo\n", $IndentedCode->code());
    }
}
