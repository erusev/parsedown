<!DOCTYPE html>

<!-- (c) 2009 - 2013 Emanuil Rusev, All rights reserved. -->

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

	<head>
		
		<meta content="text/html; charset=UTF-8" http-equiv="Content-Type" />
		
		<link href="reset.css" rel="stylesheet" type="text/css" />
		<link href="index.css" rel="stylesheet" type="text/css" />
		
		<title>Parsedown Test</title>
		
	</head>
	
	<body>
		
		<div style="padding: 50px; width: 500px;">
			
			<h1 style="margin: 0;"><a href="/">Parsedown PHP</a> » Tests</h1>
			
			<br/>
			
			<table>

				<tr class="header">
					<th style="width: 480px;">Test</th>
					<th style="text-align: right; width: 120px">Time</th>
				</tr>

				<?php foreach ($Tests as $index => $Test): ?>
				<tr class="<?= $index % 2 ? 'even' : 'odd' ?>">
					<td><a href="/tests/<?= $Test['basename'] ?>"><?= $Test['name'] ?></a> - <span class="<?= $Test['result'] ?>"><?= $Test['result'] ?></span></td>
					<td style="text-align: right;"><?= $Test['time'] ?> ms</td>
				</tr>
				<?php endforeach ?>

			</table>
			
			<div class="<?= $failed_test_count ? 'fail' : 'pass' ?>" style="border-top: 1px solid #555; color: #fff; margin-top: 1px; padding:5px 10px;">
				<?php if ($failed_test_count): ?>
				<?= $failed_test_count ?> tests failed.
				<?php else: ?>
				All <?= count($Tests) ?> tests passed.
				<?php endif ?>
			</div>
		
		</div>
			
	</body>
	
</html>