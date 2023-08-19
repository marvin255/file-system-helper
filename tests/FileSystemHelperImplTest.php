<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper\Tests;

use Marvin255\FileSystemHelper\Exception\FileSystemException;
use Marvin255\FileSystemHelper\FileSystemHelperImpl;

/**
 * @internal
 */
class FileSystemHelperImplTest extends BaseCase
{
    public function testEmptyBasePathUnexistedInConstructException(): void
    {
        $path = '/test-path-123';
        $exception = FileSystemException::create("Base folder '{$path}' doesn't exist");

        $this->expectExceptionObject($exception);
        new FileSystemHelperImpl($path);
    }

    /**
     * @dataProvider provideRemove
     */
    public function testRemove(string|\SplFileInfo $file, string $baseDir = null, \Exception $exception = null): void
    {
        $helper = new FileSystemHelperImpl($baseDir);

        if ($exception) {
            $this->expectExceptionObject($exception);
        }

        $helper->remove($file);

        if (!$exception) {
            $this->assertFileDoesNotExist(self::convertPathToString($file));
        }
    }

    public static function provideRemove(): array
    {
        $id = [self::class, 'provideRemove'];

        $dirWithContent = self::getPathToTestDir($id, 'dir_content');
        self::getPathToTestFile($id, 'dir_content');
        self::getPathToTestFile($id, 'dir_content');

        return [
            'remove file out of restricted folder' => [
                self::getPathToTestFile($id, 'dir_1'),
                self::getPathToTestDir($id, 'dir_1', 'nested'),
                FileSystemException::create('All paths must be within base directory'),
            ],
            'remove file within base folder' => [
                self::getPathToTestFile($id, 'dir_2'),
                self::getPathToTestDir($id, 'dir_2'),
            ],
            'remove file out of restricted folder by relative path' => [
                self::getPathToTestDir($id, 'dir_3', 'nested') . '/../../',
                self::getPathToTestDir($id, 'dir_3'),
                FileSystemException::create('All paths must be within base directory'),
            ],
            'remove file with utf symbols in the name' => [
                self::getPathToTestFile($id, 'dir_4', 'тест'),
                self::getPathToTestDir($id, 'dir_4', 'тест'),
            ],
            'remove file without base folder set' => [
                self::getPathToTestFile($id, 'dir_5'),
            ],
            'remove file with backslashes in name' => [
                self::convertPathToString(
                    self::getPathToTestFile($id, 'dir_6'),
                    '\\'
                ),
                self::convertPathToString(
                    self::getPathToTestDir($id, 'dir_6'),
                    '\\'
                ),
            ],
            'remove file object' => [
                self::convertPathToSpl(
                    self::getPathToTestFile($id, 'dir_7')
                ),
            ],
            'remove dir' => [
                $dirWithContent,
            ],
            'remove non existed entity' => [
                '/test_file_not_exist.txt',
                null,
                FileSystemException::create("Can't find entity"),
            ],
            'remove with empty string' => [
                '',
                null,
                FileSystemException::create("Can't create SplFileInfo"),
            ],
        ];
    }
}
