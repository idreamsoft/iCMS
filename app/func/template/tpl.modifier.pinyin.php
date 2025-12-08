<?php

function tpl_modifier_pinyin($string, $split = '', $punct = true)
{
    return Pinyin::get($string, $split, $punct);
}
