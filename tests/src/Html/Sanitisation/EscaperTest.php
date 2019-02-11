<?php

namespace Erusev\Parsedown\Tests\Html\Sanitisation;

use Erusev\Parsedown\Html\Sanitisation\Escaper;
use PHPUnit\Framework\TestCase;

final class EscaperTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testHtmlElementValue()
    {
        $this->assertSame(
            Escaper::htmlElementValue('<foo bar="baz" boo=\'bim\'>&'),
            '&lt;foo bar="baz" boo=\'bim\'&gt;&amp;'
        );
    }
}
