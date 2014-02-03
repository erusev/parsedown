<?php

include 'Parsedown.php';

class Test extends PHPUnit_Framework_TestCase
{
	const provider_dir = 'data/';

	/**
	 * @dataProvider provider
	 */
	function test_($filename)
	{
		$path = $this->get_data_path();
		$markdown = file_get_contents($path . $filename . '.md');
		$expected_markup = file_get_contents($path . $filename . '.html');
		$expected_markup = str_replace("\r\n", "\n", $expected_markup);
		$expected_markup = str_replace("\r", "\n", $expected_markup);

		$actual_markup = Parsedown::instance()->parse($markdown);

		$this->assertEquals($expected_markup, $actual_markup);
	}

	function provider()
	{
		$provider = array();

		$path = $this->get_data_path();
		$DirectoryIterator = new DirectoryIterator($path);

		foreach ($DirectoryIterator as $Item)
		{
			if ($Item->isFile())
			{
				$filename = $Item->getFilename();

				$extension = pathinfo($filename, PATHINFO_EXTENSION);

				if ($extension !== 'md')
					continue;

				$basename = $Item->getBasename('.md');
				if (file_exists($path.$basename.'.html')) {
					$provider [] = array($basename);
				}
			}
		}

		return $provider;
	}

	function get_data_path()
	{
		return dirname(__FILE__).'/'.self::provider_dir;
	}
}