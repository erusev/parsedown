<?php

namespace Erusev\Parsedown\Tests\Components\Inlines;

use Erusev\Parsedown\Components\Inlines\Url;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testUrlAPI()
    {
        $Url = Url::build(new Excerpt('https://example.com', 0), new State);

        $this->assertSame('https://example.com', $Url->url());
    }
}
