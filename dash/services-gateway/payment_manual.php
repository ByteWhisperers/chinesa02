<?php
// Melhor configuração de erros - Para produção, mantenha display_errors desativado
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();

// Inclua o arquivo de conexão ao banco de dados
include_once('../services/database.php');
include_once('../services/funcao.php');
include_once('../services/crud.php'); // Se houver funções adicionais de segurança, como PHP_SEGURO()
global $url_base;
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

function identificarTipoChavePix($flag)
{
    $tiposChavePix = [
        [
            "displayName" => "CPF",
            "ty" => 3,
            "enable" => true,
            "num" => 1,
        ],
        [
            "displayName" => "PHONE",
            "ty" => 2,
            "enable" => true,
            "num" => 1,
        ],
        [
            "displayName" => "EMAIL",
            "ty" => 1,
            "enable" => true,
            "num" => 1,
        ],
        [
            "displayName" => "CNPJ",
            "ty" => 4,
            "enable" => false,
            "num" => 1,
        ],
    ];

    foreach ($tiposChavePix as $tipo) {
        if ($tipo['ty'] == $flag) {
            return $tipo['displayName'];
        }
    }

    return 'invalid';
}

function normalizarMensagemErro($msg)
{
    $m = trim((string)$msg);
    $lower = strtolower($m);
    if ($lower === 'saldo insuficiente' || strpos($lower, 'saldo insuficiente') !== false || strpos($lower, 'insufficient') !== false) {
        return 'Saldo Insuficiente.';
    }
    if ($m !== '' && substr($m, -1) !== '.') {
        return $m . '.';
    }
    return $m !== '' ? $m : 'Erro desconhecido.';
}

function processarSaqueAkadPay($valor, $chavepix, $id, $tipoChavePix)
{
    global $mysqli, $url_base, $data_akadpay;

    $url = 'https://painel.akadpay.com.br/api/pixout';

    $data = array(
        "token" => $data_akadpay['token'],
        "secret" => $data_akadpay['secret'],
        "baasPostbackUrl" => $url_base . 'gateway/akadpay',
        "amount" => (float)$valor,
        "pixKey" => $chavepix['pix_account'],
        "pixKeyType" => strtolower($tipoChavePix)
    );

    $header = array(
        'Content-Type: application/json',
        'Accept: application/json'
    );

    // cURL com captura de erro e HTTP code
    $ch = curl_init();
    $data_json = json_encode($data);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
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
    $log_payload = $data;
    if (isset($log_payload['token'])) {
        $log_payload['token'] = '***';
    }
    if (isset($log_payload['secret'])) {
        $log_payload['secret'] = '***';
    }
    log_withdraw_debug('akadpay_response', [
        'transacao_id' => $id,
        'payload' => $log_payload,
        'response_raw' => $response,
        'http_code' => $http_code,
        'curl_error' => $curl_error,
        'json_error' => json_last_error_msg(),
    ]);
    if (isset($responsejson['id'])) {
        $idTransaction = $responsejson['id'];

        $sql_update = "UPDATE solicitacao_saques SET status = 1, telefone = ? WHERE transacao_id = ?";
        if ($stmt_update = $mysqli->prepare($sql_update)) {
            $stmt_update->bind_param("ss", $idTransaction, $id);
            $stmt_update->execute();

            if ($stmt_update->affected_rows > 0) {
                return ["success" => true, "message" => "Saque com AkadPay aprovado com sucesso."];
            } else {
                return ["status" => "error", "message" => normalizarMensagemErro("Nenhuma alteração realizada no banco de dados para AkadPay")];
            }
        } else {
            return ["status" => "error", "message" => normalizarMensagemErro("Erro ao preparar query de atualização para AkadPay: " . $mysqli->error)];
        }
    } else {
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
        $errorMessage = normalizarMensagemErro($errorMessage ?? 'Erro desconhecido no AkadPay');
        return ["status" => "error", "message" => $errorMessage];
    }
}

