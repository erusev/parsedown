<?php

declare(strict_types=1);

/**
 * Parsedown
 * http://parsedown.org
 *
 * (c) Emanuil Rusev
 * http://erusev.com
 *
 * For the full license information, view the LICENSE file that was distributed
 * with this source code.
 */
class Parsedown
{

	public const version = '1.8.0';

	/**
	 * @var self[]
	 */
	private static $instances = [];

	/**
	 * @var bool
	 */
	protected $breaksEnabled;

	/**
	 * @var bool
	 */
	protected $markupEscaped;

	/**
	 * @var bool
	 */
	protected $urlsLinked = true;

	/**
	 * @var bool
	 */
	protected $safeMode;

	/**
	 * @var string[]
	 */
	protected $safeLinksWhitelist = [
		'http://',
		'https://',
		'ftp://',
		'ftps://',
		'mailto:',
		'data:image/png;base64,',
		'data:image/gif;base64,',
		'data:image/jpeg;base64,',
		'irc:',
		'ircs:',
		'git:',
		'ssh:',
		'news:',
		'steam:',
	];

	/**
	 * @var string[][]
	 */
	protected $blockTypes = [
		'#' => ['Header'],
		'*' => ['Rule', 'List'],
		'+' => ['List'],
		'-' => ['SetextHeader', 'Table', 'Rule', 'List'],
		'0' => ['List'],
		'1' => ['List'],
		'2' => ['List'],
		'3' => ['List'],
		'4' => ['List'],
		'5' => ['List'],
		'6' => ['List'],
		'7' => ['List'],
		'8' => ['List'],
		'9' => ['List'],
		':' => ['Table'],
		'<' => ['Comment', 'Markup'],
		'=' => ['SetextHeader'],
		'>' => ['Quote'],
		'[' => ['Reference'],
		'_' => ['Rule'],
		'`' => ['FencedCode'],
		'|' => ['Table'],
		'~' => ['FencedCode'],
	];

	/**
	 * @var string[]
	 */
	protected $unmarkedBlockTypes = [
		'Code',
	];

	/**
	 * @var string[][]
	 */
	protected $inlineTypes = [
		'"' => ['SpecialCharacter'],
		'!' => ['Image'],
		'&' => ['SpecialCharacter'],
		'*' => ['Emphasis'],
		':' => ['Url'],
		'<' => ['UrlTag', 'EmailTag', 'Markup', 'SpecialCharacter'],
		'>' => ['SpecialCharacter'],
		'[' => ['Link'],
		'_' => ['Emphasis'],
		'`' => ['Code'],
		'~' => ['Strikethrough'],
		'\\' => ['EscapeSequence'],
	];

	protected $inlineMarkerList = '!"*_&[:<>`~\\';

	protected $DefinitionData;

	protected $specialCharacters = [
		'\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '>', '#', '+', '-', '.', '!', '|',
	];

	protected $StrongRegex = [
		'*' => '/^[*]{2}((?:\\\\\*|[^*]|[*][^*]*[*])+?)[*]{2}(?![*])/s',
		'_' => '/^__((?:\\\\_|[^_]|_[^_]*_)+?)__(?!_)/us',
	];

	protected $EmRegex = [
		'*' => '/^[*]((?:\\\\\*|[^*]|[*][*][^*]+?[*][*])+?)[*](?![*])/s',
		'_' => '/^_((?:\\\\_|[^_]|__[^_]*__)+?)_(?!_)\b/us',
	];

	protected $regexHtmlAttribute = '[a-zA-Z_:][\w:.-]*(?:\s*=\s*(?:[^"\'=<>`\s]+|"[^"]*"|\'[^\']*\'))?';

	protected $voidElements = [
		'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source',
	];

	protected $textLevelElements = [
		'a', 'br', 'bdo', 'abbr', 'blink', 'nextid', 'acronym', 'basefont',
		'b', 'em', 'big', 'cite', 'small', 'spacer', 'listing',
		'i', 'rp', 'del', 'code', 'strike', 'marquee',
		'q', 'rt', 'ins', 'font', 'strong',
		's', 'tt', 'kbd', 'mark',
		'u', 'xm', 'sub', 'nobr',
		'sup', 'ruby',
		'var', 'span',
		'wbr', 'time',
	];

