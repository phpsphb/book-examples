<?php

class BasicReactor {
    private $onConnection;

    function onConnection(callable $handler) {
        $this->onConnection = $handler;
    }

    function run() {
        $port = @$_SERVER['PORT'] ?: 1337;
        $server = @stream_socket_server("tcp://127.0.0.1:$port", $errno, $errstr);

        if (false === $server) {
            # Write error message to STDERR and exit, just like UNIX programs usually do
            fprintf(STDERR, "Error connecting to socket: %d %s\n", $errno, $errstr);
            exit(1);
        }

        printf("Listening on port %d\n", $port);

        for (;;) {
            $conn = @stream_socket_accept($server, -1, $peer);

            if (is_resource($conn)) {
                $handler = $this->onConnection;
                $handler($conn);
            }
        }
    }
}

$reactor = new BasicReactor();

$reactor->onConnection(function ($conn) {
    while ($buf = fread($conn, 4096)) {
        fwrite($conn, $buf);
    }

    fclose($conn);
});

$reactor->run();
