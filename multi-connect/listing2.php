<?php

$server = @stream_socket_server('tcp://0.0.0.0:1337', $errno, $errstr);
stream_set_blocking($server, 0);

if (false === $server) {
    fwrite(STDERR, "Error connecting to socket: $errno: $errstr\n");
    exit(1);
}

$connections = [];
$buffers = [];

for (;;) {
    $readable = array_merge([$server], $connections);
    $writable = $connections;
    $except = null;

    if (stream_select($readable, $writable, $except, 0, 500) > 0) {
        // Some streams have data to read
        foreach ((array) $readable as $stream) {
            // When the server is readable this means that a client
            // connection is available. Let's accept the connection and store it
            if ($stream === $server) {
                $client = @stream_socket_accept($stream, 0, $clientAddress);
                $key = (int) $client;
                if (is_resource($client)) {
                    printf("Client %s connected\n", $clientAddress);
                    stream_set_blocking($client, 0);
                    $connections[$key] = $client;
                }
            } else {
                // One of the clients sent data, read it in a client specific buffer
                $key = (int) $stream;

                if (!isset($buffer[$key])) {
                    $buffers[$key] = '';
                }

                $buffers[$key] .= fread($stream, 4096);
            }
        }

        // Some streams are waiting for data
        foreach ((array) $writable as $stream) {
            $key = (int) $stream;

            // Try to write 4096 bytes, look how many bytes were really written,
            // and subtract the written bytes from this client's buffer
            if (isset($buffers[$key]) && strlen($buffers[$key]) > 0)) {
                $bytesWritten = fwrite($stream, $buffers[$key], 4096);
                $buffers[$key] = substr($buffers[$key], $bytesWritten);
            }
        }

        // Out of band data, usually not handled.
        foreach ((array) $except as $stream) {
            // Can't happen, we haven't set $except to anything
        }
    }

    // House keeping
    // Purge connections which were closed by the peer
    foreach ($connections as $key => $conn) {
        if (feof($conn)) {
            printf("Client %s closed the connection\n", stream_socket_get_name($conn, true));
            unset($connections[$key]);
            fclose($conn);
        }
    }
}