	/**
	 * @param string|null $name
	 * @return Parsedown
	 */
	public static function instance(?string $name = null): self
	{
		$name = $name ?? 'default';

		if (isset(self::$instances[$name]) === true) {
			return self::$instances[$name];
		}

		$instance = new static;

		self::$instances[$name] = $instance;

		return $instance;
	}

	protected static function escape(string $text, bool $allowQuotes = false): string
	{
		return htmlspecialchars($text, $allowQuotes ? ENT_NOQUOTES : ENT_QUOTES, 'UTF-8');
	}

	protected static function striAtStart(string $string, string $needle): bool
	{
		if (strlen($needle) > strlen($string)) {
			return false;
		}

		return stripos($string, strtolower($needle)) === 0;
	}

	public function text(string $text): string
	{
		// make sure no definitions are set
		$this->DefinitionData = [];

		// standardize line breaks
		$text = str_replace(["\r\n", "\r"], "\n", $text);

		// remove surrounding line breaks
		$text = trim($text, "\n");

		// split text into lines
		$lines = explode("\n", $text);

		// iterate through lines to identify blocks
		$markup = $this->lines($lines);

		// trim line breaks
		$markup = trim($markup, "\n");

		return $markup;
	}

	public function setBreaksEnabled(bool $breaksEnabled): self
	{
		$this->breaksEnabled = $breaksEnabled;

		return $this;
	}

	public function setMarkupEscaped(bool $markupEscaped): self
	{
		$this->markupEscaped = $markupEscaped;

		return $this;
	}

	public function setUrlsLinked(bool $urlsLinked): self
	{
		$this->urlsLinked = $urlsLinked;

		return $this;
	}

	public function setSafeMode(bool $safeMode): self
	{
		$this->safeMode = $safeMode;

		return $this;
	}

	public function line(string $text, array $nonNestables = []): string
	{
		$markup = '';

		# $excerpt is based on the first occurrence of a marker

		while ($excerpt = strpbrk($text, $this->inlineMarkerList)) {
			$marker = $excerpt[0];
			$markerPosition = strpos($text, $marker);
			$Excerpt = ['text' => $excerpt, 'context' => $text];

			foreach ($this->inlineTypes[$marker] as $inlineType) {
				# check to see if the current inline type is nestable in the current context

				if (!empty($nonNestables) && in_array($inlineType, $nonNestables, true)) {
					continue;
				}

				$inline = $this->{'inline' . $inlineType}($Excerpt);

				if (!isset($inline)) {
					continue;
				}

				# makes sure that the inline belongs to "our" marker

				if (isset($inline['position']) && $inline['position'] > $markerPosition) {
					continue;
				}

				# sets a default inline position

				if (!isset($inline['position'])) {
					$inline['position'] = $markerPosition;
				}

				# cause the new element to 'inherit' our non nestables

				foreach ($nonNestables as $non_nestable) {
					$inline['element']['nonNestables'][] = $non_nestable;
				}

				# the text that comes before the inline
				$unmarkedText = substr($text, 0, $inline['position']);

				# compile the unmarked text
				$markup .= $this->unmarkedText($unmarkedText);

				# compile the inline
				$markup .= $inline['markup'] ?? $this->element($inline['element']);

				# remove the examined text
				$text = substr($text, $inline['position'] + $inline['extent']);

				continue 2;
			}

			# the marker does not belong to an inline

			$unmarkedText = substr($text, 0, $markerPosition + 1);
			$markup .= $this->unmarkedText($unmarkedText);
			$text = substr($text, $markerPosition + 1);
		}

		$markup .= $this->unmarkedText($text);

		return $markup;
	}

	/**
	 * Alias for ->text().
	 *
	 * @param string $haystack
	 * @return string
	 */
	public function parse(string $haystack): string
	{
		return $this->text($haystack);
	}

