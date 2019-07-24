<?php

namespace Erusev\Parsedown\Html\Sanitisation;

final class UrlSanitiser
{
    private const COMMON_SCHEMES = [
        'http://',
        'https://',
        'ftp://',
        'ftps://',
        'mailto:',
        'tel:',
        'data:image/png;base64,',
        'data:image/gif;base64,',
        'data:image/jpeg;base64,',
        'irc:',
        'ircs:',
        'git:',
        'ssh:',
        'news:',
        'steam:',
    ];

    /**
     * Disable literal intepretation of unknown scheme in $url. Returns the
     * filtered version of $url.
     * @param string $url
     * @param string[]|null $permittedSchemes
     * @return string
     */
    public static function filter($url, $permittedSchemes = null)
    {
        if (! isset($permittedSchemes)) {
            $permittedSchemes = self::COMMON_SCHEMES;
        }

        foreach ($permittedSchemes as $scheme) {
            if (self::striAtStart($url, $scheme)) {
                return $url;
            }
        }

        return \str_replace(':', '%3A', $url);
    }

    /**
     * @param string $string
     * @param string $needle
     * @return bool
     */
    private static function striAtStart($string, $needle)
    {
        $needleLen = \strlen($needle);

        return \strtolower(\substr($string, 0, $needleLen)) === \strtolower($needle);
    }
}
