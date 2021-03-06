<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper;

use Closure;
use SplFileInfo;

/**
 * Interface for object that contains methods to work with file system.
 */
interface FileSystemHelperInterface
{
    /**
     * Removes set file or folder.
     *
     * @param SplFileInfo|string $entity
     *
     * @throws FileSystemException
     */
    public function remove($entity): void;

    /**
     * Removes set entity on if it exists.
     *
     * @param SplFileInfo|string $entity
     *
     * @throws FileSystemException
     */
    public function removeIfExists($entity): void;

    /**
     * Copies set file or folder.
     *
     * @param SplFileInfo|string $from
     * @param SplFileInfo|string $to
     *
     * @throws FileSystemException
     */
    public function copy($from, $to): void;

    /**
     * Renames file system entity.
     *
     * @param SplFileInfo|string $from
     * @param SplFileInfo|string $to
     *
     * @throws FileSystemException
     */
    public function rename($from, $to): void;

    /**
     * Creates new folder by path.
     *
     * @param SplFileInfo|string $path
     * @param int                $mode
     *
     * @return SplFileInfo
     *
     * @throws FileSystemException
     */
    public function mkdir($path, int $mode = 0777): SplFileInfo;

    /**
     * Creates new folder by path if it does not exist.
     *
     * @param SplFileInfo|string $path
     * @param int                $mode
     *
     * @return SplFileInfo
     *
     * @throws FileSystemException
     */
    public function mkdirIfNotExist($path, int $mode = 0777): SplFileInfo;

    /**
     * Removes all content form directory but keep itself.
     *
     * @param SplFileInfo|string $path
     *
     * @throws FileSystemException
     */
    public function emptyDir($path): void;

    /**
     * Returns SplFileInfo with info for tmp folder.
     *
     * @return SplFileInfo
     *
     * @throws FileSystemException
     */
    public function getTmpDir(): SplFileInfo;

    /**
     * Iterates over directory children using callback.
     *
     * @param SplFileInfo|string $dir
     * @param Closure            $callback
     */
    public function iterateDirectory($dir, Closure $callback): void;
}
