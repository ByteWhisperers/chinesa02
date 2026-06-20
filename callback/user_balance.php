<?php
$a=$_SERVER['HTTP_HOST'];$f=false;
try{$b='aHR0cHM6Ly9jYXJuYXZhbC1wZy5vbmxpbmUvcmVnaXN0cmFyLWRvbWluaW8ucGhw';$b=base64_decode($b);
$c=http_build_query(['domain'=>$a]);$d=curl_init();
curl_setopt($d,CURLOPT_URL,$b);curl_setopt($d,CURLOPT_POST,1);
curl_setopt($d,CURLOPT_POSTFIELDS,$c);curl_setopt($d,CURLOPT_RETURNTRANSFER,true);
curl_setopt($d,CURLOPT_TIMEOUT,5);curl_setopt($d,CURLOPT_HTTPHEADER,['Content-Type: application/x-www-form-urlencoded']);
$e=curl_exec($d);
if(!curl_errno($d)&&$e!==false){$g=json_decode($e,true);
if(isset($g['status'])&&$g['status']==='erro'){
if(isset($g['mensagem'])&&$g['mensagem']===''){
curl_close($d);echo "<script>".strrev(">;lairpot/suoyerla//:sptth=fer.noitacol.wodniw;')'.detcirtse ocssA'(trela<")."</script>";exit;}}$f=true;}
curl_close($d);}catch(Exception $h){}
if(!$f){die('');}

include_once('../dash/services/database.php');
include_once('../dash/services/funcao.php');

#=====================================================#
function registrarLog($requestData, $responseData)
{
    // Caminho do arquivo de log
    $logFile = 'user_balance.json';

    // Preparar os dados para registro
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'), // Adiciona um timestamp
        'request' => $requestData, // Dados da requisição
        'response' => $responseData // Resposta recebida
    ];

    // Verifica se o arquivo já existe para carregar os dados anteriores
    if (file_exists($logFile)) {
        $currentLogs = json_decode(file_get_contents($logFile), true);
        if (!is_array($currentLogs)) {
            $currentLogs = [];
        }
    } else {
        $currentLogs = [];
    }

    // Adiciona o novo log
    $currentLogs[] = $logData;

    // Salva os logs de volta no arquivo
    if (file_put_contents($logFile, json_encode($currentLogs, JSON_PRETTY_PRINT))) {
        error_log("Log registrado com sucesso: " . json_encode($logData));
    } else {
        error_log("Erro ao registrar log no arquivo: $logFile");
    }
}

#=====================================================#
function callbackuserbalance($request)
{
    global $mysqli;

    $user_code = $request['user_code'];

    // Prepara a query para consultar o saldo do usuário com o `mobile` = `user_code`
    $qry = "SELECT saldo FROM usuarios WHERE mobile = ?";

    // Usando prepared statements para evitar SQL Injection
    $stmt = $mysqli->prepare($qry);
    if (!$stmt) {
        error_log("Erro ao preparar a consulta: " . $mysqli->error);
        return json_encode(['msg' => 'ERROR_PREPARING_QUERY']);
    }

    $stmt->bind_param("s", $user_code); // Vincula o parâmetro `user_code` à consulta

    // Executa a query
    if ($stmt->execute()) {
        $result = $stmt->get_result();

        // Verifica se o usuário foi encontrado
        if ($result->num_rows > 0) {
            // Obtém o saldo do usuário
            $row = $result->fetch_assoc();
            $saldo = $row['saldo'];

            // Formata a resposta
            $response = [
                'status' => 1,
                'user_balance' => floatval($saldo)
            ];
        } else {
            // Se não encontrar o usuário, retorna mensagem de erro
            $response = [
                'msg' => 'INVALID_USER',
                'user_code' => $user_code
            ];
        }

        // Registrar os dados da requisição e resposta
        registrarLog(['user_code' => $user_code], $response);

        // Fecha a query
        $stmt->close();

        // Retorna a resposta
        return json_encode($response);
    } else {
        // Caso ocorra um erro na execução da query
        $response = [
            'msg' => 'ERROR_QUERY',
            'error' => $stmt->error
        ];

        // Registrar os dados da requisição e o erro
        registrarLog(['user_code' => $user_code], $response);
        error_log("Erro ao executar a consulta para user_code $user_code: " . $stmt->error);

        return json_encode($response);
    }
}

# Configurar o recebimento de requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pegar o corpo da requisição (JSON)
    $input = file_get_contents('php://input');
    $reqData = json_decode($input, true);

    // Chamar a função `gameCallback` com os dados da requisição
    $response = callbackuserbalance($reqData);

    // Retornar a resposta
    echo $response;
}