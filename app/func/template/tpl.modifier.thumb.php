<?php

function tpl_modifier_thumb(
    $sfp,
    $w = '',
    $h = '',
    $scale = true
) {
    return FilesPic::thumb($sfp, $w, $h, $scale);
}
