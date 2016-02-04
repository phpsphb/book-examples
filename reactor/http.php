<?php

namespace SocketProgrammingHandbook;

require_once __DIR__.'/reactor.php';

$main = function () {
    $port = @$_SERVER['PORT'] ?: 1337;
    $server = @stream_socket_server("tcp://127.0.0.1:$port", $errno, $errstr);

    if (false === $server) {
        // Write error message to STDERR and exit, just like UNIX programs usually do
        fprintf(STDERR, "Error connecting to socket: %d %s\n", $errno, $errstr);
        exit(1);
    }

    stream_set_blocking($server, 0);

    printf("Listening on port %d\n", $port);

    $loop = new StreamSelectLoop();

    $loop->addReadStream($server, function ($server) use ($loop) {
        $conn = @stream_socket_accept($server);

        if (!is_resource($conn)) {
            return;
        }

        $loop->addWriteStream($conn, function ($conn) use ($loop) {
            $content = "<h1>Hello World</h1>";
            $length = strlen($content);

            fwrite($conn, "HTTP/1.1 200 OK\r\n");
            fwrite($conn, "Content-Type: text/html\r\n");
            fwrite($conn, "Content-Length: $length\r\n");
            fwrite($conn, "\r\n");
            fwrite($conn, $content);
            fclose($conn);
            $loop->removeStream($conn);
        });
    });

    $loop->run();
};

if (__FILE__ === realpath($argv[0])) {
    $main();
}
