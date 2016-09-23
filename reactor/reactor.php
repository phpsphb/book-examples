<?php

namespace SocketProgrammingHandbook;

final class StreamSelectLoop {
    private $readStreams = [];
    private $readHandlers = [];
    private $writeStreams = [];
    private $writeHandlers = [];
    private $running = true;

    function addReadStream($stream, callable $handler) {
        if (empty($this->readStreams[(int) $stream])) {
            $this->readStreams[(int) $stream] = $stream;
            $this->readHandlers[(int) $stream] = $handler;
        }
    }

    function addWriteStream($stream, callable $handler) {
        if (empty($this->writeStreams[(int) $stream])) {
            $this->writeStreams[(int) $stream] = $stream;
            $this->writeHandlers[(int) $stream] = $handler;
        }
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

    /**
     * Runs the event loop, which blocks the current process. Make sure you do
     * any necessary setup before running this.
     */
    function run() {
        while ($this->running) {
            $read = $this->readStreams;
            $write = $this->writeStreams;
            $except = null;

            if ($read || $write) {
                @stream_select($read, $write, $except, 0, 100);

                foreach ($read as $stream) {
                    $this->readHandlers[(int) $stream]($stream);
                }

                foreach ($write as $stream) {
                    $this->writeHandlers[(int) $stream]($stream);
                }
            } else {
                usleep(100);
            }
        }
    }
}
