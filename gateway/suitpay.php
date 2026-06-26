<?php
session_start();
include_once('../dash/services/database.php');
include_once('../dash/services/funcao.php');
include_once('../dash/services/crud.php');
global $mysqli;

$data = json_decode(file_get_contents("php://input"), true);

// Verificar se o JSON foi decodificado com sucesso
if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    exit;
}

$idTransaction = PHP_SEGURO($data['idTransaction']);       // id da transação
$typeTransaction = PHP_SEGURO($data['typeTransaction']);       // tipo de transação
$statusTransaction = PHP_SEGURO($data['statusTransaction']);     // status da transação

#====================================================================#
# Webhook para testes de integração
$dev_hook = 'https://webhook.site/42161bbc-8877-4171-b9df-998bb61ffdae';
//====================================================================#
function url_send()
{
    global $data, $dev_hook;
    $url = $dev_hook;
    $ch = curl_init($url);
    $corpo = json_encode($data);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $corpo);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $resultado = curl_exec($ch);
    curl_close($ch);
    return $resultado;
}
url_send();

function get_commission_percentages()
{
    global $mysqli;
    $qry = "SELECT lvl1_percentage, lvl2_percentage, lvl3_percentage FROM config LIMIT 1";
    $result = mysqli_query($mysqli, $qry);
    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    } else {
        // Valores padrão: 60%, 10% e 5%
        return [
            'lvl1_percentage' => 0.60,
            'lvl2_percentage' => 0.10,
            'lvl3_percentage' => 0.05
        ];
    }
}

function get_user_by_id($user_id)
{
    global $mysqli;
    $qry = "SELECT id, mobile, invitation_code, active_percentage FROM usuarios WHERE id = ?";
    $stmt = $mysqli->prepare($qry);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

function get_referrer($user_id)
{
    global $mysqli;
    $qry = "SELECT invitation_code FROM usuarios WHERE id = ?";
    $stmt = $mysqli->prepare($qry);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($invitation_code);
    if ($stmt->fetch()) {
        $stmt->close();
        $qry2 = "SELECT * FROM usuarios WHERE invite_code = ? LIMIT 1";
        $stmt2 = $mysqli->prepare($qry2);
        $stmt2->bind_param("s", $invitation_code);
        $stmt2->execute();
        $result = $stmt2->get_result();
        if ($result->num_rows > 0) {
            $referrer = $result->fetch_assoc();
            $stmt2->close();
            return $referrer;
        } else {
            $stmt2->close();
            return null;
        }
    } else {
        $stmt->close();
        return null;
    }
}

function add_wallet($user_id, $amount)
{
    global $mysqli;
    $qry = "UPDATE usuarios SET saldo = saldo + ? WHERE id = ?";
    $stmt = $mysqli->prepare($qry);
    $stmt->bind_param("di", $amount, $user_id);
    $stmt->execute();
    $stmt->close();
}

function distribute_commission($user_id, $valor)
{
    $commissions = get_commission_percentages();
    $ref1 = get_referrer($user_id);
    if ($ref1 && isset($ref1['active_percentage']) && $ref1['active_percentage']) {
        $commission1 = ($commissions['lvl1_percentage'] / 100) * $valor;
        add_wallet($ref1['id'], $commission1);
    }
    if ($ref1) {
        $ref2 = get_referrer($ref1['id']);
        if ($ref2 && isset($ref2['active_percentage']) && $ref2['active_percentage']) {
            $commission2 = ($commissions['lvl2_percentage'] / 100) * $valor;
            add_wallet($ref2['id'], $commission2);
        }
        if ($ref2) {
            $ref3 = get_referrer($ref2['id']);
            if ($ref3 && isset($ref3['active_percentage']) && $ref3['active_percentage']) {
                $commission3 = ($commissions['lvl3_percentage'] / 100) * $valor;
                add_wallet($ref3['id'], $commission3);
            }
        }
    }
}

function busca_valor_ipn($transacao_id)
{
    global $mysqli;
    $qry = "SELECT usuario, valor FROM transacoes WHERE transacao_id='" . $transacao_id . "'";
    $res = mysqli_query($mysqli, $qry);
    if (mysqli_num_rows($res) > 0) {
        $data = mysqli_fetch_assoc($res);
        $retornaUSER = get_user_by_id($data['usuario']);
        $retorna_insert_saldo_suit_pay = enviarSaldo($retornaUSER['mobile'], $data['valor']);
        return $retorna_insert_saldo_suit_pay;
    }
    return false;
}


function att_paymentpix($transacao_id)
{
    global $mysqli;
    $sql = $mysqli->prepare("UPDATE transacoes SET status='pago' WHERE transacao_id=?");
    $sql->bind_param("s", $transacao_id);
    if ($sql->execute()) {
        $buscar = busca_valor_ipn($transacao_id);
        $qry = "SELECT usuario, valor FROM transacoes WHERE transacao_id=?";
        $stmt = $mysqli->prepare($qry);
        $stmt->bind_param("s", $transacao_id);
        $stmt->execute();
        $stmt->bind_result($user_id, $valor);
        $stmt->fetch();
        $stmt->close();
        distribute_commission($user_id, $valor);
        $rf = $buscar ? 1 : 0;
    } else {
        $rf = 0;
    }
    return $rf;
}

#====================================================================#
// Caso a transação seja do tipo PIX com status PAID_OUT
if (isset($idTransaction) && $typeTransaction == "PIX" && $statusTransaction == "PAID_OUT") {
    $att_transacao = att_paymentpix($idTransaction);
}

// MODO SANDBOX 
if ($tipoAPI_SUITPAY == 0) {
    if (isset($idTransaction) && $typeTransaction == "PIX" && $statusTransaction == "UNPAID") {
        $att_transacao = att_paymentpix($idTransaction);
    }
}
#====================================================================#
?>