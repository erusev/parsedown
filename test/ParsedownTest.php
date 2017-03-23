<?php

class ParsedownTest extends \PHPUnit\Framework\TestCase
{
    final function __construct($name = null, array $data = array(), $dataName = '')
    {
        $this->dirs = $this->initDirs();
        $this->Parsedown = $this->initParsedown();

        parent::__construct($name, $data, $dataName);
    }

    private $dirs, $Parsedown;

    /**
     * @return array
     */
    protected function initDirs()
    {
        $dirs []= dirname(__FILE__).'/data/';

        return $dirs;
    }

    /**
     * @return Parsedown
     */
    protected function initParsedown()
    {
        $Parsedown = new Parsedown();

        return $Parsedown;
    }

    /**
     * @dataProvider data
     * @param $test
     * @param $dir
     */
    function test_($test, $dir)
    {
        $markdown = file_get_contents($dir . $test . '.md');

        $expectedMarkup = file_get_contents($dir . $test . '.html');

        $expectedMarkup = str_replace("\r\n", "\n", $expectedMarkup);
        $expectedMarkup = str_replace("\r", "\n", $expectedMarkup);

        $actualMarkup = $this->Parsedown->text($markdown);

        $this->assertEquals($expectedMarkup, $actualMarkup);
    }

    function data()
    {
        $data = array();

        foreach ($this->dirs as $dir)
        {
            $Folder = new DirectoryIterator($dir);

            foreach ($Folder as $File)
            {
                /** @var $File DirectoryIterator */

                if ( ! $File->isFile())
                {
                    continue;
                }

                $filename = $File->getFilename();

                $extension = pathinfo($filename, PATHINFO_EXTENSION);

                if ($extension !== 'md')
                {
                    continue;
                }

                $basename = $File->getBasename('.md');

                if (file_exists($dir . $basename . '.html'))
                {
                    $data []= array($basename, $dir);
                }
            }
        }

        return $data;
    }

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
        $parsedownWithNoMarkup = new Parsedown();
        $parsedownWithNoMarkup->setMarkupEscaped(true);
        $this->assertEquals($expectedHtml, $parsedownWithNoMarkup->text($markdownWithHtml));
    }

    public function testLateStaticBinding()
    {
        include __DIR__ . '/TestParsedown.php';

        $parsedown = Parsedown::instance();
        $this->assertInstanceOf('Parsedown', $parsedown);

        // After instance is already called on Parsedown
        // subsequent calls with the same arguments return the same instance
        $sameParsedown = TestParsedown::instance();
        $this->assertInstanceOf('Parsedown', $sameParsedown);
        $this->assertSame($parsedown, $sameParsedown);

        $testParsedown = TestParsedown::instance('test late static binding');
        $this->assertInstanceOf('TestParsedown', $testParsedown);

        $sameInstanceAgain = TestParsedown::instance('test late static binding');
        $this->assertSame($testParsedown, $sameInstanceAgain);
    }
}
