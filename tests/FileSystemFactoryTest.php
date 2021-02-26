<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper\Tests;

use Marvin255\FileSystemHelper\FileSystemFactory;
use Marvin255\FileSystemHelper\FileSystemHelperInterface;
use Throwable;

class FileSystemFactoryTest extends BaseCase
{
    /**
     * @throws Throwable
     */
    public function testCreate(): void
    {
        $helper = FileSystemFactory::create();

        $this->assertInstanceOf(FileSystemHelperInterface::class, $helper);
    }
}
