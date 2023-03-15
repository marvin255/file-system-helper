<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper;

use Marvin255\FileSystemHelper\Exception\FileSystemException;

/**
 * Interface for object that contains methods to work with file system.
 */
interface FileSystemHelper
{
    /**
     * Removes set file or folder.
     *
     * @throws FileSystemException
     */
    public function remove(\SplFileInfo|string $entity): void;

    /**
     * Removes set entity on if it exists.
     *
     * @throws FileSystemException
     */
    public function removeIfExists(\SplFileInfo|string $entity): void;

    /**
     * Copies set file or folder.
     *
     * @throws FileSystemException
     */
    public function copy(\SplFileInfo|string $from, \SplFileInfo|string $to): \SplFileInfo;

    /**
     * Renames file system entity.
     *
     * @throws FileSystemException
     */
    public function rename(\SplFileInfo|string $from, \SplFileInfo|string $to): \SplFileInfo;

    /**
     * Creates new folder by path.
     *
     * @throws FileSystemException
     */
    public function mkdir(\SplFileInfo|string $path, int $mode = 0777): \SplFileInfo;

    /**
     * Creates new folder by path if it does not exist.
     *
     * @throws FileSystemException
     */
    public function mkdirIfNotExist(\SplFileInfo|string $path, int $mode = 0777): \SplFileInfo;

    /**
     * Removes all content form directory but keep itself.
     *
     * @throws FileSystemException
     */
    public function emptyDir(\SplFileInfo|string $path): void;

    /**
     * Returns SplFileInfo with info for tmp folder.
     *
     * @throws FileSystemException
     */
    public function getTmpDir(): \SplFileInfo;

    /**
     * Creates iterator object that iterates over the directory content.
     *
     * @psalm-return \Iterator<\SplFileInfo>
     *
     * @throws FileSystemException
     */
    public function createDirectoryIterator(\SplFileInfo|string $dir): \Iterator;

    /**
     * Iterates over directory children using callback.
     *
     * @psalm-param callable(\SplFileInfo): void $callback
     *
     * @throws FileSystemException
     */
    public function iterateDirectory(\SplFileInfo|string $dir, callable $callback): void;

    /**
     * Tries to create SplFileInfo object from the given path.
     *
     * @throws FileSystemException
     */
    public function makeFileInfo(mixed $path): \SplFileInfo;
}
