<?php
declare(strict_types=1);

// A single-request HTTP server. The response remains open until the test writes
// to stdin, proving that headers and the first event arrive before completion.
$server = stream_socket_server('tcp://127.0.0.1:0');
if ($server === false) {
    exit(1);
}
fwrite(STDOUT, stream_socket_get_name($server, false) . "\n");
$connection = stream_socket_accept($server, 5);
if ($connection === false) {
    exit(2);
}
stream_set_timeout($connection, 5);
$headers = '';
while (($line = fgets($connection)) !== false && $line !== "\r\n") {
    $headers .= $line;
}
if ($line !== "\r\n") {
    exit(4);
}
if (preg_match('/Content-Length: (\d+)/i', $headers, $matches)) {
    $remaining = (int)$matches[1];
    while ($remaining > 0) {
        $data = fread($connection, $remaining);
        if ($data === false || $data === '') {
            exit(5);
        }
        $remaining -= strlen($data);
    }
}
$isGzip = str_contains($headers, '/gzip');
$isRedirect = str_contains($headers, '/redirect');
$isTimeout = str_contains($headers, '/timeout');
if ($isRedirect) {
    $address = stream_socket_get_name($server, false);
    fwrite($connection, "HTTP/1.1 302 Found\r\nLocation: http://{$address}/final\r\n\r\n");
    // The client must close this unfinished body before following the redirect.
    fread($connection, 1);
    fclose($connection);
    $connection = stream_socket_accept($server, 5);
    if ($connection === false) {
        exit(3);
    }
    stream_set_timeout($connection, 5);
    while (($line = fgets($connection)) !== false && $line !== "\r\n") {
    }
    if ($line !== "\r\n") {
        exit(6);
    }
}
fwrite($connection, "HTTP/1.1 200 OK\r\nContent-Type: text/event-stream\r\nTransfer-Encoding: chunked\r\n");
if ($isGzip) {
    fwrite($connection, "Content-Encoding: gzip\r\n");
}
fwrite($connection, "\r\n");
$deflate = $isGzip ? deflate_init(ZLIB_ENCODING_GZIP) : null;
$first = "data: first\n\n";
if (!$isTimeout) {
    $bytes = $isGzip ? deflate_add($deflate, $first, ZLIB_SYNC_FLUSH) : $first;
    fwrite($connection, dechex(strlen($bytes)) . "\r\n" . $bytes . "\r\n");
}
// The test releases the rest of the body after checking the first chunk.
// PHP does not reliably apply stream_set_timeout() to pipes. Poll stdin with
// a deadline so the child also exits if the parent never releases the body.
stream_set_blocking(STDIN, false);
$deadline = microtime(true) + 5;
$release = false;
do {
    $release = fgets(STDIN);
    if ($release !== false || feof(STDIN)) {
        break;
    }
    usleep(10000);
} while (microtime(true) < $deadline);
if ($release === false) {
    exit(7);
}
$last = "data: second\n\n";
$bytes = $isGzip ? deflate_add($deflate, $last, ZLIB_FINISH) : $last;
fwrite($connection, dechex(strlen($bytes)) . "\r\n" . $bytes . "\r\n0\r\n\r\n");
fclose($connection);
fclose($server);
