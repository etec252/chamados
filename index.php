<?php
// index.php - Formulário de Cadastro de Chamados
// Este arquivo exibe o formulário para os usuários registrarem novos chamados,
// com o campo "Número do Computador" unificado para aceitar números ou "prof".
// ATUALIZADO: Lógica para "Sala Maker" para exibir o campo "Número do PC" com range 1-15.

// Inclui o arquivo de conexão com o banco de dados.
require_once 'conexao.php';

// Mensagens de feedback (sucesso/erro) podem ser armazenadas na sessão
$mensagem = '';
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']); // Limpa a mensagem da sessão após exibir.
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abrir Chamado - Chamados ETEC</title>
    <!-- Inclui Tailwind CSS para um estilo moderno e responsivo -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Inclui a fonte Montserrat do Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <style>
        /* Define a cor base personalizada para facilitar o uso no Tailwind */
        :root {
            --primary-color: #7e0000; /* Cor principal para títulos, botões, etc. */
            --primary-hover-color: #630000; /* Um pouco mais escuro para hover */
            /* Cores para mensagens de sucesso (agora verdes) */
            --success-bg: #d4edda; /* Fundo verde claro */
            --success-text: #155724; /* Texto verde escuro */
            /* Cores para mensagens de erro */
            --error-bg: #f8d7da; /* Fundo vermelho claro */
            --error-text: #721c24; /* Texto vermelho escuro */
        }

        /* Estilos personalizados para o corpo da página e fontes */
        body {
            font-family: 'Inter', sans-serif; /* Fonte Inter para o corpo */
            background-color: #f3f4f6; /* Cor de fundo suave */
        }
        /* Centraliza o conteúdo e adiciona padding */
        .container {
            max-width: 380px; /* Mantém o max-width para telas muito grandes */
            width: 50% !important; /* Define a largura para 50% com !important */
            min-width: 300px; /* Garante uma largura mínima para a div em telas pequenas */
            margin: 40px auto;
            padding: 20px;
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
        .feedback-success {
            background-color: var(--success-bg); /* Usa a nova cor de fundo verde para sucesso */
            color: var(--success-text); /* Usa a nova cor de texto verde para sucesso */
        }
        .feedback-error {
            background-color: var(--error-bg); /* Usa a nova cor de fundo vermelho para erro */
            color: var(--error-text); /* Usa a nova cor de texto vermelho para erro */
        }
        /* Estilos para rádio e checkbox groups */
        .radio-group, .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem; /* Espaçamento entre os itens */
            margin-top: 0.5rem;
        }
        .radio-group label, .checkbox-group label {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            background-color: #f9fafb;
            transition: all 0.2s ease-in-out;
        }
        .radio-group label:hover, .checkbox-group label:hover {
            background-color: #f0f4f8;
            border-color: #cbd5e0;
        }
        .radio-group input[type="radio"]:checked + span,
        .checkbox-group input[type="checkbox"]:checked + span {
            font-weight: bold;
            color: var(--primary-color); /* Cor de destaque para o texto do selecionado */
        }
        .radio-group input[type="radio"], .checkbox-group input[type="checkbox"] {
            margin-right: 0.5rem;
            accent-color: var(--primary-color); /* Cor de destaque para o input em si */
        }

        /* Aplica a fonte Montserrat especificamente ao h1 */
        h1 {
            font-family: 'Montserrat', sans-serif;
            /* O peso da fonte já é definido no link do Google Fonts (wght@700) */
        }

        /* Estilo para a linha horizontal (HR) */
        .custom-hr {
            border: none;
            height: 2px; /* Espessura da linha */
            background: linear-gradient(to right, transparent, var(--primary-color), transparent); /* Gradiente para efeito minimalista */
            margin: 2rem auto; /* Margem superior e inferior, centraliza */
            width: 80%; /* Largura da linha */
            border-radius: 1px; /* Cantos arredondados para a linha */
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Título principal com a nova cor e fonte Montserrat -->
        <h1 class="text-3xl font-extrabold mb-4 text-center" style="color: var(--primary-color);">Abrir Chamado</h1>
        <!-- Linha horizontal estilizada abaixo do h1 -->
        <hr class="custom-hr">

        <?php if (!empty($mensagem)): ?>
            <!-- Exibe a mensagem de feedback, se houver -->
            <div class="feedback-message <?php echo strpos($mensagem, 'sucesso') !== false ? 'feedback-success' : 'feedback-error'; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <form action="salvar_chamado.php" method="POST" class="space-y-6 mt-8">

            <!-- Nome do Professor -->
            <div>
                <label for="nome_professor" class="block text-sm font-medium text-gray-700 mb-1" style="color: var(--primary-color);">Nome do Professor:</label>
                <input type="text" id="nome_professor" name="nome_professor" required
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] sm:text-sm">
            </div>

            <!-- Tipo de Local -->
            <div>
                <label for="local_tipo" class="block text-sm font-medium text-gray-700 mb-1" style="color: var(--primary-color);">Local:</label>
                <select id="local_tipo" name="local_tipo" required
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] sm:text-sm">
                    <option value="">Selecione o local</option>
                    <option value="Laboratório">Laboratório</option>
                    <option value="Carrinho">Carrinho</option>
                    <option value="Sala">Sala</option>
                    <option value="Multimeios">Multimeios</option>
                </select>
            </div>

            <!-- Detalhe do Local (Botões de Rádio Dinâmicos) -->
            <div id="local_detalhe_container" style="display:none;">
                <label class="block text-sm font-medium text-gray-700 mb-1" style="color: var(--primary-color);">Detalhe do Local</label>
                <div id="local_detalhe_radios" class="radio-group">
                    <!-- Botões de rádio serão carregados via JavaScript -->
                </div>
            </div>

            <!-- Número do Computador (Campo de Texto unificado para número ou 'prof') -->
            <div id="numero_computador_container" style="display:none;">
                <label for="numero_computador" class="block text-sm font-medium text-gray-700 mb-1" style="color: var(--primary-color);">Número do PC</label>
                <input type="text" id="numero_computador" name="numero_computador"
                       placeholder=""
                       class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] sm:text-sm">
            </div>

            <!-- Equipamentos Afetados (Checkboxes) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" style="color: var(--primary-color);">Equipamentos:</label>
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="equipamentos_afetados[]" value="Computador">
                        <span>Computador</span>
                    </label>
                    <label>
                        <input type="checkbox" name="equipamentos_afetados[]" value="Monitor">
                        <span>Monitor</span>
                    </label>
                    <label>
                        <input type="checkbox" name="equipamentos_afetados[]" value="Mouse">
                        <span>Mouse</span>
                    </label>
                    <label>
                        <input type="checkbox" name="equipamentos_afetados[]" value="Teclado">
                        <span>Teclado</span>
                    </label>
                    <label>
                        <input type="checkbox" name="equipamentos_afetados[]" value="Outro">
                        <span>Outro</span>
                    </label>
                </div>
            </div>

            <!-- Descrição do Problema -->
            <div>
                <label for="descricao" class="block text-sm font-medium text-gray-700 mb-1" style="color: var(--primary-color);">Descrição do problema:</label>
                <textarea id="descricao" name="descricao" rows="5" required
                          class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] sm:text-sm"></textarea>
            </div>

            <div class="flex justify-center">
                <button type="submit"
                        class="px-6 py-3 text-white font-semibold rounded-lg shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition duration-200 ease-in-out"
                        style="background-color: var(--primary-color); hover:background-color: var(--primary-hover-color);">
                    Enviar
                </button>
            </div>
        </form>

        <div class="mt-8 text-center">
            <p class="text-gray-600">
                <a href="admin/login.php" class="font-medium" style="color: var(--primary-color); hover:color: var(--primary-hover-color);">Acessar Painel do Administrador</a>
            </p>
        </div>
    </div>

    <script>
        // JavaScript para lidar com as opções dinâmicas de local_detalhe e numero_computador
        document.addEventListener('DOMContentLoaded', function() {
            const localTipoSelect = document.getElementById('local_tipo');
            const localDetalheContainer = document.getElementById('local_detalhe_container');
            const localDetalheRadios = document.getElementById('local_detalhe_radios');
            const numeroComputadorContainer = document.getElementById('numero_computador_container');
            const numeroComputadorInput = document.getElementById('numero_computador'); // Agora é um input de texto

            function createRadioOption(value, text) {
                const label = document.createElement('label');
                label.innerHTML = `
                    <input type="radio" name="local_detalhe" value="${value}" required>
                    <span>${text}</span>
                `;
                return label;
            }

            function updateConditionalFields() {
                const tipoSelecionado = localTipoSelect.value;
                
                // Lógica para Detalhe do Local
                localDetalheRadios.innerHTML = ''; // Limpa as opções anteriores
                if (tipoSelecionado === 'Laboratório') {
                    localDetalheContainer.style.display = 'block';
                    for (let i = 1; i <= 5; i++) {
                        localDetalheRadios.appendChild(createRadioOption(`Laboratório ${i}`, `Laboratório ${i}`));
                    }
                } else if (tipoSelecionado === 'Sala') {
                    localDetalheContainer.style.display = 'block';
                    for (let i = 1; i <= 7; i++) {
                        localDetalheRadios.appendChild(createRadioOption(`Sala ${i}`, `Sala ${i}`));
                    }
                    localDetalheRadios.appendChild(createRadioOption('Sala Maker', 'Sala Maker'));
                } else if (tipoSelecionado === 'Multimeios') {
                    localDetalheContainer.style.display = 'block';
                    for (let i = 1; i <= 2; i++) {
                        localDetalheRadios.appendChild(createRadioOption(`Multimeios ${i}`, `Multimeios ${i}`));
                    }
                } else {
                    localDetalheContainer.style.display = 'none';
                }

                // Lógica para Número do Computador (campo de texto unificado)
                // Adiciona um listener para os botões de rádio de local_detalhe para atualizar o campo de número do computador
                const localDetalheRadiosList = localDetalheRadios.querySelectorAll('input[name="local_detalhe"]');
                localDetalheRadiosList.forEach(radio => {
                    radio.addEventListener('change', () => {
                        const detalheSelecionado = radio.value;
                        if (detalheSelecionado === 'Sala Maker') {
                            numeroComputadorContainer.style.display = 'block';
                            numeroComputadorInput.setAttribute('required', 'required');
                            numeroComputadorInput.placeholder = "1-15";
                        } else if (tipoSelecionado === 'Laboratório' || tipoSelecionado === 'Carrinho') {
                            numeroComputadorContainer.style.display = 'block';
                            numeroComputadorInput.setAttribute('required', 'required');
                            numeroComputadorInput.placeholder = (tipoSelecionado === 'Laboratório') ? "1-20 ou 'prof'" : "1-30";
                        } else {
                            numeroComputadorContainer.style.display = 'none';
                            numeroComputadorInput.removeAttribute('required');
                            numeroComputadorInput.value = '';
                        }
                    });
                });

                // Lógica inicial para o campo de número do computador ao carregar ou mudar o tipo principal
                const anyLocalDetalheSelected = document.querySelector('input[name="local_detalhe"]:checked');
                if (tipoSelecionado === 'Laboratório' || tipoSelecionado === 'Carrinho' || (tipoSelecionado === 'Sala' && anyLocalDetalheSelected && anyLocalDetalheSelected.value === 'Sala Maker')) {
                    numeroComputadorContainer.style.display = 'block';
                    numeroComputadorInput.setAttribute('required', 'required');
                    if (tipoSelecionado === 'Laboratório') {
                        numeroComputadorInput.placeholder = "1-20 ou 'prof'";
                    } else if (tipoSelecionado === 'Carrinho') {
                        numeroComputadorInput.placeholder = "1-30";
                    } else if (tipoSelecionado === 'Sala' && anyLocalDetalheSelected && anyLocalDetalheSelected.value === 'Sala Maker') {
                        numeroComputadorInput.placeholder = "1-15";
                    }
                } else {
                    numeroComputadorContainer.style.display = 'none';
                    numeroComputadorInput.removeAttribute('required');
                    numeroComputadorInput.value = '';
                }
            }

            // Adiciona o evento de mudança ao select de tipo de local
            localTipoSelect.addEventListener('change', updateConditionalFields);

            // Chama a função uma vez ao carregar a página para configurar o estado inicial
            updateConditionalFields();
        });
    </script>
</body>
</html>
