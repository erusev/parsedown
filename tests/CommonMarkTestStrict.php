<?php

namespace Erusev\Parsedown\Tests;

use Erusev\Parsedown\Components\Inlines\Url;
use Erusev\Parsedown\Configurables\InlineTypes;
use Erusev\Parsedown\Configurables\StrictMode;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\State;
use PHPUnit\Framework\TestCase;

/**
 * Test Parsedown against the CommonMark spec
 *
 * @link http://commonmark.org/ CommonMark
 */
class CommonMarkTestStrict extends TestCase
{
    const SPEC_URL = 'https://raw.githubusercontent.com/jgm/CommonMark/master/spec.txt';
    const SPEC_LOCAL_CACHE = 'spec_cache.txt';
    const SPEC_CACHE_SECONDS = 300;

    /** @var Parsedown */
    protected $Parsedown;

    /**
     * @param string|null $name
     * @param array $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        $this->Parsedown = new Parsedown(new State([
            StrictMode::enabled(),
            InlineTypes::initial()->removing([Url::class]),
        ]));

        parent::__construct($name, $data, $dataName);
    }

    /**
     * @dataProvider data
     * @param int $_
     * @param string $__
     * @param string $markdown
     * @param string $expectedHtml
     * @return void
     * @throws \PHPUnit\Framework\AssertionFailedError
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testExample($_, $__, $markdown, $expectedHtml)
    {
        $actualHtml = $this->Parsedown->text($markdown);
        $this->assertEquals($expectedHtml, $actualHtml);
    }

    /**
     * @return string
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public function getSpec()
    {
        $specPath = __DIR__ .'/'.self::SPEC_LOCAL_CACHE;

        if (
            \is_file($specPath)
            && \time() - \filemtime($specPath) < self::SPEC_CACHE_SECONDS
        ) {
            $spec = \file_get_contents($specPath);
        } else {
            $spec = \file_get_contents(self::SPEC_URL);
            \file_put_contents($specPath, $spec);
        }

        if ($spec === false) {
            $this->fail('Unable to load CommonMark spec from ' . self::SPEC_URL);
        }

        return $spec;
    }

    /**
     * @return array<int, array{id: int, section: string, markdown: string, expectedHtml: string}>
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public function data()
    {
        $spec = $this->getSpec();

        $spec = \str_replace("\r\n", "\n", $spec);
        /** @var string */
        $spec = \strstr($spec, '<!-- END TESTS -->', true);

        $matches = [];
        \preg_match_all('/^`{32} example\n((?s).*?)\n\.\n(?:|((?s).*?)\n)`{32}$|^#{1,6} *(.*?)$/m', $spec, $matches, \PREG_SET_ORDER);

        $data = [];
        $currentId = 0;
        $currentSection = '';
        /** @var array{0: string, 1: string, 2?: string, 3?: string} $match */
        foreach ($matches as $match) {
            if (isset($match[3])) {
                $currentSection = $match[3];
            } else {
                $currentId++;
                $markdown = \str_replace('→', "\t", $match[1]);
                $expectedHtml = isset($match[2]) ? \str_replace('→', "\t", $match[2]) : '';

                $data[$currentId] = [
                    'id' => $currentId,
                    'section' => $currentSection,
                    'markdown' => $markdown,
                    'expectedHtml' => $expectedHtml
                ];
            }
        }

        return $data;
    }
}
