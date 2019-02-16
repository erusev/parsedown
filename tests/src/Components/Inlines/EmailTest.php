<?php

namespace Erusev\Parsedown\Tests\Components\Inlines;

use Erusev\Parsedown\Components\Inlines\Email;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testEmailAPI()
    {
        $Email = Email::build(new Excerpt('<foo@bar.com>', 0), new State);

        $this->assertSame('foo@bar.com', $Email->text());
        $this->assertSame('mailto:foo@bar.com', $Email->url());

        $Email = Email::build(new Excerpt('<mailto:foo@bar.com>', 0), new State);

        $this->assertSame('mailto:foo@bar.com', $Email->text());
        $this->assertSame('mailto:foo@bar.com', $Email->url());
    }
}
