<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper;

use Marvin255\FileSystemHelper\Exception\FileSystemException;
use Marvin255\FileSystemHelper\Helper\PathHelper;

/**
 * Object to manipulate files and folders.
 *
 * @internal
 */
final class FileSystemHelperBase implements FileSystemHelper
{
    private readonly ?string $baseFolder;

    public function __construct(?string $baseFolder = null)
    {
        $validatedBaseFolder = null;

        if ($baseFolder !== null) {
            $validatedBaseFolder = PathHelper::realpath($baseFolder);
            if ($validatedBaseFolder === null) {
                throw FileSystemException::create(
                    "Base folder '%s' doesn't exist",
                    $baseFolder
                );
            }
        }

        $this->baseFolder = $validatedBaseFolder;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(\SplFileInfo|string $entity): void
    {
        $splEntity = $this->makeFileInfoAndCheckBasePath($entity);

        if ($splEntity->isFile()) {
            $this->runPhpFunction(
                'unlink',
                $splEntity->getRealPath()
            );
        } elseif ($splEntity->isDir()) {
            $this->iterateDirectory(
                $splEntity,
                fn (\SplFileInfo $file): mixed => $this->remove($file)
            );
            $this->runPhpFunction(
                'rmdir',
                $splEntity->getRealPath()
            );
        } else {
            throw FileSystemException::create(
                "Can't find entity '%s' to remove",
                $splEntity
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function removeIfExists(\SplFileInfo|string $entity): void
    {
        $splEntity = $this->makeFileInfoAndCheckBasePath($entity);

        if ($splEntity->isFile() || $splEntity->isDir()) {
            $this->remove($splEntity);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function copy(\SplFileInfo|string $from, \SplFileInfo|string $to): \SplFileInfo
    {
        $source = $this->makeFileInfoAndCheckBasePath($from);
        $target = $this->makeFileInfoAndCheckBasePath($to);

        if (!$source->isDir() && !$source->isFile()) {
            throw FileSystemException::create(
                "Can't find source '%s' to copy",
                $from
            );
        }

        if ($target->isFile() || $target->isDir()) {
            throw FileSystemException::create(
                "Target path '%s' for copy '%s' already exists",
                $source,
                $target
            );
        }

        $parent = $this->makeFileInfoAndCheckBasePath($target->getPath());

        if (!$parent->isDir()) {
            throw FileSystemException::create(
                "Target directory '%s' for copying is not a direcotry or doesn't exist",
                $parent
            );
        }

        if (!$parent->isWritable()) {
            throw FileSystemException::create(
                "Target directory '%s' for copying isn't writable",
                $parent
            );
        }

        if ($source->isFile()) {
            $this->runPhpFunction(
                'copy',
                $source->getRealPath(),
                $target->getPathname()
            );
        } elseif ($source->isDir()) {
            $this->mkdir($target);
            $this->iterateDirectory(
                $source,
                function (\SplFileInfo $file) use ($target): void {
                    $nestedPath = $target->getPathname() . \DIRECTORY_SEPARATOR . $file->getBasename();
                    $nestedTarget = new \SplFileInfo($nestedPath);
                    $this->copy($file, $nestedTarget);
                }
            );
        }

        return $target;
    }

    /**
     * {@inheritDoc}
     */
    public function rename(\SplFileInfo|string $from, \SplFileInfo|string $to): \SplFileInfo
    {
        $source = $this->makeFileInfoAndCheckBasePath($from);
        $destination = $this->makeFileInfoAndCheckBasePath($to);

        if (!$source->isFile() && !$source->isDir()) {
            throw FileSystemException::create(
                "Can't find source entity '%s' to rename",
                $source
            );
        }

        if ($destination->isFile() || $destination->isDir()) {
            throw FileSystemException::create(
                "Target entity '%s' to rename already exists",
                $destination
            );
        }

        $parent = $this->makeFileInfoAndCheckBasePath($destination->getPath());

        if (!$parent->isDir()) {
            throw FileSystemException::create(
                "Target directory '%s' for copying is not a direcotry or doesn't exist",
                $parent
            );
        }

        if (!$parent->isWritable()) {
            throw FileSystemException::create(
                "Target directory '%s' for renaming isn't writable",
                $parent
            );
        }

        $this->runPhpFunction(
            'rename',
            $source->getRealPath(),
            $destination->getPathName()
        );

        return $destination;
    }

    /**
     * {@inheritDoc}
     */
    public function mkdir(\SplFileInfo|string $path, int $mode = 0777): \SplFileInfo
    {
        $dir = $this->makeFileInfoAndCheckBasePath($path);

        if ($dir->isFile() || $dir->isDir()) {
            throw FileSystemException::create(
                "Entity '%s' already exists",
                $dir
            );
        }

        $this->runPhpFunction(
            'mkdir',
            $dir->getPathname(),
            $mode,
            true
        );

        // recoursive directory creation sometimes can't set permissions for nested folder
        chmod($dir->getPathname(), $mode);

        return $dir;
    }

    /**
     * {@inheritDoc}
     */
    public function mkdirIfNotExist(\SplFileInfo|string $path, int $mode = 0777): \SplFileInfo
    {
        $dir = $this->makeFileInfoAndCheckBasePath($path);

        if ($dir->isDir()) {
            return $dir;
        }

        return $this->mkdir($dir, $mode);
    }

    /**
     * {@inheritDoc}
     */
    public function emptyDir(\SplFileInfo|string $path): void
    {
        $dir = $this->makeFileInfoAndCheckBasePath($path);

        if ($dir->isFile()) {
            throw FileSystemException::create(
                "Can't empty directory '%s' because it's a file",
                $dir
            );
        } elseif (!$dir->isDir()) {
            throw FileSystemException::create(
                "Directory '%s' must exist to be emptied",
                $dir
            );
        }

        $this->iterateDirectory(
            $dir,
            fn (\SplFileInfo $file): mixed => $this->remove($file)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getTmpDir(): \SplFileInfo
    {
        $dir = sys_get_temp_dir();

        if (empty($dir) || !is_dir($dir)) {
            throw FileSystemException::create(
                "Can't find system temporary directory"
            );
        }

        return $this->makeFileInfoAndCheckBasePath($dir);
    }

    /**
     * {@inheritDoc}
     */
    public function iterateDirectory(\SplFileInfo|string $dir, \Closure $callback): void
    {
        $splEntity = $this->makeFileInfoAndCheckBasePath($dir);

        if (!$splEntity->isDir()) {
            throw FileSystemException::create(
                "Target '%s' doesn't exist or not a directory",
                $splEntity
            );
        }

        $it = new \RecursiveDirectoryIterator(
            $splEntity->getRealPath(),
            \RecursiveDirectoryIterator::SKIP_DOTS
        );

        $content = new \RecursiveIteratorIterator(
            $it,
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var \SplFileInfo $file */
        foreach ($content as $file) {
            \call_user_func_array($callback, [$file]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function makeFileInfo(mixed $path): \SplFileInfo
    {
        if (\is_string($path)) {
            $trimmedPath = PathHelper::unifyPath($path);
            if ($trimmedPath === '') {
                throw FileSystemException::create("Can't create SplFileInfo using empty string");
            }
            $fileInfo = new \SplFileInfo($trimmedPath);
        } elseif ($path instanceof \SplFileInfo) {
            $fileInfo = $path;
        } else {
            throw FileSystemException::create("Can't create SplFileInfo from given object type");
        }

        return $fileInfo;
    }

    /**
     * Creates SplFileInfo object from set data.
     *
     * @throws FileSystemException
     */
    private function makeFileInfoAndCheckBasePath(\SplFileInfo|string $data): \SplFileInfo
    {
        $data = $this->makeFileInfo($data);

        if ($this->baseFolder !== null && !PathHelper::isPathParentForPath($this->baseFolder, $data->getPathName())) {
            throw FileSystemException::create(
                "Not allowed path '%s'. All paths must be within base directory '%s'",
                $data,
                $this->baseFolder
            );
        }

        return $data;
    }

    /**
     * Runs set php function in try/catch.
     *
     * @param string  $functionName
     * @param mixed[] $params
     *
     * @throws FileSystemException
     */
    private function runPhpFunction(string $functionName, ...$params): void
    {
        if (\call_user_func_array($functionName, $params) === false) {
            throw FileSystemException::create(
                "Got false result from '%s' function",
                $functionName
            );
        }
    }
}
