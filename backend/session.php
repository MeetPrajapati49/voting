<?php
$lifetime = 60 * 60 * 24 * 7; // 7 days in seconds
session_set_cookie_params([
    "lifetime" => $lifetime,
    "path" => "/",
    "domain" => "",
    "secure" => true,
    "httponly" => true,
    "samesite" => "Lax"
]);

session_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}