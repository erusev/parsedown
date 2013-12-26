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
	# Setters
	#

	private $break_marker = "  \n";

	function set_breaks_enabled($breaks_enabled)
	{
		$this->break_marker = $breaks_enabled ? "\n" : "  \n";

		return $this;
	}

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

		# ~

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

							unset($element['interrupted']);

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

					if (preg_match('/^\[(.+?)\]:[ ]*(.+?)(?:[ ]+[\'"](.+?)[\'"])?[ ]*$/', $deindented_line, $matches))
					{
						$label = strtolower($matches[1]);

						$this->reference_map[$label] = array(
							'»' => trim($matches[2], '<>'),
						);

						if (isset($matches[3]))
						{
							$this->reference_map[$label]['#'] = $matches[3];
						}

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

					$markup .= isset($element['language'])
						? '<pre><code class="language-'.$element['language'].'">'.$text.'</code></pre>'
						: '<pre><code>'.$text.'</code></pre>';

					$markup .= "\n";

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

				case 'markup':

					$markup .= $this->parse_span_elements($element['text'])."\n";

					break;

				default:

					$markup .= $element['text']."\n";
			}
		}

		return $markup;
	}

	# ~

	private $strong_regex = array(
		'*' => '/^[*]{2}([^*]+?)[*]{2}(?![*])/s',
		'_' => '/^__([^_]+?)__(?!_)/s',
	);

	private $em_regex = array(
		'*' => '/^[*]([^*]+?)[*](?![*])/s',
		'_' => '/^_([^_]+?)[_](?![_])\b/s',
	);

	private $strong_em_regex = array(
		'*' => '/^[*]{2}(.*?)[*](.+?)[*](.*?)[*]{2}/s',
		'_' => '/^__(.*?)_(.+?)_(.*?)__/s',
	);

	private $em_strong_regex = array(
		'*' => '/^[*](.*?)[*]{2}(.+?)[*]{2}(.*?)[*]/s',
		'_' => '/^_(.*?)__(.+?)__(.*?)_/s',
	);

	private function parse_span_elements($text, $markers = array('![', '&', '*', '<', '[', '_', '`', 'http', '~~'))
	{
		if (isset($text[2]) === false or $markers === array())
		{
			return $text;
		}

		# ~

		$markup = '';

		while ($markers)
		{
			$closest_marker = null;
			$closest_marker_index = 0;
			$closest_marker_position = null;

			foreach ($markers as $index => $marker)
			{
				$marker_position = strpos($text, $marker);

				if ($marker_position === false)
				{
					unset($markers[$index]);

					continue;
				}

				if ($closest_marker === null or $marker_position < $closest_marker_position)
				{
					$closest_marker = $marker;
					$closest_marker_index = $index;
					$closest_marker_position = $marker_position;
				}
			}

			# ~

			if ($closest_marker === null or isset($text[$closest_marker_position + 2]) === false)
			{
				$markup .= $text;

				break;
			}
			else
			{
				$markup .= substr($text, 0, $closest_marker_position);
			}

			$text = substr($text, $closest_marker_position);

			# ~

			unset($markers[$closest_marker_index]);

			# ~

			switch ($closest_marker)
			{
				case '![':
				case '[':

					if (strpos($text, ']') and preg_match('/\[((?:[^][]|(?R))*)\]/', $text, $matches))
					{
						$element = array(
							'!' => $text[0] === '!',
							'a' => $matches[1],
						);

						$offset = strlen($matches[0]);

						$element['!'] and $offset++;

						$remaining_text = substr($text, $offset);

						if ($remaining_text[0] === '(' and preg_match('/\([ ]*(.*?)(?:[ ]+[\'"](.+?)[\'"])?[ ]*\)/', $remaining_text, $matches))
						{
							$element['»'] = $matches[1];

							if (isset($matches[2]))
							{
								$element['#'] = $matches[2];
							}

							$offset += strlen($matches[0]);
						}
						elseif ($this->reference_map)
						{
							$reference = $element['a'];

							if (preg_match('/^\s*\[(.*?)\]/', $remaining_text, $matches))
							{
								$reference = $matches[1] ? $matches[1] : $element['a'];

								$offset += strlen($matches[0]);
							}

							$reference = strtolower($reference);

							if (isset($this->reference_map[$reference]))
							{
								$element['»'] = $this->reference_map[$reference]['»'];

								if (isset($this->reference_map[$reference]['#']))
								{
									$element['#'] = $this->reference_map[$reference]['#'];
								}
							}
							else
							{
								unset($element);
							}
						}
						else
						{
							unset($element);
						}
					}

					if (isset($element))
					{
						$element['»'] = str_replace('&', '&amp;', $element['»']);
						$element['»'] = str_replace('<', '&lt;', $element['»']);

						if ($element['!'])
						{
							$markup .= '<img alt="'.$element['a'].'" src="'.$element['»'].'" />';
						}
						else
						{
							$element['a'] = $this->parse_span_elements($element['a'], $markers);

							$markup .= isset($element['#'])
								? '<a href="'.$element['»'].'" title="'.$element['#'].'">'.$element['a'].'</a>'
								: '<a href="'.$element['»'].'">'.$element['a'].'</a>';
						}

						unset($element);
					}
					else
					{
						$markup .= $closest_marker;

						$offset = $closest_marker === '![' ? 2 : 1;
					}

					break;

				case '&':

					$markup .= '&amp;';

					$offset = substr($text, 0, 5) === '&amp;' ? 5 : 1;

					break;

				case '*':
				case '_':

					if ($text[1] === $closest_marker and preg_match($this->strong_regex[$closest_marker], $text, $matches))
					{
						$matches[1] = $this->parse_span_elements($matches[1], $markers);

						$markup .= '<strong>'.$matches[1].'</strong>';
					}
					elseif (preg_match($this->em_regex[$closest_marker], $text, $matches))
					{
						$matches[1] = $this->parse_span_elements($matches[1], $markers);

						$markup .= '<em>'.$matches[1].'</em>';
					}
					elseif ($text[1] === $closest_marker and preg_match($this->strong_em_regex[$closest_marker], $text, $matches))
					{
						$matches[2] = $this->parse_span_elements($matches[2], $markers);

						$matches[1] and $matches[1] = $this->parse_span_elements($matches[1], $markers);
						$matches[3] and $matches[3] = $this->parse_span_elements($matches[3], $markers);

						$markup .= '<strong>'.$matches[1].'<em>'.$matches[2].'</em>'.$matches[3].'</strong>';
					}
					elseif (preg_match($this->em_strong_regex[$closest_marker], $text, $matches))
					{
						$matches[2] = $this->parse_span_elements($matches[2], $markers);

						$matches[1] and $matches[1] = $this->parse_span_elements($matches[1], $markers);
						$matches[3] and $matches[3] = $this->parse_span_elements($matches[3], $markers);

						$markup .= '<em>'.$matches[1].'<strong>'.$matches[2].'</strong>'.$matches[3].'</em>';
					}

					if (isset($matches) and $matches)
					{
						$offset = strlen($matches[0]);
					}
					else
					{
						$markup .= $closest_marker;

						$offset = 1;
					}

					break;

				case '<':

					if (strpos($text, '>') !== false)
					{
						if ($text[1] === 'h' and preg_match('/^<(https?:[\/]{2}[^\s]+?)>/i', $text, $matches))
						{
							$element_url = $matches[1];
							$element_url = str_replace('&', '&amp;', $element_url);
							$element_url = str_replace('<', '&lt;', $element_url);

							$markup .= '<a href="'.$element_url.'">'.$element_url.'</a>';

							$offset = strlen($matches[0]);
						}
						elseif (preg_match('/^<\/?\w.*?>/', $text, $matches))
						{
							$markup .= $matches[0];

							$offset = strlen($matches[0]);
						}
						else
						{
							$markup .= '&lt;';

							$offset = 1;
						}
					}
					else
					{
						$markup .= '&lt;';

						$offset = 1;
					}

					break;

				case '`':

					if (preg_match('/^`(.+?)`/', $text, $matches))
					{
						$element_text = $matches[1];
						$element_text = htmlspecialchars($element_text, ENT_NOQUOTES, 'UTF-8');

						if ($this->escape_sequence_map and strpos($element_text, "\x1A") !== false)
						{
							$element_text = strtr($element_text, $this->escape_sequence_map);
						}

						$markup .= '<code>'.$element_text.'</code>';

						$offset = strlen($matches[0]);
					}
					else
					{
						$markup .= '`';

						$offset = 1;
					}

					break;

				case 'http':

					if (preg_match('/^https?:[\/]{2}[^\s]+\b/i', $text, $matches))
					{
						$element_url = $matches[0];
						$element_url = str_replace('&', '&amp;', $element_url);
						$element_url = str_replace('<', '&lt;', $element_url);

						$markup .= '<a href="'.$element_url.'">'.$element_url.'</a>';

						$offset = strlen($matches[0]);
					}
					else
					{
						$markup .= 'http';

						$offset = 4;
					}

					break;

				case '~~':

					if (preg_match('/^~~(?=\S)(.+?)(?<=\S)~~/', $text, $matches))
					{
						$matches[1] = $this->parse_span_elements($matches[1], $markers);

						$markup .= '<del>'.$matches[1].'</del>';

						$offset = strlen($matches[0]);
					}
					else
					{
						$markup .= '~~';

						$offset = 2;
					}

					break;
			}

			if (isset($offset))
			{
				$text = substr($text, $offset);
			}

			$markers[$closest_marker_index] = $closest_marker;
		}

		$markup = str_replace($this->break_marker, '<br />'."\n", $markup);

		return $markup;
	}
}