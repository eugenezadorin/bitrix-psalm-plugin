<?php

namespace Zadorin\BitrixPsalmPlugin;

class UnknownArgument
{
    public $arg;

    public function __construct(\PhpParser\Node\Arg $arg)
    {
        $this->arg = $arg;
    }
}