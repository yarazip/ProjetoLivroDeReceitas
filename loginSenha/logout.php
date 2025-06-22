<?php
session_start();
session_unset();
session_destroy();

// Impede que o navegador armazene a página anterior
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redireciona para a página de login
header("Location: login.php");
exit();
