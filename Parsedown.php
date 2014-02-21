<?php

#
#
# Parsedown
# http://parsedown.org
#
# (c) Emanuil Rusev
# http://erusev.com
#
# For the full license information, view the LICENSE file that was distributed
# with this source code.
#
#

class Parsedown
{
    #
    # Philosophy
    #

    # Markdown is intended to be easy-to-read by humans - those of us who read
    # line by line, left to right, top to bottom. In order to take advantage of
    # this, Parsedown tries to read in a similar way. It breaks texts into
    # lines, it iterates through them and it looks at how they start and relate
    # to each other.

    #
    # Setters
    #

    # Enables GFM line breaks.

    function set_breaks_enabled($breaks_enabled)
    {
        $this->breaks_enabled = $breaks_enabled;

        return $this;
    }

    private $breaks_enabled = false;

    #
    # Methods
    #

    function parse($text)
    {
        # standardize line breaks
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);

        # replace tabs with spaces
        $text = str_replace("\t", '    ', $text);

        # remove surrounding line breaks
        $text = trim($text, "\n");

        # split text into lines
        $lines = explode("\n", $text);

        # iterate through lines to identify blocks
        $blocks = $this->find_blocks($lines);

        # iterate through blocks to build markup
        $markup = $this->compile($blocks);

        # trim line breaks
        $markup = trim($markup, "\n");

