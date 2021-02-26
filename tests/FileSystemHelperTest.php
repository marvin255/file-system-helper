<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper\Tests;

use Marvin255\FileSystemHelper\FileSystemException;
use Marvin255\FileSystemHelper\FileSystemHelper;
use SplFileInfo;
use Throwable;

class FileSystemHelperTest extends BaseCase
{
    /**
     * @throws Throwable
     */
    public function testRemoveFile(): void
    {
        $file = $this->getPathToTestFile();

        $helper = new FileSystemHelper();
        $helper->remove($file);

        $this->assertFileDoesNotExist($file);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveFileSplObject(): void
    {
        $file = $this->getPathToTestFile();

        $helper = new FileSystemHelper();
        $helper->remove(new SplFileInfo($file));

        $this->assertFileDoesNotExist($file);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveDir(): void
    {
        $dir = $this->getPathToTestDir();
        $nestedFile = $this->getPathToTestFile($dir . '/nested.txt');
        $nestedDir = $this->getPathToTestDir($dir . '/nested');
        $nestedFileSecondLevel = $this->getPathToTestDir($nestedDir . '/nested_second.txt');

        $helper = new FileSystemHelper();
        $helper->remove($dir);

        $this->assertFileDoesNotExist($nestedFile);
        $this->assertFileDoesNotExist($nestedFileSecondLevel);
        $this->assertDirectoryDoesNotExist($nestedDir);
        $this->assertDirectoryDoesNotExist($dir);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveUnexistedException(): void
    {
        $file = '/test_file_not_exist.txt';

        $helper = new FileSystemHelper();

        $this->expectException(FileSystemException::class);
        $helper->remove($file);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveEmptyStringException(): void
    {
        $file = '';

        $helper = new FileSystemHelper();

        $this->expectException(FileSystemException::class);
        $helper->remove($file);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveWrongTypeException(): void
    {
        $file = [];

        $helper = new FileSystemHelper();

        $this->expectException(FileSystemException::class);
        $helper->remove($file);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveIfExistsFile(): void
    {
        $file = $this->getPathToTestFile();

        $helper = new FileSystemHelper();
        $helper->removeIfExists($file);

        $this->assertFileDoesNotExist($file);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveIfExistsFileSplObject(): void
    {
        $file = $this->getPathToTestFile();

        $helper = new FileSystemHelper();
        $helper->removeIfExists(new SplFileInfo($file));

        $this->assertFileDoesNotExist($file);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveIfExistsDir(): void
    {
        $dir = $this->getPathToTestDir();
        $nestedFile = $this->getPathToTestFile($dir . '/nested.txt');
        $nestedDir = $this->getPathToTestDir($dir . '/nested');
        $nestedFileSecondLevel = $this->getPathToTestDir($nestedDir . '/nested_second.txt');

        $helper = new FileSystemHelper();
        $helper->removeIfExists($dir);

        $this->assertFileDoesNotExist($nestedFile);
        $this->assertFileDoesNotExist($nestedFileSecondLevel);
        $this->assertDirectoryDoesNotExist($nestedDir);
        $this->assertDirectoryDoesNotExist($dir);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveIfExustsUnexistedException(): void
    {
        $file = '/test_file_not_exist.txt';

        $helper = new FileSystemHelper();

        $helper->removeIfExists($file);

        $this->assertFileDoesNotExist($file);
    }

    /**
     * @throws Throwable
     */
    public function testCopyFile(): void
    {
        $dir = $this->getPathToTestDir();
        $from = $this->getPathToTestFile($dir . '/test.txt');
        $to = $dir . '/test_copy.txt';

        $helper = new FileSystemHelper();
        $helper->copy($from, $to);

        $this->assertFileExists($to);
        $this->assertFileEquals($from, $to);
    }

    /**
     * @throws Throwable
     */
    public function testCopyDir(): void
    {
        $from = $this->getPathToTestDir();
        $nestedFile = $this->getPathToTestFile($from . '/nested.txt');
        $nestedDir = $this->getPathToTestDir($from . '/nested');
        $nestedFileSecondLevel = $this->getPathToTestFile($nestedDir . '/nested_second.txt');

        $to = $this->getTempDir() . '/destination';
        $destinationNestedFile = $to . '/nested.txt';
        $destinationNestedFileSecondLevel = $to . '/nested/nested_second.txt';

        $helper = new FileSystemHelper();
        $helper->copy($from, $to);

        $this->assertDirectoryExists($to);
        $this->assertFileExists($destinationNestedFile);
        $this->assertFileEquals($nestedFile, $destinationNestedFile);
        $this->assertFileExists($destinationNestedFileSecondLevel);
        $this->assertFileEquals($nestedFileSecondLevel, $destinationNestedFileSecondLevel);
    }

    /**
     * @throws Throwable
     */
    public function testCopyUnexistedSourceException(): void
    {
        $from = $this->getTempDir() . '/non_existed_file';
        $to = $this->getTempDir() . '/destination';

        $helper = new FileSystemHelper();

        $this->expectException(FileSystemException::class);
        $helper->copy($from, $to);
    }

    /**
     * @throws Throwable
     */
    public function testCopyExistedDestinationException(): void
    {
        $from = $this->getPathToTestDir();
        $to = $this->getPathToTestDir();

        $helper = new FileSystemHelper();

        $this->expectException(FileSystemException::class);
        $helper->copy($from, $to);
    }

    /**
     * @throws Throwable
     */
    public function testCopyExistedParentDestinationException(): void
    {
        $from = $this->getPathToTestDir();
        $to = '/test/destination/folder';

        $helper = new FileSystemHelper();

        $this->expectException(FileSystemException::class);
        $helper->copy($from, $to);
    }

    /**
     * @throws Throwable
     */
    public function testRenameFile(): void
    {
        $dir = $this->getPathToTestDir();
        $from = $this->getPathToTestFile($dir . '/test.txt');
        $to = $dir . '/test_rename.txt';

        $helper = new FileSystemHelper();
        $helper->rename($from, $to);

        $this->assertFileExists($to);
        $this->assertFileDoesnotExist($from);
    }

    /**
     * @throws Throwable
     */
    public function testRenameDir(): void
    {
        $from = $this->getPathToTestDir();
        $nestedFile = $this->getPathToTestFile($from . '/nested.txt');
        $nestedDir = $this->getPathToTestDir($from . '/nested');
        $nestedFileSecondLevel = $this->getPathToTestFile($nestedDir . '/nested_second.txt');

        $to = $this->getTempDir() . '/destination';
        $destinationNestedFile = $to . '/nested.txt';
        $destinationNestedFileSecondLevel = $to . '/nested/nested_second.txt';

        $helper = new FileSystemHelper();
        $helper->rename($from, $to);

        $this->assertDirectoryExists($to);
        $this->assertDirectoryDoesNotExist($from);
        $this->assertFileExists($destinationNestedFile);
        $this->assertFileDoesnotExist($nestedFile);
        $this->assertFileExists($destinationNestedFileSecondLevel);
        $this->assertFileDoesnotExist($nestedFileSecondLevel);
    }

    /**
     * @throws Throwable
     */
    public function testRenameUnexistedSourceException(): void
    {
        $from = $this->getTempDir() . '/non_existed_file';
        $to = $this->getTempDir() . '/destination';

        $helper = new FileSystemHelper();

        $this->expectException(FileSystemException::class);
        $helper->rename($from, $to);
    }

    /**
     * @throws Throwable
     */
    public function testRenameExistedDestinationException(): void
    {
        $from = $this->getPathToTestDir();
        $to = $this->getPathToTestDir();

        $helper = new FileSystemHelper();

        $this->expectException(FileSystemException::class);
        $helper->rename($from, $to);
    }

    /**
     * @throws Throwable
     */
    public function testMkdir(): void
    {
        $dir = $this->getPathToTestDir();
        $newDir = $dir . '/nested1/nested2/nested3';

        $helper = new FileSystemHelper();
        $helper->mkdir($newDir);

        $this->assertDirectoryExists($newDir);
    }

    /**
     * @throws Throwable
     */
    public function testMkdirAlreadyExistException(): void
    {
        $dir = $this->getPathToTestDir();

        $helper = new FileSystemHelper();

        $this->expectException(FileSystemException::class);
        $helper->mkdir($dir);
    }

    /**
     * @throws Throwable
     */
    public function testMkdirIfNotExist(): void
    {
        $dir = $this->getPathToTestDir();
        $newDir = $dir . '/nested1/nested2/nested3';

        $helper = new FileSystemHelper();
        $helper->mkdirIfNotExist($newDir);

        $this->assertDirectoryExists($newDir);
    }

    /**
     * @throws Throwable
     */
    public function testMkdirIfNotExistExisted(): void
    {
        $dir = $this->getPathToTestDir();

        $helper = new FileSystemHelper();
        $helper->mkdirIfNotExist($dir);

        $this->assertDirectoryExists($dir);
    }

    /**
     * @throws Throwable
     */
    public function testEmptyDir(): void
    {
        $dir = $this->getPathToTestDir();
        $nestedFile = $this->getPathToTestFile($dir . '/nested.txt');
        $nestedDir = $this->getPathToTestDir($dir . '/nested');
        $nestedFileSecondLevel = $this->getPathToTestFile($nestedDir . '/nested_second.txt');

        $helper = new FileSystemHelper();
        $helper->emptyDir($dir);

        $this->assertDirectoryExists($dir);
        $this->assertDirectoryDoesNotExist($nestedDir);
        $this->assertFileDoesnotExist($nestedFile);
        $this->assertFileDoesnotExist($nestedFileSecondLevel);
    }

    /**
     * @throws Throwable
     */
    public function testEmptyDirUnexistedException(): void
    {
        $dir = '/unexisted/dir';

        $helper = new FileSystemHelper();

        $this->expectException(FileSystemException::class);
        $helper->emptyDir($dir);
    }

    /**
     * @throws Throwable
     */
    public function testGetTmpDir(): void
    {
        $helper = new FileSystemHelper();
        $tmpDir = $helper->getTmpDir();

        $this->assertInstanceOf(SplFileInfo::class, $tmpDir);
    }
}
