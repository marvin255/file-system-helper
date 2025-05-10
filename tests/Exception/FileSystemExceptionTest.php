<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper\Tests\Exception;

use Marvin255\FileSystemHelper\Exception\FileSystemException;
use Marvin255\FileSystemHelper\Tests\BaseCase;

/**
 * @internal
 */
final class FileSystemExceptionTest extends BaseCase
{
    public function testCreate(): void
    {
        $message = 'test message';

        $exception = FileSystemException::create($message);

        $this->assertInstanceOf(FileSystemException::class, $exception);
        $this->assertSame($message, $exception->getMessage());
    }

    public function testCreateWithParams(): void
    {
        $message = 'test %s message';
        $param = 'test param';

        $exception = FileSystemException::create($message, $param);

        $this->assertInstanceOf(FileSystemException::class, $exception);
        $this->assertSame('test test param message', $exception->getMessage());
    }
}
