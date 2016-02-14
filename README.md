> You might also like [Caret](http://caret.io?ref=parsedown) - our Markdown editor for Mac / Windows / Linux.

## Parsedown

[![Build Status](https://img.shields.io/travis/erusev/parsedown/master.svg?style=flat-square)](https://travis-ci.org/erusev/parsedown)
<!--[![Total Downloads](http://img.shields.io/packagist/dt/erusev/parsedown.svg?style=flat-square)](https://packagist.org/packages/erusev/parsedown)-->

Better Markdown Parser in PHP

[Demo](http://parsedown.org/demo) |
[Benchmarks](http://parsedown.org/speed) |
[Tests](http://parsedown.org/tests/) |
[Documentation](https://github.com/erusev/parsedown/wiki/)

### Features

* Super Fast
* [GitHub flavored](https://help.github.com/articles/github-flavored-markdown)
* Extensible
* Tested in 5.3 to 7.0 and in HHVM
* [Markdown Extra extension](https://github.com/erusev/parsedown-extra)

### Installation

Include `Parsedown.php` or install [the composer package](https://packagist.org/packages/erusev/parsedown).

### Example

``` php
$Parsedown = new Parsedown();

echo $Parsedown->text('Hello _Parsedown_!'); # prints: <p>Hello <em>Parsedown</em>!</p>
```

### Hooks
This fork adds hook functionality that allows you to extend Parsedown.  To use, write a class that declares static methods with the same name as the methods which 
you would like to modify the output.  Then register the class with Parsedown.
#### Hook Example
``` php
class HookExample
{
	public static function inlineUrl($url) {
		$url['element']['attributes']['rel'] = 'nofollow';
		return $url;
	}
}

Parsedown::registerHook('HookExample');
```

More examples in [the wiki](https://github.com/erusev/parsedown/wiki/) and in [this video tutorial](http://youtu.be/wYZBY8DEikI).

### Questions

**How does Parsedown work?**

It tries to read Markdown like a human. First, it looks at the lines. It’s interested in how the lines start. This helps it recognise blocks. It knows, for example, that if a line start with a `-` then it perhaps belong to a list. Once it recognises the blocks, it continues to the content. As it reads, it watches out for special characters. This helps it recognise inline elements (or inlines).

We call this approach "line based". We believe that Parsedown is the first Markdown parser to use it. Since the release of Parsedown, other developers have used the same approach to develop other Markdown parsers in PHP and in other languages.

**Is it compliant with CommonMark?**

It passes most of the CommonMark tests. Most of the tests that don't pass deal with cases that are quite uncommon. Still, as CommonMark matures, compliance should improve.

**Who uses it?**

[phpDocumentor](http://www.phpdoc.org/), [October CMS](http://octobercms.com/), [Bolt CMS](http://bolt.cm/), [Kirby CMS](http://getkirby.com/), [Grav CMS](http://getgrav.org/), [Statamic CMS](http://www.statamic.com/), [Herbie CMS](http://www.getherbie.org/), [RaspberryPi.org](http://www.raspberrypi.org/) and [more](https://www.versioneye.com/php/erusev:parsedown/references).

**How can I help?**

Use it, star it, share it and if you feel generous, [donate](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=528P3NZQMP8N2).
