<?php
function arrayUcFirst(&$arr){
    $arr=ucfirst($arr);
}

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return '' === $needle || false !== strpos($haystack, $needle);
    }
}

function assert_result($result):bool{
    return $result->getStatus()==200;
}
?>