<?php include 'partials/html.php' ?>

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include_once "services/database.php";
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
function get_apidrakon_config()
{
    global $mysqli;
    $qry = "SELECT * FROM apidrakon WHERE id = 1";
    $result = mysqli_query($mysqli, $qry);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return false;
}
function update_apidrakon_config($data)
{
    global $mysqli;
    $qry = $mysqli->prepare("UPDATE apidrakon SET 
        url = ?, 
        agent_code = ?, 
        agent_token = ?, 
        agent_secret = ?, 
        ativo = ? 
        WHERE id = 1");
    if (!$qry) {
        error_log("Erro ao preparar a query: " . $mysqli->error);
        return false;
    }
    $qry->bind_param("ssssi", $data['url'], $data['agent_code'], $data['agent_token'], $data['agent_secret'], $data['ativo']);
    $result = $qry->execute();
    $qry->close();
    return $result;
}

function get_royalgames_config()
{
    global $mysqli;
    $qry = "SELECT * FROM apiroyalgames WHERE id = 1";
    $result = mysqli_query($mysqli, $qry);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return false;
}

function update_royalgames_config($data)
{
    global $mysqli;
    $qry = $mysqli->prepare("UPDATE apiroyalgames SET 
        url = ?, 
        agent_code = ?, 
        agent_token = ?, 
        agent_secret = ?, 
        ativo = ? 
        WHERE id = 1");
    if (!$qry) {
        error_log("Erro ao preparar a query: " . $mysqli->error);
        return false;
    }
    $qry->bind_param("ssssi", $data['url'], $data['agent_code'], $data['agent_token'], $data['agent_secret'], $data['ativo']);
    $result = $qry->execute();
    $qry->close();
    return $result;
}

function get_playfiver_config()
{
    global $mysqli;
    $qry = "SELECT * FROM fiverscan WHERE id=1";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result);
}

function get_apiphillyps_config()
{
    global $mysqli;
    $qry = "SELECT * FROM apiphillyps WHERE id=1";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result);
}


