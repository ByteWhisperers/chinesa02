<?php
// Melhor configuracao de erros - Para producao, mantenha display_errors desativado
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();

// Inclua o arquivo de conexao ao banco de dados
include_once('../services/database.php');
include_once('../services/funcao.php');
include_once('../services/crud.php'); // Se houver funcoes adicionais de seguranca, como PHP_SEGURO()

// Log simples para diagnostico de saques
function log_withdraw_debug($label, $context = [])
{
    $log_dir = __DIR__ . '/../logs';
    $log_file = $log_dir . '/withdraw.log';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    $entry = [
        'ts' => date('Y-m-d H:i:s'),
        'label' => $label,
        'context' => $context,
    ];
    @file_put_contents($log_file, json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
}

// Funcao para determinar o tipo de chave PIX (AkadPay)
function identificarTipoChavePix($chavepix, $flag = null)
{
    if ($flag == 3) {
        return 'cpf';
    }
    if ($flag == 4) {
        return 'cnpj';
    }
    if ($flag == 2) {
        return 'phone';
    }
    if ($flag == 1) {
        return 'email';
    }
    if (preg_match('/^[0-9a-f]{32}$/i', $chavepix)) {
        return 'random';
    }
    return 'invalid';
}

// Obtendo a configuracao
$qry = "SELECT * FROM config WHERE id=1";
$res = $mysqli->query($qry);
$data = $res->fetch_assoc();

$chavepix1 = $_POST['chavepix'] ?? '';
$chavepix = localizarchavepixall($chavepix1);
$valor = floatval($_POST['valor'] ?? 0);
$id = $_POST['id'] ?? '';

if ($chavepix && $valor > 0 && $valor <= $data['saque_automatico']) {
    $valor = number_format($valor, 2, '.', '');
    $filename = 'used_ids.json';
    $used_ids = [];

    if (file_exists($filename)) {
        $file_content = file_get_contents($filename);
        if ($file_content) {
            $used_ids = json_decode($file_content, true);
        }
    }

    if (in_array($id, $used_ids)) {
        die("Anti-fraude acionado: Este ID ja foi usado.");
    }

    if (!empty($id)) {
        $used_ids[] = $id;
        file_put_contents($filename, json_encode($used_ids, JSON_PRETTY_PRINT));
    } else {
        die("ID invalido.");
    }

    $pixKey = $chavepix['pix_account'];
    $tipoChavePix = identificarTipoChavePix($pixKey, $chavepix['flag']);

    if ($tipoChavePix === 'invalid') {
        die("Chave Pix invalida.");
    }

    $url = 'https://painel.akadpay.com.br/api/pixout';
    $payload = [
        "token" => $data_akadpay['token'],
        "secret" => $data_akadpay['secret'],
        "baasPostbackUrl" => $url_base . 'gateway/akadpay',
        "amount" => (float)$valor,
        "pixKey" => $pixKey,
        "pixKeyType" => $tipoChavePix
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    // Workaround temporario: desativa verificacao SSL (inseguro)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $responsejson = json_decode($response, true);
    $log_payload = $payload;
    $log_payload['token'] = '***';
    $log_payload['secret'] = '***';
    log_withdraw_debug('akadpay_auto_response', [
        'transacao_id' => $id,
        'payload' => $log_payload,
        'response_raw' => $response,
        'http_code' => $http_code,
        'curl_error' => $curl_error,
        'json_error' => json_last_error_msg(),
    ]);

    if (isset($responsejson['id'])) {
        die("Pagamento realizado com sucesso");
    }

    $errorMessage = null;
    if (isset($responsejson['message'])) {
        $errorMessage = $responsejson['message'];
    } elseif (isset($responsejson['error'])) {
        $errorMessage = is_array($responsejson['error']) && isset($responsejson['error']['message'])
            ? $responsejson['error']['message']
            : $responsejson['error'];
    } elseif (isset($responsejson['errors'])) {
        if (is_array($responsejson['errors'])) {
            $first = reset($responsejson['errors']);
            $errorMessage = is_array($first) && isset($first['message']) ? $first['message'] : (string)$first;
        } else {
            $errorMessage = (string)$responsejson['errors'];
        }
    }
    $errorMessage = $errorMessage ? $errorMessage : 'Erro desconhecido no AkadPay';
    die("Erro ao processar o pagamento: " . $errorMessage);
} else {
    echo "Chave Pix invalida, valor invalido ou valor fora do limite permitido.";
    exit;
}
$mysqli->close();
?>
