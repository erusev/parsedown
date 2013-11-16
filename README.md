## Parsedown

Parsedown is a Markdown parser for PHP. It is fast, consistent and easy to use.

[Home](http://parsedown.org)  &middot; [Demo](http://parsedown.org/explorer/) &middot; [Tests](http://parsedown.org/tests/)

### Installation

Include `Parsedown.php` or install [the composer package](https://packagist.org/packages/erusev/parsedown).

### Example

```php
$text = 'Hello **Parsedown**!';

$result = Parsedown::instance()->parse($text);

echo $result; # prints: <p>Hello <strong>Parsedown</strong>!</p>
```
