<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper;

/**
 * Factory object for file system helper.
 */
class FileSystemFactory
{
    /**
     * Cretaes new FileSystemHelperInterface instance with default settings.
     */
    public static function create(): FileSystemHelperInterface
    {
        return new FileSystemHelper();
    }
}
