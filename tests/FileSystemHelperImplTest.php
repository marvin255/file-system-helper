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
    /**
     * @test
     */
    public function testEmptyBasePathUnexistedInConstructException(): void
    {
        $path = '/test-path-123';

        $this->expectExceptionObject(
            FileSystemException::create("Base folder '{$path}' doesn't exist")
        );

        new FileSystemHelperImpl($path);
    }

    /**
     * @test
     *
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
                FileSystemException::create('All paths must be within base directory'),
            ],
            'remove file within base folder' => [
                $this->getPathToTestFile('correct_base_folder/test.txt'),
                $this->getPathToTestDir('correct_base_folder'),
            ],
            'remove file out of restricted folder by relative path' => [
                $this->getPathToTestDir('relative_base_folder/nested') . '/../../',
                $this->getPathToTestDir('relative_base_folder'),
                FileSystemException::create('All paths must be within base directory'),
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
                FileSystemException::create('Can\'t find entity'),
            ],
            'remove with empty string' => [
                '',
                null,
                FileSystemException::create('Can\'t create SplFileInfo'),
            ],
        ];
    }

    /**
     * @test
     *
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
    public function testCopyFile(string|\SplFileInfo $from, string|\SplFileInfo $to, \Exception $exception = null, string $baseDir = null): void
    {
        $helper = new FileSystemHelperImpl($baseDir);

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

    /**
     * @return array<string, mixed[]>
     */
    public function provideCopyFile(): array
    {
        $dir = $this->getPathToTestDir('copy');
        $utfDir = $this->getPathToTestDir('копирование');

        $dirOutsideBaseDir = $this->getPathToTestDir('outside_base_dir');
        $this->getPathToTestDir($dirOutsideBaseDir . '/outside_base_dir.txt');

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
                FileSystemException::create('Can\'t find source'),
            ],
            'copy to existed file' => [
                $this->getPathToTestFile(),
                $this->getPathToTestFile(),
                FileSystemException::create('already exists'),
            ],
            'copy to existed dir' => [
                $this->getPathToTestFile(),
                $this->getPathToTestDir(),
                FileSystemException::create('already exists'),
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
                FileSystemException::create('is not a direcotry or doesn\'t exist'),
            ],
            'copy from outside base folder' => [
                $this->getPathToTestFile('outside_base_dir/test.txt'),
                $dir . '/outside_base_dir_destination.txt',
                FileSystemException::create('All paths must be within base directory'),
                $dir,
            ],
            'copy to outside base folder' => [
                $this->getPathToTestFile($dir . '/test.txt'),
                $this->getPathToTestDir() . '/outside_base_dir_destination.txt',
                FileSystemException::create('All paths must be within base directory'),
                $dir,
            ],
            'copy from outside base folder by relative path' => [
                $dir . '/../outside_base_dir/outside_base_dir.txt',
                $dir . '/outside_base_dir_destination.txt',
                FileSystemException::create('All paths must be within base directory'),
                $dir,
            ],
            'copy to outside base folder by relative path' => [
                $this->getPathToTestFile($dir . '/outside_base_dir_destination.txt'),
                $dir . '/../outside_base_dir/outside_base_dir.txt',
                FileSystemException::create('All paths must be within base directory'),
                $dir,
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

        $helper = new FileSystemHelperImpl();
        $helper->copy($from, $to);

        $this->assertDirectoryExists($to);
        $this->assertFileExists($destinationNestedFile);
        $this->assertFileEquals($nestedFile, $destinationNestedFile);
        $this->assertFileExists($destinationNestedFileSecondLevel);
        $this->assertFileEquals($nestedFileSecondLevel, $destinationNestedFileSecondLevel);
    }

    /**
     * @test
     *
     * @dataProvider provideCopyFile
     */
    public function testRenameFile(string|\SplFileInfo $from, string|\SplFileInfo $to, \Exception $exception = null, string $baseDir = null): void
    {
        $helper = new FileSystemHelperImpl($baseDir);

        if ($exception) {
            $this->expectExceptionObject($exception);
        }

        $helper->rename($from, $to);

        if (!$exception) {
            $from = $this->convertPathToString($from);
            $to = $this->convertPathToString($to);
            $this->assertFileExists($to);
            $this->assertFileDoesnotExist($from);
        }
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

        $helper = new FileSystemHelperImpl();
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
     *
     * @dataProvider provideMkdir
     */
    public function testMkdir(\SplFileInfo|string $name, int $permissions = null, \Exception $exception = null, string $baseDir = null): void
    {
        $helper = new FileSystemHelperImpl($baseDir);

        if ($exception) {
            $this->expectExceptionObject($exception);
        }

        if ($permissions === null) {
            $helper->mkdir($name);
        } else {
            $helper->mkdir($name, $permissions);
        }

        if (!$exception) {
            $this->assertDirectoryExists($this->convertPathToString($name));
            $this->assertDirectoryHasPermissions($permissions ?: 0777, $name);
        }
    }

    /**
     * @return array<string, mixed[]>
     */
    public function provideMkdir(): array
    {
        return [
            'make dir' => [
                $this->getPathToTestDir() . '/dir_1',
                0775,
            ],
            'make dir with default permissions' => [
                $this->getPathToTestDir() . '/dir_2',
            ],
            'make nested dir' => [
                $this->getPathToTestDir() . '/one_1/two_1/three_1',
                0775,
            ],
            'make nested dir with default permissions' => [
                $this->getPathToTestDir() . '/one_2/two_2/three_2',
            ],
            'dir already exists' => [
                $this->getPathToTestDir(),
                null,
                FileSystemException::create('already exists'),
            ],
            'make dir outside base dir' => [
                $this->getPathToTestDir() . '/outside',
                null,
                FileSystemException::create('All paths must be within base directory'),
                $this->getPathToTestDir(),
            ],
            'make dir outside base dir by relative path' => [
                $this->getPathToTestDir() . '/../../outside',
                null,
                FileSystemException::create('All paths must be within base directory'),
                $this->getPathToTestDir(),
            ],
            'make dir with utf symbols' => [
                $this->getPathToTestDir() . '/тест',
            ],
        ];
    }

    /**
     * @test
     *
     * @dataProvider provideMkdirIfNotExist
     */
    public function testMkdirIfNotExist(\SplFileInfo|string $name, int $permissions = null, \Exception $exception = null, string $baseDir = null): void
    {
        $helper = new FileSystemHelperImpl($baseDir);

        if ($exception) {
            $this->expectExceptionObject($exception);
        }

        if ($permissions === null) {
            $helper->mkdirIfNotExist($name);
        } else {
            $helper->mkdirIfNotExist($name, $permissions);
        }

        if (!$exception) {
            $this->assertDirectoryExists($this->convertPathToString($name));
            $this->assertDirectoryHasPermissions($permissions ?: 0777, $name);
        }
    }

    /**
     * @return array<string, mixed[]>
     */
    public function provideMkdirIfNotExist(): array
    {
        $tests = $this->provideMkdir();
        $tests['dir already exists'] = [
            $this->getPathToTestDir(),
            0755,
        ];

        return $tests;
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

        $helper = new FileSystemHelperImpl();
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

        $helper = new FileSystemHelperImpl();

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

        $helper = new FileSystemHelperImpl();

        $this->expectException(FileSystemException::class);
        $this->expectExceptionMessage("Can't empty directory '{$file}' because it's a file");
        $helper->emptyDir($file);
    }

    /**
     * @test
     */
    public function testGetTmpDir(): void
    {
        $helper = new FileSystemHelperImpl();
        $tmpDir = $helper->getTmpDir();

        $this->assertInstanceOf(\SplFileInfo::class, $tmpDir);
    }

    /**
     * @test
     */
    public function testIterateNonDirectoryException(): void
    {
        $file = $this->getPathToTestFile();

        $helper = new FileSystemHelperImpl();

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

        $helper = new FileSystemHelperImpl();
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

        $helper = new FileSystemHelperImpl();
        $fileInfo = $helper->makeFileInfo($file);

        $this->assertInstanceOf(\SplFileInfo::class, $fileInfo);
        $this->assertSame($file->getPathname(), $fileInfo->getPathname());
    }

    /**
     * @test
     */
    public function testMakeFileInfoEmptyString(): void
    {
        $helper = new FileSystemHelperImpl();

        $this->expectException(FileSystemException::class);
        $helper->makeFileInfo('    ');
    }

    /**
     * @test
     */
    public function testMakeFileInfoWrongInput(): void
    {
        $helper = new FileSystemHelperImpl();

        $this->expectException(FileSystemException::class);
        $helper->makeFileInfo(true);
    }
}
