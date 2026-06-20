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
//inicio do script expulsa usuario bloqueado
if ($_SESSION['data_adm']['status'] != '1') {
    echo "<script>setTimeout(function() { window.location.href = 'bloqueado.php'; }, 0);</script>";
    exit();
}

// --- Edbanking functions ---
function get_edbanking_config()
{
    global $mysqli;
    $qry = "SELECT * FROM edbanking WHERE id=1";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result);
}

function update_edbanking_config($data, $is_active)
{
    global $mysqli;
    if ($is_active) {
        $mysqli->query("UPDATE akadpay SET ativo = 0 WHERE id = 1");
    }
    $stmt = $mysqli->prepare("UPDATE edbanking SET client_id = ?, client_secret = ?, url = ?, ativo = ? WHERE id = 1");
    $ativo = $is_active ? 1 : 0;
    $stmt->bind_param("sssi", $data['client_id'], $data['client_secret'], $data['url'], $ativo);
    return $stmt->execute();
}

// --- AkadPay functions ---
function get_akadpay_config()
{
    global $mysqli;
    $qry = "SELECT * FROM akadpay WHERE id=1";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result);
}

function update_akadpay_config($data, $is_active)
{
    global $mysqli;
    if ($is_active) {
        $mysqli->query("UPDATE edbanking SET ativo = 0 WHERE id = 1");
    }
    $stmt = $mysqli->prepare("UPDATE akadpay SET token = ?, secret = ?, url = ?, ativo = ? WHERE id = 1");
    $ativo = $is_active ? 1 : 0;
    $stmt->bind_param("sssi", $data['token'], $data['secret'], $data['url'], $ativo);
    return $stmt->execute();
}

$toastType = null;
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $gateway_name = $_POST['gateway_name'];
    $toastType = 'error';
    $toastMessage = 'Gateway desconhecido.';

    if ($gateway_name == 'edbanking') {
        $data = [
            'client_id' => $_POST['edbanking_client_id'],
            'client_secret' => $_POST['edbanking_client_secret'],
            'url' => $_POST['edbanking_url'],
        ];
        $is_active = isset($_POST['edbanking_ativo']);
        if (update_edbanking_config($data, $is_active)) {
            $toastType = 'success';
            $toastMessage = 'Credenciais Edbanking atualizadas com sucesso!';
        } else {
            $toastMessage = 'Erro ao atualizar as Credenciais Edbanking.';
        }
    } elseif ($gateway_name == 'akadpay') {
        $data = [
            'token' => $_POST['akadpay_token'],
            'secret' => $_POST['akadpay_secret'],
            'url' => $_POST['akadpay_url'],
        ];
        $is_active = isset($_POST['akadpay_ativo']);
        if (update_akadpay_config($data, $is_active)) {
            $toastType = 'success';
            $toastMessage = 'Credenciais AkadPay atualizadas com sucesso!';
        } else {
            $toastMessage = 'Erro ao atualizar as Credenciais AkadPay.';
        }
    }
}

# Buscar os dados atuais
$edbanking_config = get_edbanking_config() ?: ['client_id' => '', 'client_secret' => '', 'url' => '', 'ativo' => 0];
$akadpay_config = get_akadpay_config() ?: ['token' => '', 'secret' => '', 'url' => '', 'ativo' => 0];
?>

