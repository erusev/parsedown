<?php

namespace Erusev\Parsedown\Tests\Components\Inlines;

use Erusev\Parsedown\Components\Inlines\Link;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class LinkTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testLinkAPI()
    {
        $Link = Link::build(new Excerpt('[foo](https://example.com)', 0), new State);

        $this->assertSame('foo', $Link->label());
        $this->assertSame('https://example.com', $Link->url());
        $this->assertSame(null, $Link->title());

        $Link = Link::build(new Excerpt('[foo](https://example.com "bar")', 0), new State);

        $this->assertSame('foo', $Link->label());
        $this->assertSame('https://example.com', $Link->url());
        $this->assertSame("bar", $Link->title());
    }
}
