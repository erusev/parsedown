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
		# removes UTF-8 BOM and marker characters
		$text = preg_replace('{^\xEF\xBB\xBF|\x1A}', '', $text);

		# removes \r characters
		$text = str_replace("\r\n", "\n", $text);
		$text = str_replace("\r", "\n", $text);

		# replaces tabs with spaces
		$text = str_replace("\t", '    ', $text);

		# encodes escape sequences

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

		# decodes escape sequences

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
			#
			# fenced elements

			switch ($element['type'])
			{
				case 'fenced_code_block':

					if ( ! isset($element['closed']))
					{
						if (preg_match('/^[ ]*'.$element['fence'][0].'{3,}[ ]*$/', $line))
						{
							$element['closed'] = true;
						}
						else
						{
							$element['text'] !== '' and $element['text'] .= "\n";

							$element['text'] .= $line;
						}

						continue 2;
					}

					break;

				case 'markup':

					if ( ! isset($element['closed']))
					{
						if (preg_match('{<'.$element['subtype'].'>$}', $line)) # opening tag
						{
							$element['depth']++;
						}

						if (preg_match('{</'.$element['subtype'].'>$}', $line)) # closing tag
						{
							$element['depth'] > 0
								? $element['depth']--
								: $element['closed'] = true;
						}

						$element['text'] .= "\n".$line;

						continue 2;
					}

					break;
			}

			# *

			if ($line === '')
			{
				$element['interrupted'] = true;

				continue;
			}

			#
			# composite elements

			switch ($element['type'])
			{
				case 'blockquote':

					if ( ! isset($element['interrupted']))
					{
						$line = preg_replace('/^[ ]*>[ ]?/', '', $line);

						$element['lines'] []= $line;

						continue 2;
					}

					break;

				case 'li':

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

						continue 2;
					}

					if (isset($element['interrupted']))
					{
						if ($line[0] === ' ')
						{
							$element['lines'] []= '';

							$line = preg_replace('/^[ ]{0,4}/', '', $line);

							$element['lines'] []= $line;

							continue 2;
						}
					}
					else
					{
						$line = preg_replace('/^[ ]{0,4}/', '', $line);

						$element['lines'] []= $line;

						continue 2;
					}

					break;
			}

			#
			# indentation sensitive types

			$deindented_line = $line;

			switch ($line[0])
			{
				case ' ':

					# ~

					$deindented_line = ltrim($line);

					if ($deindented_line === '')
					{
						continue 2;
					}

					# code block

					if (preg_match('/^[ ]{4}(.*)/', $line, $matches))
					{
						if ($element['type'] === 'code_block')
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
								'type' => 'code_block',
								'text' => $matches[1],
							);
						}

						continue 2;
					}

					break;

				case '#':

					# atx heading (#)

					if (preg_match('/^(#{1,6})[ ]*(.+?)[ ]*#*$/', $line, $matches))
					{
						$elements []= $element;

						$level = strlen($matches[1]);

						$element = array(
							'type' => 'h.',
							'text' => $matches[2],
							'level' => $level,
						);

						continue 2;
					}

					break;

				case '-':

					# setext heading (---)

					if ($line[0] === '-' and $element['type'] === 'p' and ! isset($element['interrupted']) and preg_match('/^[-]+[ ]*$/', $line))
					{
						$element['type'] = 'h.';
						$element['level'] = 2;

						continue 2;
					}

					break;

				case '=':

					# setext heading (===)

					if ($line[0] === '=' and $element['type'] === 'p' and ! isset($element['interrupted']) and preg_match('/^[=]+[ ]*$/', $line))
					{
						$element['type'] = 'h.';
						$element['level'] = 1;

						continue 2;
					}

					break;
			}

			#
			# indentation insensitive types

			switch ($deindented_line[0])
			{
				case '<':

					# self-closing tag

					if (preg_match('{^<.+?/>$}', $deindented_line))
					{
						$elements []= $element;

						$element = array(
							'type' => '',
							'text' => $deindented_line,
						);

						continue 2;
					}

					# opening tag

					if (preg_match('{^<(\w+)(?:[ ].*?)?>}', $deindented_line, $matches))
					{
						$elements []= $element;

						$element = array(
							'type' => 'markup',
							'subtype' => strtolower($matches[1]),
							'text' => $deindented_line,
							'depth' => 0,
						);

						preg_match('{</'.$matches[1].'>\s*$}', $deindented_line) and $element['closed'] = true;

						continue 2;
					}

					break;

				case '>':

					# quote

					if (preg_match('/^>[ ]?(.*)/', $deindented_line, $matches))
					{
						$elements []= $element;

						$element = array(
							'type' => 'blockquote',
							'lines' => array(
								$matches[1],
							),
						);

						continue 2;
					}

					break;

				case '[':

					# reference

					if (preg_match('/^\[(.+?)\]:[ ]*([^ ]+)/', $deindented_line, $matches))
					{
						$label = strtolower($matches[1]);

						$this->reference_map[$label] = trim($matches[2], '<>');;

						continue 2;
					}

					break;

				case '`':
				case '~':

					# fenced code block

					if (preg_match('/^([`]{3,}|[~]{3,})[ ]*(\S+)?[ ]*$/', $deindented_line, $matches))
					{
						$elements []= $element;

						$element = array(
							'type' => 'fenced_code_block',
							'text' => '',
							'fence' => $matches[1],
						);

						isset($matches[2]) and $element['language'] = $matches[2];

						continue 2;
					}

					break;

				case '*':
				case '+':
				case '-':
				case '_':

					# hr

					if (preg_match('/^([-*_])([ ]{0,2}\1){2,}[ ]*$/', $deindented_line))
					{
						$elements []= $element;

						$element = array(
							'type' => 'hr',
						);

						continue 2;
					}

					# li

					if (preg_match('/^([ ]*)[*+-][ ](.*)/', $line, $matches))
					{
						$elements []= $element;

						$element = array(
							'type' => 'li',
							'ordered' => false,
							'indentation' => $matches[1],
							'last' => true,
							'lines' => array(
								preg_replace('/^[ ]{0,4}/', '', $matches[2]),
							),
						);

						continue 2;
					}
			}

			# li

			if ($deindented_line[0] <= '9' and $deindented_line >= '0' and preg_match('/^([ ]*)\d+[.][ ](.*)/', $line, $matches))
			{
				$elements []= $element;

				$element = array(
					'type' => 'li',
					'ordered' => true,
					'indentation' => $matches[1],
					'last' => true,
					'lines' => array(
						preg_replace('/^[ ]{0,4}/', '', $matches[2]),
					),
				);

				continue;
			}

			# paragraph

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

		unset($elements[0]);

		#
		# ~
		#

		$markup = '';

		foreach ($elements as $element)
		{
			switch ($element['type'])
			{
				case 'p':

					$text = $this->parse_span_elements($element['text']);

					$text = preg_replace('/[ ]{2}\n/', '<br />'."\n", $text);

					if ($context === 'li' and $markup === '')
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

				case 'blockquote':

					$text = $this->parse_block_elements($element['lines']);

					$markup .= '<blockquote>'."\n".$text.'</blockquote>'."\n";

					break;

				case 'code_block':
				case 'fenced_code_block':

					$text = htmlspecialchars($element['text'], ENT_NOQUOTES, 'UTF-8');

					strpos($text, "\x1A\\") !== FALSE and $text = strtr($text, $this->escape_sequence_map);

					$markup .= '<pre><code>'.$text.'</code></pre>'."\n";

					break;

				case 'h.':

					$text = $this->parse_span_elements($element['text']);

					$markup .= '<h'.$element['level'].'>'.$text.'</h'.$element['level'].'>'."\n";

					break;

				case 'hr':

					$markup .= '<hr />'."\n";

					break;

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

				default:

					$markup .= $element['text']."\n";
			}
		}

		return $markup;
	}

	private function parse_span_elements($text)
	{
		$map = array();

		$index = 0;

		# code span

		if (strpos($text, '`') !== FALSE and preg_match_all('/`(.+?)`/', $text, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $matches)
			{
				$element_text = $matches[1];
				$element_text = htmlspecialchars($element_text, ENT_NOQUOTES, 'UTF-8');

				# decodes escape sequences

				$this->escape_sequence_map
					and strpos($element_text, "\x1A") !== FALSE
					and $element_text = strtr($element_text, $this->escape_sequence_map);

				# composes element

				$element = '<code>'.$element_text.'</code>';

				# encodes element

				$code = "\x1A".'$'.$index;

				$text = str_replace($matches[0], $code, $text);

				$map[$code] = $element;

				$index ++;
			}
		}

		# inline link or image

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
					$element_text = $this->parse_span_elements($matches[3]);

					$element = '<a href="'.$url.'">'.$element_text.'</a>';
				}

				# ~

				$code = "\x1A".'$'.$index;

				$text = str_replace($matches[0], $code, $text);

				$map[$code] = $element;

				$index ++;
			}
		}

		# reference link or image

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
						$element_text = $this->parse_span_elements($matches[2]);

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

		if (strpos($text, '://') !== FALSE)
		{
			switch (TRUE)
			{
				case preg_match_all('{<(https?:[/]{2}[^\s]+)>}i', $text, $matches, PREG_SET_ORDER):
				case preg_match_all('{\b(https?:[/]{2}[^\s]+)\b}i', $text, $matches, PREG_SET_ORDER):

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

					break;
			}
		}

		# ~

		strpos($text, '&') !== FALSE and $text = preg_replace('/&(?!#?\w+;)/', '&amp;', $text);
		strpos($text, '<') !== FALSE and $text = preg_replace('/<(?!\/?\w.*?>)/', '&lt;', $text);

		# ~

		if (strpos($text, '~~') !== FALSE)
		{
			$text = preg_replace('/~~(?=\S)(.+?)(?<=\S)~~/s', '<del>$1</del>', $text);
		}

		if (strpos($text, '_') !== FALSE)
		{
			$text = preg_replace('/__(?=\S)([^_]+?)(?<=\S)__/s', '<strong>$1</strong>', $text, -1, $count);
			$count or $text = preg_replace('/__(?=\S)(.+?)(?<=\S)__(?!_)/s', '<strong>$1</strong>', $text);

			$text = preg_replace('/\b_(?=\S)(.+?)(?<=\S)_\b/s', '<em>$1</em>', $text);
		}

		if (strpos($text, '*') !== FALSE)
		{
			$text = preg_replace('/\*\*(?=\S)([^*]+?)(?<=\S)\*\*/s', '<strong>$1</strong>', $text, -1, $count);
			$count or $text = preg_replace('/\*\*(?=\S)(.+?)(?<=\S)\*\*(?!\*)/s', '<strong>$1</strong>', $text);

			$text = preg_replace('/\*(?=\S)([^*]+?)(?<=\S)\*/s', '<em>$1</em>', $text, -1, $count);
			$count or $text = preg_replace('/\*(?=\S)(.+?)(?<=\S)\*(?!\*)/s', '<em>$1</em>', $text);
		}

		$text = strtr($text, $map);

		return $text;
	}
}
