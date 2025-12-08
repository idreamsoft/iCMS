<?php

// namespace iPHP;
/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.2.0
 */
class sException extends \Exception
{
    protected $state;

    public function __construct($message = '', $code = 0,$previous = null)
    {
        $this->state = $code;
        is_array($message) && $message = json_encode($message);
        parent::__construct($message, (int)$code, $previous);
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function getState()
    {
        return $this->state;
    }
}
class FalseEx extends sException
{
}
class NullEx extends sException
{
}