function update_playfiver_config($data)
{
    global $mysqli;

    // Verifica se agent_secret existe no array $data
    if (!isset($data['agent_secret'])) {
        // Se não existir, pega o valor atual do banco de dados
        $current_config = get_playfiver_config();
        $data['agent_secret'] = $current_config['agent_secret'] ?? '';
    }

    $qry = $mysqli->prepare("UPDATE fiverscan SET 
        url = ?, 
        agent_code = ?, 
        agent_token = ?,
        agent_secret = ? 
        WHERE id = 1");

    if (!$qry) {
        error_log("Erro ao preparar a query: " . $mysqli->error);
        return false;
    }

    $qry->bind_param("ssss", $data['url'], $data['agent_code'], $data['agent_token'], $data['agent_secret']);
    $result = $qry->execute();
    $qry->close();
    return $result;
}
function update_apiphillyps_config($data)
{
    global $mysqli;

    $qry = $mysqli->prepare("UPDATE apiphillyps SET 
        url = ?, 
        agent_code = ?, 
        agent_token = ? 
        WHERE id = 1");

    if (!$qry) {
        error_log("Erro ao preparar a query: " . $mysqli->error);
        return false;
    }

    $qry->bind_param("sss", $data['url'], $data['agent_code'], $data['agent_token']);
    $result = $qry->execute();
    $qry->close();
    return $result;
}


function update_gateway_status($gateway, $status)
{
    global $mysqli;
    if ($gateway === 'playfiver') {
        $qry = "UPDATE fiverscan SET ativo = ? WHERE id = 1";
        $stmt = $mysqli->prepare($qry);
        $stmt->bind_param("i", $status);
        $stmt->execute();
        $qry = "UPDATE payigaming SET ativo = ? WHERE id = 1";
        $status = $status ? 0 : 1; // Inverte o estado para o outro gateway
    } else {
        $qry = "UPDATE payigaming SET ativo = ? WHERE id = 1";
        $stmt = $mysqli->prepare($qry);
        $stmt->bind_param("i", $status);
        $stmt->execute();
        $qry = "UPDATE fiverscan SET ativo = ? WHERE id = 1";
        $status = $status ? 0 : 1; // Inverte o estado para o outro gateway
    }
    $stmt = $mysqli->prepare($qry);
    $stmt->bind_param("i", $status);
    return $stmt->execute();
}

$toastType = null;
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['update_apidrakon']) && !isset($_POST['update_royalgames']) && isset($_POST['agent_code'])) {
    $data = [
        'url' => $_POST['url'],
        'agent_code' => $_POST['agent_code'],
        'agent_token' => $_POST['agent_token'],
        'agent_secret' => $_POST['agent_secret'] ?? null,
    ];

    if (update_playfiver_config($data)) {
        $toastType = 'success';
        $toastMessage = 'Credenciais PlayFiver atualizadas com sucesso!';
    } else {
        $toastType = 'error';
        $toastMessage = 'Erro ao atualizar as Credenciais PlayFiver. Tente novamente.';
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['client_id']) && isset($_POST['client_secret']) && !isset($_POST['update_apidrakon']) && !isset($_POST['update_royalgames'])) {
    $data = [
        'url' => $_POST['url'],
        'agent_code' => $_POST['client_id'],
        'agent_token' => $_POST['client_secret'],
    ];

    if (update_apiphillyps_config($data)) {
        $toastType = 'success';
        $toastMessage = 'Credenciais API16 JOGOS atualizadas com sucesso!';
    } else {
        $toastType = 'error';
        $toastMessage = 'Erro ao atualizar as Credenciais API16 JOGOS. Tente novamente.';
    }

    // Atualiza a variável com os novos dados salvos
    $config2 = get_apiphillyps_config();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_apidrakon'])) {
    $data = [
        'url' => $_POST['url'],
        'agent_code' => $_POST['agent_code'],
        'agent_token' => $_POST['agent_token'],
        'agent_secret' => $_POST['agent_secret'],
        'ativo' => isset($_POST['ativo']) ? 1 : 0,
    ];

    if (update_apidrakon_config($data)) {
        $toastType = 'success';
        $toastMessage = 'Credenciais API Drakon atualizadas com sucesso!';
    } else {
        $toastType = 'error';
        $toastMessage = 'Erro ao atualizar as credenciais API Drakon. Tente novamente.';
    }
    // Recarrega a configuração atualizada
    $apidrakon_config = get_apidrakon_config();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_royalgames'])) {
    $data = [
        'url' => $_POST['url'],
        'agent_code' => $_POST['agent_code'],
        'agent_token' => $_POST['agent_token'],
        'agent_secret' => $_POST['agent_secret'],
        'ativo' => isset($_POST['ativo']) ? 1 : 0,
    ];

    if (update_royalgames_config($data)) {
        $toastType = 'success';
        $toastMessage = 'Credenciais Royal Games atualizadas com sucesso!';
    } else {
        $toastType = 'error';
        $toastMessage = 'Erro ao atualizar as credenciais Royal Games. Tente novamente.';
    }
    // Recarrega a configuração atualizada
    $royalgames_config = get_royalgames_config();
}

$playfiver_config = get_playfiver_config();
$config2 = get_apiphillyps_config();
$apidrakon_config = get_apidrakon_config();
$royalgames_config = get_royalgames_config();
?>


<head>
    <?php $title = "Configurações de Credenciais";
    include 'partials/title-meta.php' ?>
    <link rel="stylesheet" href="assets/libs/jsvectormap/jsvectormap.min.css">
    <?php include 'partials/head-css.php' ?>
    <style>
        .api-account {
            width: 100%;
            margin: 15px 0;
        }

        .api-account__inner {
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
        }

        .api-account__text {
            margin: 0 0 12px;
            font-weight: 700;
            color: #111111;
            background: #ffffff;
            display: inline-block;
            padding: 8px 14px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        }

        .api-account__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: 999px;
            color: #ffffff;
            font-weight: 600;
            text-decoration: none;
            min-width: 220px;
            background: #7c3aed;
            transition: filter 0.2s ease, transform 0.2s ease;
        }

        .api-account__btn:hover {
            filter: brightness(0.92);
            transform: translateY(-1px);
            color: #ffffff;
        }

        .api-account__actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
        }
    </style>
