<?php

require_once __DIR__.'/job.php';

class Producer
{
    private $socket;

    function push(Job $job)
    {
        fwrite($this->getSocket(), serialize($job)."\n");
    }

    private function getSocket()
    {
        if (null === $this->socket) {
            $this->socket = @stream_socket_client('tcp://127.0.0.1:8001', $errno, $errstr);

            if (false === $this->socket) {
                throw new \UnexpectedValueException(sprintf("Couldn't connect to queue: %s", $errstr));
            }
        }

        return $this->socket;
    }
}
