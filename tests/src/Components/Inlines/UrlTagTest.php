<?php

namespace Erusev\Parsedown\Tests\Components\Inlines;

use Erusev\Parsedown\Components\Inlines\UrlTag;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class UrlTagTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testUrlTagAPI()
    {
        $UrlTag = UrlTag::build(new Excerpt('<https://example.com>', 0), new State);

        $this->assertSame('https://example.com', $UrlTag->url());
    }
}
