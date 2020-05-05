<?php

namespace Erusev\Parsedown\Configurables;

use Erusev\Parsedown\Configurable;

final class HeaderSlug implements Configurable
{
    /** @var bool */
    private $enabled = false;

    /** @var \Closure(string):string */
    private $slugCallback;

    /** @var \Closure(string,int):string */
    private $duplicationCallback;

    /**
     * @param bool $enabled
     * @param (\Closure(string):string)|null $slugCallback
     * @param (\Closure(string, int):string)|null $duplicationCallback
     */
    public function __construct(
        $enabled,
        $slugCallback = null,
        $duplicationCallback = null
    ) {
        $this->enabled = $enabled;

        if (! isset($slugCallback)) {
            $this->slugCallback = function (string $text): string {
                $slug = \mb_strtolower($text);
                $slug = \str_replace(' ', '-', $slug);
                $slug = \preg_replace('/[^\p{L}\p{Nd}\p{Nl}\p{M}-]+/u', '', $slug);
                $slug = \trim($slug, '-');

                return $slug;
            };
        } else {
            $this->slugCallback = $slugCallback;
        }

        if (! isset($duplicationCallback)) {
            $this->duplicationCallback = function (string $slug, int $duplicateNumber): string {
                return $slug . '-' . \strval($duplicateNumber-1);
            };
        } else {
            $this->duplicationCallback = $duplicationCallback;
        }
    }

    /** @return bool */
    public function isEnabled()
    {
        return $this->enabled;
    }

    public function transform(SlugRegister $SlugRegister, string $text): string
    {
        $slug = ($this->slugCallback)($text);

        if ($SlugRegister->slugCount($slug) > 0) {
            $newSlug = ($this->duplicationCallback)($slug, $SlugRegister->mutatingIncrement($slug));

            while ($SlugRegister->slugCount($newSlug) > 0) {
                $newSlug = ($this->duplicationCallback)($slug, $SlugRegister->mutatingIncrement($slug));
            }

            return $newSlug;
        }

        $SlugRegister->mutatingIncrement($slug);

        return $slug;
    }

    /** @param \Closure(string):string $slugCallback */
    public static function withCallback($slugCallback): self
    {
        return new self(true, $slugCallback);
    }

    /** @param \Closure(string,int):string $duplicationCallback */
    public static function withDuplicationCallback($duplicationCallback): self
    {
        return new self(true, null, $duplicationCallback);
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
