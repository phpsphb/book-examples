<?php

final class Reactor {
    private $readStreams = [];
    private $readHandlers = [];
    private $writeStreams = [];
    private $writeHandlers = [];

    function addReadStream($stream, callable $handler) {
        $this->readStreams[(int) $stream] = $stream;
        $this->readHandlers[(int) $stream] = $handler;
    }

    function addWriteStream($stream, callable $handler) {
        $this->writeStreams[(int) $stream] = $stream;
        $this->writeHandlers[(int) $stream] = $handler;
    }

    function removeReadStream($stream) {
        unset($this->readStreams[(int) $stream]);
    }

    function removeWriteStream($stream) {
        unset($this->writeStreams[(int) $stream]);
    }

    function removeStream($stream) {
        $this->removeReadStream($stream);
        $this->removeWriteStream($stream);
    }

    function loop() {
        for (;;) {
            $read = $this->readStreams;
            $write = $this->writeStreams;
            $except = null;

            if ($read || $write) {
                $ready = @stream_select($read, $write, $except, 0, 100);

                foreach ($read as $stream) {
                    call_user_func($this->readHandlers[(int) $stream], $stream);
                }

                foreach ($write as $stream) {
                    call_user_func($this->writeHandlers[(int) $stream], $stream);
                }
            } else {
                usleep(100);
            }
        }
    }
}

function echoServer(Reactor $reactor, $server) {
    // This code runs when the socket has a connection ready for accepting
    $reactor->addReadStream($server, function ($server) use ($reactor) {
        $conn = stream_socket_accept($server);
        $buf = '';

        // This runs when a read can be made without blocking:
        $reactor->addReadStream($conn, function ($conn) use ($reactor, &$buf) {
            $buf = fread($conn, 4096) ?: '';

            if (@feof($conn)) {
                $reactor->removeStream($conn);
                fclose($conn);
            }
        });

        // This runs when a write can be made without blocking:
        $reactor->addWriteStream($conn, function ($conn) use ($reactor, &$buf) {
            if (strlen($buf) > 0) {
                // Remove the connection if writing failed (connection was closed by client)
                fwrite($conn, $buf);
                $buf = '';
            }

            if (@feof($conn)) {
                $reactor->removeStream($conn);
                fclose($conn);
            }
        });
    });
}

$main = function () {
    $port = @$_SERVER['PORT'] ?: 1337;
    $server = @stream_socket_server("tcp://127.0.0.1:$port", $errno, $errstr);
    stream_set_blocking($server, 0);

    if (false === $server) {
        // Write error message to STDERR and exit, just like UNIX programs usually do
        fprintf(STDERR, "Error connecting to socket: %d %s\n", $errno, $errstr);
        exit(1);
    }

    printf("Listening on port %d\n", $port);

    $reactor = new Reactor();
    echoServer($reactor, $server);
    $reactor->loop();
};

if (__FILE__ === realpath($argv[0])) {
    $main();
}
