<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper\Helper;

/**
 * Set of functions for working with file paths.
 */
final class PathHelper
{
    private function __construct()
    {
    }

    /**
     * Return true if one path is a parent for another path.
     */
    public static function isPathParentForPath(string $parentPath, string $path): bool
    {
        $unifiedParentPath = self::unifyPath($parentPath);
        $unifiedPath = self::unifyPath($path);
        $pregDelimeter = '/';
        $quotedParentPath = preg_quote($unifiedParentPath, $pregDelimeter);
        $quotedPathDelimiter = preg_quote(\DIRECTORY_SEPARATOR, $pregDelimeter);
        $pattern = "{$pregDelimeter}^{$quotedParentPath}({$quotedPathDelimiter}|\$){$pregDelimeter}";

        return preg_match($pattern, $unifiedPath) === 1;
    }

    /**
     * Returns real path for the set path.
     */
    public static function realpath(string $path): ?string
    {
        $realPath = realpath(self::unifyPath($path));

        return \is_string($realPath) ? $realPath : null;
    }

    /**
     * Converts directory separators, trims and opens all related parts.
     */
    public static function unifyPath(string $path): string
    {
        $path = self::fixDirectorySeparatorAndTrim($path);

        if ($path === '') {
            return '';
        }

        $hasLeadingSlash = substr($path, 0, 1) === \DIRECTORY_SEPARATOR;
        $parts = array_filter(
            explode(\DIRECTORY_SEPARATOR, $path),
            fn (string $part): bool => $part !== ''
        );

        $absolutes = [];
        foreach ($parts as $part) {
            if ('..' === $part) {
                array_pop($absolutes);
            } elseif ('.' !== $part) {
                $absolutes[] = $part;
            }
        }

        return ($hasLeadingSlash ? \DIRECTORY_SEPARATOR : '')
            . implode(\DIRECTORY_SEPARATOR, $absolutes);
    }

    /**
     * Converts all directory separators to current system separator.
     */
    private static function fixDirectorySeparatorAndTrim(string $path): string
    {
        return str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, trim($path));
    }
}
