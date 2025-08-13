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
// ATUALIZADO: Implementação da lógica de paginação (LIMIT e OFFSET).
// CORRIGIDO: Aviso "Argument #2 must be passed by reference" para mysqli_stmt::bind_param.
// NOVO: A edição de status agora é feita através de um modal, removendo o dropdown na tabela.
// NOVO: O botão de edição de status foi movido de volta para a coluna 'Ações'.

// Inclui o arquivo de conexão com o banco de dados.
require_once '../conexao.php'; // Caminho ajustado para acessar conexao.php na pasta pai

// Variáveis para os filtros (recebidos via GET)
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$filtro_local_tipo = isset($_GET['local_tipo']) ? $_GET['local_tipo'] : '';
$filtro_local_detalhe = isset($_GET['local_detalhe']) ? $_GET['local_detalhe'] : '';
$busca_nome_professor = isset($_GET['busca_nome_professor']) ? trim($_GET['busca_nome_professor']) : '';

// Parâmetros de Paginação
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10; // Default de 10 registros por página
$offset = ($page - 1) * $limit;

// Variáveis de Ordenação (recebidas via GET)
$sort_column = isset($_GET['sort_column']) ? $_GET['sort_column'] : 'data_envio';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';
$allowed_columns = ['id', 'nome_professor', 'status', 'data_envio']; // Colunas permitidas para ordenação
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'data_envio';
}
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

// Constrói a query SQL base para CONTAR o total de registros (sem LIMIT e OFFSET)
$sql_count = "SELECT COUNT(*) AS total FROM chamados WHERE 1=1";
$params_count = [];
$types_count = "";

// Adiciona filtros à query de contagem
if (!empty($filtro_status)) {
    $sql_count .= " AND status = ?";
    $params_count[] = $filtro_status;
    $types_count .= "s";
}
if (!empty($filtro_local_tipo)) {
    $sql_count .= " AND local_tipo = ?";
    $params_count[] = $filtro_local_tipo;
    $types_count .= "s";
}
if (!empty($filtro_local_detalhe)) {
    $sql_count .= " AND local_detalhe = ?";
    $params_count[] = $filtro_local_detalhe;
    $types_count .= "s";
}
if (!empty($busca_nome_professor)) {
    $sql_count .= " AND nome_professor LIKE ?";
    $params_count[] = "%" . $busca_nome_professor . "%";
    $types_count .= "s";
}

$total_records = 0;
if ($stmt_count = $conexao->prepare($sql_count)) {
    if (!empty($params_count)) {
        // Correção para bind_param: passar referências
        $bind_args_count = [];
        $bind_args_count[] = $types_count;
        foreach ($params_count as $key => $value) {
            $bind_args_count[] = &$params_count[$key]; // Passar por referência
        }
        call_user_func_array([$stmt_count, 'bind_param'], $bind_args_count);
    }
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $row_count = $result_count->fetch_assoc();
    $total_records = $row_count['total'];
    $stmt_count->close();
}


// Constrói a query SQL base para buscar os chamados (com LIMIT e OFFSET)
$sql = "SELECT id, nome_professor, local_tipo, local_detalhe, numero_computador, equipamentos_afetados, descricao, status, data_envio FROM chamados WHERE 1=1";
$params = [];
$types = "";

// Adiciona filtros à query principal
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
if (!empty($filtro_local_detalhe)) {
    $sql .= " AND local_detalhe = ?";
    $params[] = $filtro_local_detalhe;
    $types .= "s";
}
if (!empty($busca_nome_professor)) {
    $sql .= " AND nome_professor LIKE ?";
    $params[] = "%" . $busca_nome_professor . "%";
    $types .= "s";
}

// Adiciona ORDER BY e LIMIT/OFFSET
$sql .= " ORDER BY " . $sort_column . " " . $sort_order . " LIMIT ? OFFSET ?";
$params[] = $limit;
$types .= "ii"; // 'i' para inteiro para LIMIT e OFFSET
$params[] = $offset;

// Prepara a declaração SQL
if ($stmt = $conexao->prepare($sql)) {
    // Vincula os parâmetros
    if (!empty($params)) {
        // Correção para bind_param: passar referências
        $bind_args = [];
        $bind_args[] = $types;
        foreach ($params as $key => $value) {
            $bind_args[] = &$params[$key]; // Passar por referência
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_args);
    }

    // Executa a declaração
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $chamados = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        echo '<p class="text-center text-red-500 text-lg mt-10">Erro ao consultar o banco de dados: ' . htmlspecialchars($stmt->error) . '</p>';
        $chamados = [];
    }
    $stmt->close();
} else {
    echo '<p class="text-center text-red-500 text-lg mt-10">Erro na preparação da consulta: ' . htmlspecialchars($conexao->error) . '</p>';
    $chamados = [];
}

$conexao->close();

// Adiciona um campo hidden com o total de registros para o JavaScript
echo '<input type="hidden" id="totalRecords" value="' . $total_records . '">';

if (!empty($chamados)): ?>
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
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php else: ?>
    <p class="text-center text-gray-600 text-lg mt-10">Nenhum chamado encontrado com os filtros aplicados.</p>
<?php endif; ?>