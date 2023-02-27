<?php
namespace TypeRocket\Pro\Utility\Loggers;

class NullLogger extends Logger
{
    protected function log($type, $message): bool
    {
        return true;
    }
}