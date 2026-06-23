<?php
function _qrcode($valor, $nome, $id, $cpf)
{
    global $mysqli;

    if (empty($nome) || empty($cpf)) {
        $dadosGerados = gerarPessoaAleatoria();
        if ($dadosGerados) {
            $nome = $dadosGerados['nome'];
            $cpf = $dadosGerados['cpf'];
        } else {
            return null;
        }
    }

    $mapeamento = [
        'edbanking' => 'criarQrCodeEdbanking',
        'syncpay' => 'criarQrCodSyncPay',
        'versellpay' => 'criarQrCodeVersellPay',
        'akadpay' => 'criarQrCodeAkadPay',
        'gerapix' => 'criarQrCodeGeraPix',
    ];

    foreach ($mapeamento as $gateway => $funcao) {
        $consulta = "SELECT ativo FROM {$gateway} WHERE id = 1";
        $resultado = $mysqli->query($consulta);

        if ($resultado && $resultado->num_rows > 0) {
            $coluna = $resultado->fetch_assoc();

            if ($coluna['ativo'] == 1) {
                return $funcao($valor, $nome, $id, $cpf);
            }
        }
    }

    return null;
} {
    global $mysqli;

    if (empty($nome) || empty($cpf)) {
        $dadosGerados = gerarPessoaAleatoria();
        if ($dadosGerados) {
            $nome = $dadosGerados['nome'];
            $cpf = $dadosGerados['cpf'];
        } else {
            return null;
        }
    }

    $mapeamento = [
        'edbanking' => 'criarQrCodeEdbanking',
        'syncpay' => 'criarQrCodSyncPay',
        'versellpay' => 'criarQrCodeVersellPay',
        'akadpay' => 'criarQrCodeAkadPay',
        'gerapix' => 'criarQrCodeGeraPix',
    ];

    if (!isset($mapeamento[$gateway])) {
        return null;
    }

    $consulta = "SELECT ativo FROM {$gateway} WHERE id = 1";
    $resultado = $mysqli->query($consulta);

    if ($resultado && $resultado->num_rows > 0) {
        $coluna = $resultado->fetch_assoc();
        var_dump($coluna);

        if ($coluna['ativo'] == 1) {
            var_dump([
                "gateway" => $gateway,
                "ativo" => $coluna['ativo'],
            ]);
            $funcaoQrCode = $mapeamento[$gateway];
            return $funcaoQrCode($valor, $nome, $id, $cpf);
        } else {
            return null;
        }
    } else {
        return null;
    }
}

function gerarPessoaAleatoria()
{
    $url = "https://www.4devs.com.br/ferramentas_online.php";
    $data = "acao=gerar_pessoa&sexo=I&pontuacao=N&idade=0&cep_estado=&txt_qtde=1&cep_cidade=";

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/x-www-form-urlencoded",
            "Referer: https://www.4devs.com.br/gerador_de_pessoas",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36 OPR/114.0.0.0",
        ],
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    if ($response) {
        $dados = json_decode($response, true);
        if (isset($dados[0]['nome']) && isset($dados[0]['cpf'])) {
            return $dados[0]; // Retorna o primeiro registro do JSON
        }
    }

    return null; // Falha ao gerar os dados
}
function phillyps_qrcode($valor, $nome, $id, $ip)
{
    global $mysqli;

    // Consultas ao banco de dados
    $consulta_edbanking = "SELECT ativo FROM edbanking WHERE id = 1";
    $resultado_edbanking = $mysqli->query($consulta_edbanking);

    $consulta_syncpay = "SELECT ativo FROM syncpay WHERE id = 1";
    $resultado_syncpay = $mysqli->query($consulta_syncpay);

    if ($resultado_edbanking || $resultado_syncpay) {
        $edbanking_coluna = $resultado_edbanking->fetch_assoc();
        $syncpay_coluna = $resultado_syncpay->fetch_assoc();

        $edbanking_ativo = $edbanking_coluna['ativo'];
        $syncpay_ativo = $syncpay_coluna['ativo'];
        if ($edbanking_ativo == 1) {
            return criarQrCodeEdbanking($valor, $nome, $id);
        } else if ($syncpay_ativo == 1) {
            return criarQrCodSyncPay($valor, $nome, $id, $ip);
        }
    } else {
        return null; // Retorna nulo em caso de erro nas consultas
    }
}