<head>
    <?php $title = "Configurações de Credenciais";
    include 'partials/title-meta.php' ?>

    <link rel="stylesheet" href="assets/libs/jsvectormap/jsvectormap.min.css">
    <?php include 'partials/head-css.php' ?>
    <style>
        .gateway-hidden {
            display: none !important;
        }

        .akadpay-instructions {
            width: 100%;
            margin: 0 0 20px;
        }

        .akadpay-instructions__inner {
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
        }

        .akadpay-instructions__text {
            margin: 0 0 16px;
            font-weight: 700;
            color: #111111;
            background: #ffffff;
            display: inline-block;
            padding: 8px 14px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        }

        .akadpay-instructions__actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
        }

        .akadpay-instructions__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: 999px;
            color: #ffffff;
            font-weight: 600;
            text-decoration: none;
            min-width: 190px;
            transition: filter 0.2s ease, transform 0.2s ease;
        }

        .akadpay-instructions__btn:hover {
            filter: brightness(0.92);
            transform: translateY(-1px);
            color: #ffffff;
        }

        .akadpay-instructions__btn--open {
            background: #2563eb;
        }

        .akadpay-instructions__btn--manager {
            background: #25D366;
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

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Gerenciamento de Credenciais (AKADPAY)</h4>
                            </div>

                            <div class="card-body">
                                <section class="akadpay-instructions" aria-label="Instrucoes de conta AkadPay">
                                    <div class="akadpay-instructions__inner">
                                        <p class="akadpay-instructions__text">
                                            Abra sua conta clicando no botao abaixo. Apos abrir a conta, solicite a
                                            aprovacao com o gerente.
                                        </p>
                                        <div class="akadpay-instructions__actions">
                                            <a class="akadpay-instructions__btn akadpay-instructions__btn--open"
                                                href="https://painel.akadpay.com.br/" target="_blank"
                                                rel="noopener noreferrer">
                                                Abrir minha conta
                                            </a>
                                            <a class="akadpay-instructions__btn akadpay-instructions__btn--manager"
                                                href="https://wa.me/5537998321396?text=Vim%20pela%20DTX.%20Gostaria%20de%20aprovar%20minha%20conta."
                                                target="_blank" rel="noopener noreferrer">
                                                Falar com o gerente
                                            </a>
                                        </div>
                                    </div>
                                </section>
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item gateway-hidden">
                                        <a class="nav-link" data-bs-toggle="tab" href="#edbanking" role="tab">Edbanking</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#akadpay" role="tab">AkadPay</a>
                                    </li>
                                </ul>

                                <!-- Tab panes -->
                                <div class="tab-content">
                                    <div class="tab-pane gateway-hidden" id="edbanking" role="tabpanel">
                                        <form method="POST" action="">
                                            <input type="hidden" name="gateway_name" value="edbanking">
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <div class="card mb-4">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><i class="iconoir-user"></i> Client ID</h5>
                                                            <input type="text" name="edbanking_client_id" class="form-control" value="<?= htmlspecialchars($edbanking_config['client_id']) ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card mb-4">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><i class="iconoir-group"></i> Client Secret</h5>
                                                            <input type="text" name="edbanking_client_secret" class="form-control" value="<?= htmlspecialchars($edbanking_config['client_secret']) ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card mb-4">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><i class="iconoir-community"></i> Endpoint</h5>
                                                            <input type="text" name="edbanking_url" class="form-control" value="<?= htmlspecialchars($edbanking_config['url']) ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="edbanking_ativo" id="edbanking_ativo" <?= $edbanking_config['ativo'] ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="edbanking_ativo">Ativar</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-center">
                                                <button type="submit" class="btn btn-success">Salvar Configurações</button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="tab-pane active" id="akadpay" role="tabpanel">
                                        <form method="POST" action="">
                                            <input type="hidden" name="gateway_name" value="akadpay">
                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <div class="card mb-4">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><i class="iconoir-user"></i> Token</h5>
                                                            <input type="text" name="akadpay_token" class="form-control" value="<?= htmlspecialchars($akadpay_config['token']) ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card mb-4">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><i class="iconoir-group"></i> Secret</h5>
                                                            <input type="text" name="akadpay_secret" class="form-control" value="<?= htmlspecialchars($akadpay_config['secret']) ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card mb-4">
                                                        <div class="card-body">
                                                            <h5 class="card-title"><i class="iconoir-community"></i> Endpoint</h5>
                                                            <input type="text" name="akadpay_url" class="form-control" value="<?= htmlspecialchars($akadpay_config['url']) ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="akadpay_ativo" id="akadpay_ativo" <?= $akadpay_config['ativo'] ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="akadpay_ativo">Ativar</label>
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
                    <img src="assets/images/logo-sm.png" alt="" height="20" class="me-1">
                    <h5 class="me-auto my-0">IGamingSB</h5>
                    <small>Agora</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">${message}</div>
            `;
            toastPlacement.appendChild(toast);

            var bootstrapToast = new bootstrap.Toast(toast);
            bootstrapToast.show();

            setTimeout(function() {
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
