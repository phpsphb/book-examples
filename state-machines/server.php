<?php

namespace SocketProgrammingHandbook;

require __DIR__.'/option.php';

use function SocketProgrammingHandbook\Option\Some;
use function SocketProgrammingHandbook\Option\None;

class Connection
{
    const STATE_ACCEPTED = 1;
    const STATE_READABLE = 2;
    const STATE_WRITEABLE = 3;
    const STATE_TERMINATED_BY_CLIENT = 4;

    public $state;

    private $stream;

    function __construct($stream)
    {
        $this->stream = $stream;
    }

    function stream()
    {
        return $this->stream;
    }

    function id()
    {
        return (int) $this->stream;
    }

    function handleRead($data)
    {
        echo "Data\n";
        return Some($this);
    }

    function handleWrite()
    {
        return Some($this);
    }
}

class Server
{
    const STATE_LISTENING = 1;
    const STATE_TERMINATED = 2;

    public $state;

    private $connections = [];

    function handleNewConnection($client)
    {
        $conn = new Connection($client);
        $this->connections[$conn->id()] = $conn;

        return Some($this);
    }

    function run()
    {
        $server = @stream_socket_server('tcp://0.0.0.0:9000', $errno, $errstr);
        stream_set_blocking($server, 0);

        if (false === $server) {
            fwrite(STDERR, "Error connecting to socket: $errno: $errstr\n");
            exit(1);
        }

        $this->state = self::STATE_LISTENING;

        fwrite(STDERR, "Server listening on 0.0.0.0:9000\n");

        while ($this->state !== self::STATE_TERMINATED) {
            $streams = array_map(function ($conn) {
                return $conn->stream();
            }, $this->connections);

            $readable = $streams;
            array_unshift($readable, $server);
            $writable = $streams;
            $except = null;

            if (stream_select($readable, $writable, $except, 0, 500) > 0) {
                // Some streams have data to read
                foreach ((array) $readable as $stream) {
                    // When the server is readable this means that a client
                    // connection is available. Let's accept the connection and store it
                    if ($stream === $server) {
                        $client = @stream_socket_accept($stream, 0, $clientAddress);

                        if (is_resource($client)) {
                            printf("Client %s connected\n", $clientAddress);
                            stream_set_blocking($client, 0);
                            $this->handleNewConnection($client);
                        }
                    } else {
                        $id = (int) $stream;
                        $conn = $this->connections[$id];
                        $result = $conn->handleRead(fread($stream, 4096));

                        if (!$result->isDefined()) {
                            fclose($conn->stream());
                            unset($this->connections[$conn->id()]);
                        }
                    }
                }

                // Some streams are waiting for data
                foreach ((array) $writable as $stream) {
                    $id = (int) $stream;
                    if (isset($this->connections[$id])) {
                        $conn = $this->connections[$id];
                        $result = $conn->handleWrite();

                        if (!$result->isDefined()) {
                            fclose($conn->stream());
                            unset($this->connections[$conn->id()]);
                        }
                    }
                }
            }

            // House keeping
            // Purge connections which were closed by the peer
            foreach ($this->connections as $id => $conn) {
                if (feof($conn->stream())) {
                    printf("Client %s closed the connection\n", stream_socket_get_name($conn->stream(), true));
                    $conn->state = Connection::STATE_TERMINATED_BY_CLIENT;
                    unset($this->connections[$id]);
                    fclose($conn->stream());
                }
            }
        }
    }
}

if (realpath($_SERVER['argv'][0]) === __FILE__) {
    $server = new Server;
    $server->run();
}
