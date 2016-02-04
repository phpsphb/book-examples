<?php

namespace SocketProgrammingHandbook;

class Event {
    const READABLE = 'readable';
    const WRITEABLE = 'writeable';
    const TERMINATED = 'terminated';

    private $name;
    private $reactor;

    function __construct($reactor, $name) {
        $this->reactor = $reactor;
        $this->name = $name;
    }

    function name() {
        return $this->name;
    }

    function reactor() {
        return $this->reactor;
    }
}

abstract class Protocol {
    const STATE_INITIAL = 1;
    const STATE_READABLE = 2;
    const STATE_WRITEABLE = 3;
    const STATE_TERMINATED = 4;

    private $state;
    private $stream;
    private $children = [];

    function __construct($stream) {
        $this->stream = $stream;
    }

    function state() {
        return $this->state;
    }

    function addChild(Protocol $protocol) {
        $this->children[$protocol->id()] = $protocol;
    }

    function id() {
        return (int) $this->stream();
    }

    function stream() {
        return $this->stream;
    }

    function handleEvent(Event $event) {
        switch ($event->name()) {
            case Event::READABLE:
                $this->state = static::STATE_READABLE;
                break;
            case Event::WRITEABLE:
                $this->state = static::STATE_WRITEABLE;
                break;
            case Event::TERMINATED:
                $this->state = static::STATE_TERMINATED;
                break;
        }

        switch ($this->state) {
            case static::STATE_READABLE;
                $this->handleData($event);
                break;
            case static::STATE_WRITEABLE;
                $this->handleWrite($event);
                break;
            case static::STATE_TERMINATED;
                $this->handleTerminate($event);
                break;
        }
    }

    function handleTerminate(Event $event) {
        if (@feof($this->stream())) {
            fclose($this->stream());

            foreach ($this->children as $child) {
                $child->handleTerminate($event);
            }

            $event->reactor()->removeProtocol($this);
        }
    }

    function handleData(Event $event) {}
    function handleWrite(Event $event) {}
}

class EchoServerConnection extends Protocol {
    private $buf = '';

    function handleData(Event $event) {
        $this->buf = fread($this->stream(), 4096) ?: '';
    }

    function handleWrite(Event $event) {
        if (strlen($this->buf) > 0) {
            fwrite($this->stream(), $this->buf);
            $this->buf = '';
        }
    }
}

class EchoServerProtocol extends Protocol {
    function handleData(Event $event)
    {
        $conn = stream_socket_accept($this->stream());

        if (!is_resource($conn)) {
            return;
        }

        $conn = new EchoServerConnection($conn);
        $this->addChild($conn);
        $event->reactor()->addProtocol($conn);
    }

    function handleWrite(Event $event)
    {}
}

class StateMachineReactor
{
    const STATE_LISTENING = 1;
    const STATE_TERMINATED = 2;

    private $state;
    private $streams = [];

    function addProtocol(Protocol $stream) {
        empty($this->streams[$stream->id()]) and $this->streams[$stream->id()] = $stream;
    }

    function removeProtocol(Protocol $stream) {
        unset($this->streams[$stream->id()]);
    }

    function terminate() {
        $this->state = static::STATE_TERMINATED;
    }

    function loop()
    {
        $this->state = self::STATE_LISTENING;

        while ($this->state !== self::STATE_TERMINATED) {
            $streams = array_map(function ($stream) {
                return $stream->stream();
            }, $this->streams);

            $read = $streams;
            $write = $streams;
            $except = null;

            if ($read || $write) {
                stream_select($read, $write, $except, 0, 500);

                // Some streams have data to read
                foreach ((array) $read as $stream) {
                    $id = (int) $stream;
                    $sm = $this->streams[$id];
                    $sm->handleEvent(new Event($this, Event::READABLE));
                }

                // Some streams are waiting for data
                foreach ((array) $write as $stream) {
                    $id = (int) $stream;
                    $sm = $this->streams[$id];
                    $sm->handleEvent(new Event($this, Event::WRITEABLE));
                }

                foreach ($streams as $stream) {
                    if (@feof($stream)) {
                        $id = (int) $stream;
                        $sm = $this->streams[$id];
                        $sm->handleEvent(new Event($this, Event::TERMINATED));
                    }
                }
            } else {
                usleep(500);
            }
        }
    }
}

if (realpath($_SERVER['argv'][0]) === __FILE__) {
    $server = stream_socket_server('tcp://127.0.0.1:9000');
    stream_set_blocking($server, 0);

    $reactor = new StateMachineReactor();
    $reactor->addProtocol(new EchoServerProtocol($server));
    $reactor->loop();
}
