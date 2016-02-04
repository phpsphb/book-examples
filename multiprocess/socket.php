<?php

$server = stream_socket_server('tcp://127.0.0.1:'.(getenv('PORT') ?: 1234), $errno, $errstr);

if (false === $server) {
    fwrite(STDERR, "Failed creating socket server: $errstr\n");
    exit(1);
}

echo "Parent!\n";

$pid = pcntl_fork();

if ($pid > 0) {
    echo "Parent!\n";
    $pid = posix_getpid();

    for (;;) {
        $conn = @stream_socket_accept($server);

        if (is_resource($conn)) {
            fwrite($conn, "Hello from Parent $pid!\n");
            fclose($conn);
            exit(0);
        }
    }
} elseif ($pid === -1) {
    fwrite(STDERR, "Forking has failed\n");
    exit(1);
} else {
    echo "Child!\n";
    $pid = posix_getpid();

    for (;;) {
        $conn = @stream_socket_accept($server);

        if (is_resource($conn)) {
            fwrite($conn, "Hello from Child $pid!\n");
            fclose($conn);
            exit(0);
        }
    }
}
