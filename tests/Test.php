<?php

include 'Parsedown.php';

class Test extends PHPUnit_Framework_TestCase
{
	const provider_dir = 'data/';
	
    /**
     * @dataProvider provider
     */
    public function testAdd($expected, $actual)
    {
		$markup = Parsedown::instance()->parse('sdf');
		
		$this->assertEquals($expected, $actual);
    }
	
    public function provider()
    {
		$provider = array();
		
		$DirectoryIterator = new DirectoryIterator(__DIR__.'/'.self::provider_dir);
		
		foreach ($DirectoryIterator as $Item)
		{
			if ($Item->isFile() and $Item->getExtension() === 'md')
			{
				$basename = $Item->getBasename('.md');
				
				$markdown = file_get_contents(__DIR__.'/'.self::provider_dir.$basename.'.md');
				
				if ( ! $markdown)
					continue;
				
				$expected_markup = file_get_contents(__DIR__.'/'.self::provider_dir.$basename.'.html');
				
				$actual_markup = Parsedown::instance()->parse($markdown);
				
				$provider []= array($expected_markup, $actual_markup);
			}
		}
		
		return $provider;
    }
}

