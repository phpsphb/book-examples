<?php

$server = stream_socket_server('tcp://127.0.0.1:'.(getenv('PORT') ?: 1234), $errno, $errstr);

if (false === $server) {
    fwrite(STDERR, "Failed creating socket server: $errstr\n");
    exit(1);
}

echo "Waiting…\n";

$children = [];
$pipe = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);


for (;;) {
    pcntl_signal_dispatch();

    $read = [$server, $pipe[0]];
    $write = null;
    $except = null;

    stream_select($read, $write, $except, 0, 1000);

    foreach ($read as $stream) {
        if ($stream === $server && count($children) < 2) {
            $conn = @stream_socket_accept($server, -1, $peer);

            if (!is_resource($conn)) {
                continue;
            }

            echo "Starting a new child process for $peer\n";
            $pid = pcntl_fork();

            if ($pid > 0) {
                $children[] = $pid;
            } elseif ($pid === 0) {
                fclose($pipe[0]);

                // Child process, implement our echo server
                $childPid = posix_getpid();
                fwrite($conn, "You are connected to process $childPid\n");

                while ($buf = fread($conn, 4096)) {
                    fwrite($conn, $buf);
                }
                fclose($conn);

                // Tell the parent that we are done
                fwrite($pipe[1], "$childPid\n");

                // We are done, quit.
                exit(0);
            }
        } elseif ($stream === $pipe[0]) {
            $result = fgets($pipe[0]);

            if ($result === '0') {
                foreach ($children as $i => $pid) {
                    $wait = pcntl_waitpid($pid, $status, WNOHANG);

                    if ($wait > 0 && pcntl_wifexited($status)) {
                        unset($chilren[$i]);
                    }
                }
            } else {
                foreach ($children as $i => $pid) {
                    if ((string) $pid == (string) $result) {
                        unset($children[$i]);
                    }
                }
            }
        }
    }

    echo count($children)." connected\n";
}
