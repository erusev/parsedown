<!DOCTYPE html>

<!-- (c) 2009 - 2013 Emanuil Rusev, All rights reserved. -->

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

	<head>
		
		<meta content="text/html; charset=UTF-8" http-equiv="Content-Type" />
		
		<link href="reset.css" rel="stylesheet" type="text/css" />
		<link href="test.css" rel="stylesheet" type="text/css" />
		
		<script src="https://cdnjs.cloudflare.com/ajax/libs/prettify/r224/prettify.js" type="text/javascript"></script>
		
		<title><?= $name ?> &laquo; Parsedown Test</title>
		
	</head>
	
	<body onload="prettyPrint();">
		
		<table style="width: 100%; height: 100%;">
			<tr class="<?= $result ?>">
				<td colspan="3"></td>
			</tr>
			<tr class="header">
				<td colspan="2"><a href="/">Parsedown PHP</a> » <a href=".">Tests</a> » <?= $name ?></td>
				<td style="text-align: right;">
					<form action="http://parsedown.org<?= $_SERVER['SERVER_NAME'] === 'parsedown.org.local' ? '.local' : '' ?>/explorer/" method="post">
						<input type="hidden" name="text" value="<?= $md ?>"/>
						<button type="submit">Open in Explorer</button>
					</form>
				</td>
			</tr>
			<tr class="body" style="height: 100%; vertical-align: top;">
				<td style="background: #eee; width: 30%;">
					<pre id="md" style="word-wrap: break-word;"><?= $md ?></pre>
				</td>
				<td><pre class="prettyprint"><code><?= $expected_mu ?></code></pre></td>
				<td><pre class="prettyprint"><code><?= $actual_mu ?></code></pre></td>
			</tr>
			<tr class="footer">
				<td style="background: #eee;">Markdown</td>
				<td>Expected Markup</td>
				<td>Actual Markup</td>
			</tr>
		</table>
		
	</body>
	
</html>