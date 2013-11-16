<?php

#
#
# Parsedown
# http://parsedown.org
#
# (c) Emanuil Rusev
# http://erusev.com
#
# For the full license information, please view the LICENSE file that was
# distributed with this source code.
#
#

class Parsedown
{
	#
	# Multiton (http://en.wikipedia.org/wiki/Multiton_pattern)
	#

	static function instance($name = 'default')
	{
		if (isset(self::$instances[$name]))
			return self::$instances[$name];

		$instance = new Parsedown();

		self::$instances[$name] = $instance;

		return $instance;
	}

	private static $instances = array();

	#
	# Fields
	#

	private $reference_map = array();
	private $escape_sequence_map = array();

	#
	# Public Methods
	#

	function parse($text)
	{
		# Removes UTF-8 BOM and marker characters.
		$text = preg_replace('{^\xEF\xBB\xBF|\x1A}', '', $text);

		# Removes \r characters.
		$text = str_replace("\r\n", "\n", $text);
		$text = str_replace("\r", "\n", $text);

		# Replaces tabs with spaces.
		$text = str_replace("\t", '    ', $text);

		# Encodes escape sequences.

		if (strpos($text, '\\') !== FALSE)
		{
			$escape_sequences = array('\\\\', '\`', '\*', '\_', '\{', '\}', '\[', '\]', '\(', '\)', '\>', '\#', '\+', '\-', '\.', '\!');

			foreach ($escape_sequences as $index => $escape_sequence)
			{
				if (strpos($text, $escape_sequence) !== FALSE)
				{
					$code = "\x1A".'\\'.$index.';';

					$text = str_replace($escape_sequence, $code, $text);

					$this->escape_sequence_map[$code] = $escape_sequence;
				}
			}
		}

		# ~

		$text = preg_replace('/\n\s*\n/', "\n\n", $text);
		$text = trim($text, "\n");

		$lines = explode("\n", $text);

		$text = $this->parse_block_elements($lines);

		# Decodes escape sequences (leaves out backslashes).

		foreach ($this->escape_sequence_map as $code => $escape_sequence)
		{
			$text = str_replace($code, $escape_sequence[1], $text);
		}

		$text = rtrim($text, "\n");

		return $text;
	}

	#
	# Private Methods
	#

