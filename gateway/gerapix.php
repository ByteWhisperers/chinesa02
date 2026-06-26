<?php
session_start();
include_once('../dash/services/database.php');
include_once('../dash/services/funcao.php');
include_once('../dash/services/crud.php');
global $mysqli, $dataconfig, $data_gerapix;

$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    exit;
}

// Valida token secreto enviado pela GeraPix no header Authorization
$authorization_header = '';
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authorization_header = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authorization_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} elseif (function_exists('apache_request_headers')) {
    $all_headers = apache_request_headers();
    $authorization_header = $all_headers['Authorization'] ?? '';
}

$token_recebido = str_replace('Bearer ', '', $authorization_header);
$token_secreto  = isset($data_gerapix['secret']) ? trim($data_gerapix['secret']) : '';

if (!empty($token_secreto) && $token_recebido !== $token_secreto) {
    http_response_code(401);
    exit;
}

// Campos do webhook GeraPix:
// id_transacao, status ("Aprovado" = pago), external_reference
$id_transacao       = PHP_SEGURO($data['id_transacao']       ?? '');
$status             = PHP_SEGURO($data['status']             ?? '');
$external_reference = PHP_SEGURO($data['external_reference'] ?? '');

// Localiza a transação salva casando pela NOSSA referência (external_reference) com
// fallback no id do gateway. Cobre tanto depósitos novos (salvos com external_reference)
// quanto registros antigos (salvos com o id do gateway).
function localizar_transacao_gerapix($id_transacao, $external_reference)
{
    global $mysqli;

    $stmt = $mysqli->prepare(
        "SELECT transacao_id FROM transacoes
         WHERE transacao_id = ? OR transacao_id = ?
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->bind_param("ss", $external_reference, $id_transacao);
    $stmt->execute();
    $stmt->bind_result($tid);
    $encontrou = $stmt->fetch();
    $stmt->close();

    return $encontrou ? $tid : null;
}

function busca_valor_ipn_gerapix($transacao_id)
{
    global $mysqli, $dataconfig;

    $qry = "SELECT usuario, valor FROM transacoes WHERE transacao_id=?";
    $stmt = $mysqli->prepare($qry);
    $stmt->bind_param("s", $transacao_id);
    $stmt->execute();
    $stmt->bind_result($usuario_id, $valor);
    $stmt->fetch();
    $stmt->close();

    if (!$usuario_id) {
        return false;
    }

    $qry_user = "SELECT mobile FROM usuarios WHERE id=?";
    $stmt_user = $mysqli->prepare($qry_user);
    $stmt_user->bind_param("i", $usuario_id);
    $stmt_user->execute();
    $stmt_user->bind_result($mobile);
    $stmt_user->fetch();
    $stmt_user->close();

    // Verifica se é primeiro depósito para calcular bônus
    $qry_check = "SELECT COUNT(*) as total FROM transacoes WHERE usuario=? AND status='pago'";
    $stmt_check = $mysqli->prepare($qry_check);
    $stmt_check->bind_param("i", $usuario_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check    = $result_check->fetch_assoc();
    $stmt_check->close();

    $bonus = 0;
    if ($row_check['total'] == 0 && isset($dataconfig['porcentage_bonus_deposit'])) {
        $bonus = round(($valor * $dataconfig['porcentage_bonus_deposit']) / 100, 2);
    }

    return enviarSaldo($mobile, $valor + $bonus);
}

function att_paymentpix_gerapix($id_transacao, $external_reference = '')
{
    global $mysqli;

    // Resolve a transação pela tag de correlação, com fallback no id do gateway.
    $transacao_id = localizar_transacao_gerapix($id_transacao, $external_reference);
    if (!$transacao_id) {
        error_log("GeraPix: transação não encontrada (id_transacao='{$id_transacao}', external_reference='{$external_reference}')");
        return 0;
    }

    // Proteção contra duplo crédito
    $sql_check = $mysqli->prepare("SELECT status FROM transacoes WHERE transacao_id=?");
    $sql_check->bind_param("s", $transacao_id);
    $sql_check->execute();
    $sql_check->bind_result($status_atual);
    $sql_check->fetch();
    $sql_check->close();

    if ($status_atual === 'pago') {
        http_response_code(200);
        return 1;
    }

    $saldo_adicionado = busca_valor_ipn_gerapix($transacao_id);

    if ($saldo_adicionado) {
        // 'pago' é o único valor válido do ENUM transacoes.status reconhecido pelo restante do sistema.
        $sql = $mysqli->prepare("UPDATE transacoes SET status='pago' WHERE transacao_id=?");
        $sql->bind_param("s", $transacao_id);
        if ($sql->execute()) {
            return 1;
        }
    }

    return 0;
}

// Dispara crédito quando o pagamento é aprovado (comparação tolerante a caixa/variações).
$status_pago = in_array(strtolower(trim($status)), ['aprovado', 'approved', 'paid', 'pago', 'completed'], true);

if ($status_pago && (!empty($id_transacao) || !empty($external_reference))) {
    att_paymentpix_gerapix($id_transacao, $external_reference);
} else {
    error_log("GeraPix webhook não processado: status='{$status}' id_transacao='{$id_transacao}' external_reference='{$external_reference}'");
}

http_response_code(200);
echo json_encode(['received' => true]);
