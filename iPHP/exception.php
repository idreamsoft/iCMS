<?php

/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 */
class sException extends Exception
{
    protected $state;
    public function __construct($message = "", $code = 0, Exception $previous = NULL)
    {
        $this->state = $code;
        is_array($message) && $message = json_encode($message);
        parent::__construct($message, (int)$code, $previous);
    }
    public function getState()
    {
        return (string)$this->state;
    }
}
class FalseEx extends sException
{
}
class NullEx extends sException
{
}
