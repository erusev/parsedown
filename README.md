## Parsedown

Fast and consistent [Markdown][1] parser for PHP.

[Home](http://parsedown.org) &middot; [Demo](http://parsedown.org/explorer/) &middot; [Tests](http://parsedown.org/tests/)

### Installation

Include `Parsedown.php` or install [the composer package](https://packagist.org/packages/erusev/parsedown).

### Example

```php
$text = 'Hello **Parsedown**!';

$result = Parsedown::instance()->parse($text);

echo $result; # prints: <p>Hello <strong>Parsedown</strong>!</p>
```

[1]: http://daringfireball.net/projects/markdown/
