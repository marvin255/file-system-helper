<?php

declare(strict_types=1);

namespace Marvin255\FileSystemHelper\Exception;

/**
 * Exception class for errors in file helper.
 *
 * @psalm-api
 */
final class FileSystemException extends \Exception
{
    private function __construct(string $message = '')
    {
        parent::__construct($message);
    }

    /**
     * Creates FileSystemException.
     *
     * @param array<int, \SplFileInfo|string> $params
     */
    public static function create(string $message, ...$params): FileSystemException
    {
        array_unshift($params, $message);

        /** @var string */
        $compiledMessage = \call_user_func_array('sprintf', $params);

        return new FileSystemException($compiledMessage);
    }
}
