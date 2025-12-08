<?php
function tpl_modifier_htmlencode($value) {
    if (is_array($value)) {
        return array_map('tpl_modifier_htmlencode', $value);
    }
    return $value ? htmlspecialchars($value) : '';
}