	private function parse_block_elements(array $lines, $context = '')
	{
		$elements = array();

		$element = array(
			'type' => '',
		);

		foreach ($lines as $line)
		{
			# Block-Level HTML

			if ($element['type'] === 'block' and ! isset($element['closed']))
			{
				if (preg_match('{<'.$element['subtype'].'>$}', $line)) # <open>
				{
					$element['depth']++;
				}

				if (preg_match('{</'.$element['subtype'].'>$}', $line)) # </close>
				{
					$element['depth'] > 0
						? $element['depth']--
						: $element['closed'] = true;
				}

				$element['text'] .= "\n".$line;

				continue;
			}

			# Empty

			if ($line === '')
			{
				$element['interrupted'] = true;

				continue;
			}

			# Lazy Blockquote

			if ($element['type'] === 'blockquote' and ! isset($element['interrupted']))
			{
				$line = preg_replace('/^[ ]*>[ ]?/', '', $line);

				$element['lines'] []= $line;

				continue;
			}

			# Lazy List Item

			if ($element['type'] === 'li')
			{
				if (preg_match('/^([ ]{0,3})(\d+[.]|[*+-])[ ](.*)/', $line, $matches))
				{
					if ($element['indentation'] !== $matches[1])
					{
						$element['lines'] []= $line;
					}
					else
					{
						unset($element['last']);

						$elements []= $element;

						$element = array(
							'type' => 'li',
							'indentation' => $matches[1],
							'last' => true,
							'lines' => array(
								preg_replace('/^[ ]{0,4}/', '', $matches[3]),
							),
						);
					}

					continue;
				}

				if (isset($element['interrupted']))
				{
					if ($line[0] === ' ')
					{
						$element['lines'] []= '';

						$line = preg_replace('/^[ ]{0,4}/', '', $line);;

						$element['lines'] []= $line;

						continue;
					}
				}
				else
				{
					$line = preg_replace('/^[ ]{0,4}/', '', $line);;

					$element['lines'] []= $line;

					continue;
				}
			}

			# Quick Paragraph

			if ($line[0] >= 'a' or $line[0] >= 'A' and $line[0] <= 'Z')
			{
				goto paragraph;
			}

			# Code Block

			if ($line[0] === ' ' and preg_match('/^[ ]{4}(.*)/', $line, $matches))
			{
				if (trim($line) === '')
				{
					continue;
				}

				if ($element['type'] === 'code')
				{
					if (isset($element['interrupted']))
					{
						$element['text'] .= "\n";

						unset ($element['interrupted']);
					}

					$element['text'] .= "\n".$matches[1];
				}
				else
				{
					$elements []= $element;

					$element = array(
						'type' => 'code',
						'text' => $matches[1],
					);
				}

				continue;
			}

			# Setext Header (---)

			if ($line[0] === '-' and $element['type'] === 'p' and ! isset($element['interrupted']) and preg_match('/^[-]+[ ]*$/', $line))
			{
				$element['type'] = 'h.';
				$element['level'] = 2;

				continue;
			}

			# Atx Header (#)

			if ($line[0] === '#' and preg_match('/^(#{1,6})[ ]*(.+?)[ ]*#*$/', $line, $matches))
			{
				$elements []= $element;

				$level = strlen($matches[1]);

				$element = array(
					'type' => 'h.',
					'text' => $matches[2],
					'level' => $level,
				);

				continue;
			}

			# Setext Header (===)

			if ($line[0] === '=' and $element['type'] === 'p' and ! isset($element['interrupted']) and preg_match('/^[=]+[ ]*$/', $line))
			{
				$element['type'] = 'h.';
				$element['level'] = 1;

				continue;
			}

			# ~

			$pure_line = $line[0] !== ' ' ? $line : ltrim($line);

			if ($pure_line === '')
			{
				continue;
			}

			# Link Reference

			if ($pure_line[0] === '[' and preg_match('/^\[(.+?)\]:[ ]*([^ ]+)/', $pure_line, $matches))
			{
				$label = strtolower($matches[1]);
				$url = trim($matches[2], '<>');

				$this->reference_map[$label] = $url;

				continue;
			}

			# Blockquote

			if ($pure_line[0] === '>' and preg_match('/^>[ ]?(.*)/', $pure_line, $matches))
			{
				if ($element['type'] === 'blockquote')
				{
					if (isset($element['interrupted']))
					{
						$element['lines'] []= '';

						unset($element['interrupted']);
					}

					$element['lines'] []= $matches[1];
				}
				else
				{
					$elements []= $element;

					$element = array(
						'type' => 'blockquote',
						'lines' => array(
							$matches[1],
						),
					);
				}

				continue;
			}

			# HTML

			if ($pure_line[0] === '<')
			{
				# Block-Level HTML <self-closing/>

				if (preg_match('{^<.+?/>$}', $pure_line))
				{
					$elements []= $element;

					$element = array(
						'type' => '',
						'text' => $pure_line,
					);

					continue;
				}

				# Block-Level HTML <open>

				if (preg_match('{^<(\w+)(?:[ ].*?)?>}', $pure_line, $matches))
				{
					$elements []= $element;

					$element = array(
						'type' => 'block',
						'subtype' => strtolower($matches[1]),
						'text' => $pure_line,
						'depth' => 0,
					);

					preg_match('{</'.$matches[1].'>\s*$}', $pure_line) and $element['closed'] = true;

					continue;
				}
			}

			# Horizontal Rule

			if (preg_match('/^([-*_])([ ]{0,2}\1){2,}[ ]*$/', $pure_line))
			{
				$elements []= $element;

				$element = array(
					'type' => 'hr',
				);

				continue;
			}

			# List Item

			if (preg_match('/^([ ]*)(\d+[.]|[*+-])[ ](.*)/', $line, $matches))
			{
				$elements []= $element;

				$element = array(
					'type' => 'li',
					'ordered' => isset($matches[2][1]),
					'indentation' => $matches[1],
					'last' => true,
					'lines' => array(
						preg_replace('/^[ ]{0,4}/', '', $matches[3]),
					),
				);

				continue;
			}

			# ~

			paragraph:

			if ($element['type'] === 'p')
			{
				if (isset($element['interrupted']))
				{
					$elements []= $element;

					$element['text'] = $line;

					unset($element['interrupted']);
				}
				else
				{
					$element['text'] .= "\n".$line;
				}
			}
			else
			{
				$elements []= $element;

				$element = array(
					'type' => 'p',
					'text' => $line,
				);
			}
		}

		$elements []= $element;

		array_shift($elements);

		#
		# ~
		#

		$markup = '';

		foreach ($elements as $index => $element)
		{
			switch ($element['type'])
			{
				case 'li':

					if (isset($element['ordered'])) # first
					{
						$list_type = $element['ordered'] ? 'ol' : 'ul';

						$markup .= '<'.$list_type.'>'."\n";
					}

					if (isset($element['interrupted']) and ! isset($element['last']))
					{
						$element['lines'] []= '';
					}

					$text = $this->parse_block_elements($element['lines'], 'li');

					$markup .= '<li>'.$text.'</li>'."\n";

					isset($element['last']) and $markup .= '</'.$list_type.'>'."\n";

					break;

				case 'p':

					$text = $this->parse_inline_elements($element['text']);

					$text = preg_replace('/[ ]{2}\n/', '<br />'."\n", $text);

					if ($context === 'li' and $index === 0)
					{
						if (isset($element['interrupted']))
						{
							$markup .= "\n".'<p>'.$text.'</p>'."\n";
						}
						else
						{
							$markup .= $text;
						}
					}
					else
					{
						$markup .= '<p>'.$text.'</p>'."\n";
					}

					break;

				case 'code':

					$text = htmlentities($element['text'], ENT_NOQUOTES);

					strpos($text, "\x1A\\") !== FALSE and $text = strtr($text, $this->escape_sequence_map);

					$markup .= '<pre><code>'.$text.'</code></pre>'."\n";

					break;

				case 'blockquote':

					$text = $this->parse_block_elements($element['lines']);

					$markup .= '<blockquote>'."\n".$text.'</blockquote>'."\n";

					break;

				case 'h.':

					$text = $this->parse_inline_elements($element['text']);

					$markup .= '<h'.$element['level'].'>'.$text.'</h'.$element['level'].'>'."\n";

					break;

				case 'hr':

					$markup .= '<hr />'."\n";

					break;

				default:

					$markup .= $element['text']."\n";
			}
		}

		return $markup;
	}

