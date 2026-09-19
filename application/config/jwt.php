<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['jwt_key'] = function_exists('env') ? env('JWT_SECRET', 'SIMPELKES_RS_SECRET_TOKEN_KEY_2026_!#@#99') : 'SIMPELKES_RS_SECRET_TOKEN_KEY_2026_!#@#99';
$config['jwt_algorithm'] = 'HS256';
$config['jwt_expire_seconds'] = function_exists('env') ? (int)env('JWT_EXPIRE_SECONDS', 604800) : 604800; // 7 hari