	protected function lines(array $lines): string
	{
		$currentBlock = null;

		foreach ($lines as $line) {
			if (rtrim($line) === '') {
				if (isset($currentBlock)) {
					$currentBlock['interrupted'] = true;
				}

				continue;
			}

			if (strpos($line, "\t") !== false) {
				$parts = explode("\t", $line);
				$line = $parts[0];
				unset($parts[0]);

				foreach ($parts as $part) {
					$shortage = 4 - mb_strlen($line, 'utf-8') % 4;
					$line .= str_repeat(' ', $shortage);
					$line .= $part;
				}
			}

			$indent = 0;

			while (isset($line[$indent]) && $line[$indent] === ' ') {
				$indent++;
			}

			$text = $indent > 0 ? substr($line, $indent) : $line;
			$Line = ['body' => $line, 'indent' => $indent, 'text' => $text];

			if (isset($currentBlock['continuable'])) {
				$block = $this->{'block' . $currentBlock['type'] . 'Continue'}($Line, $currentBlock);

				if (isset($block)) {
					$currentBlock = $block;

					continue;
				}

				if ($this->isBlockCompletable($currentBlock['type'])) {
					$currentBlock = $this->{'block' . $currentBlock['type'] . 'Complete'}($currentBlock);
				}
			}

			# ~

			$marker = $text[0];
			$blockTypes = $this->unmarkedBlockTypes;

			if (isset($this->blockTypes[$marker])) {
				foreach ($this->blockTypes[$marker] as $blockType) {
					$blockTypes[] = $blockType;
				}
			}

			# ~

			foreach ($blockTypes as $blockType) {
				$block = $this->{'block' . $blockType}($Line, $currentBlock);

				if (isset($block)) {
					$block['type'] = $blockType;

					if (!isset($block['identified'])) {
						$blocks[] = $currentBlock;
						$block['identified'] = true;
					}

					if ($this->isBlockContinuable($blockType)) {
						$block['continuable'] = true;
					}

					$currentBlock = $block;

					continue 2;
				}
			}

			# ~

			if (isset($currentBlock) && !isset($currentBlock['type']) && !isset($currentBlock['interrupted'])) {
				$currentBlock['element']['text'] .= "\n" . $text;
			} else {
				$blocks[] = $currentBlock;
				$currentBlock = $this->paragraph($Line);
				$currentBlock['identified'] = true;
			}
		}

		if (isset($currentBlock['continuable']) && $this->isBlockCompletable($currentBlock['type'])) {
			$currentBlock = $this->{'block' . $currentBlock['type'] . 'Complete'}($currentBlock);
		}

		$blocks[] = $currentBlock;

		unset($blocks[0]);

		$markup = '';

		foreach ($blocks as $block) {
			if (isset($block['hidden'])) {
				continue;
			}

			$markup .= "\n";
			$markup .= $block['markup'] ?? $this->element($block['element']);
		}

		$markup .= "\n";

		return $markup;
	}

	protected function isBlockContinuable($type): bool
	{
		return method_exists($this, 'block' . $type . 'Continue');
	}

	protected function isBlockCompletable(string $type): bool
	{
		return method_exists($this, 'block' . $type . 'Complete');
	}

	protected function blockCode(array $Line, ?array $block = null): ?array
	{
		if (isset($block) && !isset($block['type']) && !isset($block['interrupted'])) {
			return null;
		}

		if ($Line['indent'] >= 4) {
			return [
				'element' => [
					'name' => 'pre',
					'handler' => 'element',
					'text' => [
						'name' => 'code',
						'text' => substr($Line['body'], 4),
					],
				],
			];
		}

		return null;
	}

	protected function blockCodeContinue(array $line, array $block): ?array
	{
		if ($line['indent'] >= 4) {
			if (isset($block['interrupted'])) {
				$block['element']['text']['text'] .= "\n";
				unset($block['interrupted']);
			}

			$block['element']['text']['text'] .= "\n";
			$text = substr($line['body'], 4);
			$block['element']['text']['text'] .= $text;

			return $block;
		}

		return null;
	}

	protected function blockCodeComplete(array $block): array
	{
		$text = $block['element']['text']['text'];
		$block['element']['text']['text'] = $text;

		return $block;
	}

	protected function blockComment(array $line): ?array
	{
		if ($this->markupEscaped || $this->safeMode) {
			return null;
		}

		if (isset($line['text'][3]) && $line['text'][3] === '-' && $line['text'][2] === '-' && $line['text'][1] === '!') {
			$block = [
				'markup' => $line['body'],
			];

			if (preg_match('/-->$/', $line['text'])) {
				$block['closed'] = true;
			}

			return $block;
		}

		return null;
	}

	protected function blockCommentContinue(array $line, array $block): ?array
	{
		if (isset($block['closed'])) {
			return null;
		}

		$block['markup'] .= "\n" . $line['body'];

		if (preg_match('/-->$/', $line['text'])) {
			$block['closed'] = true;
		}

		return $block;
	}