</head>

<body>
    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>

    <div class="page-wrapper">
        <div class="page-content">



            <div class="container-xxl">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Credenciais DTX API</h4>
                            </div>
                            <div class="card-body">
                                <section class="api-account" aria-label="Criacao de conta na API de jogos">
                                    <div class="api-account__inner">
                                        <p class="api-account__text">
                                            Para utilizar mais de 100 jogos gratuitos, crie sua conta na API de jogos
                                            clicando no botao abaixo.
                                        </p>
                                        <div class="api-account__actions">
                                            <a class="api-account__btn" href="https://painel.dtxapi.site/register"
                                                target="_blank" rel="noopener noreferrer">
                                                Criar conta na API de jogos
                                            </a>
                                            <a class="api-account__btn" href="https://saldo.dtxsistemas.com"
                                                target="_blank" rel="noopener noreferrer">
                                                Solicitar saldo Api
                                            </a>
                                        </div>
                                    </div>
                                </section>
                                <form method="POST" action="">
                                    <div class="row">
                                        <!-- Campo URL -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">URL</h5>
                                                    <input type="text" name="url" class="form-control"
                                                        value="<?= htmlspecialchars($royalgames_config['url'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                        required>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Campo Agent Code -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">Agent Code</h5>
                                                    <input type="text" name="agent_code" class="form-control"
                                                        value="<?= htmlspecialchars($royalgames_config['agent_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                        required>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Campo Agent Token -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">Agent Token</h5>
                                                    <input type="text" name="agent_token" class="form-control"
                                                        value="<?= htmlspecialchars($royalgames_config['agent_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                        required>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Campo Agent Secret -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">Agent Secret</h5>
                                                    <input type="text" name="agent_secret" class="form-control"
                                                        value="<?= htmlspecialchars($royalgames_config['agent_secret'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                        required>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Campo Ativo -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">Ativo</h5>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="ativo"
                                                            id="royalgamesSwitch" <?= (isset($royalgames_config['ativo']) && $royalgames_config['ativo'] == 1) ? 'checked' : '' ?>>
                                                        <label class="form-check-label"
                                                            for="royalgamesSwitch">Ativo</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Botão de Salvar -->
                                    <div class="text-center">
                                        <button type="submit" name="update_royalgames" class="btn btn-success">Salvar
                                            Credenciais Dtx API</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div><!-- end row -->
            </div><!-- container -->

        </div><!-- page content -->
    </div><!-- page-wrapper -->

    <div id="toastPlacement" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>
    <script>
        function toggleGateway(activeGateway) {
            const suitpaySwitch = document.getElementById('suitpaySwitch');
            const digitopaySwitch = document.getElementById('digitopaySwitch');

            if (activeGateway === 'playfiver') {
                digitopaySwitch.checked = false; // Desativa Digitopay
                updateGatewayStatus('playfiver', 1);
                updateGatewayStatus('digitopay', 0);
            } else {
                suitpaySwitch.checked = false; // Desativa Suitpay
                updateGatewayStatus('digitopay', 1);
                updateGatewayStatus('playfiver', 0);
            }
        }

        function updateGatewayStatus(gateway, status) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "fetch/update_provedor.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function () {
                if (xhr.status === 200) {
                    console.log(xhr.responseText); // Log do retorno para depuração
                } else {
                    console.error('Erro ao atualizar o status do gateway.');
                }
            };
            xhr.send("gateway=" + gateway + "&status=" + status + "&csrf_token=<?= $csrf->getToken() ?>");
        }
    </script>

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

    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>

</html>
