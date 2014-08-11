<?php

$server = @stream_socket_server('tcp://0.0.0.0:1337', $errno, $errstr);

if (false === $server) {
    # Write error message to STDERR and exit, just like UNIX programs usually do
    fprintf(STDERR, "Error connecting to socket: %d %s\n", $errno, $errstr);
    exit(1);
}

for (;;) {
    $conn = @stream_socket_accept($server, -1, $peer);

    if (is_resource($conn)) {
        while ($buf = fread($conn, 4096)) {
            fwrite($conn, $buf);
        }

        fclose($conn);
    }
}
