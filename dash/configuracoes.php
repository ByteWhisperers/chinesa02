<?php include 'partials/html.php'; ?>

<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
include_once "services/database.php";
include_once 'logs/registrar_logs.php';
include_once "services/funcao.php";
include_once "services/crud.php";
include_once "services/crud-adm.php";
include_once 'services/checa_login_adm.php';
include_once "services/CSRF_Protect.php";
$csrf = new CSRF_Protect();

checa_login_adm();

if ($_SESSION['data_adm']['status'] != '1') {
    echo "<script>setTimeout(function() { window.location.href = 'bloqueado.php'; }, 0);</script>";
    exit();
}

function get_afiliados_config() {
    global $mysqli;
    $qry = "SELECT * FROM config WHERE id=1";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result);
}

function update_config($data) {
    global $mysqli;
    try {
        $qry = $mysqli->prepare("UPDATE config SET 
            minsaque = ?, 
            saldo_inicial = ?, 
            maxsaque = ?, 
            saque_automatico = ?, 
            rollover = ?, 
            mindep = ?, 
            jackpot = ?,
            limite_saque = ?,
            porcentage_bonus_deposit = ?
            WHERE id = 1");

        if (!$qry) throw new Exception("Erro na preparação da query: " . $mysqli->error);

        $bind_result = $qry->bind_param(
            "dddddsddd",
            $data['minsaque'],
            $data['saldo_inicial'],
            $data['maxsaque'],
            $data['saque_automatico'],
            $data['rollover'],
            $data['mindep'],
            $data['jackpot'],
            $data['limite_saque'],
            $data['porcentage_bonus_deposit']
        );

        if (!$bind_result) throw new Exception("Erro ao vincular parâmetros: " . $qry->error);
        if (!$qry->execute()) throw new Exception("Erro ao executar query: " . $qry->error);

        return true;
    } catch (Exception $e) {
        error_log("Erro em update_config: " . $e->getMessage());
        echo "<div style='color: red; background: #ffeeee; padding: 10px; margin: 10px; border: 1px solid #ff0000;'>Erro ao processar requisição: " . $e->getMessage() . "</div>";
        return false;
    }
}

$toastType = null;
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $minsaque = str_replace(',', '.', $_POST['minsaque']);
        $maxsaque = str_replace(',', '.', $_POST['maxsaque']);
        $saque_automatico = str_replace(',', '.', $_POST['saque_automatico']);
        $rollover = str_replace(',', '.', $_POST['rollover']);
        $mindep = $_POST['mindep'];
        $jackpot = str_replace(',', '.', $_POST['jackpot']);
        $limite_saque = str_replace(',', '.', $_POST['limite_saque']);
        $porcentage_bonus_deposit = str_replace(',', '.', $_POST['porcentage_bonus_deposit']);
        $saldo_inicial = str_replace(',', '.', $_POST['saldo_inicial']);

        if (!is_numeric($saldo_inicial) || !is_numeric($minsaque) || !is_numeric($maxsaque) ||
            !is_numeric($saque_automatico) || !is_numeric($rollover) || !is_numeric($jackpot) ||
            !is_numeric($limite_saque) || !is_numeric($porcentage_bonus_deposit)) {
            throw new Exception("Um ou mais valores não são numéricos válidos.");
        }

        $data = [
            'saldo_inicial' => floatval($saldo_inicial),
            'minsaque' => floatval($minsaque),
            'maxsaque' => floatval($maxsaque),
            'saque_automatico' => floatval($saque_automatico),
            'mindep' => $mindep,
            'jackpot' => floatval($jackpot),
            'rollover' => floatval($rollover),
            'limite_saque' => floatval($limite_saque),
            'porcentage_bonus_deposit' => floatval($porcentage_bonus_deposit)
        ];

        if (update_config($data)) {
            $toastType = 'success';
            $toastMessage = 'Configurações atualizadas com sucesso!';
        } else {
            $toastType = 'error';
            $toastMessage = 'Erro ao atualizar as configurações. Verifique os logs para mais detalhes.';
        }
    } catch (Exception $e) {
        $toastType = 'error';
        $toastMessage = 'Erro: ' . $e->getMessage();
        error_log("Erro no processamento POST: " . $e->getMessage());
    }
}

$config = get_afiliados_config();
?>


<head>
    <?php $title = "Configurações de Afiliados";
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
                                <h4 class="card-title">Gerenciamento de valores da plataforma</h4>
                            </div>

                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <!-- Saque Minimo -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title"><i class="iconoir-user"></i> Saque Minimo
                                                    </h5>
                                                    <input type="text" name="minsaque" class="form-control"
                                                        value="<?= $config['minsaque'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Saque Maximo -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title"><i class="iconoir-user"></i> Saque Máximo
                                                    </h5>
                                                    <input type="text" name="maxsaque" class="form-control"
                                                        value="<?= $config['maxsaque'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Saque automatico maximo -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title"><i class="iconoir-group"></i> Saque
                                                        automatico Máximo
                                                    </h5>
                                                    <input type="text" name="saque_automatico" class="form-control"
                                                        value="<?= $config['saque_automatico'] ?>" required>
                                                </div>
                                            </div>
                                        </div>
<!-- Saldo Inicial -->
<div class="col-md-6">
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title"><i class="iconoir-money"></i> Saldo Inicial no Cadastro</h5>
            <input type="text" name="saldo_inicial" class="form-control"
                value="<?= $config['saldo_inicial'] ?? '0.00' ?>" required>
        </div>
    </div>
</div>

                                        <!-- Depósito minimo -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title"><i class="iconoir-community"></i> Depósito
                                                        minimo
                                                    </h5>
                                                    <input type="text" name="mindep" class="form-control"
                                                        value="<?= $config['mindep'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Valor no jackpot -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title"><i class="iconoir-percentage-circle"></i>
                                                        Valor no jackpot</h5>
                                                    <input type="text" name="jackpot" class="form-control"
                                                        value="<?= $config['jackpot'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Rollover -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title"><i class="iconoir-percentage-circle"></i>
                                                        Rollover</h5>
                                                    <input type="text" name="rollover" class="form-control"
                                                        value="<?= $config['rollover'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Limite Saque -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title"><i class="iconoir-percentage-circle"></i>
                                                        Limite Diário De Saque</h5>
                                                    <input type="text" name="limite_saque" class="form-control"
                                                        value="<?= $config['limite_saque'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Porcentagem de bonus deposit -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title"><i class="iconoir-percentage-circle"></i>
                                                        Porcentagem Bonus de Depósito</h5>
                                                    <input type="text" name="porcentage_bonus_deposit"
                                                        class="form-control"
                                                        value="<?= $config['porcentage_bonus_deposit'] ?>" required>
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
                    <h5 class="me-auto my-0">Phillyps</h5>
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