<?php 
				
$DirectoryIterator = new DirectoryIterator($dir);

$failed_test_count = 0;

foreach ($DirectoryIterator as $Item) 
{
	if ($Item->isFile() and $Item->getBasename() != '.DS_Store')
	{
		if ($Item->getExtension() === 'md')
		{
			$basename = $Item->getBasename('.md');

			$markdown = file_get_contents($dir.$basename.'.md');
			$expected_markup = file_get_contents($dir.$basename.'.html');

			if ( ! $markdown)
				continue;
			
			$Parsedown = Parsedown::instance();

			$start = microtime(true);

			$actual_markup = $Parsedown->parse($markdown);

			$time = microtime(true) - $start;
			$time = $time * 1000; # ms?
			$time = round($time, 2);

			$result = $expected_markup === $actual_markup
				? 'pass'
				: 'fail';

			$result === 'fail' and $failed_test_count ++;
			
			$Tests []= array(
				'basename' => $basename,
				'name' => str_replace('_', ' ', $basename),
				'result' => $result,
				'time' => $time,
			);
		}
	}
}

