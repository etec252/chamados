<?php
// admin/criar_admin.php - Criar Novo Administrador
// Este script permite que um administrador logado crie novos usuários administradores,
// com mensagens de feedback agora em verde para sucesso.

// Inclui o arquivo de conexão com o banco de dados e inicia a sessão.
require_once '../conexao.php'; // Caminho ajustado para acessar conexao.php na pasta pai

// Verifica se o usuário está logado. Se não estiver, redireciona para a página de login.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['mensagem'] = "Acesso negado. Por favor, faça login.";
    header("Location: login.php");
    exit();
}

// Inicializa a mensagem de feedback.
$mensagem = '';

// Verifica se o formulário foi submetido.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e sanitiza os dados do formulário.
    $novo_usuario = $conexao->real_escape_string(trim($_POST['novo_usuario']));
    $nova_senha = $_POST['nova_senha']; // Senha bruta, será hashed
    $confirma_senha = $_POST['confirma_senha'];

    // Validação básica dos campos.
    if (empty($novo_usuario) || empty($nova_senha) || empty($confirma_senha)) {
        $mensagem = "Por favor, preencha todos os campos.";
    } elseif ($nova_senha !== $confirma_senha) {
        $mensagem = "As senhas não coincidem.";
    } elseif (strlen($nova_senha) < 6) { // Exemplo: exige no mínimo 6 caracteres
        $mensagem = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        // Gera o hash da senha usando password_hash() para segurança.
        $senha_hashed = password_hash($nova_senha, PASSWORD_DEFAULT);

        // Prepara a query SQL para verificar se o usuário já existe.
        $sql_check = "SELECT id FROM usuarios WHERE usuario = ?";
        if ($stmt_check = $conexao->prepare($sql_check)) {
            $stmt_check->bind_param("s", $novo_usuario);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $mensagem = "O usuário '{$novo_usuario}' já existe. Por favor, escolha outro nome de usuário.";
            } else {
                // O usuário não existe, pode inserir.
                $sql_insert = "INSERT INTO usuarios (usuario, senha) VALUES (?, ?)";
                if ($stmt_insert = $conexao->prepare($sql_insert)) {
                    $stmt_insert->bind_param("ss", $novo_usuario, $senha_hashed);

                    if ($stmt_insert->execute()) {
                        $mensagem = "Usuário administrador '{$novo_usuario}' criado com sucesso!";
                    } else {
                        $mensagem = "Erro ao criar usuário: " . $stmt_insert->error;
                    }
                    $stmt_insert->close();
                } else {
                    $mensagem = "Erro na preparação da consulta de inserção: " . $conexao->error;
                }
            }
            $stmt_check->close();
        } else {
            $mensagem = "Erro na preparação da consulta de verificação: " . $conexao->error;
        }
    }
}

// Fecha a conexão com o banco de dados.
$conexao->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Administrador</title>
    <!-- Inclui Tailwind CSS para um estilo moderno e responsivo -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Inclui a fonte Montserrat do Google Fonts para o título -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <style>
        /* Define a cor base personalizada */
        :root {
            --primary-color: #7e0000;
            --primary-hover-color: #630000;
            /* Cores para mensagens de sucesso (agora verdes) */
            --success-bg: #d4edda; /* Fundo verde claro */
            --success-text: #155724; /* Texto verde escuro */
            /* Cores para mensagens de erro */
            --error-bg: #f8d7da; /* Fundo vermelho claro */
            --error-text: #721c24; /* Texto vermelho escuro */
        }
        /* Estilos personalizados */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            max-width: 500px;
            width: 40% !important;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .feedback-message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
        }
        .feedback-success {
            background-color: var(--success-bg);
            color: var(--success-text);
        }
        .feedback-error {
            background-color: var(--error-bg);
            color: var(--error-text);
        }
        .feedback-warning {
            background-color: #fde68a;
            color: #92400e;
        }
        h1 {
            font-family: 'Montserrat', sans-serif;
            color: var(--primary-color);
        }
        .custom-hr {
            border: none;
            height: 2px;
            background: linear-gradient(to right, transparent, var(--primary-color), transparent);
            margin: 1rem auto 2rem auto;
            width: 80%;
            border-radius: 1px;
        }
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border-radius: 0.5rem;
            padding: 0.75rem 1.5rem;
            transition: background-color 0.2s ease-in-out;
        }
        .btn-primary:hover {
            background-color: var(--primary-hover-color);
        }
        .text-primary {
            color: var(--primary-color);
        }
        .text-primary:hover {
            color: var(--primary-hover-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-3xl font-extrabold mb-4 text-center">Cadastro de administrador</h1>
        <hr class="custom-hr">

        <?php if (!empty($mensagem)): ?>
            <div class="feedback-message <?php
                if (strpos($mensagem, 'sucesso') !== false) {
                    echo 'feedback-success';
                } elseif (strpos($mensagem, 'existe') !== false) {
                    echo 'feedback-warning';
                } else {
                    echo 'feedback-error';
                }
            ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <form action="criar_admin.php" method="POST" class="space-y-6 mt-8">
            <div>
                <label for="novo_usuario" class="block text-sm font-medium text-gray-700 mb-1 text-primary">Nome de Usuário</label>
                <input type="text" id="novo_usuario" name="novo_usuario" required
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
            </div>

            <div>
                <label for="nova_senha" class="block text-sm font-medium text-gray-700 mb-1 text-primary">Senha</label>
                <input type="password" id="nova_senha" name="nova_senha" required
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
            </div>

            <div>
                <label for="confirma_senha" class="block text-sm font-medium text-gray-700 mb-1 text-primary">Confirmar Senha</label>
                <input type="password" id="confirma_senha" name="confirma_senha" required
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
            </div>

            <div class="flex justify-center">
                <button type="submit" class="btn-primary">
                    Criar Administrador
                </button>
            </div>
        </form>

        <div class="mt-8 text-center">
            <p class="text-gray-600">
                <a href="dashboard.php" class="font-medium text-primary">Voltar para o Painel</a>
            </p>
        </div>
    </div>
</body>
</html>
