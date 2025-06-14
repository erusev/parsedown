# Parsedown v2 Extension Reference

This document provides an overview of several core concepts that appear when implementing custom extensions.
It is intended to complement the extension tutorial and the migration guide.

## State and StateBearers

Extensions communicate their behaviour through a `State` object. Every
`State` contains a collection of **configurables** which describe how
Parsedown should parse and render Markdown. Objects that expose a
`state()` method and can build on another `State` via
`::from(StateBearer $Other)` are known as **StateBearers**. Parsedown
and its extensions are all implemented this way so they can be composed
together:

```php
$Parsedown = new Parsedown(
    ExtensionB::from(
        ExtensionA::from(new State)
    )
);
```

Each call to `::from()` receives the previous `State` and returns a new
object that wraps a modified version. The final `State` controls every
aspect of parsing and rendering.

## Configurables

A [`Configurable`](../src/Configurable.php) represents a single configuration value within a [`State`](../src/State.php).
Each configurable type may appear at most once in a state. You retrieve an instance with `$State->get(SomeConfigurable::class)`
and insert a replacement using `$State->setting($Configurable)`.

Configurables are immutable by default. If a configurable needs to be written to during parsing it should instead implement
[`MutableConfigurable`](../src/MutableConfigurable.php). The `State` will automatically provide an isolated clone of such
objects when `->get()` is called, allowing safe mutation within a single parse.

## Books

"Books" are mutable configurables used to store information gathered while parsing. The built in
[`DefinitionBook`](../src/Configurables/DefinitionBook.php) collects link reference definitions and can be queried by
inlines. Other extensions may implement their own books in the same fashion.

```php
use Erusev\Parsedown\Configurables\DefinitionBook;

$State->get(DefinitionBook::class)->mutatingSet('id', ['url' => 'https://example.com', 'title' => null]);
```

The `mutatingSet()` method updates the stored data and affects the rest of the current parse.

Custom books let you expose extension specific state. A book simply needs to
implement `MutableConfigurable` and provide an `isolatedCopy()` method:

```php
final class CalloutBook implements MutableConfigurable
{
    /** @var array<int, string> */
    private $callouts;

    public function __construct(array $callouts = [])
    {
        $this->callouts = $callouts;
    }

    public static function initial(): self
    {
        return new self;
    }

    public function mutatingAdd(string $text): int
    {
        $this->callouts[] = $text;

        return count($this->callouts);
    }

    public function isolatedCopy(): self
    {
        return new self($this->callouts);
    }
}
```

An inline or block can store entries in the book using `mutatingAdd()` and later
reference them while rendering.

## AST

Parsedown creates an abstract syntax tree (AST) while parsing. Each block or inline returns an object that exposes a
`stateRenderable()` method. This method yields an [`AST\StateRenderable`](../src/AST/StateRenderable.php) which can later be
converted into a [`Renderable`](../src/Html/Renderable.php) by providing a `State`.

This two step approach lets extensions alter the rendering process without changing the tree structure itself.

## Renderables

Renderables are small objects that model the resulting HTML. Core renderables include [`Element`](../src/Html/Renderables/Element.php),
[`Text`](../src/Html/Renderables/Text.php) and [`Container`](../src/Html/Renderables/Container.php). After parsing, the state
provides a `RenderStack` that transforms these objects into the final HTML string.

### Element

Represents a single HTML tag. The constructor accepts the tag name, a map of attributes and an optional array of child renderables. `Element` objects are immutable; methods such as `settingAttributes()` and `settingContents()` return new instances with the modified data. A convenience helper `Element::selfClosing()` creates elements without children. When rendered, tag names and attribute values are escaped to protect against invalid markup.

### Text

A plain text node that automatically escapes HTML entities. Use it for raw strings that should not be treated as markup. Because `Text` implements `TransformableRenderable`, it can participate in render stack transformations like any other element.

### Container

A lightweight wrapper that stores an ordered list of renderables. The `adding()` method returns a new instance with an extra item appended while `contents()` exposes the underlying list. Containers are commonly produced when transforming renderables or constructing small fragments of output.

### RenderStack

After constructing the AST every renderable is passed through the
`RenderStack` configurable. The stack contains an ordered list of
transformations applied before the final HTML is produced. Extensions may
add new callbacks by retrieving the current stack and inserting a new
step:

```php
use Erusev\Parsedown\Configurables\RenderStack;
use Erusev\Parsedown\Html\Renderable;
use Erusev\Parsedown\Html\Renderables\Container;
use Erusev\Parsedown\Html\Renderables\Element;
use Erusev\Parsedown\State;

$Stack = $State->get(RenderStack::class)
    ->adding(function (Renderable $r, State $s) {
        return new Container([new Element('span', ['class' => 'debug'], $r)]);
    });

$State = $State->setting($Stack);
```

Each function receives a `Renderable` and returns a replacement. Multiple
extensions can work together by adding their own callbacks to the stack.

## WidthTrait

Inline components commonly use the [`WidthTrait`](../src/Components/Inlines/WidthTrait.php) to track how many characters were
consumed while parsing. The `width()` method is later consulted when advancing through the source text.

## Parsing

Parsing is performed line by line. `BlockTypes` holds the list of block parsers that may start on a given marker while
`InlineTypes` performs a similar role for inlines. The [`Parsing\Context`](../src/Parsing/Context.php) object tracks the
current line and facilitates continuations. Recursion is limited via the `RecursionLimiter` configurable to avoid runaway
processing.

The limiter defaults to a depth of **15**. Extensions may change this by replacing the configurable:

```php
use Erusev\Parsedown\Configurables\RecursionLimiter;

$State = $State->setting(
    RecursionLimiter::withLimit(30)
);
```

If the limit is exceeded an exception is thrown to prevent malicious input from exhausting resources.


### Input Representation

Parsedown first separates the raw Markdown string into [`Lines`](../src/Parsing/Lines.php) objects. Each `Line` stores its text and indentation while a `Context` tracks preceding blank lines. Blocks read from these structures as they parse.

The available block and inline components define the syntax. They are organised in `BlockTypes` and `InlineTypes`. Extensions may add, remove or replace entries in these lists to change how the input is interpreted.


### Block and Inline Precedence

`InlineTypes` provides `addingHighPrecedence()` and `addingLowPrecedence()` to
control the order of inline parsers for a given marker. High precedence inserts
new types at the front of the list whereas low precedence appends them.

`BlockTypes` distinguishes between blocks that require a marker and those that
do not. Marked blocks are added with `addingMarkedHighPrecedence()` or
`addingMarkedLowPrecedence()`. Unmarked blocks use
`addingUnmarkedHighPrecedence()` or `addingUnmarkedLowPrecedence()`. Choose the
method that matches how your block is triggered.

For a practical walkthrough of extending Parsedown refer to
[Creating-Extensions-v2.0.md](Creating-Extensions-v2.0.md).
