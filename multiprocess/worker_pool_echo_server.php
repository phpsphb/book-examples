<?php

$server = stream_socket_server('tcp://127.0.0.1:'.(getenv('PORT') ?: 1234), $errno, $errstr);

if (false === $server) {
    fwrite(STDERR, "Failed creating socket server: $errstr\n");
    exit(1);
}

$maxProcs = getenv('MAX_PROCS') ?: 2;
$workers = [];

function create_worker($server) {
    $pid = pcntl_fork();

    if ($pid > 0) {
        return $pid;
    } elseif ($pid < 0) {
        return -1;
    } else {
        for (;;) {
            $conn = @stream_socket_accept($server, -1, $peer);

            if (!is_resource($conn)) {
                continue;
            }

            $childPid = posix_getpid();
            fwrite($conn, "You are connected to process $childPid\n");

            while ($buf = fread($conn, 4096)) {
                fwrite($conn, $buf);
            }
            fclose($conn);
        }

        exit(0);
    }
}

for (;;) {
    // Check if workers are alive
    foreach ($workers as $i => $worker) {
        $result = pcntl_waitpid($worker, $status, WNOHANG);

        if ($result > 0 && (pcntl_wifexited($status) || pcntl_wifsignaled($status))) {
            echo "Worker $worker has died :(\n";
            unset($workers[$i]);
        }
    }

    // Respawn lost workers
    while (($maxProcs - count($workers)) > 0) {
        echo "Creating worker process\n";
        $workers[] = create_worker($server);
    }

    usleep(500);
}
