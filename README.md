## Parsedown

Better [Markdown](http://daringfireball.net/projects/markdown/) parser for PHP.

***

[demo](http://parsedown.org/demo) &middot; [tests](http://parsedown.org/tests/)

***

### Features

* [fast](http://parsedown.org/speed)
* [consistent](http://parsedown.org/consistency)
* [GitHub Flavored](https://help.github.com/articles/github-flavored-markdown)
* [tested](https://travis-ci.org/erusev/parsedown) in PHP 5.2, 5.3, 5.4 and 5.5
* friendly to international input

### Installation

Include `Parsedown.php` or install [the composer package](https://packagist.org/packages/erusev/parsedown).

### Example

```php
$text = 'Hello **Parsedown**!';

$result = Parsedown::instance()->parse($text);

echo $result; # prints: <p>Hello <strong>Parsedown</strong>!</p>
```
