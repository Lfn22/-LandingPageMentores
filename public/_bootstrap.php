<?php

// Caminho da camada compartilhada. Por padrão fica um nível acima do
// docroot (fora do public_html na Hostinger). Trocar para
// __DIR__ . '/app' caso app/ precise ficar dentro do public_html.
if (!defined('APP_PATH')) {
    define('APP_PATH', __DIR__ . '/../app');
}

require APP_PATH . '/bootstrap.php';
