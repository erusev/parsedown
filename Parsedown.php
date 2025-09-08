<?php
declare(strict_types=1);

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
    # ~

    const version = '1.8.0';

    # ~

    function text(string $text) : string
    {
        $Elements = $this->textElements($text);

        # convert to markup
        $markup = $this->elements($Elements);

        # trim line breaks
        $markup = trim($markup, "\n");

        return $markup;
    }

    protected function textElements(string $text) : array
    {
        # make sure no definitions are set
        $this->DefinitionData = array();

        # standardize line breaks
        $text = str_replace(array("\r\n", "\r"), "\n", $text);

        # remove surrounding line breaks
        $text = trim($text, "\n");

        # split text into lines
        $lines = explode("\n", $text);

        # iterate through lines to identify blocks
        return $this->linesElements($lines);
    }

    #
    # Setters
    #

    function setBreaksEnabled(bool $breaksEnabled) : Parsedown
    {
        $this->breaksEnabled = $breaksEnabled;

        return $this;
    }

    protected bool $breaksEnabled = false;

    function setMarkupEscaped(bool $markupEscaped) : Parsedown
    {
        $this->markupEscaped = $markupEscaped;

        return $this;
    }

    protected bool $markupEscaped = false;

    function setUrlsLinked(bool $urlsLinked) : Parsedown
    {
        $this->urlsLinked = $urlsLinked;

        return $this;
    }

    protected bool $urlsLinked = true;

    function setSafeMode(bool $safeMode) : Parsedown
    {
        $this->safeMode = $safeMode;

        return $this;
    }

    protected bool $safeMode = false;

    function setStrictMode(bool $strictMode) : Parsedown
    {
        $this->strictMode = $strictMode;

        return $this;
    }

    protected bool $strictMode = false;

    /** @var string[] */
    protected array $safeLinksWhitelist = array(
        'http://',
        'https://',
        'ftp://',
        'ftps://',
        'mailto:',
        'tel:',
        'data:image/png;base64,',
        'data:image/gif;base64,',
        'data:image/jpeg;base64,',
        'irc:',
        'ircs:',
        'git:',
        'ssh:',
        'news:',
        'steam:',
    );

    #
    # Lines
    #

    /** @var string[][] */
    protected array $BlockTypes = array(
        '#' => array('Header'),
        '*' => array('Rule', 'List'),
        '+' => array('List'),
        '-' => array('SetextHeader', 'Table', 'Rule', 'List'),
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
        '<' => array('Comment', 'Markup'),
        '=' => array('SetextHeader'),
        '>' => array('Quote'),
        '[' => array('Reference'),
        '_' => array('Rule'),
        '`' => array('FencedCode'),
        '|' => array('Table'),
        '~' => array('FencedCode'),
    );

    # ~

    /** @var string[] */
    protected array $unmarkedBlockTypes = array(
        'Code',
    );

    #
    # Blocks
    #

    protected function lines(array $lines) : string
    {
        return $this->elements($this->linesElements($lines));
    }

    protected function linesElements(array $lines) : array
    {
        $Elements = array();
        $CurrentBlock = null;

        foreach ($lines as $line)
        {
            if (rtrim($line) === '')
            {
                if (isset($CurrentBlock))
                {
                    $CurrentBlock['interrupted'] = (isset($CurrentBlock['interrupted'])
                        ? $CurrentBlock['interrupted'] + 1 : 1
                    );
                }

                continue;
            }

            while (($beforeTab = strstr($line, "\t", true)) !== false)
            {
                $shortage = 4 - mb_strlen($beforeTab, 'utf-8') % 4;

                $line = $beforeTab
                    . str_repeat(' ', $shortage)
                    . substr($line, strlen($beforeTab) + 1)
                ;
            }

            $indent = strspn($line, ' ');

            $text = $indent > 0 ? substr($line, $indent) : $line;

            # ~

            $Line = array('body' => $line, 'indent' => $indent, 'text' => $text);

            # ~

            if (isset($CurrentBlock['continuable']))
            {
                $methodName = 'block' . $CurrentBlock['type'] . 'Continue';
                $Block = $this->$methodName($Line, $CurrentBlock);

                if (isset($Block))
                {
                    $CurrentBlock = $Block;

                    continue;
                }
                else
                {
                    if ($this->isBlockCompletable($CurrentBlock['type']))
                    {
                        $methodName = 'block' . $CurrentBlock['type'] . 'Complete';
                        $CurrentBlock = $this->$methodName($CurrentBlock);
                    }
                }
            }

            # ~

            $marker = $text[0];

            # ~

            $blockTypes = $this->unmarkedBlockTypes;

            if (isset($this->BlockTypes[$marker]))
            {
                foreach ($this->BlockTypes[$marker] as $blockType)
                {
                    $blockTypes []= $blockType;
                }
            }

            #
            # ~

            foreach ($blockTypes as $blockType)
            {
                $Block = $this->{"block$blockType"}($Line, $CurrentBlock);

                if (isset($Block))
                {
                    $Block['type'] = $blockType;

                    if ( ! isset($Block['identified']))
                    {
                        if (isset($CurrentBlock))
                        {
                            $Elements[] = $this->extractElement($CurrentBlock);
                        }

                        $Block['identified'] = true;
                    }

                    if ($this->isBlockContinuable($blockType))
                    {
                        $Block['continuable'] = true;
                    }

                    $CurrentBlock = $Block;

                    continue 2;
                }
            }

            # ~

            if (isset($CurrentBlock) and $CurrentBlock['type'] === 'Paragraph')
            {
                $Block = $this->paragraphContinue($Line, $CurrentBlock);
            }

            if (isset($Block))
            {
                $CurrentBlock = $Block;
            }
            else
            {
                if (isset($CurrentBlock))
                {
                    $Elements[] = $this->extractElement($CurrentBlock);
                }

                $CurrentBlock = $this->paragraph($Line);

                $CurrentBlock['identified'] = true;
            }
        }

        # ~

        if (isset($CurrentBlock['continuable']) and $this->isBlockCompletable($CurrentBlock['type']))
        {
            $methodName = 'block' . $CurrentBlock['type'] . 'Complete';
            $CurrentBlock = $this->$methodName($CurrentBlock);
        }

        # ~

        if (isset($CurrentBlock))
        {
            $Elements[] = $this->extractElement($CurrentBlock);
        }

        # ~

        return $Elements;
    }

    protected function extractElement(array $Component) : array
    {
        if ( ! isset($Component['element']))
        {
            if (isset($Component['markup']))
            {
                $Component['element'] = array('rawHtml' => $Component['markup']);
            }
            elseif (isset($Component['hidden']))
            {
                $Component['element'] = array();
            }
        }

        return $Component['element'];
    }

    protected function isBlockContinuable(string $Type) : bool
    {
        return method_exists($this, 'block' . $Type . 'Continue');
    }

    protected function isBlockCompletable(string $Type) : bool
    {
        return method_exists($this, 'block' . $Type . 'Complete');
    }

    #
    # Code

    protected function blockCode(array $Line, ?array $Block = null) : ?array
    {
        if (isset($Block) and $Block['type'] === 'Paragraph' and ! isset($Block['interrupted']))
        {
            return null;
        }

        if ($Line['indent'] >= 4)
        {
            $text = substr($Line['body'], 4);

            $Block = array(
                'element' => array(
                    'name' => 'pre',
                    'element' => array(
                        'name' => 'code',
                        'text' => $text,
                    ),
                ),
            );

            return $Block;
        }

        return null;
    }

    protected function blockCodeContinue(array $Line, array $Block) : ?array
    {
        if ($Line['indent'] >= 4)
        {
            if (isset($Block['interrupted']))
            {
                $Block['element']['element']['text'] .= str_repeat("\n", $Block['interrupted']);

                unset($Block['interrupted']);
            }

            $Block['element']['element']['text'] .= "\n";

            $text = substr($Line['body'], 4);

            $Block['element']['element']['text'] .= $text;

            return $Block;
        }

        return null;
    }

    protected function blockCodeComplete(array $Block) : array
    {
        return $Block;
    }

    #
    # Comment

    protected function blockComment(array $Line) : ?array
    {
        if ($this->markupEscaped or $this->safeMode)
        {
            return null;
        }

        if (strpos($Line['text'], '<!--') === 0)
        {
            $Block = array(
                'element' => array(
                    'rawHtml' => $Line['body'],
                    'autobreak' => true,
                ),
            );

            if (strpos($Line['text'], '-->') !== false)
            {
                $Block['closed'] = true;
            }

            return $Block;
        }

        return null;
    }

    protected function blockCommentContinue(array $Line, array $Block) : ?array
    {
        if (isset($Block['closed']))
        {
            return null;
        }

        $Block['element']['rawHtml'] .= "\n" . $Line['body'];

        if (strpos($Line['text'], '-->') !== false)
        {
            $Block['closed'] = true;
        }

        return $Block;
    }

    #
    # Fenced Code

    protected function blockFencedCode(array $Line) : ?array
    {
        $marker = $Line['text'][0];

        $openerLength = strspn($Line['text'], $marker);

        if ($openerLength < 3)
        {
            return null;
        }

        $infostring = trim(substr($Line['text'], $openerLength), "\t ");

        if (strpos($infostring, '`') !== false)
        {
            return null;
        }

        $Element = array(
            'name' => 'code',
            'text' => '',
        );

        if ($infostring !== '')
        {
            /**
             * https://www.w3.org/TR/2011/WD-html5-20110525/elements.html#classes
             * Every HTML element may have a class attribute specified.
             * The attribute, if specified, must have a value that is a set
             * of space-separated tokens representing the various classes
             * that the element belongs to.
             * [...]
             * The space characters, for the purposes of this specification,
             * are U+0020 SPACE, U+0009 CHARACTER TABULATION (tab),
             * U+000A LINE FEED (LF), U+000C FORM FEED (FF), and
             * U+000D CARRIAGE RETURN (CR).
             */
            $language = substr($infostring, 0, strcspn($infostring, " \t\n\f\r"));

            $Element['attributes'] = array('class' => "language-$language");
        }

        $Block = array(
            'char' => $marker,
            'openerLength' => $openerLength,
            'element' => array(
                'name' => 'pre',
                'element' => $Element,
            ),
        );

        return $Block;
    }

    protected function blockFencedCodeContinue(array $Line, array $Block) : ?array
    {
        if (isset($Block['complete']))
        {
            return null;
        }

        if (isset($Block['interrupted']))
        {
            $Block['element']['element']['text'] .= str_repeat("\n", $Block['interrupted']);

            unset($Block['interrupted']);
        }

        if (($len = strspn($Line['text'], $Block['char'])) >= $Block['openerLength']
            and rtrim(substr($Line['text'], $len), ' ') === ''
        ) {
            $Block['element']['element']['text'] = substr($Block['element']['element']['text'], 1);

            $Block['complete'] = true;

            return $Block;
        }

        $Block['element']['element']['text'] .= "\n" . $Line['body'];

        return $Block;
    }

    protected function blockFencedCodeComplete(array $Block) : array
    {
        return $Block;
    }

    #
    # Header

    protected function blockHeader(array $Line) : ?array
    {
        $level = strspn($Line['text'], '#');

        if ($level > 6)
        {
            return null;
        }

        $text = trim($Line['text'], '#');

        if ($this->strictMode and isset($text[0]) and $text[0] !== ' ')
        {
            return null;
        }

        $text = trim($text, ' ');

        $Block = array(
            'element' => array(
                'name' => 'h' . $level,
                'handler' => array(
                    'function' => 'lineElements',
                    'argument' => $text,
                    'destination' => 'elements',
                )
            ),
        );

        return $Block;
    }

    #
    # List

    protected function blockList(array $Line, ?array $CurrentBlock = null) : ?array
    {
        list($name, $pattern) = $Line['text'][0] <= '-' ? array('ul', '[*+-]') : array('ol', '[0-9]{1,9}+[.\)]');

        if (preg_match('/^('.$pattern.'([ ]++|$))(.*+)/', $Line['text'], $matches))
        {
            $contentIndent = strlen($matches[2]);

            if ($contentIndent >= 5)
            {
                $contentIndent -= 1;
                $matches[1] = substr($matches[1], 0, -$contentIndent);
                $matches[3] = str_repeat(' ', $contentIndent) . $matches[3];
            }
            elseif ($contentIndent === 0)
            {
                $matches[1] .= ' ';
            }

            $markerWithoutWhitespace = strstr($matches[1], ' ', true);

            $Block = array(
                'indent' => $Line['indent'],
                'pattern' => $pattern,
                'data' => array(
                    'type' => $name,
                    'marker' => $matches[1],
                    'markerType' => ($name === 'ul' ? $markerWithoutWhitespace : substr($markerWithoutWhitespace, -1)),
                ),
                'element' => array(
                    'name' => $name,
                    'elements' => array(),
                ),
            );
            $Block['data']['markerTypeRegex'] = preg_quote($Block['data']['markerType'], '/');

            if ($name === 'ol')
            {
                $listStart = ltrim(strstr($matches[1], $Block['data']['markerType'], true), '0') ?: '0';

                if ($listStart !== '1')
                {
                    if (
                        isset($CurrentBlock)
                        and $CurrentBlock['type'] === 'Paragraph'
                        and ! isset($CurrentBlock['interrupted'])
                    ) {
                        return null;
                    }

                    $Block['element']['attributes'] = array('start' => $listStart);
                }
            }

            $Block['li'] = array(
                'name' => 'li',
                'handler' => array(
                    'function' => 'li',
                    'argument' => !empty($matches[3]) ? array($matches[3]) : array(),
                    'destination' => 'elements'
                )
            );

            $Block['element']['elements'] []= & $Block['li'];

            return $Block;
        }

        return null;
    }

    protected function blockListContinue(array $Line, array $Block) : ?array
    {
        if (isset($Block['interrupted']) and empty($Block['li']['handler']['argument']))
        {
            return null;
        }

        $requiredIndent = ($Block['indent'] + strlen($Block['data']['marker']));

        if ($Line['indent'] < $requiredIndent
            and (
                (
                    $Block['data']['type'] === 'ol'
                    and preg_match('/^[0-9]++'.$Block['data']['markerTypeRegex'].'(?:[ ]++(.*)|$)/', $Line['text'], $matches)
                ) or (
                    $Block['data']['type'] === 'ul'
                    and preg_match('/^'.$Block['data']['markerTypeRegex'].'(?:[ ]++(.*)|$)/', $Line['text'], $matches)
                )
            )
        ) {
            if (isset($Block['interrupted']))
            {
                $Block['li']['handler']['argument'] []= '';

                $Block['loose'] = true;

                unset($Block['interrupted']);
            }

            unset($Block['li']);

            $text = isset($matches) && isset($matches[1]) ? $matches[1] : '';

            $Block['indent'] = $Line['indent'];

            $Block['li'] = array(
                'name' => 'li',
                'handler' => array(
                    'function' => 'li',
                    'argument' => array($text),
                    'destination' => 'elements'
                )
            );

            $Block['element']['elements'] []= & $Block['li'];

            return $Block;
        }
        elseif ($Line['indent'] < $requiredIndent and $this->blockList($Line))
        {
            return null;
        }

        if ($Line['text'][0] === '[' and $this->blockReference($Line))
        {
            return $Block;
        }

        if ($Line['indent'] >= $requiredIndent)
        {
            if (isset($Block['interrupted']))
            {
                $Block['li']['handler']['argument'] []= '';

                $Block['loose'] = true;

                unset($Block['interrupted']);
            }

            $text = substr($Line['body'], $requiredIndent);

            $Block['li']['handler']['argument'] []= $text;

            return $Block;
        }

        if ( ! isset($Block['interrupted']))
        {
            $text = preg_replace('/^[ ]{0,'.$requiredIndent.'}+/', '', $Line['body']);

            $Block['li']['handler']['argument'] []= $text;

            return $Block;
        }

        return null;
    }

    protected function blockListComplete(array $Block) : array
    {
        if (isset($Block['loose']))
        {
            foreach ($Block['element']['elements'] as &$li)
            {
                if (end($li['handler']['argument']) !== '')
                {
                    $li['handler']['argument'] []= '';
                }
            }
        }

        return $Block;
    }

    #
    # Quote

    protected function blockQuote(array $Line) : ?array
    {
        if (preg_match('/^>[ ]?+(.*+)/', $Line['text'], $matches))
        {
            $Block = array(
                'element' => array(
                    'name' => 'blockquote',
                    'handler' => array(
                        'function' => 'linesElements',
                        'argument' => (array) $matches[1],
                        'destination' => 'elements',
                    )
                ),
            );

            return $Block;
        }

        return null;
    }

    protected function blockQuoteContinue(array $Line, array $Block) : ?array
    {
        if (isset($Block['interrupted']))
        {
            return null;
        }

        if ($Line['text'][0] === '>' and preg_match('/^>[ ]?+(.*+)/', $Line['text'], $matches))
        {
            $Block['element']['handler']['argument'] []= $matches[1];

            return $Block;
        }

        $Block['element']['handler']['argument'] []= $Line['text'];

        return $Block;
    }

    #
    # Rule

    protected function blockRule(array $Line) : ?array
    {
        $marker = $Line['text'][0];

        if (substr_count($Line['text'], $marker) >= 3 and rtrim($Line['text'], " $marker") === '')
        {
            $Block = array(
                'element' => array(
                    'name' => 'hr',
                ),
            );

            return $Block;
        }

        return null;
    }

    #
    # Setext

    protected function blockSetextHeader(array $Line, ?array $Block = null) : ?array
    {
        if ( ! isset($Block) or $Block['type'] !== 'Paragraph' or isset($Block['interrupted']))
        {
            return null;
        }

        if ($Line['indent'] < 4 and rtrim(rtrim($Line['text'], ' '), $Line['text'][0]) === '')
        {
            $Block['element']['name'] = $Line['text'][0] === '=' ? 'h1' : 'h2';

            return $Block;
        }

        return null;
    }

    #
    # Markup

    protected function blockMarkup(array $Line) : ?array
    {
        if ($this->markupEscaped or $this->safeMode)
        {
            return null;
        }

        if (preg_match('/^<[\/]?+(\w*)(?:[ ]*+'.$this->regexHtmlAttribute.')*+[ ]*+(\/)?>/', $Line['text'], $matches))
        {
            $element = strtolower($matches[1]);

            if (in_array($element, $this->textLevelElements, true))
            {
                return null;
            }

            $Block = array(
                'name' => $matches[1],
                'element' => array(
                    'rawHtml' => $Line['text'],
                    'autobreak' => true,
                ),
            );

            return $Block;
        }

        return null;
    }

    protected function blockMarkupContinue(array $Line, array $Block) : ?array
    {
        if (isset($Block['closed']) or isset($Block['interrupted']))
        {
            return null;
        }

        $Block['element']['rawHtml'] .= "\n" . $Line['body'];

        return $Block;
    }

    #
    # Reference

    protected function blockReference(array $Line) : ?array
    {
        if (strpos($Line['text'], ']') !== false
            and preg_match('/^\[(.+?)\]:[ ]*+<?(\S+?)>?(?:[ ]+["\'(](.+)["\')])?[ ]*+$/', $Line['text'], $matches)
        ) {
            $id = strtolower($matches[1]);

            $Data = array(
                'url' => $matches[2],
                'title' => isset($matches[3]) ? $matches[3] : null,
            );

            $this->DefinitionData['Reference'][$id] = $Data;

            $Block = array(
                'element' => array(),
            );

            return $Block;
        }

        return null;
    }

    #
    # Table

    protected function blockTable(array $Line, ?array $Block = null) : ?array
    {
        if ( ! isset($Block) or $Block['type'] !== 'Paragraph' or isset($Block['interrupted']))
        {
            return null;
        }

        if (
            strpos($Block['element']['handler']['argument'], '|') === false
            and strpos($Line['text'], '|') === false
            and strpos($Line['text'], ':') === false
            or strpos($Block['element']['handler']['argument'], "\n") !== false
        ) {
            return null;
        }

        if (rtrim($Line['text'], ' -:|') !== '')
        {
            return null;
        }

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
                return null;
            }

            $alignment = null;

            if ($dividerCell[0] === ':')
            {
                $alignment = 'left';
            }

            if (substr($dividerCell, - 1) === ':')
            {
                $alignment = $alignment === 'left' ? 'center' : 'right';
            }

            $alignments []= $alignment;
        }

        # ~

        $HeaderElements = array();

        $header = $Block['element']['handler']['argument'];

        $header = trim($header);
        $header = trim($header, '|');

        $headerCells = explode('|', $header);

        if (count($headerCells) !== count($alignments))
        {
            return null;
        }

        foreach ($headerCells as $index => $headerCell)
        {
            $headerCell = trim($headerCell);

            $HeaderElement = array(
                'name' => 'th',
                'handler' => array(
                    'function' => 'lineElements',
                    'argument' => $headerCell,
                    'destination' => 'elements',
                )
            );

            if (isset($alignments[$index]))
            {
                $alignment = $alignments[$index];

                $HeaderElement['attributes'] = array(
                    'style' => "text-align: $alignment;",
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
                'elements' => array(),
            ),
        );

        $Block['element']['elements'] []= array(
            'name' => 'thead',
        );

        $Block['element']['elements'] []= array(
            'name' => 'tbody',
            'elements' => array(),
        );

        $Block['element']['elements'][0]['elements'] []= array(
            'name' => 'tr',
            'elements' => $HeaderElements,
        );

        return $Block;
    }

    protected function blockTableContinue(array $Line, array $Block) : ?array
    {
        if (isset($Block['interrupted']))
        {
            return null;
        }

        if (count($Block['alignments']) === 1 or $Line['text'][0] === '|' or strpos($Line['text'], '|'))
        {
            $Elements = array();

            $row = $Line['text'];

            $row = trim($row);
            $row = trim($row, '|');

            preg_match_all('/(?:(\\\\[|])|[^|`]|`[^`]++`|`)++/', $row, $matches);

            $cells = array_slice($matches[0], 0, count($Block['alignments']));

            foreach ($cells as $index => $cell)
            {
                $cell = trim($cell);

                $Element = array(
                    'name' => 'td',
                    'handler' => array(
                        'function' => 'lineElements',
                        'argument' => $cell,
                        'destination' => 'elements',
                    )
                );

                if (isset($Block['alignments'][$index]))
                {
                    $Element['attributes'] = array(
                        'style' => 'text-align: ' . $Block['alignments'][$index] . ';',
                    );
                }

                $Elements []= $Element;
            }

            $Element = array(
                'name' => 'tr',
                'elements' => $Elements,
            );

            $Block['element']['elements'][1]['elements'] []= $Element;

            return $Block;
        }

        return null;
    }

    #
    # ~
    #

    protected function paragraph(array $Line) : array
    {
        return array(
            'type' => 'Paragraph',
            'element' => array(
                'name' => 'p',
                'handler' => array(
                    'function' => 'lineElements',
                    'argument' => $Line['text'],
                    'destination' => 'elements',
                ),
            ),
        );
    }

    protected function paragraphContinue(array $Line, array $Block) : ?array
    {
        if (isset($Block['interrupted']))
        {
            return null;
        }

        $Block['element']['handler']['argument'] .= "\n".$Line['text'];

        return $Block;
    }

    #
    # Inline Elements
    #

    /** @var string[][] */
    protected array $InlineTypes = array(
        '!' => array('Image'),
        '&' => array('SpecialCharacter'),
        '*' => array('Emphasis'),
        ':' => array('Url'),
        '<' => array('UrlTag', 'EmailTag', 'Markup'),
        '[' => array('Link'),
        '_' => array('Emphasis'),
        '`' => array('Code'),
        '~' => array('Strikethrough'),
        '\\' => array('EscapeSequence'),
    );

    # ~

    protected string $inlineMarkerList = '!*_&[:<`~\\';

    #
    # ~
    #

    public function line(string $text, array $nonNestables = array()) : string
    {
        return $this->elements($this->lineElements($text, $nonNestables));
    }

    protected function lineElements(string $text, array $nonNestables = array()) : array
    {
        # standardize line breaks
        $text = str_replace(array("\r\n", "\r"), "\n", $text);

        $Elements = array();

        $nonNestables = (empty($nonNestables)
            ? array()
            : array_combine($nonNestables, $nonNestables)
        );

        # $excerpt is based on the first occurrence of a marker

        while ($excerpt = strpbrk($text, $this->inlineMarkerList))
        {
            $marker = $excerpt[0];

            $markerPosition = strlen($text) - strlen($excerpt);

            $Excerpt = array('text' => $excerpt, 'context' => $text);

            foreach ($this->InlineTypes[$marker] as $inlineType)
            {
                # check to see if the current inline type is nestable in the current context

                if (isset($nonNestables[$inlineType]))
                {
                    continue;
                }

                $Inline = $this->{"inline$inlineType"}($Excerpt);

                if ( ! isset($Inline))
                {
                    continue;
                }

                # makes sure that the inline belongs to "our" marker

                if (isset($Inline['position']) and $Inline['position'] > $markerPosition)
                {
                    continue;
                }

                # sets a default inline position

                if ( ! isset($Inline['position']))
                {
                    $Inline['position'] = $markerPosition;
                }

                # cause the new element to 'inherit' our non nestables


                $Inline['element']['nonNestables'] = isset($Inline['element']['nonNestables'])
                    ? array_merge($Inline['element']['nonNestables'], $nonNestables)
                    : $nonNestables
                ;

                # the text that comes before the inline
                $unmarkedText = substr($text, 0, $Inline['position']);

                # compile the unmarked text
                $InlineText = $this->inlineText($unmarkedText);
                $Elements[] = $InlineText['element'];

                # compile the inline
                $Elements[] = $this->extractElement($Inline);

                # remove the examined text
                $text = substr($text, $Inline['position'] + $Inline['extent']);

                continue 2;
            }

            # the marker does not belong to an inline

            $unmarkedText = substr($text, 0, $markerPosition + 1);

            $InlineText = $this->inlineText($unmarkedText);
            $Elements[] = $InlineText['element'];

            $text = substr($text, $markerPosition + 1);
        }

        $InlineText = $this->inlineText($text);
        $Elements[] = $InlineText['element'];

        foreach ($Elements as &$Element)
        {
            if ( ! isset($Element['autobreak']))
            {
                $Element['autobreak'] = false;
            }
        }

        return $Elements;
    }

    #
    # ~
    #

    protected function inlineText(string $text) : array
    {
        $Inline = array(
            'extent' => strlen($text),
            'element' => array(),
        );

        $Inline['element']['elements'] = self::pregReplaceElements(
            $this->breaksEnabled ? '/[ ]*+\n/' : '/(?:[ ]*+\\\\|[ ]{2,}+)\n/',
            array(
                array('name' => 'br'),
                array('text' => "\n"),
            ),
            $text
        );

        return $Inline;
    }

    protected function inlineCode(array $Excerpt) : ?array
    {
        $marker = $Excerpt['text'][0];

        if (preg_match('/^(['.$marker.']++)[ ]*+(.+?)[ ]*+(?<!['.$marker.'])\1(?!'.$marker.')/s', $Excerpt['text'], $matches))
        {
            $text = $matches[2];
            $text = preg_replace('/[ ]*+\n/', ' ', $text);

            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'code',
                    'text' => $text,
                ),
            );
        }

        return null;
    }

    protected function inlineEmailTag(array $Excerpt) : ?array
    {
        $hostnameLabel = '[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?';

        $commonMarkEmail = '[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]++@'
            . $hostnameLabel . '(?:\.' . $hostnameLabel . ')*';

        if (strpos($Excerpt['text'], '>') !== false
            and preg_match("/^<((mailto:)?$commonMarkEmail)>/i", $Excerpt['text'], $matches)
        ){
            $url = $matches[1];

            if ( ! isset($matches[2]))
            {
                $url = "mailto:$url";
            }

            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'a',
                    'text' => $matches[1],
                    'attributes' => array(
                        'href' => $url,
                    ),
                ),
            );
        }

        return null;
    }

    protected function inlineEmphasis(array $Excerpt) : ?array
    {
        if ( ! isset($Excerpt['text'][1]))
        {
            return null;
        }

        $marker = $Excerpt['text'][0];

        if ($Excerpt['text'][1] === $marker and preg_match($this->StrongRegex[$marker], $Excerpt['text'], $matches))
        {
            $emphasis = 'strong';
        }
        elseif (preg_match($this->EmRegex[$marker], $Excerpt['text'], $matches))
        {
            $emphasis = 'em';
        }
        else
        {
            return null;
        }

        return array(
            'extent' => strlen($matches[0]),
            'element' => array(
                'name' => $emphasis,
                'handler' => array(
                    'function' => 'lineElements',
                    'argument' => $matches[1],
                    'destination' => 'elements',
                )
            ),
        );
    }

    protected function inlineEscapeSequence(array $Excerpt) : ?array
    {
        if (isset($Excerpt['text'][1]) and in_array($Excerpt['text'][1], $this->specialCharacters, true))
        {
            return array(
                'element' => array('rawHtml' => $Excerpt['text'][1]),
                'extent' => 2,
            );
        }

        return null;
    }

    protected function inlineImage(array $Excerpt) : ?array
    {
        if ( ! isset($Excerpt['text'][1]) or $Excerpt['text'][1] !== '[')
        {
            return null;
        }

        $Excerpt['text']= substr($Excerpt['text'], 1);

        $Link = $this->inlineLink($Excerpt);

        if ($Link === null)
        {
            return null;
        }

        $Inline = array(
            'extent' => $Link['extent'] + 1,
            'element' => array(
                'name' => 'img',
                'attributes' => array(
                    'src' => $Link['element']['attributes']['href'],
                    'alt' => $Link['element']['handler']['argument'],
                ),
                'autobreak' => true,
            ),
        );

        $Inline['element']['attributes'] += $Link['element']['attributes'];

        unset($Inline['element']['attributes']['href']);

        return $Inline;
    }

    protected function inlineLink(array $Excerpt) : ?array
    {
        $Element = array(
            'name' => 'a',
            'handler' => array(
                'function' => 'lineElements',
                'argument' => null,
                'destination' => 'elements',
            ),
            'nonNestables' => array('Url', 'Link'),
            'attributes' => array(
                'href' => null,
                'title' => null,
            ),
        );

        $extent = 0;

        $remainder = $Excerpt['text'];

        if (preg_match('/\[((?:[^][]++|(?R))*+)\]/', $remainder, $matches))
        {
            $Element['handler']['argument'] = $matches[1];

            $extent += strlen($matches[0]);

            $remainder = substr($remainder, $extent);
        }
        else
        {
            return null;
        }

        if (preg_match('/^[(]\s*+((?:[^ ()]++|[(][^ )]+[)])++)(?:[ ]+("[^"]*+"|\'[^\']*+\'))?\s*+[)]/', $remainder, $matches))
        {
            $Element['attributes']['href'] = $matches[1];

            if (isset($matches[2]))
            {
                $Element['attributes']['title'] = substr($matches[2], 1, - 1);
            }

            $extent += strlen($matches[0]);
        }
        else
        {
            if (preg_match('/^\s*\[(.*?)\]/', $remainder, $matches))
            {
                $definition = strlen($matches[1]) ? $matches[1] : $Element['handler']['argument'];
                $definition = strtolower($definition);

                $extent += strlen($matches[0]);
            }
            else
            {
                $definition = strtolower($Element['handler']['argument']);
            }

            if ( ! isset($this->DefinitionData['Reference'][$definition]))
            {
                return null;
            }

            $Definition = $this->DefinitionData['Reference'][$definition];

            $Element['attributes']['href'] = $Definition['url'];
            $Element['attributes']['title'] = $Definition['title'];
        }

        return array(
            'extent' => $extent,
            'element' => $Element,
        );
    }

    protected function inlineMarkup(array $Excerpt) : ?array
    {
        if ($this->markupEscaped or $this->safeMode or strpos($Excerpt['text'], '>') === false)
        {
            return null;
        }

        if ($Excerpt['text'][1] === '/' and preg_match('/^<\/\w[\w-]*+[ ]*+>/s', $Excerpt['text'], $matches))
        {
            return array(
                'element' => array('rawHtml' => $matches[0]),
                'extent' => strlen($matches[0]),
            );
        }

        if ($Excerpt['text'][1] === '!' and preg_match('/^<!---?[^>-](?:-?+[^-])*-->/s', $Excerpt['text'], $matches))
        {
            return array(
                'element' => array('rawHtml' => $matches[0]),
                'extent' => strlen($matches[0]),
            );
        }

        if ($Excerpt['text'][1] !== ' ' and preg_match('/^<\w[\w-]*+(?:[ ]*+'.$this->regexHtmlAttribute.')*+[ ]*+\/?>/s', $Excerpt['text'], $matches))
        {
            return array(
                'element' => array('rawHtml' => $matches[0]),
                'extent' => strlen($matches[0]),
            );
        }

        return null;
    }

    protected function inlineSpecialCharacter(array $Excerpt) : ?array
    {
        if (substr($Excerpt['text'], 1, 1) !== ' ' and strpos($Excerpt['text'], ';') !== false
            and preg_match('/^&(#?+[0-9a-zA-Z]++);/', $Excerpt['text'], $matches)
        ) {
            return array(
                'element' => array('rawHtml' => '&' . $matches[1] . ';'),
                'extent' => strlen($matches[0]),
            );
        }

        return null;
    }

    protected function inlineStrikethrough(array $Excerpt) : ?array
    {
        if ( ! isset($Excerpt['text'][1]))
        {
            return null;
        }

        if ($Excerpt['text'][1] === '~' and preg_match('/^~~(?=\S)(.+?)(?<=\S)~~/', $Excerpt['text'], $matches))
        {
            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => 'del',
                    'handler' => array(
                        'function' => 'lineElements',
                        'argument' => $matches[1],
                        'destination' => 'elements',
                    )
                ),
            );
        }

        return null;
    }

    protected function inlineUrl(array $Excerpt) : ?array
    {
        if ($this->urlsLinked !== true or ! isset($Excerpt['text'][2]) or $Excerpt['text'][2] !== '/')
        {
            return null;
        }

        if (strpos($Excerpt['context'], 'http') !== false
            and preg_match('/\bhttps?+:[\/]{2}[^\s<]+\b\/*+/ui', $Excerpt['context'], $matches, PREG_OFFSET_CAPTURE)
        ) {
            $url = $matches[0][0];

            $Inline = array(
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

            return $Inline;
        }

        return null;
    }

    protected function inlineUrlTag(array $Excerpt) : ?array
    {
        if (strpos($Excerpt['text'], '>') !== false and preg_match('/^<(\w++:\/{2}[^ >]++)>/i', $Excerpt['text'], $matches))
        {
            $url = $matches[1];

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
        return null;
    }

    # ~

    protected function unmarkedText(string $text) : string
    {
        $Inline = $this->inlineText($text);
        return $this->element($Inline['element']);
    }

    #
    # Handlers
    #

    protected function handle(array $Element) : array
    {
        if (isset($Element['handler']))
        {
            if (!isset($Element['nonNestables']))
            {
                $Element['nonNestables'] = array();
            }

            if (is_string($Element['handler']))
            {
                $function = $Element['handler'];
                $argument = $Element['text'];
                unset($Element['text']);
                $destination = 'rawHtml';
            }
            else
            {
                $function = $Element['handler']['function'];
                $argument = $Element['handler']['argument'];
                $destination = $Element['handler']['destination'];
            }

            $Element[$destination] = $this->{$function}($argument, $Element['nonNestables']);

            if ($destination === 'handler')
            {
                $Element = $this->handle($Element);
            }

            unset($Element['handler']);
        }

        return $Element;
    }

    protected function handleElementRecursive(array $Element) : array
    {
        return $this->elementApplyRecursive(array($this, 'handle'), $Element);
    }

    protected function handleElementsRecursive(array $Elements) : array
    {
        return $this->elementsApplyRecursive(array($this, 'handle'), $Elements);
    }

    protected function elementApplyRecursive(callable $closure, array $Element) : array
    {
        $Element = call_user_func($closure, $Element);

        if (isset($Element['elements']))
        {
            $Element['elements'] = $this->elementsApplyRecursive($closure, $Element['elements']);
        }
        elseif (isset($Element['element']))
        {
            $Element['element'] = $this->elementApplyRecursive($closure, $Element['element']);
        }

        return $Element;
    }

    protected function elementApplyRecursiveDepthFirst(callable $closure, array $Element) : array
    {
        if (isset($Element['elements']))
        {
            $Element['elements'] = $this->elementsApplyRecursiveDepthFirst($closure, $Element['elements']);
        }
        elseif (isset($Element['element']))
        {
            $Element['element'] = $this->elementsApplyRecursiveDepthFirst($closure, $Element['element']);
        }

        $Element = call_user_func($closure, $Element);

        return $Element;
    }

    protected function elementsApplyRecursive(callable $closure, array $Elements) : array
    {
        foreach ($Elements as &$Element)
        {
            $Element = $this->elementApplyRecursive($closure, $Element);
        }

        return $Elements;
    }

    protected function elementsApplyRecursiveDepthFirst(callable $closure, array $Elements) : array
    {
        foreach ($Elements as &$Element)
        {
            $Element = $this->elementApplyRecursiveDepthFirst($closure, $Element);
        }

        return $Elements;
    }

    protected function element(array $Element) : string
    {
        if ($this->safeMode)
        {
            $Element = $this->sanitiseElement($Element);
        }

        # identity map if element has no handler
        $Element = $this->handle($Element);

        $hasName = isset($Element['name']);

        $markup = '';

        if ($hasName)
        {
            $markup .= '<' . $Element['name'];

            if (isset($Element['attributes']))
            {
                foreach ($Element['attributes'] as $name => $value)
                {
                    if ($value === null)
                    {
                        continue;
                    }

                    $markup .= " $name=\"".self::escape((string)$value).'"';
                }
            }
        }

        $text = null;
        $permitRawHtml = false;

        if (isset($Element['text']))
        {
            $text = $Element['text'];
        }
        // very strongly consider an alternative if you're writing an
        // extension
        elseif (isset($Element['rawHtml']))
        {
            $text = $Element['rawHtml'];

            $allowRawHtmlInSafeMode = isset($Element['allowRawHtmlInSafeMode']) && $Element['allowRawHtmlInSafeMode'];
            $permitRawHtml = !$this->safeMode || $allowRawHtmlInSafeMode;
        }

        $hasContent = $text !== null || isset($Element['element']) || isset($Element['elements']);

        if ($hasContent)
        {
            $markup .= $hasName ? '>' : '';

            if (isset($Element['elements']))
            {
                $markup .= $this->elements($Element['elements']);
            }
            elseif (isset($Element['element']))
            {
                $markup .= $this->element($Element['element']);
            }
            else
            {
                if (!$permitRawHtml)
                {
                    $markup .= self::escape($text, true);
                }
                else
                {
                    $markup .= $text;
                }
            }

            $markup .= $hasName ? '</' . $Element['name'] . '>' : '';
        }
        elseif ($hasName)
        {
            $markup .= ' />';
        }

        return $markup;
    }

    protected function elements(array $Elements) : string
    {
        $markup = '';

        $autoBreak = true;

        foreach ($Elements as $Element)
        {
            if (empty($Element))
            {
                continue;
            }

            $autoBreakNext = (isset($Element['autobreak'])
                ? $Element['autobreak'] : isset($Element['name'])
            );
            // (autobreak === false) covers both sides of an element
            $autoBreak = !$autoBreak ? $autoBreak : $autoBreakNext;

            $markup .= ($autoBreak ? "\n" : '') . $this->element($Element);
            $autoBreak = $autoBreakNext;
        }

        $markup .= $autoBreak ? "\n" : '';

        return $markup;
    }

    # ~

    protected function li(array $lines) : array
    {
        $Elements = $this->linesElements($lines);

        if ( ! in_array('', $lines, true)
            and isset($Elements[0]) and isset($Elements[0]['name'])
            and $Elements[0]['name'] === 'p'
        ) {
            unset($Elements[0]['name']);
        }

        return $Elements;
    }

    #
    # AST Convenience
    #

    /**
     * Replace occurrences $regexp with $Elements in $text. Return an array of
     * elements representing the replacement.
     */
    protected static function pregReplaceElements(string $regexp, array $Elements, string $text) : array
    {
        $newElements = array();

        while (preg_match($regexp, $text, $matches, PREG_OFFSET_CAPTURE))
        {
            $offset = $matches[0][1];
            $before = substr($text, 0, $offset);
            $after = substr($text, $offset + strlen($matches[0][0]));

            $newElements[] = array('text' => $before);

            foreach ($Elements as $Element)
            {
                $newElements[] = $Element;
            }

            $text = $after;
        }

        $newElements[] = array('text' => $text);

        return $newElements;
    }

    #
    # Deprecated Methods
    #

    function parse(string $text) : string
    {
        $markup = $this->text($text);

        return $markup;
    }

    protected function sanitiseElement(array $Element) : array
    {
        static $goodAttribute = '/^[a-zA-Z0-9][a-zA-Z0-9-_]*+$/';
        static $safeUrlNameToAtt  = array(
            'a'   => 'href',
            'img' => 'src',
        );

        if ( ! isset($Element['name']))
        {
            unset($Element['attributes']);
            return $Element;
        }

        if (isset($safeUrlNameToAtt[$Element['name']]))
        {
            $Element = $this->filterUnsafeUrlInAttribute($Element, $safeUrlNameToAtt[$Element['name']]);
        }

        if ( ! empty($Element['attributes']))
        {
            foreach ($Element['attributes'] as $att => $val)
            {
                # filter out badly parsed attribute
                if ( ! preg_match($goodAttribute, $att))
                {
                    unset($Element['attributes'][$att]);
                }
                # dump onevent attribute
                elseif (self::striAtStart($att, 'on'))
                {
                    unset($Element['attributes'][$att]);
                }
            }
        }

        return $Element;
    }

    protected function filterUnsafeUrlInAttribute(array $Element, string $attribute) : array
    {
        foreach ($this->safeLinksWhitelist as $scheme)
        {
            if (self::striAtStart($Element['attributes'][$attribute], $scheme))
            {
                return $Element;
            }
        }

        $Element['attributes'][$attribute] = str_replace(':', '%3A', $Element['attributes'][$attribute]);

        return $Element;
    }

    #
    # Static Methods
    #

    protected static function escape(string $text, bool $allowQuotes = false) : string
    {
        return htmlspecialchars($text, $allowQuotes ? ENT_NOQUOTES : ENT_QUOTES, 'UTF-8');
    }

    protected static function striAtStart(string $string, string $needle) : bool
    {
        $len = strlen($needle);

        if ($len > strlen($string))
        {
            return false;
        }
        else
        {
            return strtolower(substr($string, 0, $len)) === strtolower($needle);
        }
    }

    static function instance($name = 'default')
    {
        if (isset(self::$instances[$name]))
        {
            return self::$instances[$name];
        }

        $instance = new static();

        self::$instances[$name] = $instance;

        return $instance;
    }

    private static $instances = array();

    #
    # Fields
    #

    protected array $DefinitionData = [];

    #
    # Read-Only

    /** @var string[] */
    protected array $specialCharacters = array(
        '\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '>', '#', '+', '-', '.', '!', '|', '~'
    );

    /** @var array<string, string> */
    protected array $StrongRegex = array(
        '*' => '/^[*]{2}((?:\\\\\*|[^*]|[*][^*]*+[*])+?)[*]{2}(?![*])/s',
        '_' => '/^__((?:\\\\_|[^_]|_[^_]*+_)+?)__(?!_)/us',
    );

    /** @var array<string, string> */
    protected array $EmRegex = array(
        '*' => '/^[*]((?:\\\\\*|[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
        '_' => '/^_((?:\\\\_|[^_]|__[^_]*__)+?)_(?!_)\b/us',
    );

    protected string $regexHtmlAttribute = '[a-zA-Z_:][\w:.-]*+(?:\s*+=\s*+(?:[^"\'=<>`\s]+|"[^"]*+"|\'[^\']*+\'))?+';

    /** @var string[] */
    protected array $voidElements = array(
        'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source',
    );

    /** @var string[] */
    protected array $textLevelElements = array(
        'a', 'br', 'bdo', 'abbr', 'blink', 'nextid', 'acronym', 'basefont',
        'b', 'em', 'big', 'cite', 'small', 'spacer', 'listing',
        'i', 'rp', 'del', 'code',          'strike', 'marquee',
        'q', 'rt', 'ins', 'font',          'strong',
        's', 'tt', 'kbd', 'mark',
        'u', 'xm', 'sub', 'nobr',
                   'sup', 'ruby',
                   'var', 'span',
                   'wbr', 'time',
    );
}
