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
# Expulsa usuário
checa_login_adm();
#======================================#
// Início do script: expulsa usuário bloqueado
if ($_SESSION['data_adm']['status'] != '1') {
    echo "<script>setTimeout(function() { window.location.href = 'bloqueado.php'; }, 0);</script>";
    exit();
}

# Função para buscar os dados atuais da tabela afiliados_config
function get_afiliados_config()
{
    global $mysqli;
    $qry = "SELECT * FROM config WHERE id=1";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result);
}

# Função para atualizar os dados da tabela afiliados_config
function update_config($data)
{
    global $mysqli;
    $qry = $mysqli->prepare("UPDATE config SET 
        qntsbaus = ?, 
        niveisbau = ?, 
        pessoasbau = ?,
        apostas_validas = ?,
        depositos_validos = ?,
        lvl1_percentage = ?,
        lvl2_percentage = ?,
        lvl3_percentage = ?
        WHERE id = 1");

    $qry->bind_param(
        "dsdddddd",
        $data['qntsbaus'],
        $data['niveisbau'],
        $data['pessoasbau'],
        $data['apostas_validas'],
        $data['depositos_validos'],
        $data['lvl1_percentage'],
        $data['lvl2_percentage'],
        $data['lvl3_percentage']
    );
    return $qry->execute();
}

$toastType = null; // Variável para definir o tipo de Toast
$toastMessage = ''; // Variável para definir a mensagem do Toast

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'qntsbaus' => floatval($_POST['qntsbaus']),
        'niveisbau' => $_POST['niveisbau'],
        'pessoasbau' => floatval($_POST['pessoasbau']),
        'apostas_validas' => floatval($_POST['apostas_validas']),
        'depositos_validos' => floatval($_POST['depositos_validos']),
        'lvl1_percentage' => floatval($_POST['lvl1_percentage']),
        'lvl2_percentage' => floatval($_POST['lvl2_percentage']),
        'lvl3_percentage' => floatval($_POST['lvl3_percentage'])
    ];

    if (update_config($data)) {
        $toastType = 'success';
        $toastMessage = 'Configurações de Baús atualizadas com sucesso!';
    } else {
        $toastType = 'error';
        $toastMessage = 'Erro ao atualizar as configurações. Tente novamente.';
    }
}

# Buscar os dados atuais
$config = get_afiliados_config();
?>

<head>
    <?php
    $title = "Configurações de Baús";
    include 'partials/title-meta.php';
    ?>

    <link rel="stylesheet" href="assets/libs/jsvectormap/jsvectormap.min.css">
    <?php include 'partials/head-css.php'; ?>
</head>

<body>

    <!-- Top Bar Start -->
    <?php include 'partials/topbar.php'; ?>
    <!-- Top Bar End -->
    <!-- leftbar-tab-menu -->
    <?php include 'partials/startbar.php'; ?>
    <!-- end leftbar-tab-menu-->

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Gerenciamento de baús da plataforma</h4>
                            </div>

                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title"><i class="iconoir-user"></i> Qtd. Baús</h5>
                                                    <input type="text" name="qntsbaus" class="form-control"
                                                        value="<?= $config['qntsbaus'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title"><i class="iconoir-group"></i> Valores Dos
                                                        Baús</h5>
                                                    <input type="text" name="niveisbau" class="form-control"
                                                        value="<?= $config['niveisbau'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title"><i class="iconoir-community"></i> Pessoas Baú
                                                    </h5>
                                                    <input type="text" name="pessoasbau" class="form-control"
                                                        value="<?= $config['pessoasbau'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title"><i class="iconoir-game-controller"></i>
                                                        Apostas Válidas</h5>
                                                    <input type="text" name="apostas_validas" class="form-control"
                                                        value="<?= $config['apostas_validas'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title"><i class="iconoir-deposit"></i> Depósitos
                                                        Válidos</h5>
                                                    <input type="text" name="depositos_validos" class="form-control"
                                                        value="<?= $config['depositos_validos'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title"><i class="iconoir-user"></i> Comissão Nível 1
                                                    </h5>
                                                    <input type="text" name="lvl1_percentage" class="form-control"
                                                        value="<?= $config['lvl1_percentage'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title"><i class="iconoir-user"></i> Comissão Nível 2
                                                    </h5>
                                                    <input type="text" name="lvl2_percentage" class="form-control"
                                                        value="<?= $config['lvl2_percentage'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title"><i class="iconoir-user"></i> Comissão Nível 3
                                                    </h5>
                                                    <input type="text" name="lvl3_percentage" class="form-control"
                                                        value="<?= $config['lvl3_percentage'] ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-success">Salvar Configurações</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div><!-- end row -->
            </div><!-- container -->
        </div><!-- page content -->
    </div><!-- page-wrapper -->

    <!-- Toast container -->
    <div id="toastPlacement" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <!-- Javascript -->
    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>

    <!-- Função de Toast -->
    <script>
        function showToast(type, message) {
            var toastPlacement = document.getElementById('toastPlacement');
            var toast = document.createElement('div');
            toast.className = `toast align-items-center bg-light border-0 fade show`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
                <div class="toast-header">
                    <img src="/uploads/logo.png.webp" alt="" height="20" class="me-1">
                    <h5 class="me-auto my-0"></h5>
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

    <!-- Exibir o Toast baseado nas ações do formulário -->
    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>

</html>