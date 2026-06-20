<?php include 'partials/html.php' ?>

<?php
#======================================#
ini_set('display_errors', 0);
error_reporting(E_ALL);
#======================================#
session_start();
include_once "services/database.php";
include_once 'logs/registrar_logs.php';
include_once "services/funcao.php";
include_once "services/crud.php";
include_once "services/crud-adm.php";
include_once 'services/checa_login_adm.php';
include_once "services/CSRF_Protect.php";
$csrf = new CSRF_Protect();
#======================================#
#expulsa user
checa_login_adm();
#======================================#

# Função para buscar todos os provedores únicos
function get_providers()
{
    global $mysqli;
    $qry = "SELECT DISTINCT provider FROM games WHERE provider IS NOT NULL AND provider != '' ORDER BY provider ASC";
    $result = mysqli_query($mysqli, $qry);
    $providers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $providers[] = $row['provider'];
    }
    return $providers;
}

# Função para buscar todos os jogos da tabela games com paginação, pesquisa e filtros
function get_games($limit, $offset, $search = '', $provider = '', $popular = '')
{
    global $mysqli;
    $search = $mysqli->real_escape_string($search);
    $provider = $mysqli->real_escape_string($provider);
    
    $qry = "SELECT * FROM games WHERE game_name LIKE '%$search%'";
    
    if ($provider !== '') {
        $qry .= " AND provider = '$provider'";
    }
    
    if ($popular !== '') {
        $qry .= " AND popular = " . intval($popular);
    }
    
    $qry .= " LIMIT $limit OFFSET $offset";
    
    $result = mysqli_query($mysqli, $qry);
    $games = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $games[] = $row;
    }
    return $games;
}

# Função para contar o total de jogos com pesquisa e filtros
function count_games($search = '', $provider = '', $popular = '')
{
    global $mysqli;
    $search = $mysqli->real_escape_string($search);
    $provider = $mysqli->real_escape_string($provider);
    
    $qry = "SELECT COUNT(*) as total FROM games WHERE game_name LIKE '%$search%'";
    
    if ($provider !== '') {
        $qry .= " AND provider = '$provider'";
    }
    
    if ($popular !== '') {
        $qry .= " AND popular = " . intval($popular);
    }
    
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result)['total'];
}

# Função para atualizar os dados do jogo
function update_game($data)
{
    global $mysqli;

    $qry = $mysqli->prepare("UPDATE games SET 
        game_code = ?, 
        game_name = ?, 
        banner = ?, 
        status = ?, 
        provider = ?, 
        popular = ?, 
        type = ?, 
        game_type = ? 
        WHERE id = ?");

    if (!$qry) {
        error_log("Erro ao preparar a query: " . $mysqli->error);
        return false;
    }

    $qry->bind_param("sssisssii",
        $data['game_code'],
        $data['game_name'],
        $data['banner'],
        $data['status'],
        $data['provider'],
        $data['popular'],
        $data['type'],
        $data['game_type'],
        $data['id']
    );

    return $qry->execute();
}

# Função para excluir um jogo
function delete_game($id)
{
    global $mysqli;
    $qry = $mysqli->prepare("DELETE FROM games WHERE id = ?");
    
    if (!$qry) {
        error_log("Erro ao preparar a query: " . $mysqli->error);
        return false;
    }
    
    $qry->bind_param("i", $id);
    return $qry->execute();
}

# Função para excluir múltiplos jogos
function delete_multiple_games($ids)
{
    global $mysqli;
    
    if (empty($ids)) {
        return false;
    }
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $qry = $mysqli->prepare("DELETE FROM games WHERE id IN ($placeholders)");
    
    if (!$qry) {
        error_log("Erro ao preparar a query: " . $mysqli->error);
        return false;
    }
    
    $types = str_repeat('i', count($ids));
    $qry->bind_param($types, ...$ids);
    
    return $qry->execute();
}

# Se o formulário for enviado, atualizar os dados do jogo
$toastType = null;
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar se é uma exclusão em massa
    if (isset($_POST['action']) && $_POST['action'] === 'delete_multiple') {
        if (isset($_POST['game_ids']) && is_array($_POST['game_ids']) && !empty($_POST['game_ids'])) {
            $ids = array_map('intval', $_POST['game_ids']);
            $count = count($ids);
            
            if (delete_multiple_games($ids)) {
                $toastType = 'success';
                $toastMessage = "$count jogo(s) excluído(s) com sucesso!";
            } else {
                $toastType = 'error';
                $toastMessage = 'Erro ao excluir os jogos. Tente novamente.';
            }
        } else {
            $toastType = 'error';
            $toastMessage = 'Nenhum jogo selecionado para exclusão.';
        }
    }
    // Verificar se é uma exclusão individual
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        
        if (delete_game($id)) {
            $toastType = 'success';
            $toastMessage = 'Jogo excluído com sucesso!';
        } else {
            $toastType = 'error';
            $toastMessage = 'Erro ao excluir o jogo. Tente novamente.';
        }
    } 
    // Caso contrário, é uma atualização
    else {
        $data = [
            'id' => intval($_POST['id']),
            'game_code' => $_POST['game_code'],
            'game_name' => $_POST['game_name'],
            'banner' => $_POST['banner'],
            'status' => intval($_POST['status']),
            'provider' => $_POST['provider'],
            'popular' => intval($_POST['popular']),
            'type' => $_POST['type'],
            'game_type' => $_POST['game_type'],
        ];

        if (update_game($data)) {
            $toastType = 'success';
            $toastMessage = 'Jogo atualizado com sucesso!';
        } else {
            $toastType = 'error';
            $toastMessage = 'Erro ao atualizar o jogo. Tente novamente.';
        }
    }
}

