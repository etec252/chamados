<?php
// admin/excluir_chamado.php - Exclui um Chamado
// Este script processa a requisição para excluir um chamado específico.

// Inclui o arquivo de conexão com o banco de dados e inicia a sessão.
require_once '../conexao.php'; // Caminho ajustado para acessar conexao.php na pasta pai

// Verifica se o usuário está logado. Se não estiver, redireciona para a página de login.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['mensagem'] = "Acesso negado. Por favor, faça login.";
    header("Location: login.php");
    exit();
}

// Verifica se a requisição é um POST e se o ID do chamado foi enviado.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e sanitiza o ID do chamado.
    $id_chamado = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    // Validação básica.
    if (!$id_chamado) {
        $_SESSION['mensagem'] = "Erro: ID do chamado inválido para exclusão.";
        header("Location: dashboard.php");
        exit();
    }

    // Prepara a query SQL para excluir o chamado.
    $sql = "DELETE FROM chamados WHERE id = ?";

    // Prepara a declaração.
    if ($stmt = $conexao->prepare($sql)) {
        // Vincula o parâmetro.
        $stmt->bind_param("i", $id_chamado); // 'i' para inteiro

        // Executa a declaração.
        if ($stmt->execute()) {
            // Se a exclusão for bem-sucedida, define uma mensagem de sucesso na sessão.
            $_SESSION['mensagem'] = "Chamado ID " . $id_chamado . " excluído com sucesso!";
        } else {
            // Se houver um erro na execução.
            $_SESSION['mensagem'] = "Erro ao excluir chamado: " . $stmt->error;
        }

        // Fecha a declaração.
        $stmt->close();
    } else {
        // Se houver um erro na preparação da declaração.
        $_SESSION['mensagem'] = "Erro na preparação da consulta: " . $conexao->error;
    }

    // Fecha a conexão com o banco de dados.
    $conexao->close();

    // Redireciona de volta para o painel.
    header("Location: dashboard.php");
    exit();

} else {
    // Se a requisição não for um POST, redireciona para o painel ou exibe um erro.
    $_SESSION['mensagem'] = "Erro: Acesso inválido ao script.";
    header("Location: dashboard.php");
    exit();
}
?>
