<?php
namespace service;
use hydra\{IConfig};

abstract class Token
{
    protected $config;

    public function __construct(IConfig $config)
    {
        $this->config = $config;
    }

    function __destruct()
    {
        $this->config = null;
    }
}
?>