<?php

$client1 = stream_socket_client("udp://127.0.0.1:8005", $errno, $errorMessage);
$client2 = stream_socket_client("udp://127.0.0.1:8006", $errno, $errorMessage);

if ($client1 === false) {
    throw new UnexpectedValueException("Failed to connect to server1: $errorMessage");
}

if ($client2 === false) {
    throw new UnexpectedValueException("Failed to connect to server2: $errorMessage");
}

$server = stream_socket_server("tcp://127.0.0.1:8000", $errno, $errorMessage);

stream_set_blocking($client1, 0);
stream_set_blocking($client2, 0);
stream_set_blocking($server, 0);

if ($server === false) {
    throw new UnexpectedValueException("Could not bind to socket: $errorMessage");
}

$connections = [];
$buffers = [];

for (;;) {
    $r = array_merge([$server], $connections);
    $w = [$client1, $client2];
    $e = null;

    if (stream_select($r, $w, $e, 0, 500) > 0) {
        foreach ($r as $stream) {
            if ($stream === $server) {
                $conn = stream_socket_accept($server);
                $connections[] = $conn;
            } else {
                $buf = fread($stream, 4096);
                $buffers[(int) $client1] = isset($buffers[(int) $client1]) ? $buffers[(int) $client1] : '';
                $buffers[(int) $client2] = isset($buffers[(int) $client2]) ? $buffers[(int) $client2] : '';

                $buffers[(int) $client1] .= $buf;
                $buffers[(int) $client2] .= $buf;
                echo $buf;
            }
        }

        foreach ($w as $stream) {
            if (isset($buffers[(int) $stream]) && strlen($buffers[(int) $stream]) > 0) {
                $key = (int) $stream;
                $bytesWritten = fwrite($stream, $buffers[$key], 4096);
                $buffers[$key] = substr($buffers[$key], $bytesWritten);
            }
        }
    }

    foreach ($connections as $key => $conn) {
        if (feof($conn)) {
            printf("Client %s closed the connection\n", stream_socket_get_name($conn, true));
            unset($connections[$key]);
            fclose($conn);
        }
    }
}
