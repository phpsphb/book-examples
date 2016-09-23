<?php

pcntl_signal(SIGINT, function () {
    echo "Received SIGINT. Cleaning up and terminating\n";
    exit;
});

echo "Waiting for signals…\n";

for (;;) {
    pcntl_signal_dispatch();
    usleep(100);
}
