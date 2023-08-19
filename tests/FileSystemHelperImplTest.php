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
        self::clearDir($id);

        $dirWithContent = self::getPathToTestDir($id, 'dir_content');
        self::getPathToTestFile($id, 'dir_content', 'nested');
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

    /**
     * @dataProvider provideRemoveIfExists
     */
    public function testRemoveIfExists(string|\SplFileInfo $file, string $baseDir = null, \Exception $exception = null): void
    {
        $helper = new FileSystemHelperImpl($baseDir);

        if ($exception) {
            $this->expectExceptionObject($exception);
        }

        $helper->removeIfExists($file);

        if (!$exception) {
            $this->assertFileDoesNotExist($this->convertPathToString($file));
        }
    }

    public static function provideRemoveIfExists(): array
    {
        $id = [self::class, 'provideRemoveIfExists'];
        self::clearDir($id);

        $dirWithContent = self::getPathToTestDir($id, 'dir_content');
        self::getPathToTestFile($id, 'dir_content', 'nested');
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
            ],
            'remove with empty string' => [
                '',
                null,
                FileSystemException::create("Can't create SplFileInfo"),
            ],
        ];
    }

    /**
     * @dataProvider provideCopyFile
     */
    public function testCopyFile(string|\SplFileInfo $from, string|\SplFileInfo $to, \Exception $exception = null, string $baseDir = null): void
    {
        $helper = new FileSystemHelperImpl($baseDir);

        if ($exception) {
            $this->expectExceptionObject($exception);
        }

        $helper->copy($from, $to);

        if (!$exception) {
            $from = self::convertPathToString($from);
            $to = self::convertPathToString($to);
            $this->assertFileExists($to);
            $this->assertFileEquals($from, $to);
        }
    }

    public static function provideCopyFile(): array
    {
        $id = [self::class, 'provideCopyFile'];
        self::clearDir($id);

        return [
            'copy file' => [
                self::getPathToTestFile($id, 'dir_1'),
                self::getPathToTestDir($id, 'dir_1') . '/dest.txt',
            ],
            'copy file object' => [
                self::convertPathToSpl(
                    self::getPathToTestFile($id, 'dir_2')
                ),
                self::convertPathToSpl(
                    self::getPathToTestDir($id, 'dir_2') . '/dest.txt'
                ),
            ],
            'copy unexisted file' => [
                '/non_existed_file',
                '/destination',
                FileSystemException::create("Can't find source"),
            ],
            'copy to existed file' => [
                self::getPathToTestFile($id, 'dir_3'),
                self::getPathToTestFile($id, 'dir_3'),
                FileSystemException::create('already exists'),
            ],
            'copy to existed dir' => [
                self::getPathToTestFile($id, 'dir_4'),
                self::getPathToTestDir($id, 'dir_4'),
                FileSystemException::create('already exists'),
            ],
            'copy entites with utf in names' => [
                self::getPathToTestFile($id, 'dir_5', 'тест'),
                self::getPathToTestDir($id, 'dir_5', 'тест') . '/тест_новый.txt',
            ],
            'copy file with backslashes in name' => [
                self::convertPathToString(
                    self::getPathToTestFile($id, 'dir_6'),
                    '\\'
                ),
                self::convertPathToString(
                    self::getPathToTestDir($id, 'dir_6') . '/dest.txt',
                    '\\'
                ),
            ],
            'copy to the path where parent is not a folder' => [
                self::getPathToTestFile($id, 'dir_7'),
                self::getPathToTestFile($id, 'dir_7') . '/file.txt',
                FileSystemException::create("is not a direcotry or doesn't exist"),
            ],
            'copy from outside base folder' => [
                self::getPathToTestFile($id, 'dir_8'),
                self::getPathToTestDir($id, 'dir_8', 'base') . '/dest.txt',
                FileSystemException::create('All paths must be within base directory'),
                self::getPathToTestFile($id, 'dir_8', 'base'),
            ],
            'copy to outside base folder' => [
                self::getPathToTestFile($id, 'dir_9', 'base'),
                self::getPathToTestDir($id, 'dir_9') . '/dest.txt',
                FileSystemException::create('All paths must be within base directory'),
                self::getPathToTestFile($id, 'dir_9', 'base'),
            ],
            'copy from outside base folder by relative path' => [
                self::getPathToTestDir($id, 'dir_10') . '/../outside_base_dir/outside_base_dir.txt',
                self::getPathToTestDir($id, 'dir_10') . '/outside_base_dir_destination.txt',
                FileSystemException::create('All paths must be within base directory'),
                self::getPathToTestDir($id, 'dir_10'),
            ],
            'copy to outside base folder by relative path' => [
                self::getPathToTestFile($id, 'dir_11'),
                self::getPathToTestDir($id, 'dir_11') . '/../outside_base_dir/outside_base_dir.txt',
                FileSystemException::create('All paths must be within base directory'),
                self::getPathToTestDir($id, 'dir_11'),
            ],
        ];
    }

    public function testCopyDir(): void
    {
        $id = [self::class, 'testCopyDir'];
        self::clearDir($id);

        $from = self::getPathToTestDir($id, 'source');
        $nestedFile = self::getPathToTestFile($id, 'source');
        $nestedFileSecondLevel = self::getPathToTestFile($id, 'source', 'nested');

        $to = self::getPathToTestDir($id) . '/destination';
        $destinationNestedFile = $to . '/' . pathinfo($nestedFile, \PATHINFO_BASENAME);
        $destinationNestedFileSecondLevel = $to . '/nested/' . pathinfo($nestedFileSecondLevel, \PATHINFO_BASENAME);

        $helper = new FileSystemHelperImpl();
        $helper->copy($from, $to);

        $this->assertDirectoryExists($to);
        $this->assertFileExists($destinationNestedFile);
        $this->assertFileEquals($nestedFile, $destinationNestedFile);
        $this->assertFileExists($destinationNestedFileSecondLevel);
        $this->assertFileEquals($nestedFileSecondLevel, $destinationNestedFileSecondLevel);
    }
}
