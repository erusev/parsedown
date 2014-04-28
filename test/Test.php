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
}
