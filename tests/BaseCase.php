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
    /**
     * Returns path to temporary folder.
     */
    protected static function getTempDir(): string
    {
        $tmpDir = sys_get_temp_dir();

        if (!$tmpDir || !is_writable($tmpDir)) {
            throw new \RuntimeException(
                "Can't find or write temporary folder: {$tmpDir}"
            );
        }

        $tmpDir .= \DIRECTORY_SEPARATOR . 'file_system_helper_test';

        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0777, true)) {
            throw new \RuntimeException(
                "Can't create temporary folder: {$tmpDir}"
            );
        }

        return $tmpDir;
    }

    /**
     * Creates new directory for test and returns it's absolute path.
     *
     * @psalm-param string[]|string[][] $parts
     */
    protected static function getPathToTestDir(...$parts): string
    {
        $path = self::convertPartsToPath(...$parts);

        if (!is_dir($path) && !mkdir($path, 0777, true)) {
            throw new \RuntimeException("Can't create {$path} folder");
        }

        return $path;
    }

    /**
     * Creates file within temporary folder.
     *
     * @psalm-param string[]|string[][] $parts
     */
    protected static function getPathToTestFile(...$parts): string
    {
        $content = md5(random_bytes(10));
        $dir = self::getPathToTestDir(...$parts);
        $file = $dir . \DIRECTORY_SEPARATOR . md5(random_bytes(10)) . '.txt';

        if (file_put_contents($file, $content) === false) {
            throw new \RuntimeException("Can't create file {$file}");
        }

        return $file;
    }

    /**
     * Removes set dir with all content.
     *
     * @psalm-param string[]|string[][] $parts
     */
    protected static function clearDir(...$parts): void
    {
        $folderPath = self::convertPartsToPath(...$parts);

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
    protected static function convertPathToString(\SplFileInfo|string $path, string $delimeter = \DIRECTORY_SEPARATOR): string
    {
        $pathStr = $path instanceof \SplFileInfo ? $path->getPathname() : $path;

        return str_replace(['\\', '/'], $delimeter, trim($pathStr));
    }

    /**
     * Converts string to SplFileInfo.
     */
    protected static function convertPathToSpl(\SplFileInfo|string $path): \SplFileInfo
    {
        if ($path instanceof \SplFileInfo) {
            return $path;
        }

        return new \SplFileInfo($path);
    }

    /**
     * @psalm-param string[]|string[][] $parts
     */
    private static function convertPartsToPath(...$parts): string
    {
        $flattenParts = [];
        foreach ($parts as $part) {
            if (\is_array($part)) {
                $flattenParts = array_merge($flattenParts, $part);
            } else {
                $flattenParts[] = $part;
            }
        }

        $preparedParts = [self::getTempDir()];
        foreach ($flattenParts as $part) {
            $preparedPart = trim($part);
            $preparedPart = mb_strtolower($part);
            $preparedPart = str_replace([' ', '\\', '/', '.', ','], '_', $preparedPart);
            if ($preparedPart !== '') {
                $preparedParts[] = $preparedPart;
            }
        }

        if (\count($preparedParts) < 2) {
            throw new \RuntimeException('Parts for path must be provided');
        }

        return implode(\DIRECTORY_SEPARATOR, $preparedParts);
    }

    /**
     * Assertion that checks that directory has set permissions.
     */
    protected function assertDirectoryHasPermissions(int $awaitedPermissions, \SplFileInfo|string $directory): void
    {
        $directory = self::convertPathToString($directory);
        $awaitedPermissions = \sprintf('%o', $awaitedPermissions);
        $realPermissions = substr(\sprintf('%o', fileperms($directory)), -3);
        $this->assertSame($awaitedPermissions, $realPermissions, 'Directory has correct permissions');
    }
}
