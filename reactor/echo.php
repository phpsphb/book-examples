<?php

namespace SocketProgrammingHandbook;

require_once __DIR__.'/reactor.php';

$port = @$_SERVER['PORT'] ?: 1337;
$server = @stream_socket_server("tcp://127.0.0.1:$port", $errno, $errstr);

if (false === $server) {
    // Write error message to STDERR and exit, just like UNIX programs usually do
    fprintf(STDERR, "Error connecting to socket: %d %s\n", $errno, $errstr);
    exit(1);
}

// Make sure calling stream_socket_accept can't block when called on this server stream,
// in case someone wants to add another server stream to the reactor
// (maybe listening on another port, implementing another protocol ;))
stream_set_blocking($server, 0);

printf("Listening on port %d\n", $port);

$loop = new StreamSelectLoop();

// This code runs when the socket has a connection ready for accepting
$loop->addReadStream($server, function ($server) use ($loop) {
    $conn = @stream_socket_accept($server, -1, $peer);
    $buf = '';

    // This runs when a read can be made without blocking:
    $loop->addReadStream($conn, function ($conn) use ($loop, &$buf) {
        $buf = @fread($conn, 4096) ?: '';

        if (@feof($conn)) {
            $loop->removeStream($conn);
            fclose($conn);
        }
    });

    // This runs when a write can be made without blocking:
    $loop->addWriteStream($conn, function ($conn) use ($loop, &$buf) {
        if (strlen($buf) > 0) {
            @fwrite($conn, $buf);
            $buf = '';
        }

        if (@feof($conn)) {
            $loop->removeStream($conn);
            fclose($conn);
        }
    });
});

$loop->run();