	protected function blockFencedCode(array $Line): ?array
	{
		if (preg_match('/^[' . $Line['text'][0] . ']{3,}[ ]*([^`]+)?[ ]*$/', $Line['text'], $matches)) {
			$element = [
				'name' => 'code',
				'text' => '',
			];

			if (isset($matches[1])) {
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
				$language = substr($matches[1], 0, strcspn($matches[1], " \t\n\f\r"));
				$class = 'language-' . $language;
				$element['attributes'] = [
					'class' => $class,
				];
			}

			return [
				'char' => $Line['text'][0],
				'element' => [
					'name' => 'pre',
					'handler' => 'element',
					'text' => $element,
				],
			];
		}

		return null;
	}

	protected function blockFencedCodeContinue(array $line, array $block): ?array
	{
		if (isset($block['complete'])) {
			return null;
		}

		if (isset($block['interrupted'])) {
			$block['element']['text']['text'] .= "\n";

			unset($block['interrupted']);
		}

		if (preg_match('/^' . $block['char'] . '{3,}[ ]*$/', $line['text'])) {
			$block['element']['text']['text'] = substr($block['element']['text']['text'], 1);
			$block['complete'] = true;

			return $block;
		}

		$block['element']['text']['text'] .= "\n" . $line['body'];

		return $block;
	}

	protected function blockFencedCodeComplete(array $block): array
	{
		$text = $block['element']['text']['text'];
		$block['element']['text']['text'] = $text;

		return $block;
	}

	protected function blockHeader(array $line): ?array
	{
		if (isset($line['text'][1])) {
			$level = 1;

			while (isset($line['text'][$level]) && $line['text'][$level] === '#') {
				$level++;
			}

			if ($level > 6) {
				return null;
			}

			$Block = [
				'element' => [
					'name' => 'h' . min(6, $level),
					'text' => trim($line['text'], '# '),
					'handler' => 'line',
				],
			];

			return $Block;
		}

		return null;
	}

	protected function blockList(array $Line): ?array
	{
		[$name, $pattern] = $Line['text'][0] <= '-' ? ['ul', '[*+-]'] : ['ol', '[0-9]+[.]'];

		if (preg_match('/^(' . $pattern . '[ ]+)(.*)/', $Line['text'], $matches)) {
			$block = [
				'indent' => $Line['indent'],
				'pattern' => $pattern,
				'element' => [
					'name' => $name,
					'handler' => 'elements',
				],
			];

			if ($name === 'ol' && ($listStart = strstr($matches[0], '.', true)) !== '1') {
				$block['element']['attributes'] = ['start' => $listStart];
			}

			$block['li'] = [
				'name' => 'li',
				'handler' => 'li',
				'text' => [
					$matches[2],
				],
			];

			$block['element']['text'] [] = &$block['li'];

			return $block;
		}

		return null;
	}

	protected function blockListContinue(array $line, array $block): ?array
	{
		if ($block['indent'] === $line['indent'] && preg_match('/^' . $block['pattern'] . '(?:[ ]+(.*)|$)/', $line['text'], $matches)) {
			if (isset($block['interrupted'])) {
				$block['li']['text'] [] = '';
				$block['loose'] = true;

				unset($block['interrupted']);
			}

			unset($block['li']);
			$text = $matches[1] ?? '';

			$block['li'] = [
				'name' => 'li',
				'handler' => 'li',
				'text' => [
					$text,
				],
			];

			$block['element']['text'] [] = &$block['li'];

			return $block;
		}

		if ($line['text'][0] === '[' && $this->blockReference($line)) {
			return $block;
		}

		if (!isset($block['interrupted'])) {
			$text = preg_replace('/^[ ]{0,4}/', '', $line['body']);
			$block['li']['text'] [] = $text;

			return $block;
		}

		if ($line['indent'] > 0) {
			$block['li']['text'] [] = '';
			$text = preg_replace('/^[ ]{0,4}/', '', $line['body']);
			$block['li']['text'] [] = $text;
			unset($block['interrupted']);

			return $block;
		}

		return null;
	}

	protected function blockListComplete(array $block): array
	{
		if (isset($block['loose'])) {
			foreach ($block['element']['text'] as &$li) {
				if (end($li['text']) !== '') {
					$li['text'] [] = '';
				}
			}
		}

		return $block;
	}

	protected function blockQuote(array $line): ?array
	{
		if (preg_match('/^>[ ]?(.*)/', $line['text'], $matches)) {
			return [
				'element' => [
					'name' => 'blockquote',
					'handler' => 'lines',
					'text' => (array) $matches[1],
				],
			];
		}

		return null;
	}