	private function parse_inline_elements($text)
	{
		$map = array();

		$index = 0;

		# Code Span

		if (strpos($text, '`') !== FALSE and preg_match_all('/`(.+?)`/', $text, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $matches)
			{
				$element_text = $matches[1];
				$element_text = htmlentities($element_text, ENT_NOQUOTES);

				# Decodes escape sequences.

				$this->escape_sequence_map
					and strpos($element_text, "\x1A") !== FALSE
					and $element_text = strtr($element_text, $this->escape_sequence_map);

				# Composes element.

				$element = '<code>'.$element_text.'</code>';

				# Encodes element.

				$code = "\x1A".'$'.$index;

				$text = str_replace($matches[0], $code, $text);

				$map[$code] = $element;

				$index ++;
			}
		}

		# Inline Link / Image

		if (strpos($text, '](') !== FALSE and preg_match_all('/(!?)(\[((?:[^\[\]]|(?2))*)\])\((.*?)\)/', $text, $matches, PREG_SET_ORDER)) # inline
		{
			foreach ($matches as $matches)
			{
				$url = $matches[4];

				strpos($url, '&') !== FALSE and $url = preg_replace('/&(?!#?\w+;)/', '&amp;', $url);

				if ($matches[1]) # image
				{
					$element = '<img alt="'.$matches[3].'" src="'.$url.'">';
				}
				else
				{
					$element_text = $this->parse_inline_elements($matches[3]);

					$element = '<a href="'.$url.'">'.$element_text.'</a>';
				}

				# ~

				$code = "\x1A".'$'.$index;

				$text = str_replace($matches[0], $code, $text);

				$map[$code] = $element;

				$index ++;
			}
		}

		# Reference(d) Link / Image

		if ($this->reference_map and strpos($text, '[') !== FALSE and preg_match_all('/(!?)\[(.+?)\](?:\n?[ ]?\[(.*?)\])?/ms', $text, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $matches)
			{
				$link_definition = isset($matches[3]) && $matches[3]
					? $matches[3]
					: $matches[2]; # implicit

				$link_definition = strtolower($link_definition);

				if (isset($this->reference_map[$link_definition]))
				{
					$url = $this->reference_map[$link_definition];

					strpos($url, '&') !== FALSE and $url = preg_replace('/&(?!#?\w+;)/', '&amp;', $url);

					if ($matches[1]) # image
					{
						$element = '<img alt="'.$matches[2].'" src="'.$url.'">';
					}
					else # anchor
					{
						$element_text = $this->parse_inline_elements($matches[2]);

						$element = '<a href="'.$url.'">'.$element_text.'</a>';
					}

					# ~

					$code = "\x1A".'$'.$index;

					$text = str_replace($matches[0], $code, $text);

					$map[$code] = $element;

					$index ++;
				}
			}
		}

		# Automatic Links

		if (strpos($text, '<') !== FALSE and preg_match_all('/<((https?|ftp|dict):[^\^\s]+?)>/i', $text, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $matches)
			{
				$url = $matches[1];

				strpos($url, '&') !== FALSE and $url = preg_replace('/&(?!#?\w+;)/', '&amp;', $url);

				$element = '<a href=":href">:text</a>';
				$element = str_replace(':text', $url, $element);
				$element = str_replace(':href', $url, $element);

				# ~

				$code = "\x1A".'$'.$index;

				$text = str_replace($matches[0], $code, $text);

				$map[$code] = $element;

				$index ++;
			}
		}

		# ~

		strpos($text, '&') !== FALSE and $text = preg_replace('/&(?!#?\w+;)/', '&amp;', $text);
		strpos($text, '<') !== FALSE and $text = preg_replace('/<(?!\/?\w.*?>)/', '&lt;', $text);

		# ~

		if (strpos($text, '_') !== FALSE)
		{
			$text = preg_replace('/__(?=\S)(.+?)(?<=\S)__(?!_)/s', '<strong>$1</strong>', $text);
			$text = preg_replace('/_(?=\S)(.+?)(?<=\S)_/s', '<em>$1</em>', $text);
		}

		if (strpos($text, '*') !== FALSE)
		{
			$text = preg_replace('/\*\*(?=\S)(.+?)(?<=\S)\*\*(?!\*)/s', '<strong>$1</strong>', $text);
			$text = preg_replace('/\*(?=\S)(.+?)(?<=\S)\*/s', '<em>$1</em>', $text);
		}

		$text = strtr($text, $map);

		return $text;
	}
}