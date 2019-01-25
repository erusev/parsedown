<?php

namespace Erusev\Parsedown\Tests;

use Erusev\Parsedown\Configurables\Breaks;
use Erusev\Parsedown\Configurables\SafeMode;
use Erusev\Parsedown\Configurables\StrictMode;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

class ParsedownTest extends TestCase
{
    final public function __construct($name = null, array $data = [], $dataName = '')
    {
        $this->dirs = $this->initDirs();

        parent::__construct($name, $data, $dataName);
    }

    private $dirs;
    protected $Parsedown;

    /**
     * @return array
     */
    protected function initDirs()
    {
        $dirs []= \dirname(__FILE__).'/data/';

        return $dirs;
    }

    /**
     * @dataProvider data
     * @param $test
     * @param $dir
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

//     public function test_no_markup()
//     {
//         $markdownWithHtml = <<<MARKDOWN_WITH_MARKUP
// <div>_content_</div>

// sparse:

// <div>
// <div class="inner">
// _content_
// </div>
// </div>

// paragraph

// <style type="text/css">
//     p {
//         color: red;
//     }
// </style>

// comment

// <!-- html comment -->
// MARKDOWN_WITH_MARKUP;

//         $expectedHtml = <<<EXPECTED_HTML
// <p>&lt;div&gt;<em>content</em>&lt;/div&gt;</p>
// <p>sparse:</p>
// <p>&lt;div&gt;
// &lt;div class="inner"&gt;
// <em>content</em>
// &lt;/div&gt;
// &lt;/div&gt;</p>
// <p>paragraph</p>
// <p>&lt;style type="text/css"&gt;
// p {
// color: red;
// }
// &lt;/style&gt;</p>
// <p>comment</p>
// <p>&lt;!-- html comment --&gt;</p>
// EXPECTED_HTML;

//         $parsedownWithNoMarkup = new TestParsedown();
//         $parsedownWithNoMarkup->setMarkupEscaped(true);
//         $this->assertEquals($expectedHtml, $parsedownWithNoMarkup->text($markdownWithHtml));
//     }
}