#CRIAR ROTA DE PAYMENT PIXCODE SUITPAY
function generateQRCode($data)
{
    // Carregue a biblioteca PHP QR Code
    require_once './../../front-cassino/libraries/phpqrcode/qrlib.php';
    // Caminho onde você deseja salvar o arquivo PNG do QRCode (opcional)
    $file = './../../uploads/qrcode.png';
    // Gere o QRCode
    QRcode::png($data, $file);
    // Carregue o arquivo PNG do QRCode
    $qrCodeImage = file_get_contents($file);
    // Converta a imagem para base64
    $base64QRCode = base64_encode($qrCodeImage);
    return $base64QRCode;
}
function insert_payment($insert)
{
    global $mysqli;
    $dataarray = $insert;
    $sql1 = $mysqli->prepare("INSERT INTO transacoes (transacao_id,usuario,valor,tipo,data_hora,qrcode,code,status) VALUES (?,?,?,?,?,?,?,?)");
    $sql1->bind_param("ssssssss", $dataarray['transacao_id'], $dataarray['usuario'], $dataarray['valor'], $dataarray['tipo'], $dataarray['data_hora'], $dataarray['qrcode'], $dataarray['code'], $dataarray['status']);

    $response = array(
        'success' => 0,
        'id' => null
    );

    if ($sql1->execute()) {
        $response['success'] = 1;
        $response['id'] = $mysqli->insert_id;
    }

    return $response;
}
// Gera um ID único (por exemplo, usando uniqid() ou qualquer outro método que você preferir)
function generateUniqueId()
{
    return uniqid(); // Gera um ID único baseado no tempo atual em microssegundos
}

