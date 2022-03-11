<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper;

use Closure;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Object to manipulate files and folders.
 */
class FileSystemHelperBase implements FileSystemHelper
{
    private ?string $baseFolder;

    public function __construct(?string $baseFolder = null)
    {
        if ($baseFolder !== null) {
            $baseFolder = str_replace(
                ['\\', '/'],
                \DIRECTORY_SEPARATOR,
                trim($baseFolder)
            );
        }

        if ($baseFolder === '') {
            throw $this->createException(
                "Base folder can't be empty. Set non empty string or null"
            );
        }

        $this->baseFolder = $baseFolder;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(SplFileInfo|string $entity): void
    {
        $splEntity = $this->convertToSplFileInfo($entity);

        if ($splEntity->isFile()) {
            $this->runPhpFunction(
                'unlink',
                $splEntity->getRealPath()
            );
        } elseif ($splEntity->isDir()) {
            $this->iterateDirectory(
                $splEntity,
                fn (SplFileInfo $file): mixed => $this->remove($file)
            );
            $this->runPhpFunction(
                'rmdir',
                $splEntity->getRealPath()
            );
        } else {
            throw $this->createException(
                "Can't find entity '%s' to remove",
                $splEntity
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function removeIfExists(SplFileInfo|string $entity): void
    {
        $splEntity = $this->convertToSplFileInfo($entity);

        if ($splEntity->isFile() || $splEntity->isDir()) {
            $this->remove($splEntity);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function copy(SplFileInfo|string $from, SplFileInfo|string $to): SplFileInfo
    {
        $source = $this->convertToSplFileInfo($from);
        $target = $this->convertToSplFileInfo($to);

        if (!$source->isDir() && !$source->isFile()) {
            throw $this->createException(
                "Can't find source '%s' to copy",
                $from
            );
        }

        if ($target->isFile() || $target->isDir()) {
            throw $this->createException(
                "Target path '%s' for copy '%s' already exists",
                $source,
                $target
            );
        }

        $parent = $this->convertToSplFileInfo($target->getPath());

        if (!$parent->isDir()) {
            throw $this->createException(
                "Target directory '%s' for copying doesn't exist",
                $parent
            );
        }

        if (!$parent->isWritable()) {
            throw $this->createException(
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
                function (SplFileInfo $file) use ($target): void {
                    $nestedPath = $target->getPathname() . \DIRECTORY_SEPARATOR . $file->getBasename();
                    $nestedTarget = new SplFileInfo($nestedPath);
                    $this->copy($file, $nestedTarget);
                }
            );
        }

        return $target;
    }

    /**
     * {@inheritDoc}
     */
    public function rename(SplFileInfo|string $from, SplFileInfo|string $to): SplFileInfo
    {
        $source = $this->convertToSplFileInfo($from);
        $destination = $this->convertToSplFileInfo($to);

        if (!$source->isFile() && !$source->isDir()) {
            throw $this->createException(
                "Can't find source entity '%s' to rename",
                $source
            );
        }

        if ($destination->isFile() || $destination->isDir()) {
            throw $this->createException(
                "Target entity '%s' to rename already exists",
                $destination
            );
        }

        $parent = $this->convertToSplFileInfo($destination->getPath());

        if (!$parent->isDir()) {
            throw $this->createException(
                "Target directory '%s' for renaming doesn't exist",
                $parent
            );
        }

        if (!$parent->isWritable()) {
            throw $this->createException(
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
    public function mkdir(SplFileInfo|string $path, int $mode = 0777): SplFileInfo
    {
        $dir = $this->convertToSplFileInfo($path);

        if ($dir->isFile() || $dir->isDir()) {
            throw $this->createException(
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

        return $dir;
    }

    /**
     * {@inheritDoc}
     */
    public function mkdirIfNotExist(SplFileInfo|string $path, int $mode = 0777): SplFileInfo
    {
        $dir = $this->convertToSplFileInfo($path);

        if ($dir->isDir()) {
            return $dir;
        }

        return $this->mkdir($dir, $mode);
    }

    /**
     * {@inheritDoc}
     */
    public function emptyDir(SplFileInfo|string $path): void
    {
        $dir = $this->convertToSplFileInfo($path);

        if (!$dir->isDir()) {
            throw $this->createException(
                "Directory '%s' must exist to be emptied",
                $dir
            );
        }

        $this->iterateDirectory(
            $dir,
            fn (SplFileInfo $file): mixed => $this->remove($file)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getTmpDir(): SplFileInfo
    {
        $dir = sys_get_temp_dir();

        if (empty($dir) || !is_dir($dir)) {
            throw $this->createException(
                "Can't find system temporary directory"
            );
        }

        return $this->convertToSplFileInfo($dir);
    }

    /**
     * {@inheritDoc}
     */
    public function iterateDirectory(SplFileInfo|string $dir, Closure $callback): void
    {
        $splEntity = $this->convertToSplFileInfo($dir);

        if (!$splEntity->isDir()) {
            throw $this->createException(
                "Target '%s' doesn't exist or not a directory",
                $splEntity
            );
        }

        $it = new RecursiveDirectoryIterator(
            $splEntity->getRealPath(),
            RecursiveDirectoryIterator::SKIP_DOTS
        );

        $content = new RecursiveIteratorIterator(
            $it,
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var SplFileInfo $file */
        foreach ($content as $file) {
            \call_user_func_array($callback, [$file]);
        }
    }

    /**
     * Creates SplFileInfo object from set data.
     *
     * @param SplFileInfo|string $data
     *
     * @return SplFileInfo
     *
     * @throws FileSystemException
     */
    private function convertToSplFileInfo(SplFileInfo|string $data): SplFileInfo
    {
        if (\is_string($data)) {
            $trimmedData = trim($data);
            if ($trimmedData === '') {
                throw $this->createException(
                    "Can't create SplFileInfo using empty string"
                );
            }
            $data = new SplFileInfo($trimmedData);
        }

        if ($this->baseFolder !== null && mb_strpos($data->getPathName(), $this->baseFolder) !== 0) {
            throw $this->createException(
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
        $res = (bool) \call_user_func_array($functionName, $params);

        if (!$res) {
            throw $this->createException(
                "Got false result from '%s' function",
                $functionName
            );
        }
    }

    /**
     * Creates FileSystemException.
     *
     * @param string                         $message
     * @param array<int, SplFileInfo|string> $params
     *
     * @return FileSystemException
     */
    private function createException(string $message, ...$params): FileSystemException
    {
        $stringifyParams = array_map(
            function (SplFileInfo|string $item): string {
                return $item instanceof SplFileInfo ? $item->getPathName() : $item;
            },
            $params
        );

        $message = rtrim($message, '.') . '.';
        array_unshift($stringifyParams, $message);
        $compiledMessage = (string) \call_user_func_array('sprintf', $stringifyParams);

        return new FileSystemException($compiledMessage);
    }
}
