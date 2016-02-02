<?php

require_once __DIR__.'/job.php';

$flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;
$context = stream_context_create([
    'socket' => [
        'backlog' => 128,
    ]
]);

$server = @stream_socket_server('tcp://0.0.0.0:8001', $errno, $errstr, $flags, $context);

if (false === $server) {
    # Write error message to STDERR and exit, just like UNIX programs usually do
    fprintf(STDERR, "Error connecting to socket: %d %s\n", $errno, $errstr);
    exit(1);
}

$jobs = [];

for (;;) {
    $conn = @stream_socket_accept($server, -1, $peer);

    if (is_resource($conn)) {
        while ($data = fgets($conn)) {
            $job = unserialize($data);
            $job->run();

            if (feof($conn)) {
                break;
            }
        }
    }
}
