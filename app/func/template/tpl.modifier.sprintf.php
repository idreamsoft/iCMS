<?php
//<!--{'%s...%s..%s'|sprintf:'asd':'sdf':'aa'}-->
function tpl_modifier_sprintf($value,$format){
    $args = func_get_args();
    
    //<!--{$asd|sprintf:'%s':'sdf':'aa'}-->
    if(strpos($value,'%')===false){
        $args[0] = $format;
        $args[1] = $value;
    }
    return call_user_func_array('sprintf',$args);
}
