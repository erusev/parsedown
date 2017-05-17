> You might also like [Caret](https://caret.io?ref=parsedown) - our Markdown editor for Mac / Windows / Linux.

## Parsedown

[![Build Status](https://img.shields.io/travis/erusev/parsedown/master.svg?style=flat-square)](https://travis-ci.org/erusev/parsedown)
<!--[![Total Downloads](http://img.shields.io/packagist/dt/erusev/parsedown.svg?style=flat-square)](https://packagist.org/packages/erusev/parsedown)-->

Better Markdown Parser in PHP

[Demo](http://parsedown.org/demo) |
[Benchmarks](http://parsedown.org/speed) |
[Tests](http://parsedown.org/tests/) |
[Documentation](https://github.com/erusev/parsedown/wiki/)

### Features

* One File
* Super Fast
* Extensible
* [GitHub flavored](https://help.github.com/articles/github-flavored-markdown)
* Tested in 5.3 to 7.1 and in HHVM
* [Markdown Extra extension](https://github.com/erusev/parsedown-extra)

### Installation

Include `Parsedown.php` or install [the composer package](https://packagist.org/packages/erusev/parsedown).

### Example

``` php
$Parsedown = new Parsedown();

echo $Parsedown->text('Hello _Parsedown_!'); # prints: <p>Hello <em>Parsedown</em>!</p>
```

More examples in [the wiki](https://github.com/erusev/parsedown/wiki/) and in [this video tutorial](http://youtu.be/wYZBY8DEikI).

### Event listeners

For example, you have a block with code in your MD syntax, but it not always present. May happen that you want to highlight it only when it exist. An event listener help you to do that.

```php
$Parsedown = new Parsedown();
/**
 * @param string $Event
 *   Event name. All available events can be returned by {@link getUniqueBlockNames()} method.
 * @param callable $Callback
 *   Callback function.
 * @param bool $Onetime
 *   Execute callback function only once.
 *
 * @throws \InvalidArgumentException
 *   When you trying to attach non-existent event.
 * @throws \RuntimeException
 *   When $Callback is not valid callback function.
 *
 * @return self
 */
$Parsedown->addEventListener('FencedCode', function (array $block) {
    add_css_function('/highlight/styles/default.css');
    add_js_function('/highlight/highlight.js');
}, true);
```

Also, you can attach an event listener that will be executed on every block processing.

```php
$Parsedown = new Parsedown();
// Set the "data-id=test" for each list element in HTML.
$Parsedown->addEventListener('List', function (array &$block) {
    $block['element']['attributes']['data-id'] = 'test';
});
```

Or, imagine that you need to colorize table rows.

```php
$Parsedown->addEventListener('Table', function (array &$block) {
    $item = 0;

    // thead and tbody.
    foreach ($block['element']['text'] as &$section) {
        // tr.
        foreach ($section['text'] as &$rows) {
            $rows['attributes']['class'] = $item++ % 2 ? 'odd' : 'even';
        }
    }
});
```

### Questions

**How does Parsedown work?**

It tries to read Markdown like a human. First, it looks at the lines. It’s interested in how the lines start. This helps it recognise blocks. It knows, for example, that if a line starts with a `-` then perhaps it belongs to a list. Once it recognises the blocks, it continues to the content. As it reads, it watches out for special characters. This helps it recognise inline elements (or inlines).

We call this approach "line based". We believe that Parsedown is the first Markdown parser to use it. Since the release of Parsedown, other developers have used the same approach to develop other Markdown parsers in PHP and in other languages.

**Is it compliant with CommonMark?**

It passes most of the CommonMark tests. Most of the tests that don't pass deal with cases that are quite uncommon. Still, as CommonMark matures, compliance should improve.

**Who uses it?**

[phpDocumentor](http://www.phpdoc.org/), [October CMS](http://octobercms.com/), [Bolt CMS](http://bolt.cm/), [Kirby CMS](http://getkirby.com/), [Grav CMS](http://getgrav.org/), [Statamic CMS](http://www.statamic.com/), [Herbie CMS](http://www.getherbie.org/), [RaspberryPi.org](http://www.raspberrypi.org/), [Symfony demo](https://github.com/symfony/symfony-demo) and [more](https://packagist.org/packages/erusev/parsedown/dependents).

**How can I help?**

Use it, star it, share it and if you feel generous, [donate](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=528P3NZQMP8N2).
