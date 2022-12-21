<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper\Tests;

use Marvin255\FileSystemHelper\FileSystemException;
use Marvin255\FileSystemHelper\FileSystemHelperBase;

/**
 * @internal
 */
class FileSystemHelperBaseTest extends BaseCase
{
    /**
     * @test
     */
    public function testEmptyBasePathInConstructException(): void
    {
        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage("Base folder can't be empty. Set non empty string or null");
        new FileSystemHelperBase('');
    }

    /**
     * @test
     */
    public function testEmptyBasePathUnexistedInConstructException(): void
    {
        $path = '/test-path-123';

        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage("Base folder '{$path}' doesn't exist");
        new FileSystemHelperBase($path);
    }

    /**
     * @test
     *
     * @dataProvider provideRemove
     */
    public function testRemove(string|\SplFileInfo $file, ?string $baseDir = null, ?\Exception $exception = null): void
    {
        $helper = new FileSystemHelperBase($baseDir);

        if ($exception) {
            $this->expectExceptionObject($exception);
        }

        $helper->remove($file);

        if (!$exception) {
            $this->assertFileDoesNotExist($this->convertPathToString($file));
        }
    }

    /**
     * @return array<string, mixed[]>
     */
    public function provideRemove(): array
    {
        $dirWithContent = $this->getPathToTestDir('dir_with_content');
        $this->getPathToTestFile($dirWithContent . '/nested.txt');
        $this->getPathToTestFile($dirWithContent . '/nested_folder/nested_second.txt');

        return [
            'remove file out of restricted folder' => [
                $this->getPathToTestFile('wrong_base_folder/file.txt'),
                $this->getPathToTestDir('base_folder'),
                new FileSystemException('All paths must be within base directory'),
            ],
            'remove file within base folder' => [
                $this->getPathToTestFile('correct_base_folder/test.txt'),
                $this->getPathToTestDir('correct_base_folder'),
            ],
            'remove file with utf symbols in the name' => [
                $this->getPathToTestFile('тест/тест.txt'),
                $this->getPathToTestDir('тест'),
            ],
            'remove file without base folder set' => [
                $this->getPathToTestFile(),
            ],
            'remove file with backslashes in name' => [
                $this->convertPathToString($this->getPathToTestFile('backslashes/test.txt'), '\\'),
                $this->convertPathToString($this->getPathToTestDir('backslashes'), '\\'),
            ],
            'remove file object' => [
                $this->convertPathToSpl($this->getPathToTestFile()),
            ],
            'remove dir' => [
                $dirWithContent,
            ],
            'remove non existed entity' => [
                '/test_file_not_exist.txt',
                null,
                new FileSystemException('Can\'t find entity'),
            ],
            'remove with empty string' => [
                '',
                null,
                new FileSystemException('Can\'t create SplFileInfo'),
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider provideRemoveIfExists
     */
    public function testRemoveIfExists(string|\SplFileInfo $file, ?string $baseDir = null, ?\Exception $exception = null): void
    {
        $helper = new FileSystemHelperBase($baseDir);

        if ($exception) {
            $this->expectExceptionObject($exception);
        }

        $helper->removeIfExists($file);

        if (!$exception) {
            $this->assertFileDoesNotExist($this->convertPathToString($file));
        }
    }

    /**
     * @return array<string, mixed[]>
     */
    public function provideRemoveIfExists(): array
    {
        $tests = $this->provideRemove();
        $tests['remove non existed entity'] = [
            '/test_file_not_exist.txt',
        ];

        return $tests;
    }

    /**
     * @test
     *
     * @dataProvider provideCopyFile
     */
    public function testCopyFile(string|\SplFileInfo $from, string|\SplFileInfo $to, ?\Exception $exception = null): void
    {
        $helper = new FileSystemHelperBase();

        if ($exception) {
            $this->expectExceptionObject($exception);
        }

        $helper->copy($from, $to);

        if (!$exception) {
            $from = $this->convertPathToString($from);
            $to = $this->convertPathToString($to);
            $this->assertFileExists($to);
            $this->assertFileEquals($from, $to);
        }
    }

    public function provideCopyFile(): array
    {
        $dir = $this->getPathToTestDir('dir');
        $utfDir = $this->getPathToTestDir('тест');

        return [
            'copy file' => [
                $this->getPathToTestFile(),
                $dir . '/copy_destination.txt',
            ],
            'copy file object' => [
                $this->convertPathToSpl($this->getPathToTestFile()),
                $this->convertPathToSpl($dir . '/copy_spl_destination.txt'),
            ],
            'copy unexisted file' => [
                '/non_existed_file',
                '/destination',
                new FileSystemException('Can\'t find source'),
            ],
            'copy to existed file' => [
                $this->getPathToTestFile(),
                $this->getPathToTestFile(),
                new FileSystemException('already exists'),
            ],
            'copy to existed dir' => [
                $this->getPathToTestFile(),
                $this->getPathToTestDir(),
                new FileSystemException('already exists'),
            ],
            'copy entites with utf in names' => [
                $this->getPathToTestFile($utfDir . '/тест.txt'),
                $utfDir . '/тест_новый.txt',
            ],
            'copy file with backslashes in name' => [
                $this->convertPathToString($this->getPathToTestFile(), '\\'),
                $this->convertPathToString($dir . '/backslashes_destination.txt', '\\'),
            ],
            'copy to the path where parent is not a folder' => [
                $this->getPathToTestFile(),
                $this->getPathToTestFile() . '/file.txt',
                new FileSystemException('is not a direcotry or doesn\'t exist'),
            ],
        ];
    }

    /**
     * @test
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
     * @test
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
     * @test
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
     * @test
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
     * @test
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
     * @test
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
     * @test
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
     * @test
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
     * @test
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
     * @test
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
     * @test
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
     * @test
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
     * @test
     */
    public function testMkdirIfNotExistExisted(): void
    {
        $dir = $this->getPathToTestDir();

        $helper = new FileSystemHelperBase();
        $helper->mkdirIfNotExist($dir);

        $this->assertDirectoryExists($dir);
    }

    /**
     * @test
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
     * @test
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
     * @test
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
     * @test
     */
    public function testGetTmpDir(): void
    {
        $helper = new FileSystemHelperBase();
        $tmpDir = $helper->getTmpDir();

        $this->assertInstanceOf(\SplFileInfo::class, $tmpDir);
    }

    /**
     * @test
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
     * @test
     */
    public function testMakeFileInfoString(): void
    {
        $file = $this->getPathToTestFile();

        $helper = new FileSystemHelperBase();
        $fileInfo = $helper->makeFileInfo($file);

        $this->assertInstanceOf(\SplFileInfo::class, $fileInfo);
        $this->assertSame($file, $fileInfo->getPathname());
    }

    /**
     * @test
     */
    public function testMakeFileInfoObject(): void
    {
        $file = new \SplFileInfo($this->getPathToTestFile());

        $helper = new FileSystemHelperBase();
        $fileInfo = $helper->makeFileInfo($file);

        $this->assertInstanceOf(\SplFileInfo::class, $fileInfo);
        $this->assertSame($file->getPathname(), $fileInfo->getPathname());
    }

    /**
     * @test
     */
    public function testMakeFileInfoEmptyString(): void
    {
        $helper = new FileSystemHelperBase();

        $this->expectException(FileSystemException::class);
        $helper->makeFileInfo('    ');
    }

    /**
     * @test
     */
    public function testMakeFileInfoWrongInput(): void
    {
        $helper = new FileSystemHelperBase();

        $this->expectException(FileSystemException::class);
        $helper->makeFileInfo(true);
    }
}