function processarSaqueEdbanking($valor, $chavepix, $id, $tipoChavePix)
{
    global $mysqli, $url_base;

    $sql_credenciais = "SELECT url,client_id,client_secret FROM edbanking WHERE id = 1";
    if ($stmt_credenciais = $mysqli->prepare($sql_credenciais)) {
        $stmt_credenciais->execute();
        $stmt_credenciais->bind_result($url, $client_id, $client_secret);
        $stmt_credenciais->fetch();
        $stmt_credenciais->close();

        if ($url && $client_id && $client_secret) {
            $external_id = uniqid('pix_') . '_' . rand(1000, 9999);

            $auth_curl = curl_init();
            curl_setopt_array($auth_curl, array(
                CURLOPT_URL => 'https://api.mkmusk.online/v2/oauth/token',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => array(
                    'accept: application/json',
                    'authorization: Basic ' . base64_encode($client_id . ':' . $client_secret)
                ),
            ));

            $auth_response = curl_exec($auth_curl);
            $auth_err = curl_error($auth_curl);
            curl_close($auth_curl);

            if ($auth_err) {
                return ["status" => "error", "message" => normalizarMensagemErro("Erro na autenticação do Edbanking: " . $auth_err)];
            }

            $auth_data = json_decode($auth_response, true);

            if (!isset($auth_data['access_token'])) {
                return ["status" => "error", "message" => normalizarMensagemErro("Falha ao obter token de autenticação do Edbanking")];
            }

            $access_token = $auth_data['access_token'];

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.mkmusk.online/v2/pix/payment',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    "creditParty" => [
                        "name" => "Nome do Beneficiario",
                        "keyType" => $tipoChavePix,
                        "key" => $chavepix['pix_account'],
                        "taxId" => $chavepix['pix_id']
                    ],
                    "amount" => $valor,
                    "description" => "Pagamento na plataforma " . $url_base . " no valor de " . $valor,
                    "external_id" => $external_id
                ]),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $access_token,
                    'Content-Type: application/json'
                ),
            ));

            $enviarpagamento = curl_exec($curl);
            $curl_error = curl_error($curl);
            curl_close($curl);

            if ($curl_error) {
                return ["status" => "error", "message" => normalizarMensagemErro("Erro na comunicação com o Edbanking: " . $curl_error)];
            }

            $responsejson = json_decode($enviarpagamento, true);

            if ((isset($responsejson['status']) && $responsejson['status'] == '200') ||
                (isset($responsejson['message']) && $responsejson['message'] == 'Pagamento processado com sucesso.')
            ) {

                $idTransaction = $responsejson['transactionId'] ?? ($responsejson['data']['externalreference'] ?? null);

                $sql_update = "UPDATE solicitacao_saques SET status = 1, telefone = ? WHERE transacao_id = ?";
                if ($stmt_update = $mysqli->prepare($sql_update)) {
                    $stmt_update->bind_param("ss", $idTransaction, $id);
                    $stmt_update->execute();

                    if ($stmt_update->affected_rows > 0) {
                        return ["success" => true, "message" => "Saque com Edbanking aprovado com sucesso."];
                    } else {
                        return ["success" => false, "message" => "Nenhuma alteração realizada no banco de dados para Edbanking."];
                    }
                } else {
                    return ["success" => false, "message" => "Erro ao preparar query de atualização para Edbanking: " . $mysqli->error];
                }
            } else {
                // Extrai mensagem específica quando disponível e normaliza
                $apiMessage = null;
                if (isset($responsejson['message'])) {
                    $apiMessage = $responsejson['message'];
                } elseif (isset($responsejson['errors'])) {
                    if (is_array($responsejson['errors'])) {
                        $first = reset($responsejson['errors']);
                        $apiMessage = is_array($first) && isset($first['message']) ? $first['message'] : (string)$first;
                    } else {
                        $apiMessage = (string)$responsejson['errors'];
                    }
                }
                $apiMessage = normalizarMensagemErro($apiMessage ?? 'Erro desconhecido no Edbanking');
                return ["status" => "error", "message" => $apiMessage];
            }
        } else {
            return ["status" => "error", "message" => normalizarMensagemErro("Credenciais para Edbanking não encontradas")];
        }
    }
    return ["status" => "error", "message" => normalizarMensagemErro("Erro ao preparar a query para buscar credenciais do Edbanking")];
}

