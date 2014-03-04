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

    function setBreaksEnabled($breaksEnabled)
    {
        $this->breaksEnabled = $breaksEnabled;

        return $this;
    }

    /**
     * For backwards compatibility before PSR-2 naming.
     *
     * @deprecated Use setBreaksEnabled instead.
     */
    function set_breaks_enabled($breaks_enabled)
    {
        return $this->setBreaksEnabled($breaks_enabled);
    }

    private $breaksEnabled = false;

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
        $blocks = $this->findBlocks($lines);

        # iterate through blocks to build markup
        $markup = $this->compile($blocks);

        # trim line breaks
        $markup = trim($markup, "\n");

        return $markup;
    }

    #
    # Private

    private function findBlocks(array $lines, $blockContext = null)
    {
        $block = null;

        $context = null;
        $contextData = null;

        foreach ($lines as $line)
        {
            $indentedLine = $line;

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

                    $contextData = null;

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

                    if (preg_match('/^[ ]*'.$contextData['marker'].'{3,}[ ]*$/', $line))
                    {
                        $context = null;
                    }
                    else
                    {
                        if ($block['content'][0]['content'])
                        {
                            $block['content'][0]['content'] .= "\n";
                        }

                        $string = htmlspecialchars($indentedLine, ENT_NOQUOTES, 'UTF-8');

                        $block['content'][0]['content'] .= $string;
                    }

                    continue 2;

                case 'markup':

                    if (stripos($line, $contextData['start']) !== false) # opening tag
                    {
                        $contextData['depth']++;
                    }

                    if (stripos($line, $contextData['end']) !== false) # closing tag
                    {
                        if ($contextData['depth'] > 0)
                        {
                            $contextData['depth']--;
                        }
                        else
                        {
                            $context = null;
                        }
                    }

                    $block['content'] .= "\n".$indentedLine;

                    continue 2;

                case 'li':

                    if ($line === '')
                    {
                        $contextData['interrupted'] = true;

                        continue 2;
                    }

                    if ($contextData['indentation'] === $indentation and preg_match('/^'.$contextData['marker'].'[ ]+(.*)/', $line, $matches))
                    {
                        if (isset($contextData['interrupted']))
                        {
                            $nestedBlock['content'] []= '';

                            unset($contextData['interrupted']);
                        }

                        unset($nestedBlock);

                        $nestedBlock = array(
                            'name' => 'li',
                            'content type' => 'lines',
                            'content' => array(
                                $matches[1],
                            ),
                        );

                        $block['content'] []= & $nestedBlock;

                        continue 2;
                    }

                    if (empty($contextData['interrupted']))
                    {
                        $value = $line;

                        if ($indentation > $contextData['baseline'])
                        {
                            $value = str_repeat(' ', $indentation - $contextData['baseline']) . $value;
                        }

                        $nestedBlock['content'] []= $value;

                        continue 2;
                    }

                    if ($indentation > 0)
                    {
                        $nestedBlock['content'] []= '';

                        $value = $line;

                        if ($indentation > $contextData['baseline'])
                        {
                            $value = str_repeat(' ', $indentation - $contextData['baseline']) . $value;
                        }

                        $nestedBlock['content'] []= $value;

                        unset($contextData['interrupted']);

                        continue 2;
                    }

                    $context = null;

                    break;

                case 'quote':

                    if ($line === '')
                    {
                        $contextData['interrupted'] = true;

                        continue 2;
                    }

                    if (preg_match('/^>[ ]?(.*)/', $line, $matches))
                    {
                        $block['content'] []= $matches[1];

                        continue 2;
                    }

                    if (empty($contextData['interrupted']))
                    {
                        $block['content'] []= $line;

                        continue 2;
                    }

                    $context = null;

                    break;

                case 'code':

                    if ($line === '')
                    {
                        $contextData['interrupted'] = true;

                        continue 2;
                    }

                    if ($indentation >= 4)
                    {
                        if (isset($contextData['interrupted']))
                        {
                            $block['content'][0]['content'] .= "\n";

                            unset($contextData['interrupted']);
                        }

                        $block['content'][0]['content'] .= "\n";

                        $string = htmlspecialchars($line, ENT_NOQUOTES, 'UTF-8');
                        $string = str_repeat(' ', $indentation - 4) . $string;

                        $block['content'][0]['content'] .= $string;

                        continue 2;
                    }

                    $context = null;

                    break;

                case 'table':

                    if ($line === '')
                    {
                        $context = null;

                        continue 2;
                    }

                    if (strpos($line, '|') !== false)
                    {
                        $nestedBlocks = array();

                        $substring = preg_replace('/^[|][ ]*/', '', $line);
                        $substring = preg_replace('/[|]?[ ]*$/', '', $substring);

                        $parts = explode('|', $substring);

                        foreach ($parts as $index => $part)
                        {
                            $substring = trim($part);

                            $nestedBlock = array(
                                'name' => 'td',
                                'content type' => 'line',
                                'content' => $substring,
                            );

                            if (isset($contextData['alignments'][$index]))
                            {
                                $nestedBlock['attributes'] = array(
                                    'align' => $contextData['alignments'][$index],
                                );
                            }

                            $nestedBlocks []= $nestedBlock;
                        }

                        $nestedBlock = array(
                            'name' => 'tr',
                            'content type' => 'blocks',
                            'content' => $nestedBlocks,
                        );

                        $block['content'][1]['content'] []= $nestedBlock;

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

                    if (strpos($line, '|') !== false and strpos($block['content'], '|') !== false and chop($line, ' -:|') === '')
                    {
                        $values = array();

                        $substring = trim($line, ' |');

                        $parts = explode('|', $substring);

                        foreach ($parts as $part)
                        {
                            $substring = trim($part);

                            $value = null;

                            if ($substring[0] === ':')
                            {
                                $value = 'left';
                            }

                            if (substr($substring, -1) === ':')
                            {
                                $value = $value === 'left' ? 'center' : 'right';
                            }

                            $values []= $value;
                        }

                        # ~

                        $nestedBlocks = array();

                        $substring = preg_replace('/^[|][ ]*/', '', $block['content']);
                        $substring = preg_replace('/[|]?[ ]*$/', '', $substring);

                        $parts = explode('|', $substring);

                        foreach ($parts as $index => $part)
                        {
                            $substring = trim($part);

                            $nestedBlock = array(
                                'name' => 'th',
                                'content type' => 'line',
                                'content' => $substring,
                            );

                            if (isset($values[$index]))
                            {
                                $value = $values[$index];

                                $nestedBlock['attributes'] = array(
                                    'align' => $value,
                                );
                            }

                            $nestedBlocks []= $nestedBlock;
                        }

                        # ~

                        $block = array(
                            'name' => 'table',
                            'content type' => 'blocks',
                            'content' => array(),
                        );

                        $block['content'] []= array(
                            'name' => 'thead',
                            'content type' => 'blocks',
                            'content' => array(),
                        );

                        $block['content'] []= array(
                            'name' => 'tbody',
                            'content type' => 'blocks',
                            'content' => array(),
                        );

                        $block['content'][0]['content'] []= array(
                            'name' => 'tr',
                            'content type' => 'blocks',
                            'content' => array(),
                        );

                        $block['content'][0]['content'][0]['content'] = $nestedBlocks;

                        # ~

                        $context = 'table';

                        $contextData = array(
                            'alignments' => $values,
                        );

                        # ~

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
                            'content type' => null,
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
                        $string = $this->parseLine($string);

                        $block = array(
                            'name' => 'h'.$level,
                            'content type' => 'line',
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
                            $isClosing = true;

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
                                $isClosing = true;
                            }
                        }
                        elseif ( ! ctype_alpha($name))
                        {
                            break;
                        }

                        if (in_array($name, self::$textLevelElements))
                        {
                            break;
                        }

                        $blocks []= $block;

                        $block = array(
                            'name' => null,
                            'content type' => null,
                            'content' => $indentedLine,
                        );

                        if (isset($isClosing))
                        {
                            unset($isClosing);

                            continue 2;
                        }

                        $context = 'markup';
                        $contextData = array(
                            'start' => '<'.$name.'>',
                            'end' => '</'.$name.'>',
                            'depth' => 0,
                        );

                        if (stripos($line, $contextData['end']) !== false)
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
                            'content type' => 'lines',
                            'content' => array(
                                $matches[1],
                            ),
                        );

                        $context = 'quote';
                        $contextData = array();

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

                            $lastChar = substr($substring, -1);

                            if ($lastChar !== '"' and $lastChar !== "'" and $lastChar !== ')')
                            {
                                break;
                            }

                            $reference['title'] = substr($substring, 1, -1);
                        }

                        $this->referenceMap[$label] = $reference;

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
                                    'content type' => null,
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
                        $contextData = array(
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

                    unset($nestedBlock);

                    $nestedBlock = array(
                        'name' => 'li',
                        'content type' => 'lines',
                        'content' => array(
                            $matches[2],
                        ),
                    );

                    $block['content'] []= & $nestedBlock;

                    $baseline = $indentation + strlen($matches[1]);

                    $marker = $line[0] >= '0' ? '[0-9]+[.]' : '[*+-]';

                    $context = 'li';
                    $contextData = array(
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
                    'content type' => 'line',
                    'content' => $line,
                );

                if ($blockContext === 'li' and empty($blocks[1]))
                {
                    $block['name'] = null;
                }

                $context = 'paragraph';
            }
        }

        if ($blockContext === 'li' and $block['name'] === null)
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
                case null:

                    $markup .= $block['content'];

                    break;

                case 'line':

                    $markup .= $this->parseLine($block['content']);

                    break;

                case 'lines':

                    $result = $this->findBlocks($block['content'], $block['name']);

                    if (is_string($result)) # dense li
                    {
                        $markup .= $this->parseLine($result);

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

    private function parseLine($text, $markers = array("  \n", '![', '&', '*', '<', '[', '\\', '_', '`', 'http', '~~'))
    {
        if (isset($text[1]) === false or $markers === array())
        {
            return $text;
        }

        # ~

        $markup = '';

        while ($markers)
        {
            $closestMarker = null;
            $closestMarkerIndex = 0;
            $closestMarkerPosition = null;

            foreach ($markers as $index => $marker)
            {
                $markerPosition = strpos($text, $marker);

                if ($markerPosition === false)
                {
                    unset($markers[$index]);

                    continue;
                }

                if ($closestMarker === null or $markerPosition < $closestMarkerPosition)
                {
                    $closestMarker = $marker;
                    $closestMarkerIndex = $index;
                    $closestMarkerPosition = $markerPosition;
                }
            }

            # ~

            if ($closestMarker === null or isset($text[$closestMarkerPosition + 1]) === false)
            {
                $markup .= $text;

                break;
            }
            else
            {
                $markup .= substr($text, 0, $closestMarkerPosition);
            }

            $text = substr($text, $closestMarkerPosition);

            # ~

            unset($markers[$closestMarkerIndex]);

            # ~

            switch ($closestMarker)
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

                        $remainingText = substr($text, $offset);

                        if ($remainingText[0] === '(' and preg_match('/\([ ]*(.*?)(?:[ ]+[\'"](.+?)[\'"])?[ ]*\)/', $remainingText, $matches))
                        {
                            $element['link'] = $matches[1];

                            if (isset($matches[2]))
                            {
                                $element['title'] = $matches[2];
                            }

                            $offset += strlen($matches[0]);
                        }
                        elseif ($this->referenceMap)
                        {
                            $reference = $element['text'];

                            if (preg_match('/^\s*\[(.*?)\]/', $remainingText, $matches))
                            {
                                $reference = $matches[1] === '' ? $element['text'] : $matches[1];

                                $offset += strlen($matches[0]);
                            }

                            $reference = strtolower($reference);

                            if (isset($this->referenceMap[$reference]))
                            {
                                $element['link'] = $this->referenceMap[$reference]['link'];

                                if (isset($this->referenceMap[$reference]['title']))
                                {
                                    $element['title'] = $this->referenceMap[$reference]['title'];
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
                            $element['text'] = $this->parseLine($element['text'], $markers);

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
                        $markup .= $closestMarker;

                        $offset = $closestMarker === '![' ? 2 : 1;
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

                    if ($text[1] === $closestMarker and preg_match(self::$strongRegex[$closestMarker], $text, $matches))
                    {
                        $markers[$closestMarkerIndex] = $closestMarker;
                        $matches[1] = $this->parseLine($matches[1], $markers);

                        $markup .= '<strong>'.$matches[1].'</strong>';
                    }
                    elseif (preg_match(self::$emRegex[$closestMarker], $text, $matches))
                    {
                        $markers[$closestMarkerIndex] = $closestMarker;
                        $matches[1] = $this->parseLine($matches[1], $markers);

                        $markup .= '<em>'.$matches[1].'</em>';
                    }

                    if (isset($matches) and $matches)
                    {
                        $offset = strlen($matches[0]);
                    }
                    else
                    {
                        $markup .= $closestMarker;

                        $offset = 1;
                    }

                    break;

                case '<':

                    if (strpos($text, '>') !== false)
                    {
                        if ($text[1] === 'h' and preg_match('/^<(https?:[\/]{2}[^\s]+?)>/i', $text, $matches))
                        {
                            $elementUrl = $matches[1];
                            $elementUrl = str_replace('&', '&amp;', $elementUrl);
                            $elementUrl = str_replace('<', '&lt;', $elementUrl);

                            $markup .= '<a href="'.$elementUrl.'">'.$elementUrl.'</a>';

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

                    if (in_array($text[1], self::$specialCharacters))
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
                        $elementText = $matches[2];
                        $elementText = htmlspecialchars($elementText, ENT_NOQUOTES, 'UTF-8');

                        $markup .= '<code>'.$elementText.'</code>';

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
                        $elementUrl = $matches[0];
                        $elementUrl = str_replace('&', '&amp;', $elementUrl);
                        $elementUrl = str_replace('<', '&lt;', $elementUrl);

                        $markup .= '<a href="'.$elementUrl.'">'.$elementUrl.'</a>';

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
                        $matches[1] = $this->parseLine($matches[1], $markers);

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

            $markers[$closestMarkerIndex] = $closestMarker;
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

    private $referenceMap = array();

    #
    # Read-only

    private static $strongRegex = array(
        '*' => '/^[*]{2}((?:[^*]|[*][^*]*[*])+?)[*]{2}(?![*])/s',
        '_' => '/^__((?:[^_]|_[^_]*_)+?)__(?!_)/us',
    );

    private static $emRegex = array(
        '*' => '/^[*]((?:[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
        '_' => '/^_((?:[^_]|__[^_]*__)+?)_(?!_)\b/us',
    );

    private static $specialCharacters = array(
        '\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '>', '#', '+', '-', '.', '!',
    );

    private static $textLevelElements = array(
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
