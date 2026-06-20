<?php include 'partials/html.php' ?>

<head>
    <?php $title = "Phillyps"; ?>
    <?php include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
    <style>
        /* Estilos para facilitar adição de saldo */
        .quick-saldo-form {
            display: inline-flex;
            gap: 5px;
            align-items: center;
        }
        .quick-saldo-input {
            width: 100px;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .btn-quick {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .saldo-cell {
            min-width: 200px;
        }
        .success-animation {
            animation: successFlash 0.5s;
        }
        @keyframes successFlash {
            0% { background-color: transparent; }
            50% { background-color: #d4edda; }
            100% { background-color: transparent; }
        }
        .modal-saldo {
            position: fixed;
            top: 50px;
            right: 20px;
            width: 350px;
            z-index: 1050;
            display: none;
        }
    </style>
</head>

<body>
    <!-- Top Bar Start -->
    <?php include 'partials/topbar.php' ?>
    <!-- Top Bar End -->
    <!-- leftbar-tab-menu -->
    <?php include 'partials/startbar.php' ?>
    <!-- end leftbar-tab-menu-->

    <?php
    // Processar adição rápida de saldo via AJAX
    if (isset($_POST['quick_add_saldo'])) {
        $user_id = (int)$_POST['user_id'];
        $valor = (float)$_POST['valor'];
        $tipo = $_POST['tipo']; // 'adicionar' ou 'remover'
        
        if ($tipo == 'adicionar') {
            $query = "UPDATE usuarios SET saldo = saldo + $valor WHERE id = $user_id";
        } else {
            $query = "UPDATE usuarios SET saldo = saldo - $valor WHERE id = $user_id";
        }
        
        if (mysqli_query($mysqli, $query)) {
            // Registrar no log
            $log_query = "INSERT INTO logs_saldo (user_id, valor, tipo, admin_id, data) VALUES ($user_id, $valor, '$tipo', {$_SESSION['admin_id']}, NOW())";
            mysqli_query($mysqli, $log_query);
            
            echo json_encode(['success' => true, 'message' => 'Saldo atualizado com sucesso!']);
            exit;
        }
    }

    // Capturar os parâmetros de busca e filtro
    $search_query = '';
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search_query = mysqli_real_escape_string($mysqli, $_GET['search']);
    }

    $status_filter = '';
    if (isset($_GET['status']) && $_GET['status'] !== '') {
        $status_filter = (int) $_GET['status'];
    }

    // Configuração da paginação
    $limit = 50; // Limite de 50 usuários por página
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // Consulta para contar o total de usuários
    $query_total_usuarios = "SELECT COUNT(*) AS total_usuarios FROM usuarios WHERE 1=1";
    if (!empty($search_query)) {
        $query_total_usuarios .= " AND (id LIKE '%$search_query%' OR mobile LIKE '%$search_query%')";
    }
    if ($status_filter !== '') {
        $query_total_usuarios .= " AND statusaff = $status_filter";
    }
    $result_total_usuarios = mysqli_query($mysqli, $query_total_usuarios);
    $total_usuarios = mysqli_fetch_assoc($result_total_usuarios)['total_usuarios'];

    // Cálculo do total de páginas
    $total_pages = ceil($total_usuarios / $limit);

    // Consulta para exibir os usuários com paginação e filtro
    $query_usuarios = "SELECT * FROM usuarios WHERE 1=1";
    
    // Filtrar por busca (ID ou nome de usuário)
    if (!empty($search_query)) {
        $query_usuarios .= " AND (id LIKE '%$search_query%' OR mobile LIKE '%$search_query%')";
    }

    // Filtrar por status: Banido, Afiliado ou Usuário
    if ($status_filter !== '') {
        if ($status_filter == 2) {
            // Usuários banidos
            $query_usuarios .= " AND banido = 1";
        } else {
            // Outros status (Afiliado ou Usuário)
            $query_usuarios .= " AND statusaff = $status_filter";
        }
    }

    // Ordenar e paginar
    $query_usuarios .= " ORDER BY id DESC LIMIT $limit OFFSET $offset";
    $result_usuarios = mysqli_query($mysqli, $query_usuarios);
    ?>

    <div class="page-wrapper">
        <!-- Page Content-->
        <div class="page-content">
            <div class="container-xxl">
                
                <!-- Modal de Adição Rápida de Saldo -->
                <div class="modal fade" id="modalSaldoRapido" tabindex="-1">
                    <div class="modal-dialog modal-sm">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Adicionar Saldo Rápido</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="formSaldoRapido">
                                    <input type="hidden" id="modal_user_id" name="user_id">
                                    <div class="mb-3">
                                        <label class="form-label">Usuário: <span id="modal_username"></span></label>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Valor (R$)</label>
                                        <input type="number" class="form-control" id="modal_valor" name="valor" step="0.01" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Ação</label>
                                        <select class="form-select" id="modal_tipo" name="tipo">
                                            <option value="adicionar">Adicionar</option>
                                            <option value="remover">Remover</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Confirmar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-12 col-lg-12">
                        
                        <!-- Cards de Resumo Rápido -->
                        <div class="row mb-3">
                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h6 class="card-title text-white">Total Usuários</h6>
                                        <h3 class="mb-0"><?= number_format($total_usuarios, 0, ',', '.'); ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6 class="card-title text-white">Total Depositado</h6>
                                        <h3 class="mb-0">R$ <?= number_format(total_dep_pagos_usuarios(), 2, ',', '.'); ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h6 class="card-title text-white">Total Sacado</h6>
                                        <h3 class="mb-0">R$ <?= number_format(total_saques_usuarios(), 2, ',', '.'); ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-12">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h6 class="card-title text-white">Saldo Médio</h6>
                                        <h3 class="mb-0">R$ <?= number_format(media_saldo_usuarios(), 2, ',', '.'); ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h4 class="card-title">Todos Usuários</h4>
                                    </div>
                                    <div class="col text-end">
                                        <button class="btn btn-success me-2" onclick="toggleQuickSaldo()">
                                            <i class="las la-plus"></i> Modo Saldo Rápido
                                        </button>
                                        <a href="export/exportar_usuarios.php" class="btn btn-primary">
                                            <i class="las la-download"></i> Exportar Dados
                                        </a>
                                    </div>
                                </div>
                            </div><!--end card-header-->

                            <!-- Filtros e Busca -->
                            <div class="card-body pt-0">
                                <form method="GET" action="">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <input type="text" name="search" class="form-control"
                                                placeholder="Buscar por ID ou Nome do Usuário"
                                                value="<?= htmlspecialchars($search_query) ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <select name="status" class="form-select">
                                                <option value="">Filtrar por Status</option>
                                                <option value="2" <?= (isset($_GET['status']) && $_GET['status'] == '2') ? 'selected' : ''; ?>>Banido</option>
                                                <option value="1" <?= (isset($_GET['status']) && $_GET['status'] == '1') ? 'selected' : ''; ?>>Afiliado</option>
                                                <option value="0" <?= (isset($_GET['status']) && $_GET['status'] == '0') ? 'selected' : ''; ?>>Usuário</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <button type="submit" class="btn btn-success">
                                                <i class="las la-filter"></i> Filtrar
                                            </button>
                                            <a href="?" class="btn btn-secondary">
                                                <i class="las la-redo"></i> Limpar
                                            </a>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="table mb-0 table-centered table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Id</th>
                                                <th>Usuário</th>
                                                <th class="saldo-cell">Saldo / Ações Rápidas</th>
                                                <th>Depositado</th>
                                                <th>Sacado</th>
                                                <th>Cargo</th>
                                                <th class="text-end">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($result_usuarios && mysqli_num_rows($result_usuarios) > 0) {
                                                while ($usuario = mysqli_fetch_assoc($result_usuarios)) {
                                                    // Definir o cargo com base nos dados da tabela
                                                    $cargo_badge = ($usuario['statusaff'] == '1') ? "<span class='badge bg-danger'>Afiliado</span>" : "<span class='badge bg-secondary'>Usuário</span>";

                                                    // Consultar total sacado e depositado
                                                    $query_sacado = "SELECT SUM(valor) AS total_sacado FROM solicitacao_saques WHERE id_user = {$usuario['id']} AND status = 1";
                                                    $result_sacado = mysqli_query($mysqli, $query_sacado);
                                                    $sacado = ($result_sacado && mysqli_num_rows($result_sacado) > 0) ? mysqli_fetch_assoc($result_sacado)['total_sacado'] : 0;

                                                    $query_depositado = "SELECT SUM(valor) AS total_depositado FROM transacoes WHERE usuario = {$usuario['id']} AND status = 'pago'";
                                                    $result_depositado = mysqli_query($mysqli, $query_depositado);
                                                    $depositado = ($result_depositado && mysqli_num_rows($result_depositado) > 0) ? mysqli_fetch_assoc($result_depositado)['total_depositado'] : 0;
                                                    ?>
                                                    <tr id="user-row-<?= $usuario['id']; ?>">
                                                        <td><?= $usuario['id']; ?></td>
                                                        <td>
                                                            <strong><?= $usuario['mobile']; ?></strong>
                                                            <?php if($usuario['banido'] == 1): ?>
                                                                <span class="badge bg-dark ms-1">Banido</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="saldo-cell">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="fw-bold text-success" id="saldo-<?= $usuario['id']; ?>">
                                                                    R$ <?= number_format($usuario['saldo'], 2, ',', '.'); ?>
                                                                </span>
                                                                <div class="quick-saldo-form d-none">
                                                                    <input type="number" 
                                                                           class="form-control form-control-sm quick-saldo-input" 
                                                                           placeholder="0,00" 
                                                                           step="0.01"
                                                                           id="input-<?= $usuario['id']; ?>">
                                                                    <button class="btn btn-success btn-sm btn-quick" 
                                                                            onclick="addSaldo(<?= $usuario['id']; ?>, 'adicionar')">
                                                                        <i class="las la-plus"></i>
                                                                    </button>
                                                                    <button class="btn btn-danger btn-sm btn-quick" 
                                                                            onclick="addSaldo(<?= $usuario['id']; ?>, 'remover')">
                                                                        <i class="las la-minus"></i>
                                                                    </button>
                                                                </div>
                                                                <button class="btn btn-outline-primary btn-sm" 
                                                                        onclick="openModalSaldo(<?= $usuario['id']; ?>, '<?= $usuario['mobile']; ?>')">
                                                                    <i class="las la-wallet"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                        <td>R$ <?= number_format($depositado, 2, ',', '.'); ?></td>
                                                        <td>R$ <?= number_format($sacado, 2, ',', '.'); ?></td>
                                                        <td><?= $cargo_badge; ?></td>
                                                        <td class="text-end">
                                                            <div class="dropdown d-inline-block">
                                                                <a class="dropdown-toggle arrow-none" id="dLabel11"
                                                                    data-bs-toggle="dropdown" href="#" role="button"
                                                                    aria-haspopup="false" aria-expanded="false">
                                                                    <i class="las la-ellipsis-v fs-20 text-muted"></i>
                                                                </a>
                                                                <div class="dropdown-menu dropdown-menu-end">
                                                                    <a class="dropdown-item text-success"
                                                                        href="<?= $painel_adm_ver_usuarios . encodeAll($usuario['id']); ?>">
                                                                        <i class="las la-info-circle"></i> Detalhes
                                                                    </a>
                                                                    <a class="dropdown-item text-primary" 
                                                                       onclick="openModalSaldo(<?= $usuario['id']; ?>, '<?= $usuario['mobile']; ?>')">
                                                                        <i class="las la-wallet"></i> Adicionar Saldo
                                                                    </a>
                                                                    <a class="dropdown-item text-warning" href="#">
                                                                        <i class="las la-edit"></i> Editar
                                                                    </a>
                                                                    <a class="dropdown-item text-danger" href="#" 
                                                                       onclick="return confirm('Tem certeza que deseja deletar este usuário?');">
                                                                        <i class="las la-trash"></i> Deletar
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                            } else {
                                                echo "<tr><td colspan='7' class='text-center'>Sem dados disponíveis!</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table><!--end /table-->
                                </div><!--end /tableresponsive-->

                                <!-- Paginação -->
                                <?php if ($total_pages > 1): ?>
                                    <nav class="mt-3">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search_query) ?>&status=<?= $status_filter ?>" aria-label="Anterior">
                                                        <span aria-hidden="true">&laquo;</span>
                                                    </a>
                                                </li>
                                            <?php endif; ?>

                                            <?php 
                                            // Mostrar no máximo 5 páginas
                                            $start_page = max(1, $page - 2);
                                            $end_page = min($total_pages, $page + 2);
                                            
                                            for ($i = $start_page; $i <= $end_page; $i++): 
                                            ?>
                                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search_query) ?>&status=<?= $status_filter ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>

                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search_query) ?>&status=<?= $status_filter ?>" aria-label="Próximo">
                                                        <span aria-hidden="true">&raquo;</span>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            </div><!--end card-body-->
                        </div>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!-- container -->

            <!--Start Rightbar-->
            <?php include 'partials/endbar.php' ?>
            <!--end Rightbar-->
            <!--Start Footer-->
            <?php include 'partials/footer.php' ?>
            <!--end footer-->
        </div><!-- end page content -->
    </div><!-- end page-wrapper -->

    <!-- Javascript  -->
    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>
    
    <!-- Scripts para adicionar saldo rapidamente -->
    <script>
        // Alternar modo de saldo rápido
        function toggleQuickSaldo() {
            const forms = document.querySelectorAll('.quick-saldo-form');
            forms.forEach(form => {
                form.classList.toggle('d-none');
            });
        }
        
        // Abrir modal de saldo
        function openModalSaldo(userId, username) {
            document.getElementById('modal_user_id').value = userId;
            document.getElementById('modal_username').textContent = username;
            document.getElementById('modal_valor').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('modalSaldoRapido'));
            modal.show();
        }
        
        // Adicionar saldo via input rápido
        function addSaldo(userId, tipo) {
            const input = document.getElementById('input-' + userId);
            const valor = parseFloat(input.value);
            
            if (!valor || valor <= 0) {
                alert('Por favor, insira um valor válido!');
                return;
            }
            
            // Fazer requisição AJAX
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `quick_add_saldo=1&user_id=${userId}&valor=${valor}&tipo=${tipo}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualizar saldo na tela
                    const saldoElement = document.getElementById('saldo-' + userId);
                    const row = document.getElementById('user-row-' + userId);
                    
                    // Adicionar animação de sucesso
                    row.classList.add('success-animation');
                    setTimeout(() => row.classList.remove('success-animation'), 500);
                    
                    // Limpar input
                    input.value = '';
                    
                    // Recarregar página após 1 segundo para atualizar valores
                    setTimeout(() => location.reload(), 1000);
                }
            });
        }
        
        // Processar formulário do modal
        document.getElementById('formSaldoRapido').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const userId = document.getElementById('modal_user_id').value;
            const valor = document.getElementById('modal_valor').value;
            const tipo = document.getElementById('modal_tipo').value;
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `quick_add_saldo=1&user_id=${userId}&valor=${valor}&tipo=${tipo}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Fechar modal
                    bootstrap.Modal.getInstance(document.getElementById('modalSaldoRapido')).hide();
                    
                    // Mostrar notificação de sucesso (você pode usar toastr ou similar)
                    alert('Saldo atualizado com sucesso!');
                    
                    // Recarregar página
                    location.reload();
                }
            });
        });
        
        // Atalhos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl + S para ativar modo saldo rápido
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                toggleQuickSaldo();
            }
        });
    </script>
</body>

</html>

<?php
// Funções de totalização e média para o resumo financeiro
function total_dep_pagos_usuarios()
{
    global $mysqli;
    $qry = "SELECT SUM(valor) as total_soma FROM transacoes WHERE status = 'pago' AND tipo = 'deposito'";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result)['total_soma'] ?? 0;
}

function total_saques_usuarios()
{
    global $mysqli;
    $qry = "SELECT SUM(valor) as total_soma FROM solicitacao_saques WHERE status = 1";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result)['total_soma'] ?? 0;
}

function media_saldo_usuarios()
{
    global $mysqli;
    $qry = "SELECT AVG(saldo) as media_saldo FROM usuarios";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result)['media_saldo'] ?? 0;
}
?>