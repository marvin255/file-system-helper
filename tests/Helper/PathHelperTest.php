<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper\Tests\Helper;

use Marvin255\FileSystemHelper\Helper\PathHelper;
use Marvin255\FileSystemHelper\Tests\BaseCase;

/**
 * @internal
 */
final class PathHelperTest extends BaseCase
{
    /**
     * @dataProvider provideIsPathParentForPath
     */
    public function testIsPathParentForPath(string $parentPath, string $path, bool $awaits): void
    {
        $testResult = PathHelper::isPathParentForPath($parentPath, $path);

        $this->assertSame($awaits, $testResult);
    }

    public static function provideIsPathParentForPath(): array
    {
        return [
            'is parent path' => [
                '/parent/path',
                '/parent/path/test',
                true,
            ],
            'same paths' => [
                '/parent/path',
                '/parent/path',
                true,
            ],
            'is not parent path' => [
                '/parent/path',
                '/not/parent/path',
                false,
            ],
            'tricky path' => [
                '/parent/path',
                '/parent/pathtricky',
                false,
            ],
            'path with regexp symbols' => [
                '/parent/path.*',
                '/parent/pathtricky',
                false,
            ],
        ];
    }

    public function testRealpath(): void
    {
        $path = __DIR__;

        $testResult = PathHelper::realpath($path);

        $this->assertSame($path, $testResult);
    }

    public function testRealpathUnexisted(): void
    {
        $path = '/un/existed/path';

        $testResult = PathHelper::realpath($path);

        $this->assertNull($testResult);
    }

    /**
     * @dataProvider provideUnifyPath
     */
    public function testUnifyPath(string $path, string $awaits): void
    {
        $testResult = PathHelper::unifyPath($path);

        $this->assertSame($awaits, $testResult);
    }

    public static function provideUnifyPath(): array
    {
        $sep = \DIRECTORY_SEPARATOR;
        $nonSep = $sep === '\\' ? '/' : '\\';

        return [
            'trim spaces in the beggining and in the end of the path' => [
                "    {$sep}test{$sep}test  ",
                "{$sep}test{$sep}test",
            ],
            'trim the final slash' => [
                "{$sep}test{$sep}",
                "{$sep}test",
            ],
            'convert slashes to system slashes' => [
                "{$nonSep}test{$sep}test",
                "{$sep}test{$sep}test",
            ],
            'utf path' => [
                "{$sep}тест{$sep}тест",
                "{$sep}тест{$sep}тест",
            ],
            'path with two dots' => [
                "{$sep}test1{$sep}test2{$sep}test3{$sep}test4{$sep}..{$sep}..{$sep}..",
                "{$sep}test1",
            ],
            'path with one dot' => [
                "{$sep}test1{$sep}.{$sep}test2",
                "{$sep}test1{$sep}test2",
            ],
            'path with a dot in the folder name' => [
                "{$sep}test.1{$sep}test.2",
                "{$sep}test.1{$sep}test.2",
            ],
            'path with a space in the folder name' => [
                "{$sep}test 1{$sep}test 2",
                "{$sep}test 1{$sep}test 2",
            ],
            'path with duplicating separators' => [
                "{$sep}test1{$sep}{$sep}{$sep}test2",
                "{$sep}test1{$sep}test2",
            ],
            'path with file extension' => [
                "{$sep}test1{$sep}test2.txt",
                "{$sep}test1{$sep}test2.txt",
            ],
            "don't add leading slash if there was no leading slash" => [
                "test{$sep}test",
                "test{$sep}test",
            ],
            'empty string' => [
                '         ',
                '',
            ],
        ];
    }

    public function testJoinPaths(): void
    {
        $part1 = 'test1';
        $part2 = 'test2';

        $res = PathHelper::joinPaths($part1, $part2);

        $this->assertSame($part1 . \DIRECTORY_SEPARATOR . $part2, $res);
    }
}
