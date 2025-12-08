<?php

function tpl_modifier_encrypt($string)
{
    return Crypt::encrypt($string);
}
