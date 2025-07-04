<?php
// admin/get_chamados.php - Endpoint AJAX para Chamados
// Este script é chamado via AJAX para retornar os dados dos chamados filtrados em HTML.
// ATUALIZADO: Equipamentos afetados agora são exibidos com espaço após a vírgula.
// ATUALIZADO: Botões de ação substituídos por ícones com menu suspenso para status.
// ATUALIZADO: Código JavaScript para manipulação de eventos movido para dashboard.php.
// ATUALIZADO: Removido 'min-w-full' e 'overflow-hidden' da tag <table> para permitir rolagem horizontal e fixar larguras.
// ATUALIZADO: Data de envio formatada para o padrão brasileiro (DD/MM/AAAA HH:MM).
// ATUALIZADO: Adicionado tooltips para conteúdo truncado nas células da tabela.
// ATUALIZADO: Adicionado botão de 'info' para abrir modal com detalhes.
// ATUALIZADO: Implementado paginação de 10 em 10 chamados.

// Inclui o arquivo de conexão com o banco de dados.
require_once '../conexao.php'; // Caminho ajustado para acessar conexao.php na pasta pai

// Variáveis para os filtros (recebidos via GET)
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_local_tipo = isset($_GET['local_tipo']) ? $_GET['local_tipo'] : '';
$filtro_local_detalhe = isset($_GET['local_detalhe']) ? $_GET['local_detalhe'] : '';
$busca_nome_professor = isset($_GET['busca_nome_professor']) ? trim($_GET['busca_nome_professor']) : '';

// Parâmetros de paginação
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = ($page - 1) * $limit;

// Constrói a query SQL base para a contagem total (sem LIMIT/OFFSET)
$sql_count = "SELECT COUNT(*) FROM chamados WHERE 1=1";
$params_count = [];
$types_count = "";

// Constrói a query SQL base para os dados paginados
$sql_data = "SELECT id, nome_professor, local_tipo, local_detalhe, numero_computador, equipamentos_afetados, descricao, status, data_envio FROM chamados WHERE 1=1";
$params_data = [];
$types_data = "";

// Adiciona filtros às queries
if (!empty($filtro_status)) {
    $sql_count .= " AND status = ?";
    $params_count[] = $filtro_status;
    $types_count .= "s";

    $sql_data .= " AND status = ?";
    $params_data[] = $filtro_status;
    $types_data .= "s";
}
if (!empty($filtro_local_tipo)) {
    $sql_count .= " AND local_tipo = ?";
    $params_count[] = $filtro_local_tipo;
    $types_count .= "s";

    $sql_data .= " AND local_tipo = ?";
    $params_data[] = $filtro_local_tipo;
    $types_data .= "s";
}
// Adiciona filtro para detalhe do local, se não for vazio
if (!empty($filtro_local_detalhe)) {
    $sql_count .= " AND local_detalhe = ?";
    $params_count[] = $filtro_local_detalhe;
    $types_count .= "s";

    $sql_data .= " AND local_detalhe = ?";
    $params_data[] = $filtro_local_detalhe;
    $types_data .= "s";
}
if (!empty($busca_nome_professor)) {
    $sql_count .= " AND nome_professor LIKE ?";
    $params_count[] = "%" . $busca_nome_professor . "%";
    $types_count .= "s";

    $sql_data .= " AND nome_professor LIKE ?";
    $params_data[] = "%" . $busca_nome_professor . "%";
    $types_data .= "s";
}

// Opcional: Adicionar ORDER BY para ordenar os chamados
$sql_data .= " ORDER BY data_envio DESC";


// --- Obter o total de registros antes da paginação ---
$totalRecords = 0;
if ($stmt_count = $conexao->prepare($sql_count)) {
    if (!empty($params_count)) {
        $stmt_count->bind_param($types_count, ...$params_count);
    }
    if ($stmt_count->execute()) {
        $stmt_count->bind_result($totalRecords);
        $stmt_count->fetch();
    }
    $stmt_count->close();
}

// Adiciona LIMIT e OFFSET à query de dados
$sql_data .= " LIMIT ? OFFSET ?";
$params_data[] = $limit;
$types_data .= "i";
$params_data[] = $offset;
$types_data .= "i";