	protected function blockQuoteContinue($line, array $block): ?array
	{
		if ($line['text'][0] === '>' && preg_match('/^>[ ]?(.*)/', $line['text'], $matches)) {
			if (isset($block['interrupted'])) {
				$block['element']['text'] [] = '';
				unset($block['interrupted']);
			}

			$block['element']['text'] [] = $matches[1];

			return $block;
		}

		if (!isset($block['interrupted'])) {
			$block['element']['text'] [] = $line['text'];

			return $block;
		}

		return null;
	}

	protected function blockRule(array $line): ?array
	{
		if (preg_match('/^([' . $line['text'][0] . '])([ ]*\1){2,}[ ]*$/', $line['text'])) {
			return [
				'element' => [
					'name' => 'hr',
				],
			];
		}

		return null;
	}

	protected function blockSetextHeader(array $line, array $block = null): ?array
	{
		if (!isset($block) || isset($block['type']) || isset($block['interrupted'])) {
			return null;
		}

		if (rtrim($line['text'], $line['text'][0]) === '') {
			$block['element']['name'] = $line['text'][0] === '=' ? 'h1' : 'h2';

			return $block;
		}

		return null;
	}

	protected function blockMarkup(array $line): ?array
	{
		if ($this->markupEscaped || $this->safeMode) {
			return null;
		}

		if (preg_match('/^<(\w[\w-]*)(?:[ ]*' . $this->regexHtmlAttribute . ')*[ ]*(\/)?>/', $line['text'], $matches)) {
			$element = strtolower($matches[1]);

			if (in_array($element, $this->textLevelElements, true)) {
				return null;
			}

			$block = [
				'name' => $matches[1],
				'depth' => 0,
				'markup' => $line['text'],
			];

			$length = strlen($matches[0]);
			$remainder = substr($line['text'], $length);

			if (trim($remainder) === '') {
				if (isset($matches[2]) || in_array($matches[1], $this->voidElements, true)) {
					$block['closed'] = true;

					$block['void'] = true;
				}
			} else {
				if (isset($matches[2]) || in_array($matches[1], $this->voidElements, true)) {
					return null;
				}

				if (preg_match('/<\/' . $matches[1] . '>[ ]*$/i', $remainder)) {
					$block['closed'] = true;
				}
			}

			return $block;
		}

		return null;
	}

	protected function blockMarkupContinue(array $line, array $block): ?array
	{
		if (isset($block['closed'])) {
			return null;
		}

		if (preg_match('/^<' . $block['name'] . '(?:[ ]*' . $this->regexHtmlAttribute . ')*[ ]*>/i', $line['text'])) {
			$block['depth']++; // Open
		}

		if (preg_match('/(.*?)<\/' . $block['name'] . '>[ ]*$/i', $line['text'], $matches)) {
			if ($block['depth'] > 0) { // Close
				$block['depth']--;
			} else {
				$block['closed'] = true;
			}
		}

		if (isset($block['interrupted'])) {
			$block['markup'] .= "\n";
			unset($block['interrupted']);
		}

		$block['markup'] .= "\n" . $line['body'];

		return $block;
	}

	protected function blockReference(array $line): ?array
	{
		if (preg_match('/^\[(.+?)\]:[ ]*<?(\S+?)>?(?:[ ]+["\'(](.+)["\')])?[ ]*$/', $line['text'], $matches)) {
			$data = [
				'url' => $matches[2],
				'title' => null,
			];

			if (isset($matches[3])) {
				$data['title'] = $matches[3];
			}

			$this->DefinitionData['Reference'][strtolower($matches[1])] = $data;

			return [
				'hidden' => true,
			];
		}

		return null;
	}

