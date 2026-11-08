<?php
declare(strict_types=1);

namespace TestApp\Http\Client\Adapter;

/**
 * Upload destination that can perform short writes or run out of space.
 */
class UploadTargetStream
{
    public mixed $context;

    public static string $contents = '';

    public static ?int $capacity = null;

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        static::$contents = '';

        return true;
    }

    public function stream_write(string $data): int
    {
        $available = static::$capacity === null ? 2 : max(0, static::$capacity - strlen(static::$contents));
        $written = min(2, strlen($data), $available);
        static::$contents .= substr($data, 0, $written);

        return $written;
    }

    public function stream_flush(): bool
    {
        return true;
    }

    public function url_stat(string $path, int $flags): array|false
    {
        return stat(sys_get_temp_dir());
    }
}
