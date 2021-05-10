<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper\Tests;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Basic class for all tests in suite.
 */
abstract class BaseCase extends TestCase
{
    /**
     * @var string|null
     */
    private $tempDir = null;

    /**
     * Returns path to temporary folder.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected function getTempDir(): string
    {
        if ($this->tempDir === null) {
            $this->tempDir = sys_get_temp_dir();
            if (!$this->tempDir || !is_writable($this->tempDir)) {
                throw new RuntimeException(
                    "Can't find or write temporary folder: {$this->tempDir}"
                );
            }
            $this->tempDir .= \DIRECTORY_SEPARATOR . 'fias_component';
            $this->removeDir($this->tempDir);
            if (!mkdir($this->tempDir, 0777, true)) {
                throw new RuntimeException(
                    "Can't create temporary folder: {$this->tempDir}"
                );
            }
        }

        return $this->tempDir;
    }

    /**
     * Creates new directory for test and returns it's absolute path.
     *
     * @param string $name
     *
     * @return string
     *
     * @throws RuntimeException
     */
    protected function getPathToTestDir(string $name = ''): string
    {
        if ($name === '') {
            $name = md5(random_bytes(10));
        }

        if (strpos($name, $this->getTempDir()) === 0) {
            $pathToFolder = $name;
        } else {
            $pathToFolder = $this->getTempDir() . \DIRECTORY_SEPARATOR . $name;
        }

        if (!mkdir($pathToFolder, 0777, true)) {
            throw new RuntimeException("Can't mkdir {$pathToFolder} folder");
        }

        return $pathToFolder;
    }

    /**
     * Creates file witin temporary folder.
     *
     * @param string      $name
     * @param string|null $content
     *
     * @return string
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

        $content = $content === null ? md5(random_bytes(10)) : $content;
        if (file_put_contents($pathToFile, $content) === false) {
            throw new RuntimeException("Can't create file {$pathToFile}");
        }

        return $pathToFile;
    }

    /**
     * Removes folder.
     *
     * @param string $folderPath
     */
    protected function removeDir(string $folderPath): void
    {
        if (is_dir($folderPath)) {
            $it = new RecursiveDirectoryIterator(
                $folderPath,
                RecursiveDirectoryIterator::SKIP_DOTS
            );
            $files = new RecursiveIteratorIterator(
                $it,
                RecursiveIteratorIterator::CHILD_FIRST
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
     * Removes temporary directory.
     */
    protected function tearDown(): void
    {
        if ($this->tempDir) {
            $this->removeDir($this->tempDir);
        }

        parent::tearDown();
    }
}
