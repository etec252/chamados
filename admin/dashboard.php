<?php
// admin/dashboard.php - Painel Administrativo de Chamados
// Esta página exibe a lista de chamados e gerencia a interface do usuário.
// As operações de filtragem e busca agora são feitas via AJAX para uma experiência mais fluida.
// ATUALIZADO: Aprimoramentos visuais, incluindo sombras, spinner de carregamento e ícones.
// ATUALIZADO: Tamanho geral do sistema diminuído para melhor visualização em 100% de resolução.
// ATUALIZADO: Mensagens de feedback com animação e botão de fechar.
// ATUALIZADO: Botão "Limpar Filtros" visível apenas quando filtros são aplicados.

// Inclui o arquivo de conexão com o banco de dados e inicia a sessão.
require_once '../conexao.php'; // Caminho ajustado para acessar conexao.php na pasta pai

// Verifica se o usuário está logado. Se não estiver, redireciona para a página de login.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}

// Inicializa a mensagem de feedback.
$mensagem = '';
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']); // Limpa a mensagem após exibir.
}

// Fechar a conexão aqui, pois as requisições AJAX para 'get_chamados.php' abrirão sua própria conexão.
$conexao->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Chamados ETEC</title>
    <!-- Inclui Tailwind CSS para um estilo moderno e responsivo -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Inclui a fonte Montserrat do Google Fonts para o título -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <!-- Inclui Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            font-size: 0.9rem; /* Diminuindo a fonte base */
            background-color: #f3f4f6;
        }
        .container {
            width: 80% !important; /* Aumentei um pouco para não ficar excessivamente apertado em monitores maiores */
            max-width: 1000px; /* Diminuí o max-width */
            margin: 30px auto; /* Margem diminuída */
            padding: 15px; /* Padding diminuído */
            background-color: #ffffff;
            border-radius: 10px; /* Cantos levemente menores */
            /* Aprimoramento da sombra do container */
            box-shadow: 0 8px 12px -3px rgba(0, 0, 0, 0.1), 0 3px 5px -2px rgba(0, 0, 0, 0.05);
        }
        /* Estilo para mensagens de feedback */
        .feedback-message {
            padding: 10px; /* Diminuído */
            margin-bottom: 15px; /* Diminuído */
            border-radius: 6px; /* Diminuído */
            text-align: center;
            font-weight: bold;
            display: flex; /* Para alinhar o texto e o botão de fechar */
            align-items: center;
            justify-content: space-between; /* Espaço entre o texto e o botão */
            opacity: 1;
            transition: opacity 0.5s ease-out, transform 0.5s ease-out; /* Animação de fade e slide */
        }
        .feedback-message.fade-out {
            opacity: 0;
            transform: translateY(-10px);
        }
        .feedback-success {
            background-color: var(--success-bg);
            color: var(--success-text);
        }
        .feedback-error {
            background-color: var(--error-bg);
            color: var(--error-text);
        }
        .feedback-close-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            color: inherit; /* Herda a cor do texto da mensagem */
            margin-left: 10px;
            opacity: 0.7;
            transition: opacity 0.2s ease-in-out;
        }
        .feedback-close-btn:hover {
            opacity: 1;
        }

        /* Estilos da tabela */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px; /* Diminuído */
            border: 1px solid #5a67d8; /* Borda externa da tabela um pouco mais fina */
            border-radius: 6px; /* Arredondar cantos da tabela */
            overflow: hidden;
        }
        th, td {
            padding: 10px 12px; /* Padding diminuído */
            text-align: left;
            border: 1px solid #99aab5; /* Bordas para células e cabeçalhos um pouco mais finas */
            font-size: 0.85rem; /* Fonte da célula levemente menor */
        }
        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background-color: #f0f4f8;
        }
        .status-badge {
            padding: 3px 6px; /* Diminuído */
            border-radius: 4px; /* Diminuído */
            font-size: 0.75em; /* Diminuído */
            font-weight: bold;
            display: inline-block;
        }

        /* Estilos para badges de status (manter cores) */
        .status-Pendente { background-color: #fde68a; color: #92400e; }
        .status-Em-andamento { background-color: #bfdbfe; color: #1e40af; }
        .status-Resolvido { background-color: #d1fae5; color: #065f46; }

        h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 2rem; /* Diminuindo o tamanho do h1 */
            color: var(--primary-color);
        }
        .custom-hr {
            margin: 1rem auto; /* Ajuste para um espaço menor */
            width: 70%; /* Diminuído */
        }
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            border-radius: 0.4rem; /* Diminuído */
            padding: 0.6rem 1.2rem; /* Diminuído */
            font-size: 0.85rem; /* Diminuído */
            gap: 0.4rem; /* Espaçamento entre ícone e texto */
        }
        /* Ajuste para botões menores no geral */
        .px-5.py-2 {
            padding: 0.6rem 1.2rem;
            font-size: 0.85rem;
        }
        .px-4.py-2 {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .loading-indicator {
            padding: 0.8rem; /* Diminuído */
            font-size: 1rem; /* Diminuído */
            gap: 0.4rem; /* Diminuído */
        }
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left-color: var(--primary-color);
            border-radius: 50%;
            width: 25px; /* Diminuído */
            height: 25px; /* Diminuído */
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-3xl font-extrabold mb-4 text-center">Painel de Chamados</h1>
        <hr class="custom-hr">

        <?php if (!empty($mensagem)): ?>
            <div id="feedbackMessage" class="feedback-message <?php echo (strpos($mensagem, 'sucesso') !== false || strpos($mensagem, 'excluído') !== false) ? 'feedback-success' : 'feedback-error'; ?>">
                <span><?php echo $mensagem; ?></span>
                <button class="feedback-close-btn" onclick="document.getElementById('feedbackMessage').classList.add('fade-out'); setTimeout(() => document.getElementById('feedbackMessage').remove(), 500);">
                    &times;
                </button>
            </div>
        <?php endif; ?>

        <div class="flex justify-between items-center mb-5 flex-wrap gap-3"> <!-- Margem e gap ajustados -->
            <p class="text-base text-gray-700">Bem-vindo(a), <span class="font-semibold text-primary"><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>!</p> <!-- Fonte ajustada -->
            <div class="flex gap-3"> <!-- Gap ajustado -->
                <a href="criar_admin.php" class="btn-primary bg-green-600 hover:bg-green-700">
                    <i class="fas fa-user-plus"></i> Criar Novo Admin
                </a>
                <a href="logout.php" class="px-5 py-2 bg-red-600 text-white font-semibold rounded-lg shadow-md hover:bg-red-700 transition duration-200 ease-in-out">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>

        <!-- Formulário de Filtro e Busca -->
        <div id="filterInputs" class="mb-6 p-4 bg-gray-50 rounded-lg shadow-sm"> <!-- Margem e padding ajustados -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3"> <!-- Gap ajustado -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1 text-primary">Filtrar por Status:</label>
                    <select id="status" name="status" onchange="fetchAndDisplayChamados()"
                            class="mt-1 block w-full px-2 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm"> <!-- Padding ajustado -->
                        <option value="">Todos</option>
                        <option value="Pendente">Pendente</option>
                        <option value="Em andamento">Em andamento</option>
                        <option value="Resolvido">Resolvido</option>
                    </select>
                </div>
                <div>
                    <label for="local_tipo" class="block text-sm font-medium text-gray-700 mb-1 text-primary">Filtrar por local:</label>
                    <select id="local_tipo" name="local_tipo" onchange="updateLocalDetalheFilterOptions(); fetchAndDisplayChamados();"
                            class="mt-1 block w-full px-2 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm">
                        <option value="">Todos</option>
                        <option value="Laboratório">Laboratório</option>
                        <option value="Carrinho">Carrinho</option>
                        <option value="Sala">Sala</option>
                        <option value="Multimeios">Multimeios</option>
                    </select>
                </div>
                <div id="local_detalhe_filter_container">
                    <label for="local_detalhe" class="block text-sm font-medium text-gray-700 mb-1 text-primary">Especifique:</label>
                    <select id="local_detalhe" name="local_detalhe" onchange="fetchAndDisplayChamados()"
                            class="mt-1 block w-full px-2 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm">
                        <!-- Opções serão carregadas via JavaScript -->
                    </select>
                </div>
                <div>
                    <label for="busca_nome_professor" class="block text-sm font-medium text-gray-700 mb-1 text-primary">Buscar por nome:</label>
                    <input type="text" id="busca_nome_professor" name="busca_nome_professor" placeholder="Nome do professor..." onkeyup="debounceFetch()"
                           class="mt-1 block w-full px-2 py-1.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary text-sm">
                </div>
                <div class="md:col-span-4 flex justify-center mt-3"> <!-- Margem ajustada -->
                    <button type="button" id="clearFiltersBtn" onclick="clearFilters()" class="ml-3 px-5 py-2 bg-gray-300 text-gray-800 font-semibold rounded-lg shadow-md hover:bg-gray-400 transition duration-200 ease-in-out">Limpar Filtros</button>
                </div>
            </div>
        </div>

        <!-- Indicador de Carregamento -->
        <div id="loadingIndicator" class="loading-indicator">
            <div class="spinner"></div>
            <span>Carregando chamados...</span>
        </div>

        <!-- Tabela de Chamados será carregada aqui via AJAX -->
        <div id="chamadosTableContainer">
            <!-- Conteúdo da tabela será injetado aqui -->
        </div>
    </div>

    <script>
        let searchTimeout;
        // Referências aos elementos do DOM
        const statusSelect = document.getElementById('status');
        const localTipoSelect = document.getElementById('local_tipo');
        const localDetalheSelect = document.getElementById('local_detalhe');
        const localDetalheFilterContainer = document.getElementById('local_detalhe_filter_container');
        const buscaNomeProfessorInput = document.getElementById('busca_nome_professor');
        const chamadosTableContainer = document.getElementById('chamadosTableContainer');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const clearFiltersBtn = document.getElementById('clearFiltersBtn'); // Novo: referência ao botão

        // Função para buscar e exibir os chamados via AJAX
        async function fetchAndDisplayChamados() {
            loadingIndicator.style.display = 'flex'; // Mostra o indicador de carregamento como flex
            chamadosTableContainer.innerHTML = ''; // Limpa o conteúdo anterior

            const params = new URLSearchParams();
            if (statusSelect.value) params.append('status', statusSelect.value);
            if (localTipoSelect.value) params.append('local_tipo', localTipoSelect.value);
            
            // Adiciona local_detalhe apenas se ele for visível e tiver um valor diferente de "Todos" ou "N/A"
            if (localDetalheFilterContainer.style.display !== 'none' && localDetalheSelect.value && localDetalheSelect.value !== 'N/A') {
                params.append('local_detalhe', localDetalheSelect.value);
            } else if (localTipoSelect.value === 'Carrinho') { // Se Carrinho for selecionado, sempre enviar N/A
                params.append('local_detalhe', 'N/A');
            }

            if (buscaNomeProfessorInput.value) params.append('busca_nome_professor', buscaNomeProfessorInput.value);

            try {
                const response = await fetch(`get_chamados.php?${params.toString()}`);
                if (!response.ok) {
                    throw new Error(`Erro HTTP! status: ${response.status}`);
                }
                const html = await response.text();
                chamadosTableContainer.innerHTML = html;
            }
            catch (error) {
                console.error("Erro ao buscar chamados:", error);
                chamadosTableContainer.innerHTML = '<p class="text-center text-red-500 text-lg mt-10">Erro ao carregar chamados. Tente novamente.</p>';
            }
            finally {
                loadingIndicator.style.display = 'none'; // Esconde o indicador de carregamento
                toggleClearFiltersButton(); // Novo: Chama a função para atualizar a visibilidade do botão
            }
        }

        // Função de debounce para a busca por nome do professor
        function debounceFetch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchAndDisplayChamados();
            }, 300); // Atraso de 300ms para evitar muitas requisições
        }

        // Função para atualizar as opções do filtro de Detalhe do Local
        function updateLocalDetalheFilterOptions() {
            const tipoSelecionado = localTipoSelect.value;
            localDetalheSelect.innerHTML = '<option value="">Todos</option>'; // Limpa e adiciona "Todos"

            let options = [];
            if (tipoSelecionado === 'Laboratório') {
                for (let i = 1; i <= 5; i++) {
                    options.push(`Laboratório ${i}`);
                }
                localDetalheFilterContainer.style.display = 'block';
            } else if (tipoSelecionado === 'Sala') {
                for (let i = 1; i <= 7; i++) {
                    options.push(`Sala ${i}`);
                }
                options.push('Sala Maker');
                localDetalheFilterContainer.style.display = 'block';
            } else if (tipoSelecionado === 'Multimeios') {
                for (let i = 1; i <= 2; i++) {
                    options.push(`Multimeios ${i}`);
                }
                localDetalheFilterContainer.style.display = 'block';
            } else if (tipoSelecionado === 'Carrinho') {
                // Se 'Carrinho' for selecionado, oculta o contêiner do filtro de detalhe.
                localDetalheFilterContainer.style.display = 'none';
                // Garante que o valor do filtro de detalhe seja resetado quando o contêiner é ocultado.
                localDetalheSelect.value = ''; 
            } else {
                localDetalheFilterContainer.style.display = 'none'; // Esconde se for "Todos" ou outro vazio
                localDetalheSelect.value = ''; // Reseta o valor
            }

            // Adiciona as novas opções e tenta selecionar a opção atual do filtro (se houver)
            const urlParams = new URLSearchParams(window.location.search);
            const currentDetalheFilter = urlParams.get('local_detalhe');

            options.forEach(optionText => {
                const option = document.createElement('option');
                option.value = optionText;
                option.textContent = optionText;
                if (optionText === currentDetalheFilter) {
                    option.selected = true;
                }
                localDetalheSelect.appendChild(option);
            });
        }

        // Função para limpar todos os filtros e recarregar a tabela
        function clearFilters() {
            statusSelect.value = '';
            localTipoSelect.value = '';
            localDetalheSelect.value = ''; 
            // Atualiza o display do container de detalhe local após limpar
            updateLocalDetalheFilterOptions(); 
            buscaNomeProfessorInput.value = '';
            // Recarrega os chamados sem filtros
            fetchAndDisplayChamados(); 
        }

        // Novo: Função para alternar a visibilidade do botão Limpar Filtros
        function toggleClearFiltersButton() {
            const hasActiveFilter = 
                statusSelect.value !== '' ||
                localTipoSelect.value !== '' ||
                (localDetalheFilterContainer.style.display !== 'none' && localDetalheSelect.value !== '') ||
                buscaNomeProfessorInput.value !== '';
            
            if (hasActiveFilter) {
                clearFiltersBtn.style.display = 'inline-flex'; // Mostrar como flex para alinhar ícone
            } else {
                clearFiltersBtn.style.display = 'none'; // Esconder
            }
        }

        // Adiciona um listener para quando o DOM estiver completamente carregado
        document.addEventListener('DOMContentLoaded', function() {
            // Re-seleciona os valores dos filtros com base nos parâmetros da URL (se houver)
            const urlParams = new URLSearchParams(window.location.search);
            const initialStatus = urlParams.get('status') || '';
            const initialLocalTipo = urlParams.get('local_tipo') || '';
            const initialBuscaNome = urlParams.get('busca_nome_professor') || '';

            if (initialStatus) statusSelect.value = initialStatus;
            if (initialLocalTipo) localTipoSelect.value = initialLocalTipo;
            if (initialBuscaNome) buscaNomeProfessorInput.value = initialBuscaNome;

            // Chamar updateLocalDetalheFilterOptions para garantir que o filtro de detalhe seja populado corretamente
            // e que o valor inicial seja selecionado.
            updateLocalDetalheFilterOptions();

            // Carrega os chamados com os filtros iniciais e atualiza a visibilidade do botão
            fetchAndDisplayChamados();
            // A chamada a toggleClearFiltersButton() já está em fetchAndDisplayChamados()
        });
    </script>
</body>
</html>
