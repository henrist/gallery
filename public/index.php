<?php

// for cgi-environment, pass static files
if (preg_match('/\.(?:png|jpg|jpeg|gif|css)$/i', $_SERVER['REQUEST_URI'])) {
    return false;
}

$app = require "../app/bootstrap.php";

$app->run();
