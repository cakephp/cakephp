<?php
declare(strict_types=1);

use Cake\Http\Response;
use Cake\Http\ResponseEmitter;
use Cake\Http\Stream;

require dirname(__DIR__, 4) . '/vendor/autoload.php';

$received = false;
ob_start(function (string $chunk) use (&$received): string {
    if (str_contains($chunk, 'first ')) {
        $received = true;
    }

    return $chunk;
});
ob_start();
ob_start('ob_gzhandler');
$body = new class ('data://text/plain,first second') extends Stream {
    private bool $read = false;

    public function isSeekable(): bool
    {
        return false;
    }

    public function read(int $length): string
    {
        global $received;
        if ($this->read && !$received) {
            throw new RuntimeException('The first event was buffered.');
        }
        $this->read = true;

        return parent::read($length);
    }
};
new ResponseEmitter(6, flushOutputBuffers: true)->emit(new Response(['charset' => 'UTF-8'])->withBody($body));
ob_end_flush();
