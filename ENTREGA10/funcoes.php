<?php
// Funções auxiliares usadas em várias páginas (incluído por verifica_login.php)

// Retorna true se o usuário logado é administrador
function eh_admin() {
    return isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin';
}

// Guarda uma mensagem para ser exibida na próxima página (usada após redirecionamentos)
function definir_flash($texto, $tipo = "mensagem") {
    $_SESSION['flash'] = ['texto' => $texto, 'tipo' => ($tipo === "erro" ? "erro" : "mensagem")];
}

// Exibe e apaga a mensagem guardada
function mostrar_flash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<p class="' . $f['tipo'] . '">' . htmlspecialchars($f['texto']) . '</p>';
    }
}

// Confere se uma string é uma data válida no formato AAAA-MM-DD
function data_valida($data) {
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d && $d->format('Y-m-d') === $data;
}
?>
