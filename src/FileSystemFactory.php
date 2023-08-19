<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper;

/**
 * Factory object for file system helper.
 *
 * @psalm-api
 */
final class FileSystemFactory
{
    private function __construct()
    {
    }

    /**
     * Creates new FileSystemHelperInterface instance with default settings.
     */
    public static function create(string $baseFolder = null): FileSystemHelper
    {
        return new FileSystemHelperImpl($baseFolder);
    }
}
