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
    # Multiton

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
    # Synopsis
    #

    # Markdown is intended to be easy-to-read by humans - those of us who read
    # line by line, left to right, top to bottom. In order to take advantage of
    # this, Parsedown tries to read in a similar way. It breaks texts into
    # lines, it iterates through them and it looks at how they start and relate
    # to each other.

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

        # convert lines into html
        $text = $this->parse_block_elements($lines);

        # remove trailing line breaks
        $text = chop($text, "\n");

        return $text;
    }

    #
    # Private

    private function parse_block_elements(array $lines, $context = '')
    {
        $blocks = array();

        $block = array(
            'type' => '',
        );

        foreach ($lines as $line)
        {
            # context

            switch ($block['type'])
            {
                case 'fenced':

                    if ( ! isset($block['closed']))
                    {
                        if (preg_match('/^[ ]*'.$block['fence'][0].'{3,}[ ]*$/', $line))
                        {
                            $block['closed'] = true;
                        }
                        else
                        {
                            if ($block['text'] !== '')
                            {
                                $block['text'] .= "\n";
                            }

                            $block['text'] .= $line;
                        }

                        continue 2;
                    }

                    break;

                case 'markup':

                    if ( ! isset($block['closed']))
                    {
                        if (strpos($line, $block['start']) !== false) # opening tag
                        {
                            $block['depth']++;
                        }

                        if (strpos($line, $block['end']) !== false) # closing tag
                        {
                            if ($block['depth'] > 0)
                            {
                                $block['depth']--;
                            }
                            else
                            {
                                $block['closed'] = true;
                            }
                        }

                        $block['text'] .= "\n".$line;

                        continue 2;
                    }

                    break;
            }

            # ~

            $indentation = 0;

            while(isset($line[$indentation]) and $line[$indentation] === ' ')
            {
                $indentation++;
            }

            $outdented_line = $indentation > 0 ? ltrim($line) : $line;

            # blank

            if ($outdented_line === '')
            {
                $block['interrupted'] = true;

                continue;
            }

            # context

            switch ($block['type'])
            {
                case 'quote':

                    if ( ! isset($block['interrupted']))
                    {
                        $line = preg_replace('/^[ ]*>[ ]?/', '', $line);

                        $block['lines'] []= $line;

                        continue 2;
                    }

                    break;

                case 'li':

                    if ($block['indentation'] === $indentation and preg_match('/^'.$block['marker'].'[ ]+(.*)/', $outdented_line, $matches))
                    {
                        unset($block['last']);

                        $blocks []= $block;

                        $block['last'] = true;
                        $block['lines'] = array($matches[1]);

                        unset($block['first']);
                        unset($block['interrupted']);

                        continue 2;
                    }

                    if ( ! isset($block['interrupted']))
                    {
                        $line = preg_replace('/^[ ]{0,'.$block['baseline'].'}/', '', $line);

                        $block['lines'] []= $line;

                        continue 2;
                    }
                    elseif ($line[0] === ' ')
                    {
                        $block['lines'] []= '';

                        $line = preg_replace('/^[ ]{0,'.$block['baseline'].'}/', '', $line);

                        $block['lines'] []= $line;

                        unset($block['interrupted']);

                        continue 2;
                    }

                    break;
            }

            # indentation sensitive types

            switch ($line[0])
            {
                case ' ':

                    # code

                    if ($indentation >= 4)
                    {
                        $code_line = substr($line, 4);

                        if ($block['type'] === 'code')
                        {
                            if (isset($block['interrupted']))
                            {
                                $block['text'] .= "\n";

                                unset($block['interrupted']);
                            }

                            $block['text'] .= "\n".$code_line;
                        }
                        else
                        {
                            $blocks []= $block;

                            $block = array(
                                'type' => 'code',
                                'text' => $code_line,
                            );
                        }

                        continue 2;
                    }

                    break;

                case '#':

                    # atx heading (#)

                    if (isset($line[1]))
                    {
                        $blocks []= $block;

                        $level = 1;

                        while (isset($line[$level]) and $line[$level] === '#')
                        {
                            $level++;
                        }

                        $block = array(
                            'type' => 'heading',
                            'text' => trim($line, '# '),
                            'level' => $level,
                        );

                        continue 2;
                    }

                    break;

                case '-':
                case '=':

                    # setext heading (===)

                    if ($block['type'] === 'paragraph' and isset($block['interrupted']) === false)
                    {
                        $chopped_line = chop($line);

                        $i = 1;

                        while (isset($chopped_line[$i]))
                        {
                            if ($chopped_line[$i] !== $line[0])
                            {
                                break 2;
                            }

                            $i++;
                        }

                        $block['type'] = 'heading';

                        $block['level'] = $line[0] === '-' ? 2 : 1;

                        continue 2;
                    }

                    break;
            }

            # indentation insensitive types

            switch ($outdented_line[0])
            {
                case '<':

                    $position = strpos($outdented_line, '>');

                    if ($position > 1)
                    {
                        $substring = substr($outdented_line, 1, $position - 1);

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

                        if ( ! ctype_alpha($name))
                        {
                            break;
                        }

                        if (in_array($name, self::$text_level_elements))
                        {
                            break;
                        }

                        $blocks []= $block;

                        if (isset($is_self_closing))
                        {
                            $block = array(
                                'type' => 'self-closing tag',
                                'text' => $outdented_line,
                            );

                            unset($is_self_closing);

                            continue 2;
                        }

                        $block = array(
                            'type' => 'markup',
                            'text' => $outdented_line,
                            'start' => '<'.$name.'>',
                            'end' => '</'.$name.'>',
                            'depth' => 0,
                        );

                        if (strpos($outdented_line, $block['end']))
                        {
                            $block['closed'] = true;
                        }

                        continue 2;
                    }

                    break;

                case '>':

                    # quote

                    if (preg_match('/^>[ ]?(.*)/', $outdented_line, $matches))
                    {
                        $blocks []= $block;

                        $block = array(
                            'type' => 'quote',
                            'lines' => array(
                                $matches[1],
                            ),
                        );

                        continue 2;
                    }

                    break;

                case '[':

                    # reference

                    $position = strpos($outdented_line, ']:');

                    if ($position)
                    {
                        $reference = array();

                        $label = substr($outdented_line, 1, $position - 1);
                        $label = strtolower($label);

                        $substring = substr($outdented_line, $position + 2);
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

                            $reference['»'] = substr($substring, 1, $position - 1);

                            $substring = substr($substring, $position + 1);
                        }
                        else
                        {
                            $position = strpos($substring, ' ');

                            if ($position === false)
                            {
                                $reference['»'] = $substring;

                                $substring = false;
                            }
                            else
                            {
                                $reference['»'] = substr($substring, 0, $position);

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

                            $reference['#'] = substr($substring, 1, -1);
                        }

                        $this->reference_map[$label] = $reference;

                        continue 2;
                    }

                    break;

                case '`':
                case '~':

                    # fenced code block

                    if (preg_match('/^([`]{3,}|[~]{3,})[ ]*(\S+)?[ ]*$/', $outdented_line, $matches))
                    {
                        $blocks []= $block;

                        $block = array(
                            'type' => 'fenced',
                            'text' => '',
                            'fence' => $matches[1],
                        );

                        if (isset($matches[2]))
                        {
                            $block['language'] = $matches[2];
                        }

                        continue 2;
                    }

                    break;

                case '*':
                case '+':
                case '-':
                case '_':

                    # hr

                    if (preg_match('/^([-*_])([ ]{0,2}\1){2,}[ ]*$/', $outdented_line))
                    {
                        $blocks []= $block;

                        $block = array(
                            'type' => 'rule',
                        );

                        continue 2;
                    }

                    # li

                    if (preg_match('/^([*+-][ ]+)(.*)/', $outdented_line, $matches))
                    {
                        $blocks []= $block;

                        $baseline = $indentation + strlen($matches[1]);

                        $block = array(
                            'type' => 'li',
                            'indentation' => $indentation,
                            'baseline' => $baseline,
                            'marker' => '[*+-]',
                            'first' => true,
                            'last' => true,
                            'lines' => array(),
                        );

                        $block['lines'] []= preg_replace('/^[ ]{0,4}/', '', $matches[2]);

                        continue 2;
                    }
            }

            # li

            if ($outdented_line[0] <= '9' and preg_match('/^(\d+[.][ ]+)(.*)/', $outdented_line, $matches))
            {
                $blocks []= $block;

                $baseline = $indentation + strlen($matches[1]);

                $block = array(
                    'type' => 'li',
                    'indentation' => $indentation,
                    'baseline' => $baseline,
                    'marker' => '\d+[.]',
                    'first' => true,
                    'last' => true,
                    'ordered' => true,
                    'lines' => array(),
                );

                $block['lines'] []= preg_replace('/^[ ]{0,4}/', '', $matches[2]);

                continue;
            }

            # paragraph

            if ($block['type'] === 'paragraph')
            {
                if (isset($block['interrupted']))
                {
                    $blocks []= $block;

                    $block['text'] = $line;

                    unset($block['interrupted']);
                }
                else
                {
                    if ($this->breaks_enabled)
                    {
                        $block['text'] .= '  ';
                    }

                    $block['text'] .= "\n".$line;
                }
            }
            else
            {
                $blocks []= $block;

                $block = array(
                    'type' => 'paragraph',
                    'text' => $line,
                );
            }
        }

        $blocks []= $block;

        unset($blocks[0]);

        # $blocks » HTML

        $markup = '';

        foreach ($blocks as $block)
        {
            switch ($block['type'])
            {
                case 'paragraph':

                    $text = $this->parse_span_elements($block['text']);

                    if ($context === 'li' and $markup === '')
                    {
                        if (isset($block['interrupted']))
                        {
                            $markup .= "\n".'<p>'.$text.'</p>'."\n";
                        }
                        else
                        {
                            $markup .= $text;

                            if (isset($blocks[2]))
                            {
                                $markup .= "\n";
                            }
                        }
                    }
                    else
                    {
                        $markup .= '<p>'.$text.'</p>'."\n";
                    }

                    break;

                case 'quote':

                    $text = $this->parse_block_elements($block['lines']);

                    $markup .= '<blockquote>'."\n".$text.'</blockquote>'."\n";

                    break;

                case 'code':

                    $text = htmlspecialchars($block['text'], ENT_NOQUOTES, 'UTF-8');

                    $markup .= '<pre><code>'.$text.'</code></pre>'."\n";

                    break;

                case 'fenced':

                    $text = htmlspecialchars($block['text'], ENT_NOQUOTES, 'UTF-8');

                    $markup .= '<pre><code';

                    if (isset($block['language']))
                    {
                        $markup .= ' class="language-'.$block['language'].'"';
                    }

                    $markup .= '>'.$text.'</code></pre>'."\n";

                    break;

                case 'heading':

                    $text = $this->parse_span_elements($block['text']);

                    $markup .= '<h'.$block['level'].'>'.$text.'</h'.$block['level'].'>'."\n";

                    break;

                case 'rule':

                    $markup .= '<hr />'."\n";

                    break;

                case 'li':

                    if (isset($block['first']))
                    {
                        $type = isset($block['ordered']) ? 'ol' : 'ul';

                        $markup .= '<'.$type.'>'."\n";
                    }

                    if (isset($block['interrupted']) and ! isset($block['last']))
                    {
                        $block['lines'] []= '';
                    }

                    $text = $this->parse_block_elements($block['lines'], 'li');

                    $markup .= '<li>'.$text.'</li>'."\n";

                    if (isset($block['last']))
                    {
                        $type = isset($block['ordered']) ? 'ol' : 'ul';

                        $markup .= '</'.$type.'>'."\n";
                    }

                    break;

                case 'markup':

                    $markup .= $block['text']."\n";

                    break;

                default:

                    $markup .= $block['text']."\n";
            }
        }

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
                            'a' => $matches[1],
                        );

                        $offset = strlen($matches[0]);

                        if ($element['!'])
                        {
                            $offset++;
                        }

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
                            $markup .= '<img alt="'.$element['a'].'" src="'.$element['»'].'"';

                            if (isset($element['#']))
                            {
                                $markup .= ' title="'.$element['#'].'"';
                            }

                            $markup .= ' />';
                        }
                        else
                        {
                            $element['a'] = $this->parse_span_elements($element['a'], $markers);

                            $markup .= '<a href="'.$element['»'].'"';

                            if (isset($element['#']))
                            {
                                $markup .= ' title="'.$element['#'].'"';
                            }

                            $markup .= '>'.$element['a'].'</a>';
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
