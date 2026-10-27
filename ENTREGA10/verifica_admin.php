<?php
// Use em páginas exclusivas do administrador (já inclui verifica_login.php)
require_once "verifica_login.php";

if (!eh_admin()) {
    definir_flash("Acesso negado: apenas administradores podem acessar esta área.", "erro");
    header("Location: dashboard.php");
    exit();
}
?>
