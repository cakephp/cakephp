<?php
declare(strict_types=1);

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
while (($line = fgets($connection)) !== false && $line !== "\r\n") {
}
if ($line !== "\r\n") {
    exit(3);
}
$body = '<html><body>Local HTTP response</body></html>';
fwrite($connection, "HTTP/1.1 200 OK\r\nDate: Sat, 01 Jan 2000 00:00:00 GMT\r\n"
    . "Content-Type: text/html\r\nContent-Length: " . strlen($body) . "\r\nConnection: close\r\n\r\n" . $body);
fclose($connection);
fclose($server);