function processarSaqueGeraPix($valor, $chavepix, $id, $tipoChavePix)
{
    global $mysqli, $url_base;

    $sql_cred = "SELECT url, secret FROM gerapix WHERE id = 1";
    $stmt_cred = $mysqli->prepare($sql_cred);
    $stmt_cred->execute();
    $stmt_cred->bind_result($url_base_gerapix, $secret);
    $stmt_cred->fetch();
    $stmt_cred->close();

    if (empty($secret)) {
        return ["status" => "error", "message" => "Credenciais GeraPix não configuradas."];
    }

    $url_pixout = rtrim($url_base_gerapix, '/') . '/pix/payments/';

    $doc_tipo   = 'cpf';
    $doc_numero = preg_replace('/\D/', '', $chavepix['pix_id'] ?? '');

    if (empty($doc_numero) && $tipoChavePix === 'CPF') {
        $doc_numero = preg_replace('/\D/', '', $chavepix['pix_account']);
    }

    if (empty($doc_numero)) {
        return ["status" => "error", "message" => "CPF do destinatário não encontrado para GeraPix."];
    }

    $nome_destinatario = !empty($chavepix['realname']) ? substr(trim($chavepix['realname']), 0, 100) : 'Destinatario Pix';
    if (strpos(trim($nome_destinatario), ' ') === false) {
        $nome_destinatario = $nome_destinatario . ' Titular';
    }

    $data = [
        "valor"              => number_format((float)$valor, 2, '.', ''),
        "nome"               => $nome_destinatario,
        "doc_tipo"           => $doc_tipo,
        "doc_numero"         => $doc_numero,
        "callback_url"       => $url_base . 'gateway/gerapix',
        "external_reference" => 'SAQUE-' . $id,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_pixout);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $secret,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response   = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    log_withdraw_debug('gerapix_pixout_response', [
        'transacao_id' => $id,
        'payload'      => $data,
        'response_raw' => $response,
        'http_code'    => $http_code,
        'curl_error'   => $curl_error,
    ]);

    $responsejson = json_decode($response, true);

    if ($http_code === 201 && isset($responsejson['id_transacao'])) {
        $idTransaction = $responsejson['id_transacao'];
        $sql_update = "UPDATE solicitacao_saques SET status = 1, telefone = ? WHERE transacao_id = ?";
        $stmt_update = $mysqli->prepare($sql_update);
        if ($stmt_update) {
            $stmt_update->bind_param("ss", $idTransaction, $id);
            $stmt_update->execute();
            if ($stmt_update->affected_rows > 0) {
                return ["success" => true, "message" => "Saque com GeraPix aprovado com sucesso."];
            } else {
                return ["status" => "error", "message" => normalizarMensagemErro("Nenhuma alteração no banco para GeraPix.")];
            }
        } else {
            return ["status" => "error", "message" => normalizarMensagemErro("Erro ao preparar query de atualização para GeraPix: " . $mysqli->error)];
        }
    }

    $errorMessage = null;
    if (isset($responsejson['message'])) {
        $errorMessage = $responsejson['message'];
    } elseif (isset($responsejson['error'])) {
        $errorMessage = is_array($responsejson['error']) && isset($responsejson['error']['message'])
            ? $responsejson['error']['message']
            : $responsejson['error'];
    } elseif (isset($responsejson['errors'])) {
        $first = is_array($responsejson['errors']) ? reset($responsejson['errors']) : $responsejson['errors'];
        $errorMessage = is_array($first) && isset($first['message']) ? $first['message'] : (string)$first;
    }
    return ["status" => "error", "message" => normalizarMensagemErro($errorMessage ?? 'Erro desconhecido no GeraPix')];
}

if (isset($_GET['id'])) {
    $id = PHP_SEGURO($_GET['id']);
    $sql = "SELECT valor, pix FROM solicitacao_saques WHERE transacao_id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $stmt->bind_result($valor, $chavepix1);
        $stmt->fetch();
        $stmt->close();

        $chavepix = localizarchavepixall($chavepix1);

        if ($valor && $chavepix) {
            $valor = number_format($valor, 2, '.', '');
            $tipoChavePix = identificarTipoChavePix($chavepix['flag']);

            if ($tipoChavePix === 'invalid') {
                echo json_encode(["status" => "error", "message" => "Chave PIX inválida."]);
                exit;
            }

            $mapeamento_saque = [
                'akadpay'  => 'processarSaqueAkadPay',
                'edbanking' => 'processarSaqueEdbanking',
                'gerapix'  => 'processarSaqueGeraPix',
            ];

            $gatewayAtivo = false;
            foreach ($mapeamento_saque as $gateway => $funcao) {
                $consulta = "SELECT ativo FROM {$gateway} WHERE id = 1";
                $resultado = $mysqli->query($consulta);

                if ($resultado && $resultado->num_rows > 0) {
                    $coluna = $resultado->fetch_assoc();
                    if ($coluna['ativo'] == 1) {
                        $resultado_saque = $funcao($valor, $chavepix, $id, $tipoChavePix);
                        echo json_encode($resultado_saque);
                        $gatewayAtivo = true;
                        break;
                    }
                }
            }

            if (!$gatewayAtivo) {
                echo json_encode(["status" => "error", "message" => "Nenhum gateway de saque ativo encontrado."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Saque não encontrado ou parâmetros inválidos."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Erro ao preparar a query de busca de saque."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Parâmetro 'id' não fornecido."]);
}






