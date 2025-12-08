<?php

function tpl_modifier_fileurl($path) {
    return FilesClient::getUrl($path);
}
