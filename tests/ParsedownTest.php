<?php

namespace Erusev\Parsedown\Tests;

use Erusev\Parsedown\Components\Blocks\Markup as BlockMarkup;
use Erusev\Parsedown\Components\Inlines\Markup as InlineMarkup;
use Erusev\Parsedown\Configurables\BlockTypes;
use Erusev\Parsedown\Configurables\Breaks;
use Erusev\Parsedown\Configurables\InlineTypes;
use Erusev\Parsedown\Configurables\SafeMode;
use Erusev\Parsedown\Configurables\StrictMode;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

class ParsedownTest extends TestCase
{
    /**
     * @param string|null $name
     * @param array $data
     * @param string $dataName
     */
    final public function __construct($name = null, array $data = [], $dataName = '')
    {
        $this->dirs = $this->initDirs();

        parent::__construct($name, $data, $dataName);
    }

    /** @var string[]  */
    private $dirs;

    /**
     * @return string[]
     */
    protected function initDirs()
    {
        return [\dirname(__FILE__).'/data/'];
    }

    /**
     * @dataProvider data
     * @param string $test
     * @param string $dir
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function test_($test, $dir)
    {
        $markdown = \file_get_contents($dir . $test . '.md');

        $expectedMarkup = \file_get_contents($dir . $test . '.html');

        $expectedMarkup = \str_replace("\r\n", "\n", $expectedMarkup);
        $expectedMarkup = \str_replace("\r", "\n", $expectedMarkup);

        $Parsedown = new Parsedown(new State([
            new SafeMode(\substr($test, 0, 3) === 'xss'),
            new StrictMode(\substr($test, 0, 6) === 'strict'),
            new Breaks(\substr($test, 0, 14) === 'breaks_enabled'),
        ]));

        $actualMarkup = $Parsedown->text($markdown);

        $this->assertEquals($expectedMarkup, $actualMarkup);
    }

    /** @return array<int, array{0:string, 1:string} */
    public function data()
    {
        $data = [];

        foreach ($this->dirs as $dir) {
            $Folder = new \DirectoryIterator($dir);

            foreach ($Folder as $File) {
                /** @var $File DirectoryIterator */

                if (! $File->isFile()) {
                    continue;
                }

                $filename = $File->getFilename();

                $extension = \pathinfo($filename, \PATHINFO_EXTENSION);

                if ($extension !== 'md') {
                    continue;
                }

                $basename = $File->getBasename('.md');

                if (\file_exists($dir . $basename . '.html')) {
                    $data []= [$basename, $dir];
                }
            }
        }

        return $data;
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function test_no_markup()
    {
        $markdownWithHtml = <<<MARKDOWN_WITH_MARKUP
<div>_content_</div>

sparse:

<div>
<div class="inner">
_content_
</div>
</div>

paragraph

<style type="text/css">
    p {
        color: red;
    }
</style>

comment

<!-- html comment -->
MARKDOWN_WITH_MARKUP;

        $expectedHtml = <<<EXPECTED_HTML
<p>&lt;div&gt;<em>content</em>&lt;/div&gt;</p>
<p>sparse:</p>
<p>&lt;div&gt;
&lt;div class=&quot;inner&quot;&gt;
<em>content</em>
&lt;/div&gt;
&lt;/div&gt;</p>
<p>paragraph</p>
<p>&lt;style type=&quot;text/css&quot;&gt;
p {
color: red;
}
&lt;/style&gt;</p>
<p>comment</p>
<p>&lt;!-- html comment --&gt;</p>
EXPECTED_HTML;

        $parsedownWithNoMarkup = new Parsedown(new State([
            BlockTypes::initial()->removing([BlockMarkup::class]),
            InlineTypes::initial()->removing([InlineMarkup::class]),
        ]));

        $this->assertEquals($expectedHtml, $parsedownWithNoMarkup->text($markdownWithHtml));
    }
}
