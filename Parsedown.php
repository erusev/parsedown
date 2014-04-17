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

    # Markdown is intended to be easy-to-read by humans - those of us who read
    # line by line, left to right, top to bottom. In order to take advantage of
    # this, Parsedown tries to read in a similar way. It breaks texts into
    # lines, it iterates through them and it looks at how they start and relate
    # to each other.

    #
    # ~

    function text($text)
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
        $markup = $this->lines($lines);

        # trim line breaks
        $markup = trim($markup, "\n");

        # clean up
        $this->references = array();

        return $markup;
    }

    #
    # Setters
    #

    function setBreaksEnabled($breaksEnabled)
    {
        $this->breaksEnabled = $breaksEnabled;

        return $this;
    }

    private $breaksEnabled;

    #
    # Blocks
    #

    protected $blockMarkers = array(
        '#' => array('Atx'),
        '*' => array('Rule', 'List'),
        '+' => array('List'),
        '-' => array('Setext', 'Table', 'Rule', 'List'),
        '0' => array('List'),
        '1' => array('List'),
        '2' => array('List'),
        '3' => array('List'),
        '4' => array('List'),
        '5' => array('List'),
        '6' => array('List'),
        '7' => array('List'),
        '8' => array('List'),
        '9' => array('List'),
        ':' => array('Table'),
        '<' => array('Markup'),
        '=' => array('Setext'),
        '>' => array('Quote'),
        '[' => array('Reference'),
        '_' => array('Rule'),
        '`' => array('FencedCode'),
        '|' => array('Table'),
        '~' => array('FencedCode'),
    );

    # Draft
    protected $definitionMarkers = array(
        '[' => array('Reference'),
    );

    protected $unmarkedBlockTypes = array(
        'CodeBlock',
    );

    private function lines(array $lines)
    {
        $CurrentBlock = null;

        foreach ($lines as $line)
        {
            $indent = 0;

            while (true)
            {
                if (isset($line[$indent]))
                {
                    if ($line[$indent] === ' ')
                    {
                        $indent ++;
                    }
                    else
                    {
                        break;
                    }
                }
                else # blank line
                {
                    if (isset($CurrentBlock))
                    {
                        $CurrentBlock['interrupted'] = true;
                    }

                    continue 2;
                }
            }

            $text = $indent > 0 ? substr($line, $indent) : $line;

            # ~

            $Line = array('body' => $line, 'indent' => $indent, 'text' => $text);

            # Multiline block types define "addTo" methods.

            if (isset($CurrentBlock['incomplete']))
            {
                $Block = $this->{'addTo'.$CurrentBlock['type']}($Line, $CurrentBlock);

                if (isset($Block))
                {
                    $CurrentBlock = $Block;

                    continue;
                }
                else
                {
                    unset($CurrentBlock['incomplete']);

                    if (method_exists($this, 'complete'.$CurrentBlock['type']))
                    {
                        $CurrentBlock = $this->{'complete'.$CurrentBlock['type']}($CurrentBlock);
                    }
                }
            }

            # ~

            $blockTypes = $this->unmarkedBlockTypes;

            $marker = $text[0];

            if (isset($this->blockMarkers[$marker]))
            {
                foreach ($this->blockMarkers[$marker] as $blockType)
                {
                    $blockTypes []= $blockType;
                }
            }

            #
            # ~

            foreach ($blockTypes as $blockType)
            {
                # Block types define "identify" methods.

                $Block = $this->{'identify'.$blockType}($Line, $CurrentBlock);

                if (isset($Block))
                {
                    $Block['type'] = $blockType;

                    if ( ! isset($Block['identified'])) # »
                    {
                        $elements []= $CurrentBlock['element'];

                        $Block['identified'] = true;
                    }

                    # Multiline block types define "addTo" methods.

                    if (method_exists($this, 'addTo'.$blockType))
                    {
                        $Block['incomplete'] = true;
                    }

                    $CurrentBlock = $Block;

                    continue 2;
                }
            }

            # ~

            if ($CurrentBlock['type'] === 'Paragraph' and ! isset($CurrentBlock['interrupted']))
            {
                $CurrentBlock['element']['text'] .= "\n".$text;
            }
            else
            {
                $elements []= $CurrentBlock['element'];

                $CurrentBlock = array(
                    'type' => 'Paragraph',
                    'identified' => true,
                    'element' => array(
                        'name' => 'p',
                        'text' => $text,
                        'handler' => 'line',
                    ),
                );
            }
        }

        $elements []= $CurrentBlock['element'];

        unset($elements[0]);

        # ~

        $markup = $this->elements($elements);

        # ~

        return $markup;
    }

    #
    # Atx

    protected function identifyAtx($Line)
    {
        if (isset($Line['text'][1]))
        {
            $level = 1;

            while (isset($Line['text'][$level]) and $Line['text'][$level] === '#')
            {
                $level ++;
            }

            $text = trim($Line['text'], '# ');

            $Block = array(
                'element' => array(
                    'name' => 'h'.$level,
                    'text' => $text,
                    'handler' => 'line',
                ),
            );

            return $Block;
        }
    }

    #
    # Rule

    protected function identifyRule($Line)
    {
        if (preg_match('/^(['.$Line['text'][0].'])([ ]{0,2}\1){2,}[ ]*$/', $Line['text']))
        {
            $Block = array(
                'element' => array(
                    'name' => 'hr'
                ),
            );

            return $Block;
        }
    }

    #
    # Reference

    protected function identifyReference($Line)
    {
        if (preg_match('/^\[(.+?)\]:[ ]*<?(\S+?)>?(?:[ ]+["\'(](.+)["\')])?[ ]*$/', $Line['text'], $matches))
        {
            $label = strtolower($matches[1]);

            $this->references[$label] = array(
                'url' => $matches[2],
            );

            if (isset($matches[3]))
            {
                $this->references[$label]['title'] = $matches[3];
            }

            $Block = array(
                'element' => null,
            );

            return $Block;
        }
    }

    #
    # Setext

    protected function identifySetext($Line, array $Block = null)
    {
        if ( ! isset($Block) or $Block['type'] !== 'Paragraph' or isset($Block['interrupted']))
        {
            return;
        }

        if (chop($Line['text'], $Line['text'][0]) === '')
        {
            $Block['element']['name'] = $Line['text'][0] === '=' ? 'h1' : 'h2';

            return $Block;
        }
    }

    #
    # Markup

    protected function identifyMarkup($Line)
    {
        if (preg_match('/^<(\w[\w\d]*)(?:[ ][^>\/]*)?(\/?)[ ]*>/', $Line['text'], $matches))
        {
            if (in_array($matches[1], $this->textLevelElements))
            {
                return;
            }

            $Block = array(
                'element' => $Line['body'],
            );

            if ($matches[2] or $matches[1] === 'hr' or preg_match('/<\/'.$matches[1].'>[ ]*$/', $Line['text']))
            {
                $Block['closed'] = true;
            }
            else
            {
                $Block['depth'] = 0;
                $Block['start'] = '<'.$matches[1].'>';
                $Block['end'] = '</'.$matches[1].'>';
            }

            return $Block;
        }
    }

    protected function addToMarkup($Line, array $Block)
    {
        if (isset($Block['closed']))
        {
            return;
        }

        if (stripos($Line['text'], $Block['start']) !== false) # opening tag
        {
            $Block['depth'] ++;
        }

        if (stripos($Line['text'], $Block['end']) !== false) # closing tag
        {
            if ($Block['depth'] > 0)
            {
                $Block['depth'] --;
            }
            else
            {
                $Block['closed'] = true;
            }
        }

        $Block['element'] .= "\n".$Line['body'];

        return $Block;
    }

    #
    # Fenced Code

    protected function identifyFencedCode($Line)
    {
        if (preg_match('/^(['.$Line['text'][0].']{3,})[ ]*(\w+)?[ ]*$/', $Line['text'], $matches))
        {
            $Element = array(
                'name' => 'code',
                'text' => '',
            );

            if (isset($matches[2]))
            {
                $class = 'language-'.$matches[2];

                $Element['attributes'] = array(
                    'class' => $class,
                );
            }

            $Block = array(
                'char' => $Line['text'][0],
                'element' => array(
                    'name' => 'pre',
                    'handler' => 'element',
                    'text' => $Element,
                ),
            );

            return $Block;
        }
    }

    protected function addToFencedCode($Line, $Block)
    {
        if (isset($Block['complete']))
        {
            return;
        }

        if (isset($Block['interrupted']))
        {
            $Block['element']['text']['text'] .= "\n";

            unset($Block['interrupted']);
        }

        if (preg_match('/^'.$Block['char'].'{3,}[ ]*$/', $Line['text']))
        {
            $Block['element']['text']['text'] = substr($Block['element']['text']['text'], 1);

            $Block['complete'] = true;

            return $Block;
        }

        $string = htmlspecialchars($Line['body'], ENT_NOQUOTES, 'UTF-8');

        $Block['element']['text']['text'] .= "\n".$string;;

        return $Block;
    }

    #
    # List

    protected function identifyList($Line)
    {
        list($name, $pattern) = $Line['text'][0] <= '-' ? array('ul', '[*+-]') : array('ol', '[0-9]+[.]');

        if (preg_match('/^('.$pattern.'[ ]+)(.*)/', $Line['text'], $matches))
        {
            $Block = array(
                'indent' => $Line['indent'],
                'pattern' => $pattern,
                'element' => array(
                    'name' => $name,
                    'handler' => 'elements',
                ),
            );

            $Block['li'] = array(
                'name' => 'li',
                'handler' => 'li',
                'text' => array(
                    $matches[2],
                ),
            );

            $Block['element']['text'] []= & $Block['li'];

            return $Block;
        }
    }

    protected function addToList($Line, array $Block)
    {
        if ($Block['indent'] === $Line['indent'] and preg_match('/^'.$Block['pattern'].'[ ]+(.*)/', $Line['text'], $matches))
        {
            if (isset($Block['interrupted']))
            {
                $Block['li']['text'] []= '';

                unset($Block['interrupted']);
            }

            unset($Block['li']);

            $Block['li'] = array(
                'name' => 'li',
                'handler' => 'li',
                'text' => array(
                    $matches[1],
                ),
            );

            $Block['element']['text'] []= & $Block['li'];

            return $Block;
        }

        if ( ! isset($Block['interrupted']))
        {
            $text = preg_replace('/^[ ]{0,2}/', '', $Line['body']);

            $Block['li']['text'] []= $text;

            return $Block;
        }

        if ($Line['indent'] > 0)
        {
            $Block['li']['text'] []= '';

            $text = preg_replace('/^[ ]{0,2}/', '', $Line['body']);

            $Block['li']['text'] []= $text;

            unset($Block['interrupted']);

            return $Block;
        }
    }

    #
    # Quote

    protected function identifyQuote($Line)
    {
        if (preg_match('/^>[ ]?(.*)/', $Line['text'], $matches))
        {
            $Block = array(
                'element' => array(
                    'name' => 'blockquote',
                    'handler' => 'lines',
                    'text' => (array) $matches[1],
                ),
            );

            return $Block;
        }
    }

    protected function addToQuote($Line, array $Block)
    {
        if ($Line['text'][0] === '>' and preg_match('/^>[ ]?(.*)/', $Line['text'], $matches))
        {
            if (isset($Block['interrupted']))
            {
                $Block['element']['text'] []= '';
            }

            $Block['element']['text'] []= $matches[1];

            return $Block;
        }

        if ( ! isset($Block['interrupted']))
        {
            $Block['element']['text'] []= $Line['text'];

            return $Block;
        }
    }

    #
    # Table

    protected function identifyTable($Line, array $Block = null)
    {
        if ( ! isset($Block) or $Block['type'] !== 'Paragraph' or isset($Block['interrupted']))
        {
            return;
        }

        if (strpos($Block['element']['text'], '|') !== false and chop($Line['text'], ' -:|') === '')
        {
            $alignments = array();

            $divider = $Line['text'];

            $divider = trim($divider);
            $divider = trim($divider, '|');

            $dividerCells = explode('|', $divider);

            foreach ($dividerCells as $dividerCell)
            {
                $dividerCell = trim($dividerCell);

                if ($dividerCell === '')
                {
                    continue;
                }

                $alignment = null;

                if ($dividerCell[0] === ':')
                {
                    $alignment = 'left';
                }

                if (substr($dividerCell, -1) === ':')
                {
                    $alignment = $alignment === 'left' ? 'center' : 'right';
                }

                $alignments []= $alignment;
            }

            # ~

            $HeaderElements = array();

            $header = $Block['element']['text'];

            $header = trim($header);
            $header = trim($header, '|');

            $headerCells = explode('|', $header);

            foreach ($headerCells as $index => $headerCell)
            {
                $headerCell = trim($headerCell);

                $HeaderElement = array(
                    'name' => 'th',
                    'text' => $headerCell,
                    'handler' => 'line',
                );

                if (isset($alignments[$index]))
                {
                    $alignment = $alignments[$index];

                    $HeaderElement['attributes'] = array(
                        'align' => $alignment,
                    );
                }

                $HeaderElements []= $HeaderElement;
            }

            # ~

            $Block = array(
                'alignments' => $alignments,
                'identified' => true,
                'element' => array(
                    'name' => 'table',
                    'handler' => 'elements',
                ),
            );

            $Block['element']['text'] []= array(
                'name' => 'thead',
                'handler' => 'elements',
            );

            $Block['element']['text'] []= array(
                'name' => 'tbody',
                'handler' => 'elements',
                'text' => array(),
            );

            $Block['element']['text'][0]['text'] []= array(
                'name' => 'tr',
                'handler' => 'elements',
                'text' => $HeaderElements,
            );

            return $Block;
        }
    }

    protected function addToTable($Line, array $Block)
    {
        if ($Line['text'][0] === '|' or strpos($Line['text'], '|'))
        {
            $Elements = array();

            $row = $Line['text'];

            $row = trim($row);
            $row = trim($row, '|');

            $cells = explode('|', $row);

            foreach ($cells as $index => $cell)
            {
                $cell = trim($cell);

                $Element = array(
                    'name' => 'td',
                    'handler' => 'line',
                    'text' => $cell,
                );

                if (isset($Block['alignments'][$index]))
                {
                    $Element['attributes'] = array(
                        'align' => $Block['alignments'][$index],
                    );
                }

                $Elements []= $Element;
            }

            $Element = array(
                'name' => 'tr',
                'handler' => 'elements',
                'text' => $Elements,
            );

            $Block['element']['text'][1]['text'] []= $Element;

            return $Block;
        }
    }

    #
    # Code

    protected function identifyCodeBlock($Line)
    {
        if ($Line['indent'] >= 4)
        {
            $text = substr($Line['body'], 4);
            $text = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');

            $Block = array(
                'element' => array(
                    'name' => 'pre',
                    'handler' => 'element',
                    'text' => array(
                        'name' => 'code',
                        'text' => $text,
                    ),
                ),
            );

            return $Block;
        }
    }

    protected function addToCodeBlock($Line, $Block)
    {
        if ($Line['indent'] >= 4)
        {
            if (isset($Block['interrupted']))
            {
                $Block['element']['text']['text'] .= "\n";

                unset($Block['interrupted']);
            }

            $Block['element']['text']['text'] .= "\n";

            $text = substr($Line['body'], 4);
            $text = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');

            $Block['element']['text']['text'] .= $text;

            return $Block;
        }
    }

    #
    # ~
    #

    private function element(array $Element)
    {
        $markup = '<'.$Element['name'];

        if (isset($Element['attributes']))
        {
            foreach ($Element['attributes'] as $name => $value)
            {
                $markup .= ' '.$name.'="'.$value.'"';
            }
        }

        if (isset($Element['text']))
        {
            $markup .= '>';

            if (isset($Element['handler']))
            {
                $markup .= $this->$Element['handler']($Element['text']);
            }
            else
            {
                $markup .= $Element['text'];
            }

            $markup .= '</'.$Element['name'].'>';
        }
        else
        {
            $markup .= ' />';
        }

        return $markup;
    }

    private function elements(array $Elements)
    {
        $markup = '';

        foreach ($Elements as $Element)
        {
            if ($Element === null)
            {
                continue;
            }

            $markup .= "\n";

            if (is_string($Element)) # because of markup
            {
                $markup .= $Element;

                continue;
            }

            $markup .= $this->element($Element);
        }

        $markup .= "\n";

        return $markup;
    }

    #
    # Spans
    #

    protected $spanMarkers = array(
        '!' => array('Link'), # ?
        '&' => array('Ampersand'),
        '*' => array('Emphasis'),
        '/' => array('Url'),
        '<' => array('UrlTag', 'EmailTag', 'Tag', 'LessThan'),
        '[' => array('Link'),
        '_' => array('Emphasis'),
        '`' => array('InlineCode'),
        '~' => array('Strikethrough'),
        '\\' => array('EscapeSequence'),
    );

    protected $spanMarkerList = '*_!&[</`~\\';

    public function line($text)
    {
        $markup = '';

        $remainder = $text;

        $markerPosition = 0;

        while ($markedExcerpt = strpbrk($remainder, $this->spanMarkerList))
        {
            $marker = $markedExcerpt[0];

            $markerPosition += strpos($remainder, $marker);

            foreach ($this->spanMarkers[$marker] as $spanType)
            {
                $handler = 'identify'.$spanType;

                $Span = $this->$handler($markedExcerpt, $text);

                if (isset($Span))
                {
                    # The identified span can be ahead of the marker.

                    if (isset($Span['position']) and $Span['position'] > $markerPosition)
                    {
                        continue;
                    }

                    # Spans that start at the position of their marker don't have to set a position.

                    if ( ! isset($Span['position']))
                    {
                        $Span['position'] = $markerPosition;
                    }

                    $unmarkedText = substr($text, 0, $Span['position']);

                    $markup .= $this->readPlainText($unmarkedText);

                    $markup .= isset($Span['element']) ? $this->element($Span['element']) : $Span['markup'];

                    $text = substr($text, $Span['position'] + $Span['extent']);

                    $remainder = $text;

                    $markerPosition = 0;

                    continue 2;
                }
            }

            $remainder = substr($markedExcerpt, 1);

            $markerPosition ++;
        }

        $markup .= $this->readPlainText($text);

        return $markup;
    }

    #
    # ~
    #

    protected function identifyUrl($excerpt, $text)
    {
        if ( ! isset($excerpt[1]) or $excerpt[1] !== '/')
        {
            return;
        }

        if (preg_match('/\bhttps?:[\/]{2}[^\s]+\b\/*/ui', $text, $matches, PREG_OFFSET_CAPTURE))
        {
            $url = str_replace(array('&', '<'), array('&amp;', '&lt;'), $matches[0][0]);

            return array(
                'extent' => strlen($matches[0][0]),
                'position' => $matches[0][1],
                'element' => array(
                    'name' => 'a',
                    'text' => $url,
                    'attributes' => array(
                        'href' => $url,
                    ),
                ),
            );
        }
    }

    protected function identifyAmpersand($excerpt)
    {
        if ( ! preg_match('/^&#?\w+;/', $excerpt))
        {
            return array(
                'markup' => '&amp;',
                'extent' => 1,
            );
        }
    }

    protected function identifyStrikethrough($excerpt)
    {
        if ( ! isset($excerpt[1]))
        {
            return;
        }

        if ($excerpt[1] === $excerpt[0] and preg_match('/^~~(?=\S)(.+?)(?<=\S)~~/', $excerpt, $matches))
        {
            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'del',
                    'text' => $matches[1],
                    'handler' => 'line',
                ),
            );
        }
    }

    protected function identifyEscapeSequence($excerpt)
    {
        if (in_array($excerpt[1], $this->specialCharacters))
        {
            return array(
                'markup' => $excerpt[1],
                'extent' => 2,
            );
        }
    }

    protected function identifyLessThan()
    {
        return array(
            'markup' => '&lt;',
            'extent' => 1,
        );
    }

    protected function identifyUrlTag($excerpt)
    {
        if (strpos($excerpt, '>') !== false and preg_match('/^<(https?:[\/]{2}[^\s]+?)>/i', $excerpt, $matches))
        {
            $url = str_replace(array('&', '<'), array('&amp;', '&lt;'), $matches[1]);

            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'a',
                    'text' => $url,
                    'attributes' => array(
                        'href' => $url,
                    ),
                ),
            );
        }
    }

    protected function identifyEmailTag($excerpt)
    {
        if (strpos($excerpt, '>') !== false and preg_match('/<(\S+?@\S+?)>/', $excerpt, $matches))
        {
            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'a',
                    'text' => $matches[1],
                    'attributes' => array(
                        'href' => 'mailto:'.$matches[1],
                    ),
                ),
            );
        }
    }

    protected function identifyTag($excerpt)
    {
        if (strpos($excerpt, '>') !== false and preg_match('/^<\/?\w.*?>/', $excerpt, $matches))
        {
            return array(
                'markup' => $matches[0],
                'extent' => strlen($matches[0]),
            );
        }
    }

    protected function identifyInlineCode($excerpt)
    {
        $marker = $excerpt[0];

        if (preg_match('/^('.$marker.'+)[ ]*(.+?)[ ]*(?<!'.$marker.')\1(?!'.$marker.')/', $excerpt, $matches))
        {
            $text = $matches[2];
            $text = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');

            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'code',
                    'text' => $text,
                ),
            );
        }
    }

    protected function identifyLink($excerpt)
    {
        $extent = $excerpt[0] === '!' ? 1 : 0;

        if (strpos($excerpt, ']') and preg_match('/\[((?:[^][]|(?R))*)\]/', $excerpt, $matches))
        {
            $Link = array('text' => $matches[1], 'label' => strtolower($matches[1]));

            $extent += strlen($matches[0]);

            $substring = substr($excerpt, $extent);

            if (preg_match('/^\s*\[(.+?)\]/', $substring, $matches))
            {
                $Link['label'] = strtolower($matches[1]);

                if (isset($this->references[$Link['label']]))
                {
                    $Link += $this->references[$Link['label']];

                    $extent += strlen($matches[0]);
                }
                else
                {
                    return;
                }
            }
            elseif ($this->references and isset($this->references[$Link['label']]))
            {
                $Link += $this->references[$Link['label']];

                if (preg_match('/^[ ]*\[\]/', $substring, $matches))
                {
                    $extent += strlen($matches[0]);
                }
            }
            elseif (preg_match('/^\([ ]*(.*?)(?:[ ]+[\'"](.+?)[\'"])?[ ]*\)/', $substring, $matches))
            {
                $Link['url'] = $matches[1];

                if (isset($matches[2]))
                {
                    $Link['title'] = $matches[2];
                }

                $extent += strlen($matches[0]);
            }
            else
            {
                return;
            }
        }
        else
        {
            return;
        }

        $url = str_replace(array('&', '<'), array('&amp;', '&lt;'), $Link['url']);

        if ($excerpt[0] === '!')
        {
            $Element = array(
                'name' => 'img',
                'attributes' => array(
                    'alt' => $Link['text'],
                    'src' => $url,
                ),
            );
        }
        else
        {
            $Element = array(
                'name' => 'a',
                'handler' => 'line',
                'text' => $Link['text'],
                'attributes' => array(
                    'href' => $url,
                ),
            );
        }

        if (isset($Link['title']))
        {
            $Element['attributes']['title'] = $Link['title'];
        }

        return array(
            'extent' => $extent,
            'element' => $Element,
        );
    }

    protected function identifyEmphasis($excerpt)
    {
        if ( ! isset($excerpt[1]))
        {
            return;
        }

        $marker = $excerpt[0];

        if ($excerpt[1] === $marker and preg_match($this->strongRegex[$marker], $excerpt, $matches))
        {
            $emphasis = 'strong';
        }
        elseif (preg_match($this->emRegex[$marker], $excerpt, $matches))
        {
            $emphasis = 'em';
        }
        else
        {
            return;
        }

        return array(
            'extent' => strlen($matches[0]),
            'element' => array(
                'name' => $emphasis,
                'handler' => 'line',
                'text' => $matches[1],
            ),
        );
    }

    #
    # ~

    protected function readPlainText($text)
    {
        $breakMarker = $this->breaksEnabled ? "\n" : "  \n";

        $text = str_replace($breakMarker, "<br />\n", $text);

        return $text;
    }

    #
    # ~
    #

    protected function li($lines)
    {
        $markup = $this->lines($lines);

        $trimmedMarkup = trim($markup);

        if ( ! in_array('', $lines) and substr($trimmedMarkup, 0, 3) === '<p>')
        {
            $markup = $trimmedMarkup;
            $markup = substr($markup, 3);

            $position = strpos($markup, "</p>");

            $markup = substr_replace($markup, '', $position, 4);
        }

        return $markup;
    }

    #
    # Multiton
    #

    static function instance($name = 'default')
    {
        if (isset(self::$instances[$name]))
        {
            return self::$instances[$name];
        }

        $instance = new self();

        self::$instances[$name] = $instance;

        return $instance;
    }

    private static $instances = array();

    #
    # Deprecated Methods
    #

    /**
     * @deprecated in favor of "text"
     */
    function parse($text)
    {
        $markup = $this->text($text);

        return $markup;
    }

    #
    # Fields
    #

    protected $references = array(); # » Definitions['reference']

    #
    # Read-only

    protected $specialCharacters = array(
        '\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '>', '#', '+', '-', '.', '!',
    );

    protected $strongRegex = array(
        '*' => '/^[*]{2}((?:[^*]|[*][^*]*[*])+?)[*]{2}(?![*])/s',
        '_' => '/^__((?:[^_]|_[^_]*_)+?)__(?!_)/us',
    );

    protected $emRegex = array(
        '*' => '/^[*]((?:[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
        '_' => '/^_((?:[^_]|__[^_]*__)+?)_(?!_)\b/us',
    );

    protected $textLevelElements = array(
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
