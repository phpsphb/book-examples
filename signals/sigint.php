<?php

declare(ticks = 1);

pcntl_signal(SIGINT, function () {
    echo "Received SIGINT. Cleaning up and terminating\n";
    exit;
});

echo "Waiting for signals…\n";

for (;;) {
    usleep(100);
}
