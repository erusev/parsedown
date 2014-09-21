<?php

class Test extends PHPUnit_Framework_TestCase
{
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        $this->dataDir = dirname(__FILE__).'/data/';

        parent::__construct($name, $data, $dataName);
    }

    private $dataDir;

    /**
     * @dataProvider data
     */
    function test_($filename)
    {
        $markdown = file_get_contents($this->dataDir . $filename . '.md');

        $expectedMarkup = file_get_contents($this->dataDir . $filename . '.html');

        $expectedMarkup = str_replace("\r\n", "\n", $expectedMarkup);
        $expectedMarkup = str_replace("\r", "\n", $expectedMarkup);

        $actualMarkup = Parsedown::instance()->text($markdown);

        $this->assertEquals($expectedMarkup, $actualMarkup);
    }

    function data()
    {
        $data = array();

        $Folder = new DirectoryIterator($this->dataDir);

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

            if (file_exists($this->dataDir . $basename . '.html'))
            {
                $data []= array($basename);
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
MARKDOWN_WITH_MARKUP;

        $expectedHtml = <<<EXPECTED_HTML
<p>&lt;div><em>content</em>&lt;/div></p>
<p>sparse:</p>
<p>&lt;div>
&lt;div class="inner">
<em>content</em>
&lt;/div>
&lt;/div></p>
<p>paragraph</p>
<p>&lt;style type="text/css"></p>
<pre><code>p {
    color: red;
}</code></pre>
<p>&lt;/style></p>
EXPECTED_HTML;
        $parsedownWithNoMarkup = new Parsedown();
        $parsedownWithNoMarkup->setNoMarkup(true);
        $this->assertEquals($expectedHtml, $parsedownWithNoMarkup->text($markdownWithHtml));
    }
}
