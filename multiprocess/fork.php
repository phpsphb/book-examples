<?php

if (!function_exists('pcntl_fork')) {
    fwrite(STDERR, "pcntl_fork not available. Please make sure your PHP has pcntl\n");
    exit(1);
}

echo "This is the parent\n";

$pid = pcntl_fork();

if ($pid > 0) {
    echo "This is still the parent process. The PID of the child process is $pid\n";
    echo "Waiting on child to quit\n";
    pcntl_waitpid($pid, $status);
    echo "Child has quit. Quitting too.\n";
    exit(0);
} elseif ($pid === -1) {
    fwrite(STDERR, "Fork has failed\n");
    exit(1);
} else {
    $childPid = posix_getpid();
    echo "I'm the child process. My PID is $childPid\n";
    echo "Working…\n";
    sleep(2);
    echo "I have finished so I'm quitting\n";
    exit(0);
}