	protected function blockTable(array $line, ?array $block = null): ?array
	{
		if (!isset($block) || isset($block['type']) || isset($block['interrupted'])) {
			return null;
		}

		if (rtrim($line['text'], ' -:|') === '' && strpos($block['element']['text'], '|') !== false) {
			$alignments = [];

			foreach (explode('|', trim(trim($line['text']), '|')) as $dividerCell) {
				$dividerCell = trim($dividerCell);

				if ($dividerCell === '') {
					continue;
				}

				$alignment = null;

				if (strpos($dividerCell, ':') === 0) {
					$alignment = 'left';
				}

				if (substr($dividerCell, -1) === ':') {
					$alignment = $alignment === 'left' ? 'center' : 'right';
				}

				$alignments[] = $alignment;
			}

			$headerElements = [];
			$header = trim(trim($block['element']['text']), '|');
			$headerCells = explode('|', $header);

			foreach ($headerCells as $index => $headerCell) {
				$headerCell = trim($headerCell);

				$HeaderElement = [
					'name' => 'th',
					'text' => $headerCell,
					'handler' => 'line',
				];

				if (isset($alignments[$index])) {
					$alignment = $alignments[$index];

					$HeaderElement['attributes'] = [
						'style' => 'text-align: ' . $alignment . ';',
					];
				}

				$headerElements[] = $HeaderElement;
			}

			$block = [
				'alignments' => $alignments,
				'identified' => true,
				'element' => [
					'name' => 'table',
					'handler' => 'elements',
				],
			];

			$block['element']['text'] [] = [
				'name' => 'thead',
				'handler' => 'elements',
			];

			$block['element']['text'] [] = [
				'name' => 'tbody',
				'handler' => 'elements',
				'text' => [],
			];

			$block['element']['text'][0]['text'] [] = [
				'name' => 'tr',
				'handler' => 'elements',
				'text' => $headerElements,
			];

			return $block;
		}

		return null;
	}

	protected function blockTableContinue(array $line, array $block): ?array
	{
		if (isset($block['interrupted'])) {
			return null;
		}

		if ($line['text'][0] === '|' || strpos($line['text'], '|')) {
			$elements = [];
			$row = trim(trim($line['text']), '|');
			preg_match_all('/(?:(\\\\[|])|[^|`]|`[^`]+`|`)+/', $row, $matches);

			foreach ($matches[0] as $index => $cell) {
				$cell = trim($cell);

				$element = [
					'name' => 'td',
					'handler' => 'line',
					'text' => $cell,
				];

				if (isset($block['alignments'][$index])) {
					$element['attributes'] = [
						'style' => 'text-align: ' . $block['alignments'][$index] . ';',
					];
				}

				$elements[] = $element;
			}

			$element = [
				'name' => 'tr',
				'handler' => 'elements',
				'text' => $elements,
			];

			$block['element']['text'][1]['text'] [] = $element;

			return $block;
		}

		return null;
	}

	protected function paragraph(array $line): array
	{
		return [
			'element' => [
				'name' => 'p',
				'text' => $line['text'],
				'handler' => 'line',
			],
		];
	}

	protected function inlineCode(array $excerpt): ?array
	{
		$marker = $excerpt['text'][0];

		if (preg_match('/^(' . $marker . '+)[ ]*(.+?)[ ]*(?<!' . $marker . ')\1(?!' . $marker . ')/s', $excerpt['text'], $matches)) {
			return [
				'extent' => strlen($matches[0]),
				'element' => [
					'name' => 'code',
					'text' => (string) preg_replace("/[ ]*\n/", ' ', $matches[2]),
				],
			];
		}

		return null;
	}

	protected function inlineEmailTag(array $excerpt): ?array
	{
		if (strpos($excerpt['text'], '>') !== false && preg_match('/^<((mailto:)?\S+?@\S+?)>/i', $excerpt['text'], $matches)) {
			return [
				'extent' => strlen($matches[0]),
				'element' => [
					'name' => 'a',
					'text' => $matches[1],
					'attributes' => [
						'href' => !isset($matches[2]) ? 'mailto:' . $matches[1] : $matches[1],
					],
				],
			];
		}

		return null;
	}

	protected function inlineEmphasis(array $excerpt): ?array
	{
		if (!isset($excerpt['text'][1])) {
			return null;
		}

		$marker = $excerpt['text'][0];

		if ($excerpt['text'][1] === $marker && preg_match($this->StrongRegex[$marker], $excerpt['text'], $matches)) {
			$emphasis = 'strong';
		} elseif (preg_match($this->EmRegex[$marker], $excerpt['text'], $matches)) {
			$emphasis = 'em';
		} else {
			return null;
		}

		return [
			'extent' => strlen($matches[0]),
			'element' => [
				'name' => $emphasis,
				'handler' => 'line',
				'text' => $matches[1],
			],
		];
	}

	protected function inlineEscapeSequence(array $excerpt): ?array
	{
		if (isset($excerpt['text'][1]) && in_array($excerpt['text'][1], $this->specialCharacters, true)) {
			return [
				'markup' => $excerpt['text'][1],
				'extent' => 2,
			];
		}

		return null;
	}

