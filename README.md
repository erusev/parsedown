> I also make [Caret](https://caret.io?ref=parsedown) - a Markdown editor for Mac and PC.

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
* No Dependencies
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

### Security

Parsedown is capable of escaping user-input within the HTML that it generates. Additionally Parsedown will apply sanitisation to additional scripting vectors (such as scripting link destinations) that are introduced by the markdown syntax itself.

To tell Parsedown that it is processing untrusted user-input, use the following:
```php
$parsedown = new Parsedown;
$parsedown->setSafeMode(true);
```

If instead, you wish to allow HTML within untrusted user-input, but still want output to be free from XSS it is recommended that you make use of a HTML sanitiser that allows HTML tags to be whitelisted, like [HTML Purifier](http://htmlpurifier.org/).

In both cases you should strongly consider employing defence-in-depth measures, like [deploying a Content-Security-Policy](https://scotthelme.co.uk/content-security-policy-an-introduction/) (a browser security feature) so that your page is likely to be safe even if an attacker finds a vulnerability in one of the first lines of defence above.

#### Security of Parsedown Extensions

Safe mode does not necessarily yield safe results when using extensions to Parsedown. Extensions should be evaluated on their own to determine their specific safety against XSS.

### Escaping HTML
> ⚠️  **WARNING:** This method isn't safe from XSS!

If you wish to escape HTML **in trusted input**, you can use the following:
```php
$parsedown = new Parsedown;
$parsedown->setMarkupEscaped(true);
```

Beware that this still allows users to insert unsafe scripting vectors, such as links like `[xss](javascript:alert%281%29)`.

### Questions

**How does Parsedown work?**

It tries to read Markdown like a human. First, it looks at the lines. It’s interested in how the lines start. This helps it recognise blocks. It knows, for example, that if a line starts with a `-` then perhaps it belongs to a list. Once it recognises the blocks, it continues to the content. As it reads, it watches out for special characters. This helps it recognise inline elements (or inlines).

We call this approach "line based". We believe that Parsedown is the first Markdown parser to use it. Since the release of Parsedown, other developers have used the same approach to develop other Markdown parsers in PHP and in other languages.

**Is it compliant with CommonMark?**

It passes most of the CommonMark tests. Most of the tests that don't pass deal with cases that are quite uncommon. Still, as CommonMark matures, compliance should improve.

**Who uses it?**

[Laravel Framework](https://laravel.com/), [Bolt CMS](http://bolt.cm/), [Grav CMS](http://getgrav.org/), [Herbie CMS](http://www.getherbie.org/), [Kirby CMS](http://getkirby.com/), [October CMS](http://octobercms.com/), [Pico CMS](http://picocms.org), [Statamic CMS](http://www.statamic.com/), [phpDocumentor](http://www.phpdoc.org/), [RaspberryPi.org](http://www.raspberrypi.org/), [Symfony demo](https://github.com/symfony/symfony-demo) and [more](https://packagist.org/packages/erusev/parsedown/dependents).

**How can I help?**

Use it, star it, share it and if you feel generous, [donate](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=528P3NZQMP8N2).
