# Creating Extensions for Parsedown v2

Parsedown v2 uses a composable plugin system. Extensions are encouraged to
implement the `StateBearer` interface. A `StateBearer` exposes a [`State`](../src/State.php)
object that describes how Parsedown should parse and render Markdown.

At a minimum an extension provides two methods:

```php
use Erusev\Parsedown\StateBearer;
use Erusev\Parsedown\State;

final class MyExtension implements StateBearer
{
    private $State;

    private function __construct(State $State)
    {
        $this->State = $State;
    }

    public static function from(StateBearer $StateBearer): self
    {
        $State = $StateBearer->state();
        // customise $State here

        return new self($State);
    }

    public function state(): State
    {
        return $this->State;
    }
}
```

Inside `from()` an extension mutates the supplied `State` to register new
`Configurable` instances or tweak existing ones. The resulting `State` is stored
and returned via `state()`.

### Adding settings

Configurables represent Parsedown's settings. To adjust one you obtain the
existing instance from the `State`, mutate or replace it, and then reinsert it
using `State::setting()`. For example the `Breaks` configurable controls whether
soft line breaks inside paragraphs become `<br>` elements:

```php
use Erusev\Parsedown\Configurables\Breaks;

public static function from(StateBearer $StateBearer): self
{
    $State = $StateBearer->state();

    $State = $State->setting(Breaks::enabled());

    return new self($State);
}
```

The same approach applies to any other `Configurable` object provided by
Parsedown or by other extensions.

## Example: adding a new inline

As a simple example we will implement a `Superscript` inline which recognises
`^text^` and outputs `<sup>text</sup>`. First create the inline class:

```php
namespace MyParsedownExtension;

use Erusev\Parsedown\Components\Inline;
use Erusev\Parsedown\Components\Inlines\WidthTrait;
use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Excerpt;
use Erusev\Parsedown\State;

final class Superscript implements Inline
{
    use WidthTrait; // provides $width helper

    private $text;

    private function __construct(string $text, int $width)
    {
        $this->text = $text;
        $this->width = $width;
    }

    public static function build(Excerpt $Excerpt, State $State): ?self
    {
        if (preg_match('/^\^(?=\S)(.+?)(?<=\S)\^/', $Excerpt->text(), $m)) {
            return new self($m[1], strlen($m[0]));
        }
        return null;
    }

    public function stateRenderable(): Handler
    {
        return new Handler(function (State $State) {
            return new Element('sup', [],
                $State->applyTo(Parsedown::line($this->text, $State))
            );
        });
    }
}
```

Next update the extension to register the inline type:

```php
use Erusev\Parsedown\Configurables\InlineTypes;

public static function from(StateBearer $StateBearer): self
{
    $State = $StateBearer->state();

    $InlineTypes = $State->get(InlineTypes::class)
        ->addingHighPrecedence('^', [Superscript::class]);

    $State = $State->setting($InlineTypes);

    return new self($State);
}
```

`addingHighPrecedence()` places the inline class at the beginning of the list
for its marker so it executes before existing types. Use
`addingLowPrecedence()` to append it instead.

The extension can now be composed with Parsedown:

```php
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\State;

$Parsedown = new Parsedown(MyExtension::from(new State));
$html = $Parsedown->toHtml('2^10^');
```

This pattern can be repeated to add blocks, render steps or additional
configuration values. See [`docs/Migrating-Extensions-v2.0.md`](Migrating-Extensions-v2.0.md)
for more details on the architecture.

## Example: adding a new block

Blocks are implemented in much the same way. Below is a minimal `Callout` block
that converts lines beginning with `!!!` into a `<div class="callout">` wrapper.

```php
namespace MyParsedownExtension;

use Erusev\Parsedown\Components\Block;
use Erusev\Parsedown\Components\ContinuableBlock;
use Erusev\Parsedown\AST\Handler;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\Parsedown;
use Erusev\Parsedown\Parsing\Context;
use Erusev\Parsedown\State;

final class Callout implements ContinuableBlock
{
    private $text;

    private function __construct(string $text)
    {
        $this->text = $text;
    }

    public static function build(Context $Context, State $State, Block $Block = null)
    {
        if (preg_match('/^!!!\s*(.*)/', $Context->line()->text(), $m)) {
            return new self($m[1]);
        }

        return null;
    }

    public function advance(Context $Context, State $State)
    {
        if ($Context->precedingEmptyLines() > 0) {
            return null;
        }

        return new self($this->text . "\n" . $Context->line()->text());
    }

    public function stateRenderable()
    {
        return new Handler(function (State $State) {
            return new Element(
                'div',
                ['class' => 'callout'],
                $State->applyTo(Parsedown::line($this->text, $State))
            );
        });
    }
}
```

Update the extension to register the new block type:

```php
use Erusev\Parsedown\Configurables\BlockTypes;

public static function from(StateBearer $StateBearer): self
{
    $State = $StateBearer->state();

    $BlockTypes = $State->get(BlockTypes::class)
        ->addingMarkedHighPrecedence('!', [Callout::class]);

    return new self($State->setting($BlockTypes));
}
```

`addingMarkedHighPrecedence()` adds the block to the start of the list for the
specified marker so it is tried before other marked blocks. Blocks that do not
have a dedicated marker can be registered with
`addingUnmarkedHighPrecedence()` or `addingUnmarkedLowPrecedence()`.

## Composing multiple extensions

`StateBearer` allows you to chain extensions together. The `State` returned from
one extension is passed to the next in the chain. The order can therefore affect
the final behaviour:

```php
use Erusev\Parsedown\Parsedown;
use ParsedownExtra\ParsedownExtra;
use MyParsedownExtension\MyExtension;
use Erusev\Parsedown\State;

$Parsedown = new Parsedown(
    MyExtension::from(
        ParsedownExtra::from(new State)
    )
);
```

Here `MyExtension` receives the `State` produced by `ParsedownExtra` before the
final parser is constructed.
