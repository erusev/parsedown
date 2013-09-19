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
					$code = "\x1A".'\\'.$index;
					
					$text = str_replace($escape_sequence, $code, $text);
					
					$this->escape_sequence_map[$code] = $escape_sequence;
				}
			}
		}
		
		# Extracts link references.
		
		if (preg_match_all('/^[ ]{0,3}\[(.+)\][ ]?:[ ]*\n?[ ]*(.+)$/m', $text, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $matches)
			{
				$this->reference_map[strtolower($matches[1])] = $matches[2];
				
				$text = str_replace($matches[0], '', $text);
			}
		}
		
		# ~ 
		
		$text = trim($text, "\n");
		$text = preg_replace('/\n\s*\n/', "\n\n", $text);
		
		$text = $this->parse_lines($text);
		
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
	
	private function parse_lines($text, $context = null)
	{
		$lines = explode("\n", $text);
		$lines []= null;
		
		$line_count = count($lines);
		
		$markup = '';
		
		foreach ($lines as $index => $line)
		{
			# ~ 
			
			if (isset($line) and $line !== '' and $line[0] >= 'A')
			{
				$simple_line = $line;
				
				unset($line);
			}
			
			# Setext Heading (-)
			
			if (isset($line) and $line !== '' and isset($paragraph) and preg_match('/^[-]+[ ]*$/', $line))
			{
				$setext_heading_text = $this->parse_inline_elements($paragraph);
				
				$markup .= '<h2>'.$setext_heading_text.'</h2>'."\n";
				
				unset($paragraph, $line);
				
				continue;
			}
			
			# Rule 
			
			if (isset($line) and preg_match('/^[ ]{0,3}([-*_])([ ]{0,2}\1){2,}[ ]*$/', $line))
			{
				$rule = true;
				
				unset($line);
			}
			elseif (isset($rule))
			{
				$markup .= '<hr />'."\n";
				
				unset($rule);
			}
			
			# List 
			
			# Unlike other types, consequent lines of type "list items" may not
			# belong to the same block.
			
			if (isset($line) and $line !== '' and preg_match('/^([ ]{0,3})(\d+[.]|[*+-])[ ](.*)/', $line, $matches)) # list item 
			{
				$list_item_indentation = $matches[1];
				$list_item_type = ($matches[2] === '-' or $matches[2] === '+' or $matches[2] === '*')
					? 'ul'
					: 'ol';
				
				if (isset($list_items)) # subsequent 
				{
					if ($list_item_indentation === $list_indentation and $list_item_type === $list_type)
					{
						$list_items []= $list_item;
						
						$list_item = $matches[3];
					}
					else 
					{
						$list_item .= "\n".$line;
					}
				}
				else # first 
				{
					$list_indentation = $list_item_indentation;
					$list_type = $list_item_type;
					
					$list_item = $matches[3];
					
					$list_items = array();
				}
				
				unset($line);
			}
			elseif (isset($list_items)) # incomplete list item
			{
				if (isset($line) and ($line === '' or $line[0] === ' '))
				{
					$line and $line = preg_replace('/^[ ]{0,4}/', '', $line);;
					
					$list_item .= "\n".$line;
						
					unset($line);
				}
				else # line is consumed or does not belong to the list item 
				{
					$list_item = rtrim($list_item, "\n");
					
					$list_items []= $list_item;
					
					$markup .= '<'.$list_type.'>'."\n";
					
					foreach ($list_items as $list_item)
					{
						$list_item_text = strpos($list_item, "\n") !== false
							? $this->parse_lines($list_item, 'li')
							: $this->parse_inline_elements($list_item);
						
						$markup .= '<li>'.$list_item_text.'</li>'."\n";
					}
					
					$markup .= '</'.$list_type.'>'."\n";
					
					unset($list_items);
				}
			}
			
			# Code Block 
			
			if (isset($line) and $line !== '' and preg_match('/^[ ]{4}(.*)/', $line, $matches))
			{
				if (isset($code_block))
				{
					$code_block .= "\n".$matches[1];
				}
				else
				{
					$code_block = $matches[1];
				}
				
				unset($line);
			}
			elseif (isset($code_block))
			{
				if (isset($line) and $line === '')
				{
					$code_block .= "\n";
					
					# » continue;
				}
				else 
				{
					$code_block = rtrim($code_block);
					
					$code_block_text = htmlentities($code_block, ENT_NOQUOTES);
					
					# Decodes encoded escape sequences if present.
					strpos($code_block_text, "\x1A\\") !== FALSE and $code_block_text = strtr($code_block_text, $this->escape_sequence_map);
					
					$markup .= '<pre><code>'.$code_block_text.'</code></pre>'."\n";
					
					unset($code_block);
				}
			}
			
			# Blockquote
			
			if (isset($line) and $line !== '' and preg_match('/^[ ]*>[ ]?(.*)/', $line, $matches))
			{
				if (isset($blockquote))
				{
					$blockquote .= "\n".$matches[1];
				}
				else 
				{
					$blockquote = $matches[1];
				}
				
				unset($line);
			}
			elseif (isset($blockquote)) 
			{
				if (isset($line) and $line === '')
				{
					$blockquote .= "\n";
				}
				else 
				{
					$blockquote = $this->parse_lines($blockquote);
					
					$markup .= '<blockquote>'."\n".$blockquote.'</blockquote>'."\n";
					
					unset($blockquote);
				}
			}
			
			# Atx Heading 
			
			if (isset($line) and $line !== '' and $line[0] === '#' and preg_match('/^(#{1,6})[ ]*(.+?)[ ]*#*$/', $line, $matches))
			{
				$atx_heading_level = strlen($matches[1]);
				
				$atx_heading = $this->parse_inline_elements($matches[2]);
				
				unset($line);
			}
			elseif (isset($atx_heading))
			{
				$markup .= '<h'.$atx_heading_level.'>'.$atx_heading.'</h'.$atx_heading_level.'>'."\n";
				
				unset($atx_heading);
			}
			
			# Setext Heading (=)
			
			if (isset($line) and $line !== '' and isset($paragraph) and preg_match('/^[=]+[ ]*$/', $line))
			{
				$setext_heading_text = $this->parse_inline_elements($paragraph);
				
				$markup .= '<h1>'.$setext_heading_text.'</h1>'."\n";
				
				unset($paragraph, $line);
				
				continue;
			}
			
			# Paragraph 
			
			if (isset($simple_line))
			{
				$line = $simple_line;
				
				unset($simple_line);
			}
			
			if (isset($line) and $line !== '')
			{
				substr($line, -2) === '  ' and $line = substr_replace($line, '<br />', -2);

				if (isset($paragraph))
				{
					$paragraph .= "\n".$line;
				}
				else 
				{
					$paragraph = $line;
				}
			}
			elseif (isset($paragraph))
			{
				$paragraph_text = $this->parse_inline_elements($paragraph);
				
				if ($context === 'li')
				{
					if ( ! $markup and $index + 1 === $line_count)
					{
						$text_is_simple = true;
					}
					else
					{
						$markup or $markup .= "\n";
					}
					
					$markup .= isset($text_is_simple)
						? $paragraph_text
						: '<p>'.$paragraph_text.'</p>'."\n";
				}
				else 
				{
					$markup .= '<p>'.$paragraph_text.'</p>'."\n";
				}
				
				unset($paragraph);
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
		
		# Reference(d) Link / Image 
		
		if ($this->reference_map and strpos($text, '[') !== FALSE and preg_match_all('/(!?)\[(.+?)\](?:\n?[ ]?\[(.*?)\])?/ms', $text, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $matches)
			{
				$link_difinition = isset($matches[3]) && $matches[3]
					? $matches[3]
					: $matches[2]; # implicit 
				
				$link_difinition = strtolower($link_difinition);
				
				if (isset($this->reference_map[$link_difinition]))
				{
					$url = $this->reference_map[$link_difinition];
					
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
		
		# Inline Link / Image 
		
		if (strpos($text, '](') !== FALSE and preg_match_all('/(!?)(\[((?:[^][]+|(?2))*)\])\((.*?)\)/', $text, $matches, PREG_SET_ORDER)) # inline 
		{
			foreach ($matches as $matches)
			{
				if ($matches[1]) # image 
				{
					$element = '<img alt="'.$matches[3].'" src="'.$matches[4].'">';
				}
				else 
				{
					$element_text = $this->parse_inline_elements($matches[3]);
					
					$element = '<a href="'.$matches[4].'">'.$element_text.'</a>';
				}
				
				$element_text = $this->parse_inline_elements($matches[1]);
				
				# ~ 

				$code = "\x1A".'$'.$index;

				$text = str_replace($matches[0], $code, $text);

				$map[$code] = $element;
				
				$index ++;
			}
		}
		
		if (strpos($text, '<') !== FALSE and preg_match_all('/<((https?|ftp|dict):[^\^\s]+?)>/i', $text, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $matches)
			{
				$element = '<a href=":href">:text</a>';
				$element = str_replace(':text', $matches[1], $element);
				$element = str_replace(':href', $matches[1], $element);
				
				# ~ 
				
				$code = "\x1A".'$'.$index;
				
				$text = str_replace($matches[0], $code, $text);
				
				$map[$code] = $element;
				
				$index ++;
			}
		}
		
		if (strpos($text, '_') !== FALSE)
		{
			$text = preg_replace('/__(?=\S)(.+?)(?<=\S)__/', '<strong>$1</strong>', $text);
			$text = preg_replace('/_(?=\S)(.+?)(?<=\S)_/', '<em>$1</em>', $text);
		}
		
		if (strpos($text, '*') !== FALSE)
		{
			$text = preg_replace('/\*\*(?=\S)(.+?)(?<=\S)\*\*/', '<strong>$1</strong>', $text);
			$text = preg_replace('/\*(?=\S)(.+?)(?<=\S)\*/', '<em>$1</em>', $text);
		}
		
		$text = strtr($text, $map);
		
		return $text;
	}
}