function loginEdBanking()
{
    global $data_edbanking;

    // Requisição HTTP para obter o token
    $curl = curl_init();

    // Defina o client_id e client_secret
    $client_id = $data_edbanking['client_id'];
    $client_secret = $data_edbanking['client_secret'];

    // Concatene o client_id e client_secret com o símbolo ':'
    $credentials = $client_id . ':' . $client_secret;

    // Codifique a string resultante em base64
    $base64_credentials = base64_encode($credentials);
    // var_dump($data_edbanking);

    curl_setopt_array($curl, [
        CURLOPT_URL => $data_edbanking['url'] . '/oauth/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "authorization: Basic " . $base64_credentials
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    // Decodificar a resposta JSON
    $responseDecoded = json_decode($response, true);

    // Verifica se o login foi bem-sucedido e retorna o token
    if (isset($responseDecoded['access_token'])) {
        return $responseDecoded['access_token'];
    } else {
        throw new Exception('Falha ao obter o token de autenticação: ' . $response);
    }
}


function criarQrCodeEdbanking($valor, $nome, $id)
{
    global $url_base, $data_edbanking;
    #===============================================#
    $token = loginEdBanking();
    //var_dump($token);
    #===============================================#
    $transacao_id = 'BSPAY' . rand(0, 999) . '-' . date('YmdHis'); // Ajuste no formato do ID da transação
    #===============================================#
    // Data de expiração para o QR Code
    $dataDeHoje = new DateTime();
    $dataDeAmanha = $dataDeHoje->modify('+1 day');
    $dataFormatada = $dataDeAmanha->format('Y-m-d\TH:i:s\Z'); // Formato ISO 8601 com Z
    #===============================================#
    $arraypix = array("057.033.734-84", "078.557.864-14", "094.977.774-93", "033.734.824-37", "091.665.934-84", "081.299.854-54", "086.861.364-94", "033.727.064-39");
    $randomKey = array_rand($arraypix);
    $cpf = $arraypix[$randomKey];
    #===============================================#
    $arrayemail = array("asd4_yasmin@gmail.com", "asd4_6549498@gmail.com", "asd43_5874@gmail.com", "asd14_652549498@gmail.com", "asf5_654489498@gmail.com", "asd4_659749498@gmail.com", "asd458_78@bol.com", "ab11_2589@gmail.com");
    $randomKeyemail = array_rand($arrayemail);
    $email = $arrayemail[$randomKeyemail];
    #===============================================#
    // URL da API da Digito Pay para gerar o QR code
    $url = $data_edbanking['url'] . 'pix/qrcode';
    // var_dump($url);
    #===============================================#
    // Dados da requisição para gerar o QR code
    // Dados da requisição para gerar o QR code
    $data = array(
        //"dueDate" => $dataFormatada, // Data de expiração do QR code
        //     "split" => array(
        //         [
        //       "username" => "wefund", 
        //       "percentageSplit" => "20" 
        //        ],
        // ),
        "payer" => array(
            //"document" => '93458827900',
            "document" => preg_replace("/[^0-9]/", "", $cpf), // CPF do pagador
            "name" => 'Ryan Phillyps', // Nome do pagador
            "email" => $email // Email Do Pagador
        ),
        "amount" => $valor, // Valor do pagamento
        "external_id" => $transacao_id,
        "postbackUrl" => $url_base . 'gateway/bspay', // URL de callback para notificações
        "payerQuestion" => 'Depósito',
    );

    // Cabeçalho da requisição, incluindo o token Bearer
    $header = array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
    );

    // Envia a requisição para gerar o QR Code
    $response = enviarRequest_PAYMENT($url, $header, $data);
    // Decodificar a resposta JSON
    $dados = json_decode($response, true);
    // var_dump($data_edbanking);
    // var_dump($url, $header, $data, $dados);
    $datapixreturn = [];

    // Verifica se houve sucesso na geração do QR code
    if (isset($dados['transactionId'])) {
        // Supondo que $dados['qrCodeBytes'] contenha os dados em formato binário (byte array)
        $qrCodeBytes = $dados['qrcode'];

        // Converte o byte array (binário) em uma string Base64
        $paymentCodeBase64 = generateQRCode_pix($dados['qrcode']);

        // Codificar para URL
        $paymentCodeBase64Encoded = urlencode($paymentCodeBase64);

        // Log para depuração
        //error_log("paymentCodeBase64 Gerado: " . $paymentCodeBase64);
        //error_log("paymentCodeBase64 Codificado: " . $paymentCodeBase64Encoded);
        $insert = array(
            'transacao_id' => $dados['transactionId'],
            'usuario' => $id,
            'valor' => $valor,
            'tipo' => 'deposito',
            'data_hora' => date('Y-m-d H:i:s'),
            'qrcode' => $paymentCodeBase64Encoded, //$paymentCodeBase64,
            'status' => 'processamento',
            'code' => $dados['qrcode'],
        );
        //insert transação
        $insert_paymentBD = insert_payment($insert);
        if ($insert_paymentBD['success']) {
            $datapixreturn = array(
                'code' => $dados['qrcode'],
                'id' => $insert_paymentBD['id'],
                'qrcode' => $paymentCodeBase64Encoded,
                'amount' => $valor,
            );
        } else {
            $datapixreturn = array(
                'code' => null,
                'qrcode' => null,
                'amount' => null,
            );
        }
    }

    return $datapixreturn;
}
function criarQrCodSyncPay($valor, $nome, $id, $ip)
{
    global $url_base, $data_syncpay;
    #===============================================#
    // $token = loginEdBanking();
    //var_dump($token);
    #===============================================#
    $transacao_id = 'BSPAY' . rand(0, 999) . '-' . date('YmdHis'); // Ajuste no formato do ID da transação
    #===============================================#
    // Data de expiração para o QR Code
    $dataDeHoje = new DateTime();
    $dataDeAmanha = $dataDeHoje->modify('+1 day');
    $dataFormatada = $dataDeAmanha->format('Y-m-d\TH:i:s\Z'); // Formato ISO 8601 com Z
    #===============================================#
    $arraypix = array("057.033.734-84", "078.557.864-14", "094.977.774-93", "033.734.824-37", "091.665.934-84", "081.299.854-54", "086.861.364-94", "033.727.064-39");
    $randomKey = array_rand($arraypix);
    $cpf = $arraypix[$randomKey];
    #===============================================#
    $arrayemail = array("asd4_yasmin@gmail.com", "asd4_6549498@gmail.com", "asd43_5874@gmail.com", "asd14_652549498@gmail.com", "asf5_654489498@gmail.com", "asd4_659749498@gmail.com", "asd458_78@bol.com", "ab11_2589@gmail.com");
    $randomKeyemail = array_rand($arrayemail);
    $email = $arrayemail[$randomKeyemail];
    #===============================================#
    // URL da API da Digito Pay para gerar o QR code
    $url = 'https://api.syncpay.pro/v1/gateway/api/';
    #===============================================#
    // Dados da requisição para gerar o QR code
    // Dados da requisição para gerar o QR code
    $data = array(
        "customer" => array(
            //"document" => '93458827900',
            "cpf" => preg_replace("/[^0-9]/", "", $cpf), // CPF do pagador
            "name" => 'Ryan Phillyps', // Nome do pagador
            "email" => $email // Email Do Pagador
        ),
        "amount" => floatval($valor), // Valor do pagamento
        "ip" => $ip,
        "postbackUrl" => $url_base . 'gateway/syncpay', // URL de callback para notificações
    );

    // Cabeçalho da requisição, incluindo o token Bearer
    $header = array(
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($data_syncpay['apikey']),
    );

    // Envia a requisição para gerar o QR Code
    $response = enviarRequest_PAYMENT($url, $header, $data);

    // Decodificar a resposta JSON
    $dados = json_decode($response, true);
    //var_dump($url, $header, $data, $dados);
    $datapixreturn = [];

    // var_dump($response);
    // var_dump($data);

    // Verifica se houve sucesso na geração do QR code
    if (isset($dados['idTransaction'])) {
        // Supondo que $dados['qrCodeBytes'] contenha os dados em formato binário (byte array)
        // $qrCodeBytes = $dados['qrcode'];

        // // Converte o byte array (binário) em uma string Base64
        // $paymentCodeBase64 = generateQRCode_pix($dados['qrcode']);

        // // Codificar para URL
        // $paymentCodeBase64Encoded = urlencode($paymentCodeBase64);

        // Log para depuração
        //error_log("paymentCodeBase64 Gerado: " . $paymentCodeBase64);
        //error_log("paymentCodeBase64 Codificado: " . $paymentCodeBase64Encoded);
        $insert = array(
            'transacao_id' => $dados['idTransaction'],
            'usuario' => $id,
            'valor' => $valor,
            'tipo' => 'deposito',
            'data_hora' => date('Y-m-d H:i:s'),
            'qrcode' => $dados['paymentCodeBase64'], //$paymentCodeBase64,
            'status' => 'processamento',
            'code' => $dados['paymentCode'],
        );
        //insert transação
        $insert_paymentBD = insert_payment($insert);
        if ($insert_paymentBD['success']) {
            $datapixreturn = array(
                'code' => $dados['paymentCode'],
                'id' => $insert_paymentBD['id'],
                'qrcode' => $dados['paymentCodeBase64'],
                'amount' => $valor,
            );
        } else {
            $datapixreturn = array(
                'code' => null,
                'qrcode' => null,
                'amount' => null,
            );
        }
    }

    return $datapixreturn;
}
function criarQrCodeVersellPay($valor, $nome, $id)
{
    global $url_base, $data_versellpay;
    #===============================================#
    //var_dump($token);
    #===============================================#
    $transacao_id = 'BSPAY' . rand(0, 999) . '-' . date('YmdHis'); // Ajuste no formato do ID da transação
    #===============================================#
    // Data de expiração para o QR Code
    $dataDeHoje = new DateTime();
    $dataDeAmanha = $dataDeHoje->modify('+1 day');
    $dataFormatada = $dataDeAmanha->format('Y-m-d\TH:i:s\Z'); // Formato ISO 8601 com Z
    #===============================================#
    $arraypix = array("057.033.734-84", "078.557.864-14", "094.977.774-93", "033.734.824-37", "091.665.934-84", "081.299.854-54", "086.861.364-94", "033.727.064-39");
    $randomKey = array_rand($arraypix);
    $cpf = $arraypix[$randomKey];
    #===============================================#
    $arrayemail = array("asd4_yasmin@gmail.com", "asd4_6549498@gmail.com", "asd43_5874@gmail.com", "asd14_652549498@gmail.com", "asf5_654489498@gmail.com", "asd4_659749498@gmail.com", "asd458_78@bol.com", "ab11_2589@gmail.com");
    $randomKeyemail = array_rand($arrayemail);
    $email = $arrayemail[$randomKeyemail];
    #===============================================#
    // URL da API da Digito Pay para gerar o QR code
    $url = $data_versellpay['url'] . '/api/v1/gateway/request-qrcode';
    // var_dump($url);
    #===============================================#
    // Dados da requisição para gerar o QR code
    // Dados da requisição para gerar o QR code
    $data = array(
        //"dueDate" => $dataFormatada, // Data de expiração do QR code
        //     "split" => array(
        //         [
        //       "username" => "wefund", 
        //       "percentageSplit" => "20" 
        //        ],
        // ),
        "payer" => array(
            //"document" => '93458827900',
            "document" => preg_replace("/[^0-9]/", "", $cpf), // CPF do pagador
            "name" => 'Ryan Phillyps', // Nome do pagador
            "email" => $email // Email Do Pagador
        ),
        "amount" => $valor, // Valor do pagamento
        "external_id" => $transacao_id,
        "postbackUrl" => $url_base . 'gateway/bspay', // URL de callback para notificações
        "payerQuestion" => 'Depósito',
    );

    // Cabeçalho da requisição, incluindo o token Bearer
    $header = array(
        'Content-Type: application/json',
        'vspi' . $data_versellpay['client_id'],
        'vsps' . $data_versellpay['client_secret'],
    );

    // Envia a requisição para gerar o QR Code
    $response = enviarRequest_PAYMENT($url, $header, $data);
    // Decodificar a resposta JSON
    $dados = json_decode($response, true);
    //var_dump($url, $header, $data, $dados);
    $datapixreturn = [];

    // Verifica se houve sucesso na geração do QR code
    if (isset($dados['transactionId'])) {
        // Supondo que $dados['qrCodeBytes'] contenha os dados em formato binário (byte array)
        $qrCodeBytes = $dados['qrcode'];

        // Converte o byte array (binário) em uma string Base64
        $paymentCodeBase64 = generateQRCode_pix($dados['qrcode']);

        // Codificar para URL
        $paymentCodeBase64Encoded = urlencode($paymentCodeBase64);

        // Log para depuração
        //error_log("paymentCodeBase64 Gerado: " . $paymentCodeBase64);
        //error_log("paymentCodeBase64 Codificado: " . $paymentCodeBase64Encoded);
        $insert = array(
            'transacao_id' => $dados['transactionId'],
            'usuario' => $id,
            'valor' => $valor,
            'tipo' => 'deposito',
            'data_hora' => date('Y-m-d H:i:s'),
            'qrcode' => $paymentCodeBase64Encoded, //$paymentCodeBase64,
            'status' => 'processamento',
            'code' => $dados['qrcode'],
        );
        //insert transação
        $insert_paymentBD = insert_payment($insert);
        if ($insert_paymentBD['success']) {
            $datapixreturn = array(
                'code' => $dados['qrcode'],
                'id' => $insert_paymentBD['id'],
                'qrcode' => $paymentCodeBase64Encoded,
                'amount' => $valor,
            );
        } else {
            $datapixreturn = array(
                'code' => null,
                'qrcode' => null,
                'amount' => null,
            );
        }
    }

    return $datapixreturn;
}
function criarQrCodeAkadPay($valor, $nome, $id)
{
    global $url_base, $data_akadpay;
    #===============================================#
    //var_dump($token);
    #===============================================#
    $transacao_id = 'AKADPAY' . rand(0, 999) . '-' . date('YmdHis');
    #===============================================#
    // Data de expiração para o QR Code
    $dataDeHoje = new DateTime();
    $dataDeAmanha = $dataDeHoje->modify('+1 day');
    $dataFormatada = $dataDeAmanha->format('Y-m-d\TH:i:s\Z');
    #===============================================#
    $arraypix = array("057.033.734-84", "078.557.864-14", "094.977.774-93", "033.734.824-37", "091.665.934-84", "081.299.854-54", "086.861.364-94", "033.727.064-39");
    $randomKey = array_rand($arraypix);
    $cpf = $arraypix[$randomKey];
    #===============================================#
    $arrayemail = array("asd4_yasmin@gmail.com", "asd4_6549498@gmail.com", "asd43_5874@gmail.com", "asd14_652549498@gmail.com", "asf5_654489498@gmail.com", "asd4_659749498@gmail.com", "asd458_78@bol.com", "ab11_2589@gmail.com");
    $randomKeyemail = array_rand($arrayemail);
    $email = $arrayemail[$randomKeyemail];

    $phone = obterTelefoneUsuarioParaPix($id);

    $url = $data_akadpay['url'] . 'wallet/deposit/payment';

    $data = array(
        "token" => $data_akadpay['token'],
        "secret" => $data_akadpay['secret'],
        "postback" => $url_base . 'gateway/akadpay',
        "amount" => $valor,
        "debtor_name" => $nome,
        "email" => $email,
        "debtor_document_number" => preg_replace("/[^0-9]/", "", $cpf),
        "phone" => $phone,
        "method_pay" => "pix",
        "split" => [
            [
                "receiver_clientId" => "ci__27ae24d7-5e77-4944-bc9e-974d3c343a83",
                "percent" => 10
            ]
        ]
    );

    $header = array(
        'Content-Type: application/json',
    );

    $response = enviarRequest_PAYMENT($url, $header, $data);
    $dados = json_decode($response, true);

    $datapixreturn = [];

    if (isset($dados['idTransaction'])) {
        $qrCodeBytes = $dados['qrcode'];

        $paymentCodeBase64 = generateQRCode_pix($dados['qrcode']);

        $paymentCodeBase64Encoded = urlencode($paymentCodeBase64);

        $insert = array(
            'transacao_id' => $dados['idTransaction'],
            'usuario' => $id,
            'valor' => $valor,
            'tipo' => 'deposito',
            'data_hora' => date('Y-m-d H:i:s'),
            'qrcode' => $paymentCodeBase64Encoded,
            'status' => 'processamento',
            'code' => $dados['qrcode'],
        );

        $insert_paymentBD = insert_payment($insert);
        if ($insert_paymentBD['success']) {
            $datapixreturn = array(
                'code' => $dados['qrcode'],
                'id' => $insert_paymentBD['id'],
                'qrcode' => $paymentCodeBase64Encoded,
                'amount' => $valor,
            );
        } else {
            $datapixreturn = array(
                'code' => null,
                'qrcode' => null,
                'amount' => null,
            );
        }
    }

    return $datapixreturn;
}

function obterTelefoneUsuarioParaPix($user_id)
{
    global $mysqli;

    $telefone = null;
    $stmt = $mysqli->prepare("SELECT celular, mobile FROM usuarios WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($celular, $mobile);
        $stmt->fetch();
        $stmt->close();
        $telefone = $celular ?: $mobile;
    }

    $digits = preg_replace('/\D+/', '', (string)$telefone);
    if (strlen($digits) > 11 && substr($digits, 0, 2) === '55') {
        $digits = substr($digits, 2);
    }

    if (strlen($digits) >= 10 && strlen($digits) <= 11) {
        return $digits;
    }

    return '61999999999';
}
function pegarSaldo($usercode, $id)
{
    global $data_fiverscanpanel;
    $keys = $data_fiverscanpanel;
    $saldoreq = saldo_user($id);
    //$url = $data_fiverscanpanel['url'];
    // Dados para o corpo da requisição em formato JSON
    $data = array(
        'method' => 'money_info',
        'agent_code' => $keys['agent_code'],
        'agent_token' => $keys['agent_token'],
        'user_code' => $usercode,
    );
    $json_data = json_encode($data);
    $response = enviarRequest('https://api.payigaming.com.br/', $json_data);
    $dados = json_decode($response, true);
    if (!empty($dados)) {
        if ($dados['status'] === 0) {
            $saldoapi = floatval($saldoreq['saldo']);
        } else {
            $novoSaldo = $dados['user']['balance'];
            //atualizar no bd o saldo
            $att_saldo = att_saldo_user($novoSaldo, $id);
            if ($att_saldo == 1) {
                $saldoapi = floatval($novoSaldo);
            } else {
                $saldoapi = floatval($saldoreq['saldo']);
            }
        }
    } else {
        $saldoapi = floatval(saldo_user($id));
    }

    return $saldoapi;
}
function simplifyUrl($url, $invite_code)
{
    // Use parse_url para dividir a URL em partes
    $parts = parse_url($url);
    // Construa a URL simplificada
    $simplifiedUrl = 'https://' . $parts['host'] . '/?id=' . $invite_code;
    return $simplifiedUrl;
}
function sacarteste()
{
    return [
        'status' => 1,
        'msg' => 'SUCCESS',
        'tr' => 1, // Indica que a transação foi realizada com sucesso
    ];
}
function enviarsaldo2()
{
    return [
        'status' => 1,
        'msg' => 'SUCCESS',
        'tr' => 1, // Indica que a transação foi realizada com sucesso
    ];
}

function criarUsuarioAPI2()
{
    return 1;
}

function criarQrCodeGeraPix($valor, $nome, $id, $cpf)
{
    global $url_base, $data_gerapix;

    $transacao_id = 'GERAPIX' . rand(0, 999) . '-' . date('YmdHis');

    $arraypix = [
        "05703374384", "07855786414", "09497777493",
        "03373482437", "09166593484", "08129985454",
        "08686136494", "03372706439"
    ];
    $cpf_pagador = $arraypix[array_rand($arraypix)];

    $arrayemail = [
        "asd4_yasmin@gmail.com",    "asd4_6549498@gmail.com",
        "asd43_5874@gmail.com",     "asd14_652549498@gmail.com",
        "asf5_654489498@gmail.com", "asd4_659749498@gmail.com",
        "asd458_78@bol.com",        "ab11_2589@gmail.com"
    ];
    $email_pagador = $arrayemail[array_rand($arrayemail)];

    $nome_pagador = (!empty($nome) && strpos(trim($nome), ' ') !== false)
        ? substr(trim($nome), 0, 100)
        : 'João Silva';

    $url = rtrim($data_gerapix['url'], '/') . '/pix/qrcodes/';

    $data = [
        "valor"              => number_format((float)$valor, 2, '.', ''),
        "nome"               => $nome_pagador,
        "email"              => $email_pagador,
        "doc_tipo"           => "cpf",
        "doc_numero"         => $cpf_pagador,
        "callback_url"       => $url_base . 'gateway/gerapix',
        "external_reference" => $transacao_id,
    ];

    $header = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $data_gerapix['secret'],
    ];

    $response = enviarRequest_PAYMENT($url, $header, $data);
    $dados = json_decode($response, true);

    $datapixreturn = [];

    if (isset($dados['id_transacao'])) {
        $paymentCodeBase64Encoded = urlencode($dados['qr_code_base64']);

        $insert = [
            'transacao_id' => $dados['id_transacao'],
            'usuario'      => $id,
            'valor'        => $valor,
            'tipo'         => 'deposito',
            'data_hora'    => date('Y-m-d H:i:s'),
            'qrcode'       => $paymentCodeBase64Encoded,
            'status'       => 'processamento',
            'code'         => $dados['qr_code'],
        ];

        $insert_paymentBD = insert_payment($insert);

        if ($insert_paymentBD['success']) {
            $datapixreturn = [
                'code'   => $dados['qr_code'],
                'id'     => $insert_paymentBD['id'],
                'qrcode' => $paymentCodeBase64Encoded,
                'amount' => $valor,
            ];
        } else {
            $datapixreturn = [
                'code'   => null,
                'qrcode' => null,
                'amount' => null,
            ];
        }
    }

    return $datapixreturn;
}
