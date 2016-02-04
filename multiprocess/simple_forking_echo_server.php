<?php

$server = stream_socket_server('tcp://127.0.0.1:'.(getenv('PORT') ?: 1234), $errno, $errstr);

if (false === $server) {
    fwrite(STDERR, "Failed creating socket server: $errstr\n");
    exit(1);
}

echo "Waiting…\n";

for (;;) {
    $conn = @stream_socket_accept($server, -1, $peer);

    if (!is_resource($conn)) {
        continue;
    }

    echo "Starting a new child process for $peer\n";
    $pid = pcntl_fork();

    if ($pid === 0) {
        // Child process, implement our echo server
        $childPid = posix_getpid();
        fwrite($conn, "You are connected to process $childPid\n");

        while ($buf = fread($conn, 4096)) {
            fwrite($conn, $buf);
        }
        fclose($conn);

        // We are done, quit.
        exit(0);
    }
}
