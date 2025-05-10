<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper\Tests;

use Marvin255\FileSystemHelper\FileSystemFactory;
use Marvin255\FileSystemHelper\FileSystemHelper;

/**
 * @internal
 */
final class FileSystemFactoryTest extends BaseCase
{
    /**
     * @throws \Throwable
     */
    public function testCreate(): void
    {
        $helper = FileSystemFactory::create();

        $this->assertInstanceOf(FileSystemHelper::class, $helper);
    }
}
