<?php
// counseller/index.php - Seamless redirect for 'counseller' spelling alias
$target = '../counselor/';
if (!empty($_SERVER['QUERY_STRING'])) {
    $target .= '?' . $_SERVER['QUERY_STRING'];
}
header("Location: $target", true, 301);
exit();

