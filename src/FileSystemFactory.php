<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper;

/**
 * Factory object for file system helper.
 */
class FileSystemFactory
{
    /**
     * Creates new FileSystemHelperInterface instance with default settings.
     *
     * @retrurn FileSystemHelper
     */
    public static function create(): FileSystemHelper
    {
        return new FileSystemHelperBase();
    }
}
