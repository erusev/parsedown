## Parsedown

Better [Markdown][1] parser for PHP.

***

[home](http://parsedown.org/) &middot; [demo](http://parsedown.org/demo) &middot; [tests](http://parsedown.org/tests/)

***

Features:

* [fast](http://parsedown.org/speed)
* [consistent](http://parsedown.org/consistency)
* [ GitHub Flavored ][2]
* tested in PHP 5.2, 5.3, 5.4 and 5.5
* friendly to international input

### Installation

Include `Parsedown.php` or install [the composer package](https://packagist.org/packages/erusev/parsedown).

### Example

```php
$text = 'Hello **Parsedown**!';

$result = Parsedown::instance()->parse($text);

echo $result; # prints: <p>Hello <strong>Parsedown</strong>!</p>
```

[1]: http://daringfireball.net/projects/markdown/
[2]: https://help.github.com/articles/github-flavored-markdown 
