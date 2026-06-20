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

$client_name = PHP_SEGURO($data['client_name']);         // id da transação
$paymentcode = PHP_SEGURO($data['paymentcode']);     // tipo de transação
$idtransaction = PHP_SEGURO($data['idtransaction']);     // tipo de transação

#====================================================================#
# Webhook para testes de integração
$dev_hook = 'https://webhook.site/8166d7ee-e9d0-412f-9600-0ce0ea122fbc';
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
    $sql = $mysqli->prepare("UPDATE transacoes SET status='1' WHERE transacao_id=?");
    $sql->bind_param("s", $transacao_id);
    if ($sql->execute()) {
        $buscar = busca_valor_ipn($transacao_id);
        if ($buscar) {
            $rf = 1;
        } else {
            $rf = 0;
        }
    } else {
        $rf = 0;
    }
    return $rf;
}
#====================================================================#
# Função para lidar com saques cancelados
function handle_failed_withdrawal($externalreference, $amount)
{
    global $mysqli;

    $qry = "SELECT id, id_user, valor FROM solicitacao_saques WHERE telefone = ?";
    $stmt = $mysqli->prepare($qry);
    $stmt->bind_param("s", $externalreference);
    $stmt->execute();
    $stmt->bind_result($solicitacao_id, $user_id, $valor_saque);
    $stmt->fetch();
    $stmt->close();

    if ($user_id && $solicitacao_id) {
        $qry = "UPDATE usuarios SET saldo = saldo + ? WHERE id = ?";
        $stmt = $mysqli->prepare($qry);
        $stmt->bind_param("di", $valor_saque, $user_id);
        $stmt->execute();
        $stmt->close();

        $qry = "UPDATE solicitacao_saques SET status = 3 WHERE id = ?";
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

if (isset($client_name) && isset($paymentcode)) {
    if (empty($data['status']) || strtoupper($data['status']) === 'PAID' || strtoupper($data['status']) === 'paid_out') {
        $att_transacao = att_paymentpix($idtransaction);
    } else {
        error_log("Transação não processada - Status: " . $data['status']);
        http_response_code(400);
        echo json_encode(["message" => "Status da transação não permitido para processamento"]);
        exit;
    }
}
# MODO SANDBOX 
if ($tipoAPI_SUITPAY == 0) {
    if (isset($idTransaction) && $typeTransaction == "PIX" && $statusTransaction == "UNPAID") {
        $att_transacao = att_paymentpix($idTransaction);
    }
    #====================================================================#
# Verificar se o status é "FAILED" ou "failed"
    if (isset($data['status']) && (strtoupper($data['status']) == 'FAILED' || strtolower($data['status']) == 'failed')) {
        $externalreference = PHP_SEGURO($data['externalreference']);
        $amount = PHP_SEGURO($data['amount']);

        $result = handle_failed_withdrawal($externalreference, $amount);

        if ($result) {
            http_response_code(200);
            echo json_encode(["message" => "Saque cancelado e saldo devolvido com sucesso."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao processar saque cancelado: Saque não encontrado ou dados inválidos."]);
        }
    }
    #====================================================================#
}
#====================================================================#
?>