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
		$text = str_replace("\r", '', $text);
		
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
				$this->reference_map[$matches[1]] = $matches[2];
				
				$text = str_replace($matches[0], '', $text);
			}
		}
		
		# ~ 
		
		$text = $this->parse_blocks($text);
		
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
	
	private function parse_blocks($text)
	{
		# Divides text into blocks.
		$blocks = preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
		
		# Makes sure compound blocks get rendered.
		$blocks []= NULL;
		
		$markup = '';
		
		# Parses blocks.
		
		foreach ($blocks as $block)
		{
			if (isset($block) and $block[0] >= 'A')
			{
				$quick_block = $block;
				
				unset($block);
			}
			
			# List 
			
			if (isset($block) and preg_match('/^([ ]{0,3})(\d+[.]|[*+-])[ ]/', $block, $matches)) # list item
			{
				if (isset($list)) # subsequent 
				{
					$list .= "\n\n".$block;
				}
				else # first 
				{
					$list = $block;
					$list_indentation = strlen($matches[1]);
					
					list($list_type, $list_marker_pattern) = ($matches[2] === '-' or $matches[2] === '+' or $matches[2] === '*')
						? array('ul', '[*+-]')
						: array('ol', '\d+[.]');
				}
				
				unset($block);
			}
			elseif (isset($block) and isset($list) and $block[0] === ' ') # list item block 
			{
				$list .= "\n\n".$block;
				
				unset($block);
			}
			elseif (isset($list))
			{
				$markup .= '<'.$list_type.'>'."\n";
				
				$list_items = preg_split('/^([ ]{'.$list_indentation.'})'.$list_marker_pattern.'[ ]/m', $list, -1, PREG_SPLIT_NO_EMPTY);
				
				foreach ($list_items as $list_item)
				{
					$markup .= '<li>';
					
					if (strpos($list_item, "\n\n")) # sparse 
					{
						$list_item = trim($list_item, "\n");
						
						if (strpos($list_item, "\n\n"))
						{
							$list_item = preg_replace('/^[ ]{0,4}/m', '', $list_item);
							$list_item = $this->parse_blocks($list_item);
						}
						else 
						{
							$list_item = $this->parse_lines($list_item, TRUE);
						}
						
						$markup .= "\n".$list_item;
					}
					else # dense 
					{
						$list_item = trim($list_item, "\n");

						$list_item = strpos($list_item, "\n")
							? $this->parse_lines($list_item)
							: $this->parse_inline_elements($list_item);
						
						$markup .= $list_item;
					}
					
					$markup .= '</li>'."\n";
				}
				
				$markup .= '</'.$list_type.'>'."\n";
				
				unset($list);
			}
			
			# Code Block 
			
			if (isset($block) and strlen($block) > 4 and $block[0] === ' ' and $block[1] === ' ' and $block[2] === ' ' and $block[3] === ' ')
			{
				if (isset($code_block))
				{
					$code_block .= "\n\n".$block;
				}
				else
				{
					$code_block = $block;
				}
				
				unset($block);
			}
			elseif (isset($code_block))
			{
				$code_block_text = preg_replace('/^[ ]{4}/m', '', $code_block);
				$code_block_text = htmlentities($code_block_text, ENT_NOQUOTES);
				
				# Decodes encoded escape sequences if present.
				strpos($code_block_text, "\x1A\\") !== FALSE and $code_block_text = strtr($code_block_text, $this->escape_sequence_map);
				
				$markup .= '<pre><code>'.$code_block_text.'</code></pre>'."\n";
				
				unset($code_block);
			}
			
			# Atx Heading 
			
			if (isset($block) and $block[0] === '#' and preg_match('/^(#{1,6})[ ]*(.+?)[ ]*#*$/', $block, $matches))
			{
				$level = strlen($matches[1]);
				
				$heading = $this->parse_inline_elements($matches[2]);
				
				$markup .= '<h'.$level.'>'.$heading.'</h'.$level.'>'."\n";
				
				continue;
			}
			
			# Quote Block 
			
			if (isset($block) and preg_match('/^[ ]{0,3}>/', $block))
			{
				$block = preg_replace('/^[ ]{0,3}>[ ]?/m', '', $block);
				$block = $this->parse_blocks($block);
				
				$markup .= '<blockquote>'."\n".$block.'</blockquote>'."\n";
				
				continue;
			}
			
			# Horizontal Line 
			
			if (isset($block) and preg_match('/^[ ]{0,3}([-*_])([ ]{0,2}\1){2,}$/', $block))
			{
				$markup .= '<hr />'."\n";
				
				continue;
			}
			
			# ~ 
			
			if (isset($quick_block))
			{
				$block = $quick_block;
				
				unset ($quick_block);
			}
			
			# 
			# Paragraph 
			
			if (isset($block))
			{
				if (strpos($block, "\n"))
				{
					$markup .= $this->parse_lines($block, TRUE);
				}
				else 
				{
					$element_text = $this->parse_inline_elements($block);
					$element = '<p>'.$element_text.'</p>'."\n";

					$markup .= $element;
				}
			}
		}
		
		return $markup;
	}
	
	private function parse_lines($text, $paragraph_based = FALSE)
	{
		$text = trim($text, "\n");
		
		$lines = explode("\n", $text);
		
		$lines []= NULL;
		
		$markup = '';
		
		foreach ($lines as $line)
		{
			if (isset($line) and $line === '')
			{
				unset($line);
			}
			
			# Paragraph 
			
			if (isset($line) and $line[0] >= 'A')
			{
				$quick_line = $line;
				
				unset($line);
			}
			
			# List 
			
			if (isset($line) and preg_match('/^([ ]*)(\d+[.]|[*+-])[ ](.*)/', $line, $matches)) # list item 
			{
				$list_item_indentation = strlen($matches[1]);
				$list_item_type = ($matches[2] === '-' or $matches[2] === '+' or $matches[2] === '*')
					? 'ul'
					: 'ol';
				
				if (isset($list)) # subsequent 
				{
					if ($list_item_indentation === $list_indentation and $list_item_type === $list_type)
					{
						# Adds last list item to the list.
						$list []= $list_item;
						
						# Creates a separate list item.
						$list_item = $matches[3];
					}
					else 
					{
						# Adds line to the current list item.
						$list_item .= "\n".$line;
					}
				}
				else # first 
				{
					$list = array();
					$list_indentation = $list_item_indentation;
					$list_type = $list_item_type;
					
					$list_item = $matches[3];
				}
				
				unset($line);
			}
			else 
			{
				if (isset($list))
				{
					$list []= $list_item;
					
					$markup .= '<'.$list_type.'>'."\n";
					
					foreach ($list as $list_item)
					{
						$list_item_text = strpos($list_item, "\n") 
							? $this->parse_lines($list_item)
							: $this->parse_inline_elements($list_item);
						
						$markup .= '<li>'.$list_item_text.'</li>'."\n";
					}
					
					$markup .= '</'.$list_type.'>'."\n";
					
					unset($list);
				}
			}
			
			# Quote Block 
			
			if (isset($line) and preg_match('/^[ ]*>[ ]?(.*)/', $line, $matches))
			{
				if (isset($quote))
				{
					$quote .= "\n".$matches[1];
				}
				else 
				{
					$quote = $matches[1];
				}
				
				unset($line);
			}
			else 
			{
				if (isset($quote))
				{
					$quote = $this->parse_blocks($quote);
					
					$markup .= '<blockquote>'."\n".$quote.'</blockquote>'."\n";
					
					unset($quote);
				}
			}
			
			# Atx Heading 
			
			if (isset($atx_heading))
			{
				$markup .= '<h'.$atx_heading_level.'>'.$atx_heading.'</h'.$atx_heading_level.'>'."\n";
				
				unset($atx_heading);
			}
			
			if (isset($line) and $line[0] === '#' and preg_match('/^(#{1,6})[ ]*(.+?)[ ]*#*$/', $line, $matches))
			{
				$atx_heading_level = strlen($matches[1]);
				
				$atx_heading = $this->parse_inline_elements($matches[2]);
				
				unset($line);
			}
			
			# Setext Heading 
			
			if (isset($line) and isset($paragraph))
			{
				$setext_characters = array('=', '-');
				
				foreach ($setext_characters as $index => $setext_character)
				{
					if ($line[0] === $setext_character and preg_match('/^['.$setext_character.']+[ ]*$/', $line))
					{
						$setext_heading_level = $index + 1;
						
						$setext_heading_text = $this->parse_inline_elements($paragraph);
						
						$markup .= '<h'.$setext_heading_level.'>'.$setext_heading_text.'</h'.$setext_heading_level.'>'."\n";
						
						unset($paragraph, $line);
						
						continue 2;
					}
				}
			}
			
			# Paragraph 
			
			if (isset($quick_line))
			{
				$line = $quick_line;
				
				unset($quick_line);
			}
			
			if (isset($line))
			{
				substr($line, -2) === '  '
					and $line = substr($line, 0, -2)
					and $line .= '<br/>';
				
				if (isset($paragraph))
				{
					$paragraph .= "\n".$line;
				}
				else 
				{
					$paragraph = $line;
				}
			}
			else 
			{
				if (isset($paragraph))
				{
					$paragraph_text = $this->parse_inline_elements($paragraph);
					
					$markup .= $markup === '' && $paragraph_based === FALSE
						? $paragraph_text
						: '<p>'.$paragraph_text.'</p>'."\n";
					
					unset($paragraph);
				}
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
		
		if ($this->reference_map and strpos($text, '[') !== FALSE and preg_match_all('/(!?)\[(.+?)\][ ]?\[(.+?)\]/', $text, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $matches)
			{
				if (array_key_exists($matches[3], $this->reference_map))
				{
					$url = $this->reference_map[$matches[3]];

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

		if (strpos($text, '*') !== FALSE or strpos($text, '_') !== FALSE)
		{
			$text = preg_replace('/(\*\*|__)(.+?[*_]*)(?<=\S)\1/', '<strong>$2</strong>', $text);
			$text = preg_replace('/(\*|_)(.+?)(?<=\S)\1/', '<em>$2</em>', $text);
		}

		$text = strtr($text, $map);
		
		return $text;
	}
}

