<?php
// admin/logout.php - Encerra a Sessão do Administrador
// Este script destrói a sessão do usuário e o redireciona para a página de login.

// Inclui o arquivo de conexão para garantir que a sessão seja iniciada
// (apesar de apenas destruir, é uma boa prática para consistência).
require_once '../conexao.php'; // Caminho ajustado para acessar conexao.php na pasta pai

// Desvincula todas as variáveis de sessão.
$_SESSION = array();

// Destrói a sessão.
session_destroy();

// Redireciona o usuário para a página de login.
header("Location: login.php");
exit();
?>
