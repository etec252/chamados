<?php
// salvar_chamado.php - Processa o Envio de Chamados
// Este script recebe os dados do formulário de 'index.php',
// valida-os e insere um novo chamado no banco de dados.
// ATUALIZADO: Validação rigorosa para 'numero_computador' e remoção de 'nome_solicitante'.
// Detalhe: 'prof' é aceito SOMENTE para Laboratório, não para Carrinho.
// ATUALIZADO: Adicionada validação para o range 1-15 do campo 'numero_computador' quando 'Sala Maker' é selecionada.
// ATUALIZADO: O campo 'numero_computador' agora é preenchido com "prof" automaticamente se não for um local que exija número.

// Inclui o arquivo de c\aonexão com o banco de dados.
require_once 'conexao.php';

// Verifica se a requisição é um POST e se os campos necessários foram enviados.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e sanitiza os dados do formulário.
    // REMOVIDO: $nome_solicitante não é mais coletado, pois foi removido do formulário.
    $nome_professor = $conexao->real_escape_string(trim($_POST['nome_professor']));
    $local_tipo = $conexao->real_escape_string(trim($_POST['local_tipo']));
    
    // local_detalhe é opcional se local_tipo for 'Carrinho'
    $local_detalhe = isset($_POST['local_detalhe']) ? $conexao->real_escape_string(trim($_POST['local_detalhe'])) : null;

    // Número do Computador: Coletar como string para validação flexível
    $numero_computador_input = isset($_POST['numero_computador']) ? trim($_POST['numero_computador']) : null;
    $numero_computador_para_bd = null; // Variável que será salva no banco (NULL ou string 'prof' ou número)

    // equipments_afetados é um array de checkboxes.
    $equipamentos_afetados_array = isset($_POST['equipamentos_afetados']) ? $_POST['equipamentos_afetados'] : [];
    // Converte o array em uma string separada por vírgulas.
    $equipamentos_afetados = $conexao->real_escape_string(implode(',', $equipamentos_afetados_array));

    $descricao = $conexao->real_escape_string(trim($_POST['descricao']));

    // Validação básica dos dados.
    // O campo 'nome_solicitante' não é mais verificado aqui.
    if (empty($nome_professor) || empty($local_tipo) || empty($descricao)) {
        $_SESSION['mensagem'] = "Erro: Por favor, preencha todos os campos obrigatórios.";
        header("Location: index.php");
        exit();
    }

    // Validação obrigatória: Pelo menos um equipamento deve ser selecionado.
    if (empty($equipamentos_afetados_array)) {
        $_SESSION['mensagem'] = "Erro: É obrigatório selecionar pelo menos um equipamento afetado.";
        header("Location: index.php");
        exit();
    }

    // Lógica para local_detalhe (se não for Carrinho, deve ter detalhe)
    if ($local_tipo !== 'Carrinho' && empty($local_detalhe)) {
        $_SESSION['mensagem'] = "Erro: O detalhe do local é obrigatório para o tipo de local selecionado.";
        header("Location: index.php");
        exit();
    }
    
    // Se local_tipo for 'Carrinho', defina local_detalhe como "N/A" se ele estiver vazio.
    if ($local_tipo === 'Carrinho' && empty($local_detalhe)) {
        $local_detalhe = 'N/A';
    }

    // Validação e processamento do Número do Computador
    if ($local_tipo === 'Laboratório' || $local_tipo === 'Carrinho' || ($local_tipo === 'Sala' && $local_detalhe === 'Sala Maker')) {
        if (empty($numero_computador_input)) {
            $_SESSION['mensagem'] = "Erro: O número do computador é obrigatório para laboratório, carrinho ou Sala Maker.";
            header("Location: index.php");
            exit();
        }

        // Converte para minúsculas para validação da palavra 'prof'
        $numero_computador_lower = strtolower($numero_computador_input);

        // Lógica: 'prof' é aceito SOMENTE para Laboratório
        if ($local_tipo === 'Laboratório' && $numero_computador_lower === 'prof') {
            $numero_computador_para_bd = 'prof'; // Salva como 'prof' string
        } else {
            // Tenta validar como inteiro
            $numero_computador_int = filter_var($numero_computador_input, FILTER_VALIDATE_INT);

            if ($numero_computador_int === false || $numero_computador_int < 1) {
                 $_SESSION['mensagem'] = "Erro: Número do computador inválido. Deve ser um número (e para Laboratório, pode ser 'prof').";
                header("Location: index.php");
                exit();
            }

            // Validação de range específica para Laboratório, Carrinho e Sala Maker
            if ($local_tipo === 'Laboratório') {
                if ($numero_computador_int > 20) {
                    $_SESSION['mensagem'] = "Erro: Para laboratório, o número do computador deve ser entre 1 e 20 ou 'prof'.";
                    header("Location: index.php");
                    exit();
                }
            } elseif ($local_tipo === 'Carrinho') {
                if ($numero_computador_int > 30) {
                    $_SESSION['mensagem'] = "Erro: Para carrinho, o número do computador deve ser entre 1 e 30.";
                    header("Location: index.php");
                    exit();
                }
            } elseif ($local_tipo === 'Sala' && $local_detalhe === 'Sala Maker') {
                if ($numero_computador_int > 15) {
                    $_SESSION['mensagem'] = "Erro: Para Sala Maker, o número do computador deve ser entre 1 e 15.";
                    header("Location: index.php");
                    exit();
                }
            }
            $numero_computador_para_bd = (string)$numero_computador_int; // Salva como string de número
        }
    } else {
        // Se o tipo de local não exige um número de computador específico, define como 'prof'
        $numero_computador_para_bd = 'prof'; 
    }


    // Define o status padrão para 'Pendente'.
    $status = 'Pendente';
    // Obtém a data e hora atual do servidor para 'data_envio'.
    $data_envio = date('Y-m-d H:i:s');

    // Prepara a query SQL para inserção dos dados na tabela 'chamados'.
    // A query foi atualizada para refletir a remoção do 'nome_solicitante'.
    $sql = "INSERT INTO chamados (nome_professor, local_tipo, local_detalhe, numero_computador, equipamentos_afetados, descricao, status, data_envio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    // Prepara a declaração SQL.
    if ($stmt = $conexao->prepare($sql)) {
        // Vincula os parâmetros à declaração.
        // A string de tipos agora tem 8 's' para as 8 colunas restantes.
        $stmt->bind_param("ssssssss", $nome_professor, $local_tipo, $local_detalhe, $numero_computador_para_bd, $equipamentos_afetados, $descricao, $status, $data_envio);

        // Executa a declaração.
        if ($stmt->execute()) {
            // Se a inserção for bem-sucedida, define uma mensagem de sucesso na sessão.
            $_SESSION['mensagem'] = "Chamado registrado com sucesso! ID: " . $conexao->insert_id;
        } else {
            // Se houver um erro na execução, define uma mensagem de erro na sessão.
            $_SESSION['mensagem'] = "Erro ao registrar o chamado: " . $stmt->error;
        }

        // Fecha a declaração.
        $stmt->close();
    } else {
        // Se houver um erro na preparação da declaração.
        $_SESSION['mensagem'] = "Erro na preparação da consulta: " . $conexao->error;
    }

    // Fecha a conexão com o banco de dados.
    $conexao->close();

    // Redireciona de volta para a página inicial (index.php) para exibir a mensagem.
    header("Location: index.php");
    exit(); // Garante que o script pare de ser executado após o redirecionamento.

} else {
    // Se a requisição não for um POST direto, redireciona para a página inicial
    // ou exibe uma mensagem de erro apropriada.
    $_SESSION['mensagem'] = "Erro: Acesso inválido ao script.";
    header("Location: index.php");
    exit();
}
