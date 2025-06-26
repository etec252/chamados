<?php
// conexao.php - Configuração da Conexão com o Banco de Dados e Sessão

// Define o fuso horário padrão para todas as funções de data/hora do PHP.
// É crucial que esta linha seja uma das primeiras a serem executadas.
date_default_timezone_set('America/Sao_Paulo'); // Define para o fuso horário de São Paulo (UTC-03:00)

// Inicia a sessão PHP. Isso é crucial para o gerenciamento de login
// e para manter o estado do usuário logado no painel administrativo.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configurações do banco de dados para ambiente de desenvolvimento/localhost.
// Para hospedagens gratuitas como ProFreeHost, você precisará atualizar
// estas credenciais com as informações fornecidas pelo seu provedor.
define('DB_SERVER', 'localhost'); // Geralmente 'localhost'
define('DB_USERNAME', 'root');     // Nome de usuário do seu banco de dados
define('DB_PASSWORD', '');         // Senha do seu banco de dados
define('DB_NAME', 'chamados'); // Nome do banco de dados

// Cria uma nova conexão com o banco de dados MySQL usando MySQLi.
$conexao = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verifica a conexão. Se houver algum erro, exibe uma mensagem e encerra o script.
if ($conexao->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conexao->connect_error);
}

// Define o conjunto de caracteres para UTF-8 para garantir que
// caracteres especiais (acentos, cedilha) sejam exibidos corretamente.
$conexao->set_charset("utf8");

// Define o fuso horário da sessão MySQL para corresponder ao PHP.
// Isso garante que o MySQL interprete as datas enviadas pelo PHP corretamente
// e que funções como NOW() ou CURRENT_TIMESTAMP no MySQL usem este fuso.
// Usamos o offset direto (-03:00) para compatibilidade mais ampla em hospedagens.
$conexao->query("SET time_zone = '-03:00'");

// Em um ambiente de produção, é recomendável não exibir erros detalhados
// diretamente ao usuário para segurança. Em vez disso, registre-os.
// Para desenvolvimento, error_reporting(E_ALL) e ini_set('display_errors', 1)
// podem ser úteis para depuração.
?>
