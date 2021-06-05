<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper;

use Closure;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

/**
 * Object to manipulate files and folders.
 */
class FileSystemHelper implements FileSystemHelperInterface
{
    /**
     * {@inheritDoc}
     */
    public function remove(SplFileInfo | string $entity): void
    {
        $splEntity = $this->createSplFileInfo($entity);

        if (!$this->isSplEntityExists($splEntity)) {
            $message = sprintf("Can not find entity '%s' to remove.", $splEntity->getPath());
            throw new FileSystemException($message);
        }

        if ($splEntity->isFile()) {
            $this->removeFile($splEntity);
        } elseif ($splEntity->isDir()) {
            $this->removeDir($splEntity);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function removeIfExists(SplFileInfo | string $entity): void
    {
        $splEntity = $this->createSplFileInfo($entity);

        if ($splEntity->isFile()) {
            $this->removeFile($splEntity);
        } elseif ($splEntity->isDir()) {
            $this->removeDir($splEntity);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function copy(SplFileInfo | string $from, SplFileInfo | string $to): void
    {
        $fromSplEntity = $this->createSplFileInfo($from);
        $toSplEntity = $this->createSplFileInfo($to);

        if (!$this->isSplEntityExists($fromSplEntity)) {
            $message = sprintf("Can not find source entity '%s' to copy.", $fromSplEntity->getPath());
            throw new FileSystemException($message);
        }

        if ($this->isSplEntityExists($toSplEntity)) {
            $message = sprintf("Target entity '%s' to copy already exists.", $toSplEntity->getPath());
            throw new FileSystemException($message);
        }

        $parent = $this->createSplFileInfo($toSplEntity->getPath());

        if (!$this->isSplEntityExists($parent)) {
            $message = sprintf("Target path '%s' for copying does not exist.", $parent->getPathName());
            throw new FileSystemException($message);
        }

        if (!$parent->isWritable()) {
            $message = sprintf("Target path '%s' for copying is not writable.", $parent->getPathName());
            throw new FileSystemException($message);
        }

        if ($fromSplEntity->isFile()) {
            $this->copyFile($fromSplEntity, $toSplEntity);
        } elseif ($fromSplEntity->isDir()) {
            $this->copyDir($fromSplEntity, $toSplEntity);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function rename(SplFileInfo | string $from, SplFileInfo | string $to): void
    {
        $fromSplEntity = $this->createSplFileInfo($from);
        $toSplEntity = $this->createSplFileInfo($to);

        if (!$this->isSplEntityExists($fromSplEntity)) {
            $message = sprintf("Can not find source entity '%s' to rename.", $fromSplEntity->getPathName());
            throw new FileSystemException($message);
        }

        if ($this->isSplEntityExists($toSplEntity)) {
            $message = sprintf("Target entity '%s' to rename already exists.", $toSplEntity->getPathName());
            throw new FileSystemException($message);
        }

        $this->safeRunPhpFunction(
            'rename',
            [
                $fromSplEntity->getRealPath(),
                $toSplEntity->getPathName(),
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function mkdir(SplFileInfo | string $path, int $mode = 0777): SplFileInfo
    {
        $splEntity = $this->createSplFileInfo($path);

        if ($this->isSplEntityExists($splEntity)) {
            $message = sprintf("Directory '%s' already exists.", $splEntity->getPathName());
            throw new FileSystemException($message);
        }

        $this->safeRunPhpFunction(
            'mkdir',
            [
                $splEntity->getPathname(),
                $mode,
                true,
            ]
        );

        return $splEntity;
    }

    /**
     * {@inheritDoc}
     */
    public function mkdirIfNotExist(SplFileInfo | string $path, int $mode = 0777): SplFileInfo
    {
        $splEntity = $this->createSplFileInfo($path);

        if ($this->isSplEntityExists($splEntity)) {
            return $splEntity;
        }

        return $this->mkdir($splEntity, $mode);
    }

    /**
     * {@inheritDoc}
     */
    public function emptyDir(SplFileInfo | string $path): void
    {
        $splEntity = $this->createSplFileInfo($path);

        if (!$splEntity->isDir()) {
            $message = sprintf("Path '%s' must be an existed dir to be emptied.", $splEntity->getPathName());
            throw new FileSystemException($message);
        }

        $this->iterateDirectory(
            $splEntity,
            function (SplFileInfo $entity): void {
                $this->remove($entity);
            }
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getTmpDir(): SplFileInfo
    {
        $tmpDir = sys_get_temp_dir();

        if (empty($tmpDir)) {
            $message = 'Can not find system temporary directory.';
            throw new FileSystemException($message);
        }

        return $this->createSplFileInfo($tmpDir);
    }

    /**
     * {@inheritDoc}
     */
    public function iterateDirectory(SplFileInfo | string $dir, Closure $callback): void
    {
        $splEntity = $this->createSplFileInfo($dir);

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
     * Unlinks single file.
     *
     * @param SplFileInfo $file
     *
     * @throws FileSystemException
     */
    private function removeFile(SplFileInfo $file): void
    {
        $this->safeRunPhpFunction(
            'unlink',
            [
                $file->getRealPath(),
            ]
        );
    }

    /**
     * Removes directory and all it's content.
     *
     * @param SplFileInfo $dir
     *
     * @throws FileSystemException
     */
    private function removeDir(SplFileInfo $dir): void
    {
        $this->iterateDirectory(
            $dir,
            function (SplFileInfo $file): void {
                if ($file->isDir()) {
                    $this->removeDir($file);
                } elseif ($file->isFile()) {
                    $this->removeFile($file);
                }
            }
        );

        $this->safeRunPhpFunction(
            'rmdir',
            [
                $dir->getRealPath(),
            ]
        );
    }

    /**
     * Copies file.
     *
     * @param SplFileInfo $from
     * @param SplFileInfo $to
     *
     * @throws FileSystemException
     */
    private function copyFile(SplFileInfo $from, SplFileInfo $to): void
    {
        $this->safeRunPhpFunction(
            'copy',
            [
                $from->getRealPath(),
                $to->getPathname(),
            ]
        );
    }

    /**
     * Copies directory.
     *
     * @param SplFileInfo $from
     * @param SplFileInfo $to
     *
     * @throws FileSystemException
     */
    private function copyDir(SplFileInfo $from, SplFileInfo $to): void
    {
        $this->mkdir($to);

        $this->iterateDirectory(
            $from,
            function (SplFileInfo $file) use ($to): void {
                $destination = new SplFileInfo($to->getPathname() . '/' . $file->getBasename());
                if ($file->isDir()) {
                    $this->copyDir($file, $destination);
                } elseif ($file->isFile()) {
                    $this->copyFile($file, $destination);
                }
            }
        );
    }

    /**
     * Creates SplFileInfo object from set data.
     *
     * @param mixed $data
     *
     * @return SplFileInfo
     *
     * @throws FileSystemException
     */
    private function createSplFileInfo(SplFileInfo | string $data): SplFileInfo
    {
        if ($data instanceof SplFileInfo) {
            return $data;
        } elseif (\is_string($data)) {
            $trimmedData = rtrim(trim($data), '/\\');
            if ($trimmedData === '') {
                $message = 'Can not create SplFileInfo from empty string.';
                throw new FileSystemException($message);
            }

            return new SplFileInfo($trimmedData);
        }

        $message = 'Data to create SplFileInfo must be a string instance.';
        throw new FileSystemException($message);
    }

    /**
     * Returns true if SplFileInfo entity exists in file system.
     *
     * @param SplFileInfo $entity
     *
     * @return bool
     */
    private function isSplEntityExists(SplFileInfo $entity): bool
    {
        $realPath = $entity->getRealPath();

        return !empty($realPath) && file_exists($realPath);
    }

    /**
     * Runs set php function in try/catch.
     *
     * @param string $functionName
     * @param array  $params
     *
     * @throws FileSystemException
     */
    private function safeRunPhpFunction(string $functionName, array $params = []): void
    {
        try {
            $res = (bool) \call_user_func_array($functionName, $params);
        } catch (Throwable $e) {
            throw new FileSystemException($e->getMessage(), 0, $e);
        }

        if (!$res) {
            $message = sprintf("Error while running '%s',", $functionName);
            throw new FileSystemException($message);
        }
    }
}
