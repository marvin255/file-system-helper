<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper;

use Closure;
use Iterator;
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
    public function remove($entity): void
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
    public function copy($from, $to): void
    {
        $fromSplEntity = $this->createSplFileInfo($from);
        $toSplEntity = $this->createSplFileInfo($to);

        if (!$this->isSplEntityExists($fromSplEntity)) {
            $message = sprintf("Can not find source entity '%s' to copy.", $fromSplEntity->getPath());
            throw new FileSystemException($message);
        }

        if ($this->isSplEntityExists($toSplEntity)) {
            $message = sprintf("Target entity '%s' to copying already exists.", $toSplEntity->getPath());
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
    public function mkdir($path, int $mode = 0777): SplFileInfo
    {
        $splEntity = $this->createSplFileInfo($path);

        if ($this->isSplEntityExists($splEntity)) {
            $message = sprintf("Directory '%s' already exists.", $splEntity->getPath());
            throw new FileSystemException($message);
        }

        try {
            $mkdirRes = mkdir($splEntity->getPathname(), $mode, true);
        } catch (Throwable $e) {
            throw new FileSystemException($e->getMessage(), 0, $e);
        }

        if (!$mkdirRes) {
            $message = sprintf("Error while creating dir '%s'.", $splEntity->getPath());
            throw new FileSystemException($message);
        }

        return $splEntity;
    }

    /**
     * {@inheritDoc}
     */
    public function mkdirIfNotExist($path, int $mode = 0777): SplFileInfo
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
     * Unlinks single file.
     *
     * @param SplFileInfo $file
     *
     * @throws FileSystemException
     */
    private function removeFile(SplFileInfo $file): void
    {
        try {
            $unlinkRes = unlink($file->getRealPath());
        } catch (Throwable $e) {
            throw new FileSystemException($e->getMessage(), 0, $e);
        }

        if (!$unlinkRes) {
            $message = sprintf("Can not unlink file '%s'.", $file->getPath());
            throw new FileSystemException($message);
        }
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
        $this->iterateDIrectory(
            $dir,
            function (SplFileInfo $file): void {
                if ($file->isDir()) {
                    $this->removeDir($file);
                } elseif ($file->isFile()) {
                    $this->removeFile($file);
                }
            }
        );

        try {
            $rmRes = rmdir($dir->getRealPath());
        } catch (Throwable $e) {
            throw new FileSystemException($e->getMessage(), 0, $e);
        }

        if (!$rmRes) {
            $message = sprintf("Can not remove directory '%s'.", $dir->getPath());
            throw new FileSystemException($message);
        }
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
        try {
            $copyRes = copy($from->getRealPath(), $to->getPathname());
        } catch (Throwable $e) {
            throw new FileSystemException($e->getMessage(), 0, $e);
        }

        if (!$copyRes) {
            $message = sprintf(
                "Error while copying '%s' to '%s'.",
                $from->getPathname(),
                $to->getPathname()
            );
            throw new FileSystemException($message);
        }
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

        $this->iterateDIrectory(
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
     * Creates iterator for set directory.
     *
     * @param SplFileInfo $dir
     */
    private function iterateDIrectory(SplFileInfo $dir, Closure $callback): void
    {
        $it = new RecursiveDirectoryIterator(
            $dir->getRealPath(),
            RecursiveDirectoryIterator::SKIP_DOTS
        );

        $content = new RecursiveIteratorIterator(
            $it,
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var SplFileInfo $file */
        foreach ($content as $file) {
            call_user_func_array($callback, [$file]);
        }
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
    private function createSplFileInfo($data): SplFileInfo
    {
        if ($data instanceof SplFileInfo) {
            return $data;
        }

        if (is_string($data)) {
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
}
