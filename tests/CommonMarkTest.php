<?php

namespace Erusev\Parsedown\Tests;

/**
 * Test Parsedown against cached expect-to-pass CommonMark spec examples
 *
 * This test suite runs tests the same way as `test/CommonMarkTestWeak.php`,
 * but uses a cached set of CommonMark spec examples in `test/commonmark/`.
 * It is executed along with Parsedown's default test suite and runs various
 * CommonMark spec examples, which are expected to pass. If they don't pass,
 * the Parsedown build fails. The intention of this test suite is to make sure,
 * that previously passed CommonMark spec examples don't fail due to unwanted
 * side-effects of code changes.
 *
 * You can re-create the `test/commonmark/` directory by executing the PHPUnit
 * group `update`. The test suite will then run `test/CommonMarkTestWeak.php`
 * and create files with the Markdown source and the resulting HTML markup of
 * all passed tests. The command to execute looks like the following:
 *
 *     $ phpunit --group update
 *
 * @link http://commonmark.org/ CommonMark
 */
class CommonMarkTest extends CommonMarkTestStrict
{
    /**
     * @return array<int, array{id: int, section: string, markdown: string, expectedHtml: string}>
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public function data()
    {
        $data = [];

        $dir = static::getDataDir();
        $files = @\scandir($dir);
        if (!empty($files)) {
            foreach ($files as $file) {
                if (($file === '.') || ($file === '..')) {
                    continue;
                }

                if (\substr($file, -3) === '.md') {
                    $testName = \substr($file, 0, -3);
                    if (\file_exists($dir . $testName . '.html')) {
                        \preg_match('/^(\d+)-(.*)$/', $testName, $matches);
                        $id = isset($matches[1]) ? \intval($matches[1]) : 0;
                        $section = isset($matches[2]) ? \preg_replace('/_+/', ' ', $matches[2]) : '';

                        $markdown = \file_get_contents($dir . $testName . '.md');
                        $expectedHtml = \file_get_contents($dir . $testName . '.html');

                        $data[$id] = [
                            'id' => $id,
                            'section' => $section,
                            'markdown' => $markdown,
                            'expectedHtml' => $expectedHtml
                        ];
                    }
                }
            }
        } else {
            $this->fail('The CommonMark cache folder ' . $dir . ' is empty or not readable.');
        }

        return $data;
    }

    /**
     * @group update
     * @dataProvider dataUpdate
     * @param int $id
     * @param string $section
     * @param string $markdown
     * @param string $expectedHtml
     * @return void
     * @throws \PHPUnit\Framework\AssertionFailedError
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testUpdateDatabase($id, $section, $markdown, $expectedHtml)
    {
        parent::testExample($id, $section, $markdown, $expectedHtml);

        // you can only get here when the test passes
        $dir = static::getDataDir(true);
        $basename = \strval($id) . '-' . \preg_replace('/[^\w.-]/', '_', $section);
        \file_put_contents($dir . $basename . '.md', $markdown);
        \file_put_contents($dir . $basename . '.html', $expectedHtml);
    }

    /**
     * @return array<int, array{id: int, section: string, markdown: string, expectedHtml: string}>
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public function dataUpdate()
    {
        return parent::data();
    }

    /**
     * @param bool $mkdir
     * @return string
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
    public static function getDataDir($mkdir = false)
    {
        $dir = __DIR__ . '/commonmark/';

        if ($mkdir) {
            if (!\file_exists($dir)) {
                @\mkdir($dir);
            }
            if (!\is_dir($dir)) {
                static::fail('Unable to create CommonMark cache folder ' . $dir . '.');
            }
        }

        return $dir;
    }
}
