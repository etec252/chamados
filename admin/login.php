<?php
// admin/login.php - Página de Login do Administrador
// Este script exibe o formulário de login e processa a autenticação do administrador,
// com a nova paleta de cores e fonte moderna.

// Inclui o arquivo de conexão com o banco de dados e inicia a sessão.
require_once '../conexao.php'; // Caminho ajustado para acessar conexao.php na pasta pai

// Se o usuário já estiver logado, redireciona para o dashboard.
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: dashboard.php");
    exit();
}

// Inicializa a mensagem de feedback.
$mensagem = '';

// Verifica se o formulário foi submetido.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta e sanitiza os dados do formulário.
    $usuario = $conexao->real_escape_string(trim($_POST['usuario']));
    $senha = $conexao->real_escape_string(trim($_POST['senha']));

    // Validação básica dos campos.
    if (empty($usuario) || empty($senha)) {
        $_SESSION['mensagem'] = "Por favor, preencha todos os campos.";
    } else {
        // Prepara a query SQL para buscar o usuário no banco de dados.
        $sql = "SELECT id, usuario, senha FROM usuarios WHERE usuario = ?";

        // Prepara a declaração.
        if ($stmt = $conexao->prepare($sql)) {
            // Vincula o parâmetro.
            $stmt->bind_param("s", $usuario);

            // Executa a declaração.
            if ($stmt->execute()) {
                // Obtém o resultado da query.
                $result = $stmt->get_result();

                // Verifica se um usuário foi encontrado.
                if ($result->num_rows == 1) {
                    $row = $result->fetch_assoc();
                    $hashed_password = $row['senha'];

                    // Verifica a senha usando password_verify().
                    if (password_verify($senha, $hashed_password)) {
                        // Senha correta, inicia a sessão.
                        $_SESSION['loggedin'] = true;
                        $_SESSION['id'] = $row['id'];
                        $_SESSION['usuario'] = $row['usuario'];

                        // Redireciona para o painel administrativo.
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        // Senha incorreta.
                        $_SESSION['mensagem'] = "Usuário ou senha inválidos.";
                    }
                } else {
                    // Usuário não encontrado.
                    $_SESSION['mensagem'] = "Usuário ou senha inválidos.";
                }
            } else {
                // Erro na execução da query.
                $_SESSION['mensagem'] = "Erro na execução da consulta: " . $stmt->error;
            }

            // Fecha a declaração.
            $stmt->close();
        } else {
            // Erro na preparação da query.
            $_SESSION['mensagem'] = "Erro na preparação da consulta: " . $conexao->error;
        }
    }
}

// Obtém a mensagem de feedback da sessão, se houver.
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']); // Limpa a mensagem após exibir.
}

// Fecha a conexão com o banco de dados.
$conexao->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrativo - Chamados ETEC</title>
    <!-- Inclui Tailwind CSS para um estilo moderno e responsivo -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Inclui a fonte Montserrat do Google Fonts para o título -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <style>
        /* Define a cor base personalizada */
        :root {
            --primary-color: #7e0000;
            --primary-hover-color: #630000;
            --primary-light-bg: #ffe0e0;
            --primary-dark-text: #4f0000;
        }
        /* Estilos personalizados para o corpo da página e fontes */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6; /* Cor de fundo suave */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* Garante que ocupe a altura total da viewport */
        }
        /* Estilo para o container do formulário de login */
        .login-container {
            max-width: 400px;
            width: 90%; /* Responsivo para telas menores */
            padding: 30px;
            background-color: #ffffff;
            border-radius: 12px; /* Cantos arredondados */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); /* Sombra suave */
        }
        /* Estilo para mensagens de feedback */
        .feedback-message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
        }
        .feedback-error {
            background-color: #fee2e2; /* Mantém vermelho padrão para erro */
            color: #991b1b; /* Mantém vermelho padrão para erro */
        }
        h1 {
            font-family: 'Montserrat', sans-serif; /* Aplica Montserrat ao título principal */
            color: var(--primary-color); /* Aplica a cor definida */
        }
        .custom-hr {
            border: none;
            height: 2px;
            background: linear-gradient(to right, transparent, var(--primary-color), transparent);
            margin: 1rem auto 2rem auto; /* Ajuste a margem */
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
    <div class="login-container">
        <h1 class="text-3xl font-extrabold mb-4 text-center">Login</h1>
        <hr class="custom-hr">

        <?php if (!empty($mensagem)): ?>
            <!-- Exibe a mensagem de feedback, se houver -->
            <div class="feedback-message feedback-error">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-6 mt-8">
            <div>
                <label for="usuario" class="block text-sm font-medium text-gray-700 mb-1 text-primary">Usuário</label>
                <input type="text" id="usuario" name="usuario" required autofocus
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
            </div>

            <div>
                <label for="senha" class="block text-sm font-medium text-gray-700 mb-1 text-primary">Senha</label>
                <input type="password" id="senha" name="senha" required
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm">
            </div>

            <div class="flex justify-center">
                <button type="submit" class="btn-primary">
                    Entrar
                </button>
            </div>
        </form>

        <div class="mt-8 text-center">
            <p class="text-gray-600">
                <a href="../index.php" class="font-medium text-primary">Voltar para a página inicial</a>
            </p>
        </div>
    </div>
</body>
</html>
