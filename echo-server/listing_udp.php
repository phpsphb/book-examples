<?php

$port = @$_SERVER['PORT'] ?: 1337;
$server = stream_socket_server("udp://127.0.0.1:$port", $errno, $errstr, STREAM_SERVER_BIND);

if (false === $server) {
    # Write error message to STDERR and exit, just like UNIX programs usually do
    fprintf(STDERR, "Error connecting to socket: %d %s\n", $errno, $errstr);
    exit(1);
}

printf("Listening on port %d\n", $port);

for (;;) {
    while ($buf = stream_socket_recvfrom($server, 4096)) {
        echo $buf;
    }
}
