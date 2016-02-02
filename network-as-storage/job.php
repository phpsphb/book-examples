<?php

abstract class Job
{
    protected $data;

    function __construct($data)
    {
        $this->data = $data;
    }

    function getData()
    {
        return $this->data;
    }

    abstract function run();
}

class HelloWorldJob extends Job
{
    function run()
    {
        sleep(2);
        printf("Hello World %s\n", $this->data);
    }
}