	protected function inlineImage(array $excerpt): ?array
	{
		if (!isset($excerpt['text'][1]) || $excerpt['text'][1] !== '[') {
			return null;
		}

		$excerpt['text'] = substr($excerpt['text'], 1);
		$Link = $this->inlineLink($excerpt);

		if ($Link === null) {
			return null;
		}

		$inline = [
			'extent' => $Link['extent'] + 1,
			'element' => [
				'name' => 'img',
				'attributes' => [
					'src' => $Link['element']['attributes']['href'],
					'alt' => $Link['element']['text'],
				],
			],
		];

		$inline['element']['attributes'] += $Link['element']['attributes'];
		unset($inline['element']['attributes']['href']);

		return $inline;
	}

	protected function inlineLink(array $excerpt): ?array
	{
		$element = [
			'name' => 'a',
			'handler' => 'line',
			'nonNestables' => ['Url', 'Link'],
			'text' => null,
			'attributes' => [
				'href' => null,
				'title' => null,
			],
		];

		$extent = 0;
		$remainder = $excerpt['text'];

		if (preg_match('/\[((?:[^][]++|(?R))*+)\]/', $remainder, $matches)) {
			$element['text'] = $matches[1];
			$extent += strlen($matches[0]);
			$remainder = substr($remainder, $extent);
		} else {
			return null;
		}

		if (preg_match('/^[(]\s*+((?:[^ ()]++|[(][^ )]+[)])++)(?:[ ]+("[^"]*"|\'[^\']*\'))?\s*[)]/', $remainder, $matches)) {
			$element['attributes']['href'] = $matches[1];
			$extent += strlen($matches[0]);

			if (isset($matches[2])) {
				$element['attributes']['title'] = substr($matches[2], 1, -1);
			}
		} else {
			if (preg_match('/^\s*\[(.*?)\]/', $remainder, $matches)) {
				$definition = ($matches[1] ?? '') !== '' ? $matches[1] : $element['text'];
				$definition = strtolower($definition);
				$extent += strlen($matches[0]);
			} else {
				$definition = strtolower($element['text']);
			}

			if (!isset($this->DefinitionData['Reference'][$definition])) {
				return null;
			}

			$element['attributes']['href'] = $this->DefinitionData['Reference'][$definition]['url'];
			$element['attributes']['title'] = $this->DefinitionData['Reference'][$definition]['title'];
		}

		return [
			'extent' => $extent,
			'element' => $element,
		];
	}

	protected function inlineMarkup(array $excerpt): ?array
	{
		if ($this->markupEscaped || $this->safeMode || strpos($excerpt['text'], '>') === false) {
			return null;
		}

		if ($excerpt['text'][1] === '/' && preg_match('/^<\/\w[\w-]*[ ]*>/', $excerpt['text'], $matches)) {
			return [
				'markup' => $matches[0],
				'extent' => strlen($matches[0]),
			];
		}

		if ($excerpt['text'][1] === '!' && preg_match('/^<!---?[^>-](?:-?[^-])*-->/', $excerpt['text'], $matches)) {
			return [
				'markup' => $matches[0],
				'extent' => strlen($matches[0]),
			];
		}

		if ($excerpt['text'][1] !== ' ' && preg_match('/^<\w[\w-]*(?:[ ]*' . $this->regexHtmlAttribute . ')*[ ]*\/?>/s', $excerpt['text'], $matches)) {
			return [
				'markup' => $matches[0],
				'extent' => strlen($matches[0]),
			];
		}

		return null;
	}

	protected function inlineSpecialCharacter(array $excerpt): ?array
	{
		static $specialCharacter = ['>' => 'gt', '<' => 'lt', '"' => 'quot'];

		if ($excerpt['text'][0] === '&' && !preg_match('/^&#?\w+;/', $excerpt['text'])) {
			return [
				'markup' => '&amp;',
				'extent' => 1,
			];
		}

		if (isset($specialCharacter[$excerpt['text'][0]])) {
			return [
				'markup' => '&' . $specialCharacter[$excerpt['text'][0]] . ';',
				'extent' => 1,
			];
		}

		return null;
	}

	protected function inlineStrikethrough(array $excerpt): ?array
	{
		if (!isset($excerpt['text'][1])) {
			return null;
		}

		if ($excerpt['text'][1] === '~' && preg_match('/^~~(?=\S)(.+?)(?<=\S)~~/', $excerpt['text'], $matches)) {
			return [
				'extent' => strlen($matches[0]),
				'element' => [
					'name' => 'del',
					'text' => $matches[1],
					'handler' => 'line',
				],
			];
		}

		return null;
	}

