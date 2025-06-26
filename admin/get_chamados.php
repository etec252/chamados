<?php
// admin/get_chamados.php - Endpoint AJAX para Chamados
// Este script é chamado via AJAX para retornar os dados dos chamados filtrados em HTML.
// ATUALIZADO: Equipamentos afetados agora são exibidos com espaço após a vírgula.
// ATUALIZADO: Botões de ação substituídos por ícones com menu suspenso para status.
// ATUALIZADO: Código JavaScript para manipulação de eventos movido para dashboard.php.
// ATUALIZADO: Removido 'min-w-full' e 'overflow-hidden' da tag <table> para permitir rolagem horizontal e fixar larguras.
// ATUALIZADO: Data de envio formatada para o padrão brasileiro (DD/MM/AAAA HH:MM).

// Inclui o arquivo de conexão com o banco de dados.
require_once '../conexao.php'; // Caminho ajustado para acessar conexao.php na pasta pai

// Variáveis para os filtros (recebidos via GET)
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_local_tipo = isset($_GET['local_tipo']) ? $_GET['local_tipo'] : '';
$filtro_local_detalhe = isset($_GET['local_detalhe']) ? $_GET['local_detalhe'] : '';
$busca_nome_professor = isset($_GET['busca_nome_professor']) ? trim($_GET['busca_nome_professor']) : '';

// Constrói a query SQL base
$sql = "SELECT id, nome_professor, local_tipo, local_detalhe, numero_computador, equipamentos_afetados, descricao, status, data_envio FROM chamados WHERE 1=1";
$params = [];
$types = "";

// Adiciona filtros à query
if (!empty($filtro_status)) {
    $sql .= " AND status = ?";
    $params[] = $filtro_status;
    $types .= "s";
}
if (!empty($filtro_local_tipo)) {
    $sql .= " AND local_tipo = ?";
    $params[] = $filtro_local_tipo;
    $types .= "s";
}
// Adiciona filtro para detalhe do local, se não for vazio
if (!empty($filtro_local_detalhe)) {
    $sql .= " AND local_detalhe = ?";
    $params[] = $filtro_local_detalhe;
    $types .= "s";
}
if (!empty($busca_nome_professor)) {
    $sql .= " AND nome_professor LIKE ?";
    // Adiciona curingas para a busca por parte do nome
    $params[] = "%" . $busca_nome_professor . "%";
    $types .= "s";
}

// Opcional: Adicionar ORDER BY para ordenar os chamados
$sql .= " ORDER BY data_envio DESC";


// Prepara a declaração SQL
if ($stmt = $conexao->prepare($sql)) {
    // Vincula os parâmetros, se houver
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    // Executa a declaração
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $chamados = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        // Em caso de erro na query, retorne uma mensagem de erro HTML
        echo '<p class="text-center text-red-500 text-lg mt-10">Erro ao consultar o banco de dados: ' . htmlspecialchars($stmt->error) . '</p>';
        $chamados = []; // Garante que $chamados seja um array vazio
    }
    $stmt->close();
} else {
    // Em caso de erro na preparação, retorne uma mensagem de erro HTML
    echo '<p class="text-center text-red-500 text-lg mt-10">Erro na preparação da consulta: ' . htmlspecialchars($conexao->error) . '</p>';
    $chamados = []; // Garante que $chamados seja um array vazio
}

// Fecha a conexão com o banco de dados.
$conexao->close();

// Inicia a renderização do HTML da tabela (apenas o corpo da tabela)
if (!empty($chamados)): ?>
    <div class="overflow-x-auto">
        <table class="bg-white">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Professor</th>
                    <th>Local</th>
                    <th>Nº Computador</th>
                    <th>Equipamentos</th>
                    <th>Descrição</th>
                    <th>Status</th>
                    <th>Envio</th>
                    <th class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($chamados as $chamado):
                    // Determina o valor a ser exibido para 'Detalhe Local'
                    // Se o local_detalhe for 'N/A' E o local_tipo for 'Carrinho', exibe 'Carrinho'
                    // Caso contrário, exibe o local_detalhe original.
                    $display_local_detalhe = ($chamado['local_detalhe'] === 'N/A' && $chamado['local_tipo'] === 'Carrinho') ?
                                              htmlspecialchars($chamado['local_tipo']) :
                                              htmlspecialchars($chamado['local_detalhe']);
                    
                    // Adiciona um espaço após a vírgula para os equipamentos afetados
                    $display_equipamentos = str_replace(',', ', ', htmlspecialchars($chamado['equipamentos_afetados']));

                    // Formata a data de envio para o padrão brasileiro DD/MM/AAAA HH:MM
                    $data_envio_formatada = (new DateTime($chamado['data_envio']))->format('d/m/Y H:i');
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($chamado['id']); ?></td>
                    <td><?php echo htmlspecialchars($chamado['nome_professor']); ?></td>
                    <td><?php echo $display_local_detalhe; ?></td>
                    <td><?php echo htmlspecialchars($chamado['numero_computador']); ?></td>
                    <td><?php echo $display_equipamentos; ?></td> <td><?php echo htmlspecialchars($chamado['descricao']); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo str_replace(' ', '-', htmlspecialchars($chamado['status'])); ?>">
                            <?php echo htmlspecialchars($chamado['status']); ?>
                        </span>
                    </td>
                    <td><?php echo $data_envio_formatada; ?></td> <td class="text-center relative"> <div class="flex justify-center items-center gap-2">
                            <button type="button" class="action-button edit-status-btn" data-id="<?php echo htmlspecialchars($chamado['id']); ?>">
                                <i class="fas fa-edit"></i>
                            </button>

                            <button type="button" class="action-button delete-btn" data-id="<?php echo htmlspecialchars($chamado['id']); ?>">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>

                        <div id="status-dropdown-<?php echo htmlspecialchars($chamado['id']); ?>" class="status-dropdown hidden">
                            <span class="status-option Pendente" data-id="<?php echo htmlspecialchars($chamado['id']); ?>" data-status="Pendente">Pendente</span>
                            <span class="status-option Em-andamento" data-id="<?php echo htmlspecialchars($chamado['id']); ?>" data-status="Em andamento">Em andamento</span>
                            <span class="status-option Resolvido" data-id="<?php echo htmlspecialchars($chamado['id']); ?>" data-status="Resolvido">Resolvido</span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="text-center text-gray-600 text-lg mt-10">Nenhum chamado encontrado com os filtros aplicados.</p>
<?php endif; ?>