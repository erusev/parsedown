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
    # Methods
    #

    # Parsedown tries to read Markdown texts the way humans do. First, it breaks
    # texts into lines. Then, it identifies blocks by looking at how these lines
    # start and relate to each other. Finally, it identifies inline elements.

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
    #

    private function parse_block_elements(array $lines, $context = '')
    {
        $blocks = array();

        $block = array(
            'type' => '',
        );

        foreach ($lines as $line)
        {
            # fenced blocks

            switch ($block['type'])
            {
                case 'fenced block':

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

                case 'block-level markup':

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

            # *

            $deindented_line = ltrim($line);

            if ($deindented_line === '')
            {
                $block['interrupted'] = true;

                continue;
            }

            # composite blocks

            switch ($block['type'])
            {
                case 'blockquote':

                    if ( ! isset($block['interrupted']))
                    {
                        $line = preg_replace('/^[ ]*>[ ]?/', '', $line);

                        $block['lines'] []= $line;

                        continue 2;
                    }

                    break;

                case 'li':

                    if (preg_match('/^([ ]{0,3})(\d+[.]|[*+-])[ ](.*)/', $line, $matches))
                    {
                        if ($block['indentation'] !== $matches[1])
                        {
                            $block['lines'] []= $line;
                        }
                        else
                        {
                            unset($block['last']);

                            $blocks []= $block;

                            unset($block['first']);

                            $block['last'] = true;

                            $block['lines'] = array(
                                preg_replace('/^[ ]{0,4}/', '', $matches[3]),
                            );
                        }

                        continue 2;
                    }

                    if (isset($block['interrupted']))
                    {
                        if ($line[0] === ' ')
                        {
                            $block['lines'] []= '';

                            $line = preg_replace('/^[ ]{0,4}/', '', $line);

                            $block['lines'] []= $line;

                            unset($block['interrupted']);

                            continue 2;
                        }
                    }
                    else
                    {
                        $line = preg_replace('/^[ ]{0,4}/', '', $line);

                        $block['lines'] []= $line;

                        continue 2;
                    }

                    break;
            }

            # indentation sensitive types

            switch ($line[0])
            {
                case ' ':

                    # code block

                    if (isset($line[3]) and $line[3] === ' ' and $line[2] === ' ' and $line[1] === ' ')
                    {
                        $code_line = substr($line, 4);

                        if ($block['type'] === 'code block')
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
                                'type' => 'code block',
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

                    # setext heading

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

            switch ($deindented_line[0])
            {
                case '<':

                    $position = strpos($deindented_line, '>');

                    if ($position > 1) # tag
                    {
                        $name = substr($deindented_line, 1, $position - 1);
                        $name = chop($name);

                        if (substr($name, -1) === '/')
                        {
                            $self_closing = true;

                            $name = substr($name, 0, -1);
                        }

                        $position = strpos($name, ' ');

                        if ($position)
                        {
                            $name = substr($name, 0, $position);
                        }

                        if ( ! ctype_alpha($name))
                        {
                            break;
                        }

                        if (in_array($name, $this->inline_tags))
                        {
                            break;
                        }

                        $blocks []= $block;

                        if (isset($self_closing))
                        {
                            $block = array(
                                'type' => 'self-closing tag',
                                'text' => $deindented_line,
                            );

                            unset($self_closing);

                            continue 2;
                        }

                        $block = array(
                            'type' => 'block-level markup',
                            'text' => $deindented_line,
                            'start' => '<'.$name.'>',
                            'end' => '</'.$name.'>',
                            'depth' => 0,
                        );

                        if (strpos($deindented_line, $block['end']))
                        {
                            $block['closed'] = true;
                        }

                        continue 2;
                    }

                    break;

                case '>':

                    # quote

                    if (preg_match('/^>[ ]?(.*)/', $deindented_line, $matches))
                    {
                        $blocks []= $block;

                        $block = array(
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
                        $blocks []= $block;

                        $block = array(
                            'type' => 'fenced block',
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

                    if (preg_match('/^([-*_])([ ]{0,2}\1){2,}[ ]*$/', $deindented_line))
                    {
                        $blocks []= $block;

                        $block = array(
                            'type' => 'hr',
                        );

                        continue 2;
                    }

                    # li

                    if (preg_match('/^([ ]*)[*+-][ ](.*)/', $line, $matches))
                    {
                        $blocks []= $block;

                        $block = array(
                            'type' => 'li',
                            'ordered' => false,
                            'indentation' => $matches[1],
                            'first' => true,
                            'last' => true,
                            'lines' => array(
                                preg_replace('/^[ ]{0,4}/', '', $matches[2]),
                            ),
                        );

                        continue 2;
                    }
            }

            # li

            if ($deindented_line[0] <= '9' and $deindented_line[0] >= '0' and preg_match('/^([ ]*)\d+[.][ ](.*)/', $line, $matches))
            {
                $blocks []= $block;

                $block = array(
                    'type' => 'li',
                    'ordered' => true,
                    'indentation' => $matches[1],
                    'first' => true,
                    'last' => true,
                    'lines' => array(
                        preg_replace('/^[ ]{0,4}/', '', $matches[2]),
                    ),
                );

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

        #
        # ~
        #

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

                case 'blockquote':

                    $text = $this->parse_block_elements($block['lines']);

                    $markup .= '<blockquote>'."\n".$text.'</blockquote>'."\n";

                    break;

                case 'code block':

                    $text = htmlspecialchars($block['text'], ENT_NOQUOTES, 'UTF-8');

                    $markup .= '<pre><code>'.$text.'</code></pre>'."\n";

                    break;

                case 'fenced block':

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

                case 'hr':

                    $markup .= '<hr />'."\n";

                    break;

                case 'li':

                    if (isset($block['first']))
                    {
                        $type = $block['ordered'] ? 'ol' : 'ul';

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
                        $type = $block['ordered'] ? 'ol' : 'ul';

                        $markup .= '</'.$type.'>'."\n";
                    }

                    break;

                case 'block-level markup':

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

                    if (in_array($text[1], $this->special_characters))
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

                    if (preg_match('/^(`+)(.+?)\1(?!`)/', $text, $matches))
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

        return $markup;
    }

    #
    # Fields
    #

    private $reference_map = array();

    #
    # Read-only
    #

    private $inline_tags = array(
        'a', 'abbr', 'acronym', 'b', 'bdo', 'big', 'br', 'button',
        'cite', 'code', 'dfn', 'em', 'i', 'img', 'input', 'kbd',
        'label', 'map', 'object', 'q', 'samp', 'script', 'select', 'small',
        'span', 'strong', 'sub', 'sup', 'textarea', 'tt', 'var',
    );

    private $special_characters = array('\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '>', '#', '+', '-', '.', '!');

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
}
