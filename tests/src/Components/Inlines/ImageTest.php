<?php

namespace Erusev\Parsedown\Tests\Components\Inlines;

use Erusev\Parsedown\Components\Inlines\Image;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class ImageTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testImageAPI()
    {
        $Image = Image::build(new Excerpt('![foo](https://example.com)', 0), new State);

        $this->assertSame('foo', $Image->label());
        $this->assertSame('https://example.com', $Image->url());
        $this->assertSame(null, $Image->title());

        $Image = Image::build(new Excerpt('![foo](https://example.com "bar")', 0), new State);

        $this->assertSame('foo', $Image->label());
        $this->assertSame('https://example.com', $Image->url());
        $this->assertSame("bar", $Image->title());
    }
}
