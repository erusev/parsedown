<?php

namespace Erusev\Parsedown\Configurables;

use Erusev\Parsedown\Configurable;

final class HeaderSlug implements Configurable
{
    /** @var bool */
    private $enabled = false;

    /** @var \Closure(string):string */
    private $slugCallback;

    /**
     * @param bool $enabled
     * @param (\Closure(string):string)|null $slugCallback
     */
    public function __construct($enabled, $slugCallback = null)
    {
        $this->enabled = $enabled;

        if (! isset($slugCallback)) {
            $this->slugCallback = function (string $text): string {
                $slug = \mb_strtolower($text);
                $slug = \str_replace(' ', '-', $slug);
                $slug = \preg_replace('/[^\p{L}\p{N}\p{M}-]+/u', '', $slug);

                return $slug;
            };
        } else {
            $this->slugCallback = $slugCallback;
        }
    }

    /** @return bool */
    public function isEnabled()
    {
        return $this->enabled;
    }

    public function transform(string $text): string
    {
        return ($this->slugCallback)($text);
    }

    /** @param \Closure(string):string $slugCallback */
    public static function withCallback($slugCallback): self
    {
        return new self(true, $slugCallback);
    }

    /** @return self */
    public static function enabled()
    {
        return new self(true);
    }

    /** @return self */
    public static function initial()
    {
        return new self(false);
    }
}
