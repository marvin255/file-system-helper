<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper\Tests;

use Marvin255\FileSystemHelper\FileSystemException;
use Marvin255\FileSystemHelper\FileSystemHelperBase;
use SplFileInfo;
use Throwable;

/**
 * @internal
 */
class FileSystemHelperBaseTest extends BaseCase
{
    /**
     * @throws Throwable
     */
    public function testEmptyBasePathInConstructException(): void
    {
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage("Base folder can't be empty. Set non empty string or null");
        new FileSystemHelperBase('');
    }

    /**
     * @throws Throwable
     */
    public function testEmptyBasePathUnexistedInConstructException(): void
    {
        $path = '/test-path-123';

        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage("Base folder '{$path}' doesn't exist");
        new FileSystemHelperBase($path);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveFileOutOfRestrictedFolderException(): void
    {
        $baseDir = $this->getPathToTestDir();
        $file = $this->getPathToTestFile();

        $helper = new FileSystemHelperBase($baseDir);

        $this->expectException(FileSystemException::class);
        $helper->remove($file);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveFileWithinBaseFolder(): void
    {
        $tmpDir = $this->getTempDir();
        $file = $this->getPathToTestFile();

        $helper = new FileSystemHelperBase($tmpDir);
        $helper->remove($file);

        $this->assertFileDoesNotExist($file);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveFileWithUtf(): void
    {
        $tmpDir = $this->getPathToTestDir('тест');
        $file = $this->getPathToTestFile($tmpDir . '/тест.txt');

        $helper = new FileSystemHelperBase($tmpDir);
        $helper->remove($file);

        $this->assertFileDoesNotExist($file);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveFileWithinBaseFolderWithBackSlashes(): void
    {
        $tmpDir = '   ' . str_replace(['\\', '/'], '\\', $this->getTempDir()) . ' ';
        $file = $this->getPathToTestFile();

        $helper = new FileSystemHelperBase($tmpDir);
        $helper->remove($file);

        $this->assertFileDoesNotExist($file);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveFile(): void
    {
        $file = $this->getPathToTestFile();

        $helper = new FileSystemHelperBase();
        $helper->remove($file);

        $this->assertFileDoesNotExist($file);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveFileSplObject(): void
    {
        $file = $this->getPathToTestFile();

        $helper = new FileSystemHelperBase();
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

        $helper = new FileSystemHelperBase();
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

        $helper = new FileSystemHelperBase();

        $this->expectException(FileSystemException::class);
        $helper->remove($file);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveEmptyStringException(): void
    {
        $file = '';

        $helper = new FileSystemHelperBase();

        $this->expectException(FileSystemException::class);
        $helper->remove($file);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveIfExistsFile(): void
    {
        $file = $this->getPathToTestFile();

        $helper = new FileSystemHelperBase();
        $helper->removeIfExists($file);

        $this->assertFileDoesNotExist($file);
    }

    /**
     * @throws Throwable
     */
    public function testRemoveIfExistsFileSplObject(): void
    {
        $file = $this->getPathToTestFile();

        $helper = new FileSystemHelperBase();
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

        $helper = new FileSystemHelperBase();
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

        $helper = new FileSystemHelperBase();

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

        $helper = new FileSystemHelperBase();
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

        $helper = new FileSystemHelperBase();
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

        $helper = new FileSystemHelperBase();

        $this->expectException(FileSystemException::class);
        $helper->copy($from, $to);
    }

    /**
     * @throws Throwable
     */
    public function testCopyExistedDirDestinationException(): void
    {
        $from = $this->getPathToTestDir();
        $to = $this->getPathToTestDir();

        $helper = new FileSystemHelperBase();

        $this->expectException(FileSystemException::class);
        $helper->copy($from, $to);
    }

    /**
     * @throws Throwable
     */
    public function testCopyExistedFileDestinationException(): void
    {
        $from = $this->getPathToTestFile();
        $to = $this->getPathToTestFile();

        $helper = new FileSystemHelperBase();

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

        $helper = new FileSystemHelperBase();

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

        $helper = new FileSystemHelperBase();
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

        $helper = new FileSystemHelperBase();
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

        $helper = new FileSystemHelperBase();

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

        $helper = new FileSystemHelperBase();

        $this->expectException(FileSystemException::class);
        $helper->rename($from, $to);
    }

    /**
     * @throws Throwable
     */
    public function testRenameUnexistedParentDestination(): void
    {
        $dir = $this->getPathToTestDir();
        $from = $this->getPathToTestFile($dir . '/test.txt');
        $to = $dir . '/unexisted_folder/test_rename.txt';

        $helper = new FileSystemHelperBase();

        $this->expectException(FileSystemException::class);
        $helper->rename($from, $to);
    }

    /**
     * @throws Throwable
     */
    public function testMkdir(): void
    {
        $dir = $this->getPathToTestDir();
        $newDir = $dir . '/test_dir';
        $permissions = 0775;

        $helper = new FileSystemHelperBase();
        $helper->mkdir($newDir, $permissions);

        $this->assertDirectoryExists($newDir);
        $this->assertDirectoryHasPermissions($permissions, $newDir);
    }

    /**
     * @throws Throwable
     */
    public function testMkdirNested(): void
    {
        $dir = $this->getPathToTestDir();
        $newDir = $dir . '/nested1/nested2/nested3';
        $permissions = 0775;

        $helper = new FileSystemHelperBase();
        $helper->mkdir($newDir, $permissions);

        $this->assertDirectoryExists($newDir);
        $this->assertDirectoryHasPermissions($permissions, $newDir);
    }

    /**
     * @throws Throwable
     */
    public function testMkdirDefaultPermissions(): void
    {
        $dir = $this->getPathToTestDir();
        $newDir = $dir . '/nested1/nested2/nested3';

        $helper = new FileSystemHelperBase();
        $helper->mkdir($newDir);

        $this->assertDirectoryExists($newDir);
        $this->assertDirectoryHasPermissions(0777, $newDir);
    }

    /**
     * @throws Throwable
     */
    public function testMkdirAlreadyExistException(): void
    {
        $dir = $this->getPathToTestDir();

        $helper = new FileSystemHelperBase();

        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage("Entity '{$dir}' already exists");
        $helper->mkdir($dir);
    }

    /**
     * @throws Throwable
     */
    public function testMkdirIfNotExist(): void
    {
        $dir = $this->getPathToTestDir();
        $newDir = $dir . '/nested1/nested2/nested3';

        $helper = new FileSystemHelperBase();
        $helper->mkdirIfNotExist($newDir);

        $this->assertDirectoryExists($newDir);
    }

    /**
     * @throws Throwable
     */
    public function testMkdirIfNotExistDefaultPermissions(): void
    {
        $dir = $this->getPathToTestDir();
        $newDir = $dir . '/nested1/nested2/nested3';

        $helper = new FileSystemHelperBase();
        $helper->mkdirIfNotExist($newDir);

        $this->assertDirectoryExists($newDir);
        $this->assertDirectoryHasPermissions(0777, $newDir);
    }

    /**
     * @throws Throwable
     */
    public function testMkdirIfNotExistExisted(): void
    {
        $dir = $this->getPathToTestDir();

        $helper = new FileSystemHelperBase();
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

        $helper = new FileSystemHelperBase();
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

        $helper = new FileSystemHelperBase();

        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage("Directory '{$dir}' must exist to be emptied");
        $helper->emptyDir($dir);
    }

    /**
     * @throws Throwable
     */
    public function testEmptyDirFileException(): void
    {
        $file = $this->getPathToTestFile();

        $helper = new FileSystemHelperBase();

        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage("Can't empty directory '{$file}' because it's a file");
        $helper->emptyDir($file);
    }

    /**
     * @throws Throwable
     */
    public function testGetTmpDir(): void
    {
        $helper = new FileSystemHelperBase();
        $tmpDir = $helper->getTmpDir();

        $this->assertInstanceOf(SplFileInfo::class, $tmpDir);
    }

    /**
     * @throws Throwable
     */
    public function testIterateNonDirectoryException(): void
    {
        $file = $this->getPathToTestFile();

        $helper = new FileSystemHelperBase();

        $this->expectException(FileSystemException::class);
        $helper->iterateDirectory(
            $file,
            function (): void {}
        );
    }

    /**
     * @throws Throwable
     */
    public function testMakeFileInfoString(): void
    {
        $file = $this->getPathToTestFile();

        $helper = new FileSystemHelperBase();
        $fileInfo = $helper->makeFileInfo($file);

        $this->assertInstanceOf(SplFileInfo::class, $fileInfo);
        $this->assertSame($file, $fileInfo->getPathname());
    }

    /**
     * @throws Throwable
     */
    public function testMakeFileInfoObject(): void
    {
        $file = new SplFileInfo($this->getPathToTestFile());

        $helper = new FileSystemHelperBase();
        $fileInfo = $helper->makeFileInfo($file);

        $this->assertInstanceOf(SplFileInfo::class, $fileInfo);
        $this->assertSame($file->getPathname(), $fileInfo->getPathname());
    }

    /**
     * @throws Throwable
     */
    public function testMakeFileInfoEmptyString(): void
    {
        $helper = new FileSystemHelperBase();

        $this->expectException(FileSystemException::class);
        $helper->makeFileInfo('    ');
    }

    /**
     * @throws Throwable
     */
    public function testMakeFileInfoWrongInput(): void
    {
        $helper = new FileSystemHelperBase();

        $this->expectException(FileSystemException::class);
        $helper->makeFileInfo(true);
    }
}
