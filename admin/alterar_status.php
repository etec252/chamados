<?php
// admin/alterar_status.php - Altera o Status do Chamado
// Este script processa a requisição para atualizar o status de um chamado específico,
// e agora define a mensagem de sucesso em verde para ser exibida no dashboard.

// Inclui o arquivo de conexão com o banco de dados e inicia a sessão.
require_once '../conexao.php'; // Caminho ajustado para acessar conexao.php na pasta pai

// Verifica se o usuário está logado. Se não estiver, redireciona para a página de login.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['mensagem'] = "Acesso negado. Por favor, faça login.";
    header("Location: login.php");
    exit();
}

// Verifica se a requisição é um POST e se os IDs necessários foram enviados.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e sanitiza os dados do formulário.
    $id_chamado = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $novo_status = $conexao->real_escape_string(trim($_POST['novo_status']));

    // Validação básica
    if (!$id_chamado || !in_array($novo_status, ['Pendente', 'Em andamento', 'Resolvido'])) {
        $_SESSION['mensagem'] = "Erro: Dados inválidos para a atualização do status.";
        header("Location: dashboard.php");
        exit();
    }

    // Prepara a query SQL para atualizar o status do chamado.
    $sql = "UPDATE chamados SET status = ? WHERE id = ?";

    // Prepara a declaração.
    if ($stmt = $conexao->prepare($sql)) {
        // Vincula os parâmetros.
        $stmt->bind_param("si", $novo_status, $id_chamado); // 's' para string, 'i' para inteiro

        // Executa a declaração.
        if ($stmt->execute()) {
            // Se a atualização for bem-sucedida, define uma mensagem de sucesso na sessão.
            // A mensagem agora contém "sucesso" para que o dashboard a identifique como verde.
            $_SESSION['mensagem'] = "Status do chamado '" . $id_chamado . "' atualizado para '" . $novo_status . "' com sucesso!";
        } else {
            // Se houver um erro na execução.
            $_SESSION['mensagem'] = "Erro ao atualizar status do chamado: " . $stmt->error;
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
