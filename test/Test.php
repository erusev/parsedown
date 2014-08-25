<?php

class Test extends PHPUnit_Framework_TestCase
{
    private $safeModeAltResults = array(
        "automatic_link",
        "block-level_html",
        "code_block",
        "email",
        "escaping",
        "fenced_code_block",
        "html_entity",
        "html_simple",
        "image_title",
        "implicit_reference",
        "inline_link_title",
        "inline_title",
        "nested_block-level_html",
        "reference_title",
        "span-level_html",
        "special_characters",
        "strikethrough",
        "tab-indented_code_block"
    );
    
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
        if (strpos($filename, "_escaped"))
        {
            $this->markTestSkipped(
                'Escaped tests are for safe mode only.'
            );
        }
        
        $markdown = file_get_contents($this->dataDir . $filename . '.md');

        $expectedMarkup = file_get_contents($this->dataDir . $filename . '.html');

        $expectedMarkup = str_replace("\r\n", "\n", $expectedMarkup);
        $expectedMarkup = str_replace("\r", "\n", $expectedMarkup);

        $actualMarkup = Parsedown::instance()->text($markdown);

        $this->assertEquals($expectedMarkup, $actualMarkup);
    }
    
    /**
     * @dataProvider data
     */
    function testSafeMode($filename) {
        
        $markdown = file_get_contents($this->dataDir . $filename . '.md');

        if (in_array($filename, $this->safeModeAltResults))
            $expectedMarkup = file_get_contents($this->dataDir . $filename . '_escaped.html');
        else
            $expectedMarkup = file_get_contents($this->dataDir . $filename . '.html');

        $expectedMarkup = str_replace("\r\n", "\n", $expectedMarkup);
        $expectedMarkup = str_replace("\r", "\n", $expectedMarkup);
        
        $actualMarkup = Parsedown::instance()->setSafeMode(true)->text($markdown);

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
}