	protected function inlineUrl(array $excerpt): ?array
	{
		if ($this->urlsLinked !== true || !isset($excerpt['text'][2]) || $excerpt['text'][2] !== '/') {
			return null;
		}

		if (preg_match('/\bhttps?:[\/]{2}[^\s<]+\b\/*/ui', $excerpt['context'], $matches, PREG_OFFSET_CAPTURE)) {
			return [
				'extent' => strlen($matches[0][0]),
				'position' => $matches[0][1],
				'element' => [
					'name' => 'a',
					'text' => $matches[0][0],
					'attributes' => [
						'href' => $matches[0][0],
					],
				],
			];
		}

		return null;
	}

	protected function inlineUrlTag(array $excerpt): array
	{
		if (strpos($excerpt['text'], '>') !== false && preg_match('/^<(\w+:\/{2}[^ >]+)>/i', $excerpt['text'], $matches)) {
			return [
				'extent' => strlen($matches[0]),
				'element' => [
					'name' => 'a',
					'text' => $matches[1],
					'attributes' => [
						'href' => $matches[1],
					],
				],
			];
		}

		return null;
	}

	protected function unmarkedText(string $haystack): string
	{
		if ($this->breaksEnabled) {
			$haystack = (string) preg_replace('/[ ]*\n/', "<br />\n", $haystack);
		} else {
			$haystack = (string) preg_replace('/(?:[ ]{2,}|[ ]*\\\\)\n/', "<br />\n", $haystack);
			$haystack = str_replace(" \n", "\n", $haystack);
		}

		return $haystack;
	}

	protected function element(array $element): string
	{
		if ($this->safeMode) {
			$element = $this->sanitiseElement($element);
		}

		$markup = '<' . $element['name'];

		if (isset($element['attributes'])) {
			foreach ($element['attributes'] as $name => $value) {
				if ($value === null) {
					continue;
				}

				$markup .= ' ' . $name . '="' . self::escape($value) . '"';
			}
		}

		if (isset($element['text'])) {
			$markup .= '>';

			if (!isset($element['nonNestables'])) {
				$element['nonNestables'] = [];
			}

			if (isset($element['handler'])) {
				$markup .= $this->{$element['handler']}($element['text'], $element['nonNestables']);
			} else {
				$markup .= self::escape($element['text'], true);
			}

			$markup .= '</' . $element['name'] . '>';
		} else {
			$markup .= ' />';
		}

		return $markup;
	}

	protected function elements(array $Elements): string
	{
		$markup = '';

		foreach ($Elements as $Element) {
			$markup .= "\n" . $this->element($Element);
		}

		return $markup . "\n";
	}

	protected function li(array $lines): string
	{
		$markup = $this->lines($lines);

		if (!\in_array('', $lines, true) && strpos($trimmedMarkup = trim($markup), '<p>') === 0) {
			$markup = substr_replace(substr($trimmedMarkup, 3), '', strpos($markup, '</p>'), 4);
		}

		return $markup;
	}

	protected function sanitiseElement(array $element): array
	{
		static $goodAttribute = '/^[a-zA-Z0-9][a-zA-Z0-9-_]*+$/';
		static $safeUrlNameToAtt = [
			'a' => 'href',
			'img' => 'src',
		];

		if (isset($safeUrlNameToAtt[$element['name']])) {
			$element = $this->filterUnsafeUrlInAttribute($element, $safeUrlNameToAtt[$element['name']]);
		}

		if (!empty($element['attributes'])) {
			foreach ($element['attributes'] as $att => $val) {
				if (!preg_match($goodAttribute, $att)) { // filter out badly parsed attribute
					unset($element['attributes'][$att]);
				} elseif (self::striAtStart($att, 'on')) { // dump onevent attribute
					unset($element['attributes'][$att]);
				}
			}
		}

		return $element;
	}

	protected function filterUnsafeUrlInAttribute(array $element, $attribute): array
	{
		foreach ($this->safeLinksWhitelist as $scheme) {
			if (self::striAtStart($element['attributes'][$attribute], $scheme)) {
				return $element;
			}
		}

		$element['attributes'][$attribute] = str_replace(':', '%3A', $element['attributes'][$attribute]);

		return $element;
	}

}