$chamados = []; // Inicializa $chamados como um array vazio
// Prepara a declaração SQL para os dados paginados
if ($stmt_data = $conexao->prepare($sql_data)) {
    // Vincula os parâmetros, se houver
    if (!empty($params_data)) {
        $stmt_data->bind_param($types_data, ...$params_data);
    }

    // Executa a declaração
    if ($stmt_data->execute()) {
        $result = $stmt_data->get_result();
        $chamados = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        // Em caso de erro na query, retorne uma mensagem de erro HTML
        echo '<p class="text-center text-red-500 text-lg mt-10">Erro ao consultar o banco de dados: ' . htmlspecialchars($stmt_data->error) . '</p>';
    }
    $stmt_data->close();
} else {
    // Em caso de erro na preparação, retorne uma mensagem de erro HTML
    echo '<p class="text-center text-red-500 text-lg mt-10">Erro na preparação da consulta: ' . htmlspecialchars($conexao->error) . '</p>';
}

// Fecha a conexão com o banco de dados.
$conexao->close();

// Inicia a renderização do HTML da tabela (apenas o corpo da tabela)
?>
<input type="hidden" id="totalRecords" value="<?php echo $totalRecords; ?>">
<?php if (!empty($chamados)): ?>
<div class="overflow-x-auto">
    <table class="bg-white" style="table-layout: fixed; width: 100%;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Professor</th>
                <th>Local</th>
                <th>Nº PC</th>
                <th>Equipamentos</th>
                <th>Descrição</th>
                <th>Status</th>
                <th>Abertura</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($chamados as $chamado):
                $display_local_detalhe = ($chamado['local_detalhe'] === 'N/A' && $chamado['local_tipo'] === 'Carrinho') ?
                                          htmlspecialchars($chamado['local_tipo']) :
                                          htmlspecialchars($chamado['local_detalhe']);
                
                $display_equipamentos = str_replace(',', ', ', htmlspecialchars($chamado['equipamentos_afetados']));
                $data_envio_formatada = (new DateTime($chamado['data_envio']))->format('d/m/Y H:i');
            ?>
            <tr>
                <td><div class="truncate-content" title="<?php echo htmlspecialchars($chamado['id']); ?>"><?php echo htmlspecialchars($chamado['id']); ?></div></td>
                <td><div class="truncate-content" title="<?php echo htmlspecialchars($chamado['nome_professor']); ?>"><?php echo htmlspecialchars($chamado['nome_professor']); ?></div></td>
                <td><div class="truncate-content" title="<?php echo $display_local_detalhe; ?>"><?php echo $display_local_detalhe; ?></div></td>
                <td><div class="truncate-content" title="<?php echo htmlspecialchars($chamado['numero_computador']); ?>"><?php echo htmlspecialchars($chamado['numero_computador']); ?></div></td>
                <td><div class="truncate-content" title="<?php echo $display_equipamentos; ?>"><?php echo $display_equipamentos; ?></div></td>
                <td><div class="truncate-content" title="<?php echo htmlspecialchars($chamado['descricao']); ?>"><?php echo htmlspecialchars($chamado['descricao']); ?></div></td>
                <td>
                    <div class="truncate-content" title="<?php echo htmlspecialchars($chamado['status']); ?>">
                        <span class="status-badge status-<?php echo str_replace(' ', '-', htmlspecialchars($chamado['status'])); ?>">
                            <?php echo htmlspecialchars($chamado['status']); ?>
                        </span>
                    </div>
                </td>
                <td><div class="truncate-content" title="<?php echo $data_envio_formatada; ?>"><?php echo $data_envio_formatada; ?></div></td>
                <td class="text-center relative">
                    <div class="flex justify-center items-center gap-2">
                        <button type="button" class="action-button info-btn"
                                data-id="<?php echo htmlspecialchars($chamado['id']); ?>"
                                data-professor="<?php echo htmlspecialchars($chamado['nome_professor']); ?>"
                                data-local-tipo="<?php echo htmlspecialchars($chamado['local_tipo']); ?>"
                                data-local-detalhe="<?php echo htmlspecialchars($chamado['local_detalhe']); ?>"
                                data-numero-computador="<?php echo htmlspecialchars($chamado['numero_computador']); ?>"
                                data-equipamentos="<?php echo htmlspecialchars($chamado['equipamentos_afetados']); ?>"
                                data-descricao="<?php echo htmlspecialchars($chamado['descricao']); ?>"
                                data-status="<?php echo htmlspecialchars($chamado['status']); ?>"
                                data-data-envio="<?php echo $data_envio_formatada; ?>">
                            <i class="fas fa-info-circle"></i>
                        </button>
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