<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['usuario_nivel'] === 'cliente') {
    header("Location: minha_divida.php");
    exit();
}
require_once "funcoes.php";
?>
