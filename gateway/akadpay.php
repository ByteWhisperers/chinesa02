<?php
session_start();
include_once('../dash/services/database.php');
include_once('../dash/services/funcao.php');
include_once('../dash/services/crud.php');
global $mysqli;

// Receber dados da solicitação POST JSON
$data = json_decode(file_get_contents("php://input"), true);

// Verificar se o JSON foi decodificado com sucesso
if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    // Erro ao decodificar o JSON
    http_response_code(400); // Bad Request
    exit;
}

$idTransaction = PHP_SEGURO($data['idTransaction']);         // id da transação
$typeTransaction = PHP_SEGURO($data['typeTransaction']);     // tipo de transação
$statusTransaction = PHP_SEGURO($data['status']); // Status de Transação

#====================================================================#
# Webhook para testes de integração
$dev_hook = 'https://webhook.site/7a2efdbe-8b34-47bb-98ac-1fc3d75d646c';
//===================================================================#
function url_send()
{
    global $data, $dev_hook;
    // URL de SUA API
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

#====================================================================#

function busca_valor_ipn($transacao_id)
{
    global $mysqli, $dataconfig;
    $qry = "SELECT usuario, valor FROM transacoes WHERE transacao_id='" . $transacao_id . "'";
    $res = mysqli_query($mysqli, $qry);
    if (mysqli_num_rows($res) > 0) {
        $data = mysqli_fetch_assoc($res);
        $retornaUSER = get_user_by_id($data['usuario']);

        $qry_check = "SELECT COUNT(*) as total FROM transacoes WHERE usuario = ? AND status = 'pago'";
        $stmt_check = $mysqli->prepare($qry_check);
        $stmt_check->bind_param("i", $data['usuario']);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $row_check = $result_check->fetch_assoc();
        $stmt_check->close();

        // die($row_check['total']); 

        $bonus = 0;
        if ($row_check['total'] == 0) {
            $bonus = round(($data['valor'] * $dataconfig['porcentage_bonus_deposit']) / 100, 2);
        }

        $retorna_insert_saldo_suit_pay = enviarSaldo($retornaUSER['mobile'], $data['valor'] + $bonus);
        return $retorna_insert_saldo_suit_pay;
    }
    return false;
}

function get_user_by_id($user_id)
{
    global $mysqli;
    $qry = "SELECT mobile FROM usuarios WHERE id = ?";
    $stmt = $mysqli->prepare($qry);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($mobile);
    $stmt->fetch();
    $stmt->close();
    return ['mobile' => $mobile];
}

#====================================================================#
function att_paymentpix($transacao_id)
{
    global $mysqli;

    // Verificar se a transação já está paga
    $sql_check = $mysqli->prepare("SELECT status FROM transacoes WHERE transacao_id=?");
    $sql_check->bind_param("s", $transacao_id);
    $sql_check->execute();
    $sql_check->bind_result($status);
    $sql_check->fetch();
    $sql_check->close();

    if ($status == 'pago') {
        return 1;
    }

    $saldo_adicionado = busca_valor_ipn($transacao_id);

    if ($saldo_adicionado) {
        $sql = $mysqli->prepare("UPDATE transacoes SET status='pago' WHERE transacao_id=?");
        $sql->bind_param("s", $transacao_id);
        if ($sql->execute()) {
            return 1;
        }
    }

    return 0; // Falha na atualização ou adição de saldo
}

#====================================================================#
function handle_successful_withdrawal($transaction_id)
{
    global $mysqli;

    $qry = "SELECT id, id_user, valor FROM solicitacao_saques WHERE transacao_id = ?";
    $stmt = $mysqli->prepare($qry);
    $stmt->bind_param("s", $transaction_id);
    $stmt->execute();
    $stmt->bind_result($solicitacao_id, $user_id, $valor_saque);
    $stmt->fetch();
    $stmt->close();

    if ($user_id && $solicitacao_id) {
        $qry = "UPDATE solicitacao_saques SET status = 1 WHERE id = ?";
        $stmt = $mysqli->prepare($qry);
        $stmt->bind_param("i", $solicitacao_id);
        $stmt->execute();
        $stmt->close();

        return true;
    }

    return false;
}

#====================================================================#
#01
if (isset($idTransaction) && $typeTransaction == "PIX" && $statusTransaction == "paid") {
    $att_transacao = att_paymentpix($idTransaction);
}

if (isset($idTransaction) && $typeTransaction == "PAYMENT" && $statusTransaction == "paid") {
    handle_successful_withdrawal($idTransaction);
}

#====================================================================#