        return $markup;
    }

    #
    # Private

    private function find_blocks(array $lines, $block_context = null)
    {
        $block = null;

        $context = null;
        $context_data = null;

        foreach ($lines as $line)
        {
            $indented_line = $line;

            $indentation = 0;

            while(isset($line[$indentation]) and $line[$indentation] === ' ')
            {
                $indentation++;
            }

            if ($indentation > 0)
            {
                $line = ltrim($line);
            }

            # ~

            switch ($context)
            {
                case null:

                    $context_data = null;

                    if ($line === '')
                    {
                        continue 2;
                    }

                    break;

                # ~~~ javascript
                # var message = 'Hello!';

                case 'fenced code':

                    if ($line === '')
                    {
                        $block['content'][0]['content'] .= "\n";

                        continue 2;
                    }

                    if (preg_match('/^[ ]*'.$context_data['marker'].'{3,}[ ]*$/', $line))
                    {
                        $context = null;
                    }
                    else
                    {
                        if ($block['content'][0]['content'])
                        {
                            $block['content'][0]['content'] .= "\n";
                        }

                        $string = htmlspecialchars($line, ENT_NOQUOTES, 'UTF-8');

                        $block['content'][0]['content'] .= $string;
                    }

                    continue 2;

                case 'markup':

                    if (stripos($line, $context_data['start']) !== false) # opening tag
                    {
                        $context_data['depth']++;
                    }

                    if (stripos($line, $context_data['end']) !== false) # closing tag
                    {
                        if ($context_data['depth'] > 0)
                        {
                            $context_data['depth']--;
                        }
                        else
                        {
                            $context = null;
                        }
                    }

                    $block['content'] .= "\n".$indented_line;

                    continue 2;

                case 'li':

                    if ($line === '')
                    {
                        $context_data['interrupted'] = true;

                        continue 2;
                    }

                    if ($context_data['indentation'] === $indentation and preg_match('/^'.$context_data['marker'].'[ ]+(.*)/', $line, $matches))
                    {
                        if (isset($context_data['interrupted']))
                        {
                            $nested_block['content'] []= '';

                            unset($context_data['interrupted']);
                        }

                        unset($nested_block);

                        $nested_block = array(
                            'name' => 'li',
                            'content type' => 'markdown lines',
                            'content' => array(
                                $matches[1],
                            ),
                        );

                        $block['content'] []= & $nested_block;

                        continue 2;
                    }

                    if (empty($context_data['interrupted']))
                    {
                        $value = $line;

                        if ($indentation > $context_data['baseline'])
                        {
                            $value = str_repeat(' ', $indentation - $context_data['baseline']) . $value;
                        }

                        $nested_block['content'] []= $value;

                        continue 2;
                    }

                    if ($indentation > 0)
                    {
                        $nested_block['content'] []= '';

                        $value = $line;

                        if ($indentation > $context_data['baseline'])
                        {
                            $value = str_repeat(' ', $indentation - $context_data['baseline']) . $value;
                        }

                        $nested_block['content'] []= $value;

                        unset($context_data['interrupted']);

                        continue 2;
                    }

                    $context = null;

                    break;

                case 'quote':

                    if ($line === '')
                    {
                        $context_data['interrupted'] = true;

                        continue 2;
                    }

                    if (preg_match('/^>[ ]?(.*)/', $line, $matches))
                    {
                        $block['content'] []= $matches[1];

                        continue 2;
                    }

                    if (empty($context_data['interrupted']))
                    {
                        $block['content'] []= $line;

                        continue 2;
                    }

                    $context = null;

                    break;

                case 'code':

                    if ($line === '')
                    {
                        $context_data['interrupted'] = true;

                        continue 2;
                    }

                    if ($indentation >= 4)
                    {
                        if (isset($context_data['interrupted']))
                        {
                            $block['content'][0]['content'] .= "\n";

                            unset($context_data['interrupted']);
                        }

                        $block['content'][0]['content'] .= "\n";

                        $string = htmlspecialchars($line, ENT_NOQUOTES, 'UTF-8');
                        $string = str_repeat(' ', $indentation - 4) . $string;

                        $block['content'][0]['content'] .= $string;

                        continue 2;
                    }

                    $context = null;

                    break;

                case 'paragraph':

                    if ($line === '')
                    {
                        $block['name'] = 'p'; # dense li

                        $context = null;

                        continue 2;
                    }

                    if ($line[0] === '=' and chop($line, '=') === '')
                    {
                        $block['name'] = 'h1';

                        $context = null;

                        continue 2;
                    }

                    if ($line[0] === '-' and chop($line, '-') === '')
                    {
                        $block['name'] = 'h2';

                        $context = null;

                        continue 2;
                    }

                    break;

                default:

                    throw new Exception('Unrecognized context - '.$context);
            }

            if ($indentation >= 4)
            {
                $blocks []= $block;

                $string = htmlspecialchars($line, ENT_NOQUOTES, 'UTF-8');
                $string = str_repeat(' ', $indentation - 4) . $string;

                $block = array(
                    'name' => 'pre',
                    'content type' => 'blocks',
                    'content' => array(
                        array(
                            'name' => 'code',
                            'content type' => 'markup',
                            'content' => $string,
                        ),
                    ),
                );

                $context = 'code';

                continue;
            }

            switch ($line[0])
            {
                case '#':

                    if (isset($line[1]))
                    {
                        $blocks []= $block;

                        $level = 1;

                        while (isset($line[$level]) and $line[$level] === '#')
                        {
                            $level++;
                        }

                        $string = trim($line, '# ');
                        $string = $this->parse_span_elements($string);

                        $block = array(
                            'name' => 'h'.$level,
                            'content type' => 'markdown',
                            'content' => $string,
                        );

                        $context = null;

                        continue 2;
                    }

                    break;

                case '<':

                    $position = strpos($line, '>');

                    if ($position > 1)
                    {
                        $substring = substr($line, 1, $position - 1);

                        $substring = chop($substring);

                        if (substr($substring, -1) === '/')
                        {
                            $is_self_closing = true;

                            $substring = substr($substring, 0, -1);
                        }

                        $position = strpos($substring, ' ');

                        if ($position)
                        {
                            $name = substr($substring, 0, $position);
                        }
                        else
                        {
                            $name = $substring;
                        }

                        $name = strtolower($name);

                        if ($name[0] == 'h' and strpos('r123456', $name[1]) !== false) #  hr, h1, h2, ...
                        {
                            if ($name == 'hr')
                            {
                                $is_self_closing = true;
                            }
                        }
                        elseif ( ! ctype_alpha($name))
                        {
                            break;
                        }

                        if (in_array($name, self::$text_level_elements))
                        {
                            break;
                        }

                        $blocks []= $block;

                        $block = array(
                            'name' => null,
                            'content type' => 'markup',
                            'content' => $indented_line,
                        );

                        if (isset($is_self_closing))
                        {
                            unset($is_self_closing);

                            continue 2;
                        }

                        $context = 'markup';
                        $context_data = array(
                            'start' => '<'.$name.'>',
                            'end' => '</'.$name.'>',
                            'depth' => 0,
                        );

                        if (stripos($line, $context_data['end']) !== false)
                        {
                            $context = null;
                        }

                        continue 2;
                    }

                    break;

                case '>':

                    if (preg_match('/^>[ ]?(.*)/', $line, $matches))
                    {
                        $blocks []= $block;

                        $block = array(
                            'name' => 'blockquote',
                            'content type' => 'markdown lines',
                            'content' => array(
                                $matches[1],
                            ),
                        );

                        $context = 'quote';
                        $context_data = array();

                        continue 2;
                    }

                    break;

                case '[':

                    $position = strpos($line, ']:');

                    if ($position)
                    {
                        $reference = array();

                        $label = substr($line, 1, $position - 1);
                        $label = strtolower($label);

                        $substring = substr($line, $position + 2);
                        $substring = trim($substring);

                        if ($substring === '')
                        {
                            break;
                        }

                        if ($substring[0] === '<')
                        {
                            $position = strpos($substring, '>');

                            if ($position === false)
                            {
                                break;
                            }

                            $reference['link'] = substr($substring, 1, $position - 1);

                            $substring = substr($substring, $position + 1);
                        }
                        else
                        {
                            $position = strpos($substring, ' ');

                            if ($position === false)
                            {
                                $reference['link'] = $substring;

                                $substring = false;
                            }
                            else
                            {
                                $reference['link'] = substr($substring, 0, $position);

                                $substring = substr($substring, $position + 1);
                            }
                        }

                        if ($substring !== false)
                        {
                            if ($substring[0] !== '"' and $substring[0] !== "'" and $substring[0] !== '(')
                            {
                                break;
                            }

                            $last_char = substr($substring, -1);

                            if ($last_char !== '"' and $last_char !== "'" and $last_char !== ')')
                            {
                                break;
                            }

                            $reference['title'] = substr($substring, 1, -1);
                        }

                        $this->reference_map[$label] = $reference;

                        continue 2;
                    }

                    break;

                case '`':
                case '~':

                    if (preg_match('/^([`]{3,}|[~]{3,})[ ]*(\w+)?[ ]*$/', $line, $matches))
                    {
                        $blocks []= $block;

                        $block = array(
                            'name' => 'pre',
                            'content type' => 'blocks',
                            'content' => array(
                                array(
                                    'name' => 'code',
                                    'content type' => 'markup',
                                    'content' => '',
                                ),
                            ),
                        );

                        if (isset($matches[2]))
                        {
                            $block['content'][0]['attributes'] = array(
                                'class' => 'language-'.$matches[2],
                            );
                        }

                        $context = 'fenced code';
                        $context_data = array(
                            'marker' => $matches[1][0],
                        );

                        continue 2;
                    }

                    break;

                case '-':
                case '*':
                case '_':

                    if (preg_match('/^([-*_])([ ]{0,2}\1){2,}[ ]*$/', $line))
                    {
                        $blocks []= $block;

                        $block = array(
                            'name' => 'hr',
                            'content' => null,
                        );

                        continue 2;
                    }
            }

            switch (true)
            {
                case $line[0] <= '-' and preg_match('/^([*+-][ ]+)(.*)/', $line, $matches):
                case $line[0] <= '9' and preg_match('/^([0-9]+[.][ ]+)(.*)/', $line, $matches):

                    $blocks []= $block;

                    $name = $line[0] >= '0' ? 'ol' : 'ul';

                    $block = array(
                        'name' => $name,
                        'content type' => 'blocks',
                        'content' => array(),
                    );

                    unset($nested_block);

                    $nested_block = array(
                        'name' => 'li',
                        'content type' => 'markdown lines',
                        'content' => array(
                            $matches[2],
                        ),
                    );

                    $block['content'] []= & $nested_block;

                    $baseline = $indentation + strlen($matches[1]);

                    $marker = $line[0] >= '0' ? '[0-9]+[.]' : '[*+-]';

                    $context = 'li';
                    $context_data = array(
                        'indentation' => $indentation,
                        'baseline' => $baseline,
                        'marker' => $marker,
                        'lines' => array(
                            $matches[2],
                        ),
                    );

                    continue 2;
            }

            if ($context === 'paragraph')
            {
                $block['content'] .= "\n".$line;

                continue;
            }
            else
            {
                $blocks []= $block;

                $block = array(
                    'name' => 'p',
                    'content type' => 'markdown',
                    'content' => $line,
                );

                if ($block_context === 'li' and empty($blocks[1]))
                {
                    $block['name'] = null;
                }

                $context = 'paragraph';
            }
        }

        if ($block_context === 'li' and $block['name'] === null)
        {
            return $block['content'];
        }

        $blocks []= $block;

        unset($blocks[0]);

        return $blocks;
    }

    private function compile(array $blocks)
    {
        $markup = '';

        foreach ($blocks as $block)
        {
            $markup .= "\n";

            if (isset($block['name']))
            {
                $markup .= '<'.$block['name'];

                if (isset($block['attributes']))
                {
                    foreach ($block['attributes'] as $name => $value)
                    {
                        $markup .= ' '.$name.'="'.$value.'"';
                    }
                }

                if ($block['content'] === null)
                {
                    $markup .= ' />';

                    continue;
                }
                else
                {
                    $markup .= '>';
                }
            }

            switch ($block['content type'])
            {
                case 'markup':

                    $markup .= $block['content'];

                    break;

                case 'markdown':

                    $markup .= $this->parse_span_elements($block['content']);

                    break;

                case 'markdown lines':

                    $result = $this->find_blocks($block['content'], $block['name']);

                    if (is_string($result)) # dense li
                    {
                        $markup .= $this->parse_span_elements($result);

                        break;
                    }

                    $markup .= $this->compile($result);

                    break;

                case 'blocks':

                    $markup .= $this->compile($block['content']);

                    break;
            }

            if (isset($block['name']))
            {
                $markup .= '</'.$block['name'].'>';
            }
        }

        $markup .= "\n";

        return $markup;
    }

    private function parse_span_elements($text, $markers = array("  \n", '![', '&', '*', '<', '[', '\\', '_', '`', 'http', '~~'))
    {
        if (isset($text[1]) === false or $markers === array())
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

            if ($closest_marker === null or isset($text[$closest_marker_position + 1]) === false)
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
                case "  \n":

                    $markup .= '<br />'."\n";

                    $offset = 3;

                    break;

                case '![':
                case '[':

                    if (strpos($text, ']') and preg_match('/\[((?:[^][]|(?R))*)\]/', $text, $matches))
                    {
                        $element = array(
                            '!' => $text[0] === '!',
                            'text' => $matches[1],
                        );

                        $offset = strlen($matches[0]);

                        if ($element['!'])
                        {
                            $offset++;
                        }

                        $remaining_text = substr($text, $offset);

                        if ($remaining_text[0] === '(' and preg_match('/\([ ]*(.*?)(?:[ ]+[\'"](.+?)[\'"])?[ ]*\)/', $remaining_text, $matches))
                        {
                            $element['link'] = $matches[1];

                            if (isset($matches[2]))
                            {
                                $element['title'] = $matches[2];
                            }

                            $offset += strlen($matches[0]);
                        }
                        elseif ($this->reference_map)
                        {
                            $reference = $element['text'];

                            if (preg_match('/^\s*\[(.*?)\]/', $remaining_text, $matches))
                            {
                                $reference = $matches[1] ? $matches[1] : $element['text'];

                                $offset += strlen($matches[0]);
                            }

                            $reference = strtolower($reference);

                            if (isset($this->reference_map[$reference]))
                            {
                                $element['link'] = $this->reference_map[$reference]['link'];

                                if (isset($this->reference_map[$reference]['title']))
                                {
                                    $element['title'] = $this->reference_map[$reference]['title'];
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
                        $element['link'] = str_replace('&', '&amp;', $element['link']);
                        $element['link'] = str_replace('<', '&lt;', $element['link']);

                        if ($element['!'])
                        {
                            $markup .= '<img alt="'.$element['text'].'" src="'.$element['link'].'"';

                            if (isset($element['title']))
                            {
                                $markup .= ' title="'.$element['title'].'"';
                            }

                            $markup .= ' />';
                        }
                        else
                        {
                            $element['text'] = $this->parse_span_elements($element['text'], $markers);

                            $markup .= '<a href="'.$element['link'].'"';

                            if (isset($element['title']))
                            {
                                $markup .= ' title="'.$element['title'].'"';
                            }

                            $markup .= '>'.$element['text'].'</a>';
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

                    if (preg_match('/^&#?\w+;/', $text, $matches))
                    {
                        $markup .= $matches[0];

                        $offset = strlen($matches[0]);
                    }
                    else
                    {
                        $markup .= '&amp;';

                        $offset = 1;
                    }

                    break;

                case '*':
                case '_':

                    if ($text[1] === $closest_marker and preg_match(self::$strong_regex[$closest_marker], $text, $matches))
                    {
                        $markers[] = $closest_marker;
                        $matches[1] = $this->parse_span_elements($matches[1], $markers);

                        $markup .= '<strong>'.$matches[1].'</strong>';
                    }
                    elseif (preg_match(self::$em_regex[$closest_marker], $text, $matches))
                    {
                        $markers[] = $closest_marker;
                        $matches[1] = $this->parse_span_elements($matches[1], $markers);

                        $markup .= '<em>'.$matches[1].'</em>';
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
                        elseif (strpos($text, '@') > 1 and preg_match('/<(\S+?@\S+?)>/', $text, $matches))
                        {
                            $markup .= '<a href="mailto:'.$matches[1].'">'.$matches[1].'</a>';

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

                case '\\':

                    if (in_array($text[1], self::$special_characters))
                    {
                        $markup .= $text[1];

                        $offset = 2;
                    }
                    else
                    {
                        $markup .= '\\';

                        $offset = 1;
                    }

                    break;

                case '`':

                    if (preg_match('/^(`+)[ ]*(.+?)[ ]*(?<!`)\1(?!`)/', $text, $matches))
                    {
                        $element_text = $matches[2];
                        $element_text = htmlspecialchars($element_text, ENT_NOQUOTES, 'UTF-8');

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

                    if (preg_match('/^https?:[\/]{2}[^\s]+\b\/*/ui', $text, $matches))
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

        return $markup;
    }

    #
    # Static

    static function instance($name = 'default')
    {
        if (isset(self::$instances[$name]))
        {
            return self::$instances[$name];
        }

        $instance = new Parsedown();

        self::$instances[$name] = $instance;

        return $instance;
    }

    private static $instances = array();

    #
    # Fields
    #

    private $reference_map = array();

    #
    # Read-only

    private static $strong_regex = array(
        '*' => '/^[*]{2}((?:[^*]|[*][^*]*[*])+?)[*]{2}(?![*])/s',
        '_' => '/^__((?:[^_]|_[^_]*_)+?)__(?!_)/us',
    );

    private static $em_regex = array(
        '*' => '/^[*]((?:[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
        '_' => '/^_((?:[^_]|__[^_]*__)+?)_(?!_)\b/us',
    );

    private static $special_characters = array(
        '\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '>', '#', '+', '-', '.', '!',
    );

    private static $text_level_elements = array(
        'a', 'br', 'bdo', 'abbr', 'blink', 'nextid', 'acronym', 'basefont',
        'b', 'em', 'big', 'cite', 'small', 'spacer', 'listing',
        'i', 'rp', 'sub', 'code',          'strike', 'marquee',
        'q', 'rt', 'sup', 'font',          'strong',
        's', 'tt', 'var', 'mark',
        'u', 'xm', 'wbr', 'nobr',
                          'ruby',
                          'span',
                          'time',
    );
}
