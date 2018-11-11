<?php declare(strict_types=1);

namespace Erusev\Parsedown\Html\Renderables;

use Erusev\Parsedown\Html\Renderable;
use Erusev\Parsedown\Html\Sanitisation\CharacterFilter;
use Erusev\Parsedown\Html\Sanitisation\Escaper;

final class Element implements Renderable
{
    /** @var string */
    private $name;

    /** @var array<string, string>*/
    private $attributes;

    /** @var Renderable[]|null */
    private $Contents;

    /**
     * @param string $name
     * @param array<string, string> $attributes
     * @param Renderable[]|null $Contents
     */
    public function __construct(
        string $name,
        array $attributes,
        ?array $Contents
    ) {
        $this->name = $name;
        $this->attributes = $attributes;
        $this->Contents = $Contents;
    }

    /**
     * @param string $name
     * @param array<string, string> $attributes
     * @param Renderable ...$Contents
     *
     */
    public static function new(string $name, array $attributes, Renderable ...$Contents): self
    {
        return new self($name, $attributes, $Contents);
    }

    /**
     * @param string $name
     * @param array<string, string> $attributes
     */
    public static function selfClosing(string $name, array $attributes): self
    {
        return new self($name, $attributes, null);
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return Renderable[]|null
     */
    public function contents(): ?array
    {
        return $this->Contents;
    }

    public function getHtml(): string
    {
        $html = '';

        $elementName = CharacterFilter::htmlElementName($this->name);

        $html .= '<' . $elementName;

        if (! empty($this->attributes)) {
            foreach ($this->attributes as $name => $value) {
                $html .= ' '
                    . CharacterFilter::htmlAttributeName($name)
                    . '="'
                    . Escaper::htmlAttributeValue($value)
                    . '"'
                ;
            }
        }

        if ($this->Contents !== null) {
            $html .= '>';

            if (! empty($this->Contents)) {
                $html .= "\n";

                foreach ($this->Contents as $C) {
                    $html .= $C->getHtml();
                }
            }

            $html .= "</" . $elementName . ">\n";
        } else {
            $html .= ' />';
        }

        return $html;
    }
}
