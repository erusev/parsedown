<?php

class Test extends PHPUnit_Framework_TestCase
{
	const provider_dir = 'data/';

	/**
	 * @var Parsedown
	 */
	private $Parsedown;

	public function setUp()
	{
		parent::setUp();

		if (version_compare(PHP_VERSION, '5.3.0') >= 0)
		{
			include_once __DIR__.'/../Parsedown-5.3.php';
			$this->Parsedown = Parsedown\Parsedown::instance();
		}
		else
		{
			include_once __DIR__.'/../Parsedown.php';
			$this->Parsedown = Parsedown::instance();
		}
	}

	/**
	 * @dataProvider provider
	 */
	function test_($markdown, $expected_markup)
	{
		$actual_markup = $this->Parsedown->parse($markdown);

		$this->assertEquals($expected_markup, $actual_markup);
	}

	function provider()
	{
		$provider = array();

		$path = dirname(__FILE__).'/';

		$DirectoryIterator = new DirectoryIterator($path . '/' . self::provider_dir);

		foreach ($DirectoryIterator as $Item)
		{
			if ($Item->isFile())
			{
				$filename = $Item->getFilename();

				$extension = pathinfo($filename, PATHINFO_EXTENSION);

				if ($extension !== 'md')
					continue;

				$basename = $Item->getBasename('.md');

				$markdown = file_get_contents($path . '/' . self::provider_dir . $basename . '.md');

				if (!$markdown)
					continue;

				$expected_markup = file_get_contents($path . '/' . self::provider_dir . $basename . '.html');
				$expected_markup = str_replace("\r\n", "\n", $expected_markup);
				$expected_markup = str_replace("\r", "\n", $expected_markup);

				$provider [] = array($markdown, $expected_markup);
			}
		}

		return $provider;
	}
}