# Configurações de paginação, pesquisa e filtros
$search = isset($_GET['search']) ? $_GET['search'] : '';
$provider = isset($_GET['provider']) ? $_GET['provider'] : '';
$popular = isset($_GET['popular']) ? $_GET['popular'] : '';
$limit = 50;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$total_games = count_games($search, $provider, $popular);
$total_pages = ceil($total_games / $limit);

# Buscar jogos com filtros
$games = get_games($limit, $offset, $search, $provider, $popular);
$providers = get_providers();
?>

<head>
    <?php $title = "Gerenciamento de Jogos";
    include 'partials/title-meta.php' ?>

    <link rel="stylesheet" href="assets/libs/jsvectormap/jsvectormap.min.css">
    <?php include 'partials/head-css.php' ?>
</head>

<body>

    <!-- Top Bar Start -->
    <?php include 'partials/topbar.php' ?>
    <!-- Top Bar End -->
    <!-- leftbar-tab-menu -->
    <?php include 'partials/startbar.php' ?>
    <!-- end leftbar-tab-menu-->

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Gerenciamento de Jogos</h4>
                            </div>

                            <div class="card-body">
                                <!-- Formulário de Pesquisa e Filtros -->
                                <form method="GET" action="" class="mb-3">
                                    <div class="row g-2">
                                        <div class="col-md-5">
                                            <input type="text" name="search" class="form-control"
                                                placeholder="Pesquisar pelo nome do jogo"
                                                value="<?= htmlspecialchars($search) ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <select name="provider" class="form-select">
                                                <option value="">Todos os Provedores</option>
                                                <?php foreach ($providers as $prov): ?>
                                                    <option value="<?= htmlspecialchars($prov) ?>" 
                                                        <?= $provider === $prov ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($prov) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <select name="popular" class="form-select">
                                                <option value="">Todos</option>
                                                <option value="1" <?= $popular === '1' ? 'selected' : '' ?>>Populares</option>
                                                <option value="0" <?= $popular === '0' ? 'selected' : '' ?>>Não Populares</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-primary w-100" type="submit">
                                                <i class="fas fa-search"></i> Buscar
                                            </button>
                                        </div>
                                    </div>
                                    <?php if ($search || $provider || $popular !== ''): ?>
                                        <div class="mt-2">
                                            <a href="?" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-times"></i> Limpar Filtros
                                            </a>
                                            <span class="ms-2 text-muted">
                                                Exibindo <?= count($games) ?> de <?= $total_games ?> jogos
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </form>

                                <!-- Barra de Ações em Massa -->
                                <div class="mb-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <button type="button" class="btn btn-danger" id="deleteSelectedBtn" disabled>
                                            <i class="fas fa-trash-alt me-1"></i>
                                            Excluir Selecionados (<span id="selectedCount">0</span>)
                                        </button>
                                    </div>
                                    <div>
                                        <span class="text-muted">Total: <?= $total_games ?> jogos</span>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th style="width: 40px;">
                                                    <input type="checkbox" class="form-check-input" id="selectAll">
                                                </th>
                                                <th>Banner</th>
                                                <th>ID</th>
                                                <th>Código</th>
                                                <th>Nome</th>
                                                <th>Status</th>
                                                <th>Provider</th>
                                                <th>Popular</th>
                                                <th>Tipo</th>
                                                <th>Game Type</th>
                                                <th>Ação</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($games) > 0): ?>
                                                <?php foreach ($games as $game): ?>
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" class="form-check-input game-checkbox" 
                                                                value="<?= $game['id'] ?>">
                                                        </td>
                                                        <td>
                                                            <img src="<?= $game['banner'] ?>" alt="Banner" class="rounded-circle"
                                                                style="width: 50px; height: 50px;" loading="lazy">
                                                        </td>
                                                        <td><?= $game['id'] ?></td>
                                                        <td><?= htmlspecialchars($game['game_code']) ?></td>
                                                        <td><?= htmlspecialchars($game['game_name']) ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= $game['status'] == 1 ? 'success' : 'danger' ?>">
                                                                <?= $game['status'] == 1 ? 'Ativo' : 'Inativo' ?>
                                                            </span>
                                                        </td>
                                                        <td><?= htmlspecialchars($game['provider']) ?></td>
                                                        <td>
                                                            <?php if ($game['popular'] == 1): ?>
                                                                <span class="badge bg-warning">★ Popular</span>
                                                            <?php else: ?>
                                                                <span class="text-muted">Não</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($game['type']) ?></td>
                                                        <td><?= htmlspecialchars($game['game_type']) ?></td>
                                                        <td>
                                                            <button class="btn btn-primary btn-sm edit-game-btn" 
                                                                data-id="<?= $game['id'] ?>"
                                                                data-code="<?= htmlspecialchars($game['game_code']) ?>"
                                                                data-name="<?= htmlspecialchars($game['game_name']) ?>"
                                                                data-banner="<?= htmlspecialchars($game['banner']) ?>"
                                                                data-status="<?= $game['status'] ?>"
                                                                data-provider="<?= htmlspecialchars($game['provider']) ?>"
                                                                data-popular="<?= $game['popular'] ?>"
                                                                data-type="<?= htmlspecialchars($game['type']) ?>"
                                                                data-gametype="<?= htmlspecialchars($game['game_type']) ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-danger btn-sm delete-game-btn" 
                                                                data-id="<?= $game['id'] ?>"
                                                                data-name="<?= htmlspecialchars($game['game_name']) ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="11" class="text-center py-4">
                                                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                                        <p class="text-muted">Nenhum jogo encontrado com os filtros aplicados.</p>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Paginação -->
                                <?php if ($total_pages > 1): ?>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&provider=<?= urlencode($provider) ?>&popular=<?= urlencode($popular) ?>">Anterior</a>
                                                </li>
                                            <?php endif; ?>

                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&provider=<?= urlencode($provider) ?>&popular=<?= urlencode($popular) ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>

                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&provider=<?= urlencode($provider) ?>&popular=<?= urlencode($popular) ?>">Próximo</a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Único de Edição -->
    <div class="modal fade" id="editGameModal" tabindex="-1" aria-labelledby="editGameModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editGameModalLabel">Editar Jogo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="game_code" class="form-label">Código do Jogo</label>
                            <input type="text" name="game_code" id="edit_game_code" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="game_name" class="form-label">Nome do Jogo</label>
                            <input type="text" name="game_name" id="edit_game_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="banner" class="form-label">Banner</label>
                            <input type="text" name="banner" id="edit_banner" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="provider" class="form-label">Provider</label>
                            <input type="text" name="provider" id="edit_provider" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="popular" class="form-label">Popular</label>
                            <select name="popular" id="edit_popular" class="form-select" required>
                                <option value="1">Sim</option>
                                <option value="0">Não</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">API</label>
                            <select name="type" id="edit_type" class="form-select" required>
                                <option value="DEFAULT">Padrão (API de 10)</option>
                                <option value="PLAYFIVER">PLAYFIVER</option>
                                <option value="API30">API16</option>
                                <option value="DRAKON">DRAKON</option>
                                <option value="ROYAL">ROYAL</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="game_type" class="form-label">Game Type</label>
                            <input type="text" name="game_type" id="edit_game_type" class="form-control" required>
                        </div>
                        <input type="hidden" name="id" id="edit_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-primary">Salvar alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="deleteGameModal" tabindex="-1" aria-labelledby="deleteGameModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteGameModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Exclusão
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <p class="mb-0">Tem certeza que deseja excluir o jogo:</p>
                        <h5 class="mt-2 mb-3 text-danger" id="delete_game_name"></h5>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Atenção!</strong> Esta ação não pode ser desfeita.
                        </div>
                        <input type="hidden" name="id" id="delete_id">
                        <input type="hidden" name="action" value="delete">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Sim, Excluir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão em Massa -->
    <div class="modal fade" id="deleteMultipleModal" tabindex="-1" aria-labelledby="deleteMultipleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteMultipleModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Exclusão em Massa
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" id="deleteMultipleForm">
                    <div class="modal-body">
                        <p class="mb-2">Tem certeza que deseja excluir os jogos selecionados?</p>
                        <h4 class="text-danger mb-3">
                            <i class="fas fa-trash-alt me-2"></i>
                            <span id="deleteMultipleCount">0</span> jogo(s) selecionado(s)
                        </h4>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>ATENÇÃO!</strong> Esta ação irá excluir permanentemente todos os jogos selecionados e não pode ser desfeita.
                        </div>
                        <input type="hidden" name="action" value="delete_multiple">
                        <div id="deleteMultipleIds"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-1"></i>Sim, Excluir Todos
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast container -->
    <div id="toastPlacement" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <!-- Javascript -->
    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editButtons = document.querySelectorAll('.edit-game-btn');
            const deleteButtons = document.querySelectorAll('.delete-game-btn');
            const editModal = new bootstrap.Modal(document.getElementById('editGameModal'));
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteGameModal'));
            const deleteMultipleModal = new bootstrap.Modal(document.getElementById('deleteMultipleModal'));
            
            const selectAllCheckbox = document.getElementById('selectAll');
            const gameCheckboxes = document.querySelectorAll('.game-checkbox');
            const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
            const selectedCountSpan = document.getElementById('selectedCount');
            
            // Modal de Edição
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('edit_id').value = this.dataset.id;
                    document.getElementById('edit_game_code').value = this.dataset.code;
                    document.getElementById('edit_game_name').value = this.dataset.name;
                    document.getElementById('edit_banner').value = this.dataset.banner;
                    document.getElementById('edit_status').value = this.dataset.status;
                    document.getElementById('edit_provider').value = this.dataset.provider;
                    document.getElementById('edit_popular').value = this.dataset.popular;
                    document.getElementById('edit_type').value = this.dataset.type;
                    document.getElementById('edit_game_type').value = this.dataset.gametype;
                    
                    editModal.show();
                });
            });

            // Modal de Exclusão Individual
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('delete_id').value = this.dataset.id;
                    document.getElementById('delete_game_name').textContent = this.dataset.name;
                    
                    deleteModal.show();
                });
            });

            // Selecionar/Desselecionar Todos
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    gameCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                    updateSelectedCount();
                });
            }

            // Atualizar contagem quando checkboxes individuais mudam
            gameCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateSelectedCount();
                    
                    const allChecked = Array.from(gameCheckboxes).every(cb => cb.checked);
                    const someChecked = Array.from(gameCheckboxes).some(cb => cb.checked);
                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = allChecked;
                        selectAllCheckbox.indeterminate = someChecked && !allChecked;
                    }
                });
            });

            // Função para atualizar a contagem de selecionados
            function updateSelectedCount() {
                const selectedCheckboxes = document.querySelectorAll('.game-checkbox:checked');
                const count = selectedCheckboxes.length;
                
                if (selectedCountSpan) {
                    selectedCountSpan.textContent = count;
                }
                
                if (deleteSelectedBtn) {
                    deleteSelectedBtn.disabled = count === 0;
                }
            }

            // Botão de Exclusão em Massa
            if (deleteSelectedBtn) {
                deleteSelectedBtn.addEventListener('click', function() {
                    const selectedCheckboxes = document.querySelectorAll('.game-checkbox:checked');
                    const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);
                    
                    if (selectedIds.length === 0) {
                        return;
                    }
                    
                    document.getElementById('deleteMultipleCount').textContent = selectedIds.length;
                    
                    const idsContainer = document.getElementById('deleteMultipleIds');
                    idsContainer.innerHTML = '';
                    selectedIds.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'game_ids[]';
                        input.value = id;
                        idsContainer.appendChild(input);
                    });
                    
                    deleteMultipleModal.show();
                });
            }
        });

        function showToast(type, message) {
            var toastPlacement = document.getElementById('toastPlacement');
            var toast = document.createElement('div');
            toast.className = 'toast align-items-center bg-light border-0 fade show';
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
                <div class="toast-header">
                    <img src="assets/images/logo-sm.png" alt="" height="20" class="me-1">
                    <h5 class="me-auto my-0">ROYALGAMES</h5>
                    <small>Agora</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">${message}</div>
            `;
            toastPlacement.appendChild(toast);

            var bootstrapToast = new bootstrap.Toast(toast);
            bootstrapToast.show();

            setTimeout(function () {
                bootstrapToast.hide();
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }
    </script>

    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>

</html>