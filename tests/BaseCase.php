<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Basic class for all tests in suite.
 *
 * @internal
 */
abstract class BaseCase extends TestCase
{
    private ?string $tempDir = null;

    protected function tearDown(): void
    {
        if ($this->tempDir) {
            $this->removeDir($this->tempDir);
        }

        parent::tearDown();
    }

    /**
     * Returns path to temporary folder.
     */
    protected function getTempDir(): string
    {
        if ($this->tempDir === null) {
            $this->tempDir = sys_get_temp_dir();
            if (!$this->tempDir || !is_writable($this->tempDir)) {
                throw new \RuntimeException(
                    "Can't find or write temporary folder: {$this->tempDir}"
                );
            }
            $this->tempDir .= \DIRECTORY_SEPARATOR . md5(random_bytes(20));
            $this->removeDir($this->tempDir);
            if (!mkdir($this->tempDir, 0777, true)) {
                throw new \RuntimeException(
                    "Can't create temporary folder: {$this->tempDir}"
                );
            }
        }

        return $this->tempDir;
    }

    /**
     * Creates new directory for test and returns it's absolute path.
     */
    protected function getPathToTestDir(string $name = ''): string
    {
        if ($name === '') {
            $name = md5(random_bytes(10));
        }

        if (strpos($name, $this->getTempDir() . \DIRECTORY_SEPARATOR) === 0) {
            $pathToFolder = $name;
        } else {
            $pathToFolder = $this->getTempDir() . \DIRECTORY_SEPARATOR . $name;
        }

        if (!file_exists($pathToFolder) && !mkdir($pathToFolder, 0777, true)) {
            throw new \RuntimeException("Can't create {$pathToFolder} folder");
        }

        return $pathToFolder;
    }

    /**
     * Creates file within temporary folder.
     */
    protected function getPathToTestFile(string $name = '', ?string $content = null): string
    {
        if ($name === '') {
            $name = md5(random_bytes(10)) . '.txt';
        }

        if (strpos($name, $this->getTempDir()) === 0) {
            $pathToFile = $name;
        } else {
            $pathToFile = $this->getTempDir() . \DIRECTORY_SEPARATOR . $name;
        }

        $dir = pathinfo($pathToFile, \PATHINFO_DIRNAME);
        if (!file_exists($dir) && !mkdir($dir, 0777, true)) {
            throw new \RuntimeException("Can't create folder {$dir} for the tests file {$name}");
        }

        $content = $content === null ? md5(random_bytes(10)) : $content;
        if (file_put_contents($pathToFile, $content) === false) {
            throw new \RuntimeException("Can't create file {$pathToFile}");
        }

        return $pathToFile;
    }

    /**
     * Removes set dir with all content.
     */
    protected function removeDir(string $folderPath): void
    {
        if (is_dir($folderPath)) {
            $it = new \RecursiveDirectoryIterator(
                $folderPath,
                \RecursiveDirectoryIterator::SKIP_DOTS
            );
            /** @var iterable<\SplFileInfo> */
            $files = new \RecursiveIteratorIterator(
                $it,
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } elseif ($file->isFile()) {
                    unlink($file->getRealPath());
                }
            }
            rmdir($folderPath);
        }
    }

    /**
     * Converts SplFileInfo to string and change delimeters to the set one.
     */
    protected function convertPathToString(\SplFileInfo|string $path, string $delimeter = \DIRECTORY_SEPARATOR): string
    {
        $pathStr = $path instanceof \SplFileInfo ? $path->getPathname() : $path;

        return str_replace(['\\', '/'], $delimeter, trim($pathStr));
    }

    /**
     * Converts string to SplFileInfo.
     */
    protected function convertPathToSpl(\SplFileInfo|string $path): \SplFileInfo
    {
        if ($path instanceof \SplFileInfo) {
            return $path;
        }

        return new \SplFileInfo($path);
    }

    /**
     * Assertion that checks that directory has set permissions.
     */
    protected function assertDirectoryHasPermissions(int $awaitedPermissions, \SplFileInfo|string $directory): void
    {
        $directory = $this->convertPathToString($directory);
        $awaitedPermissions = sprintf('%o', $awaitedPermissions);
        $realPermissions = substr(sprintf('%o', fileperms($directory)), -3);
        $this->assertSame($awaitedPermissions, $realPermissions, 'Directory has correct permissions');
    }
}
