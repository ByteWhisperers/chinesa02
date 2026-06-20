<?php

include_once "../dash/services-prod/prod.php";
include_once "../dash/services/database.php";
include_once "../dash/services/funcao.php";
include_once "../dash/services/crud.php";


// Debugar Erros No Código / 1 = ON, 0  = OFF
ini_set('display_errors', 0); // CULPA DESSA BUCETA
date_default_timezone_set('America/Sao_Paulo');
error_reporting(E_ALL);

# Função para registrar logs em um arquivo JSON
function registrarLog($requestData, $responseData)
{
    // Caminho do arquivo de log
    $logFile = 'game_log.json';

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
// Função que será chamada quando a rota receber a requisição
function gameCallback($req)
{
    global $mysqli;

    // Registrar a requisição recebida
    registrarLog(['action' => 'request_received', 'data' => $req], null);

    try {
        // Extrair os dados enviados na requisição
        $user_code = $req["user_code"];
        $user_balance = $req["user_balance"];
        $user_total_credit = $req["user_total_credit"];
        $user_total_debit = $req["user_total_debit"];
        $game_type = $req["game_type"];
        $method = $req["method"];

        if ($method == "user_balance") {
            global $mysqli;

            $user_code = $req['user_code'];

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

        if ($method == "transaction" || $game_type == "slot") {
            $slotData = $req["slot"];

            // Garantir que os valores de 'bet' e 'win' sejam numéricos
            $bet = isset($slotData['bet_money']) ? floatval($slotData['bet_money']) : (isset($slotData['bet']) ? floatval($slotData['bet']) : 0);
            $win = isset($slotData['win_money']) ? floatval($slotData['win_money']) : (isset($slotData['win']) ? floatval($slotData['win']) : 0);

            // Garantir que as variáveis de saldo sejam numéricas
            // Obtenha o valor de bet_money a partir de $slotData
            $bet_money = isset($slotData["bet_money"]) ? floatval($slotData["bet_money"]) : 0;

            // Obtenha o saldo atual do usuário
            $current_balance_query = "SELECT saldo FROM usuarios WHERE mobile = ?";
            $stmtBalance = $mysqli->prepare($current_balance_query);
            $stmtBalance->bind_param("s", $user_code);
            $stmtBalance->execute();
            $resultBalance = $stmtBalance->get_result();
            $rowBalance = $resultBalance->fetch_assoc();
            $current_balance = isset($rowBalance["saldo"]) ? floatval($rowBalance["saldo"]) : 0;

            // Se existir win_money em $slotData, subtraia o valor de bet_money e adicione win_money ao saldo
            if (isset($slotData['win_money']) && $slotData['win_money'] > 0) {
                $win_money = floatval($slotData['win_money']);
                $user_after_balance = $current_balance - $bet_money + $win_money;
            } else {
                // Caso não haja win_money, subtraímos apenas o valor de bet_money
                $user_after_balance = $current_balance - $bet_money;
            }

            // Defina a variável user_after_balance, garantindo que se o valor não for encontrado, seja 0
            $user_after_balance = isset($slotData["user_after_balance"]) ? floatval($slotData["user_after_balance"]) : $user_after_balance;

            $agent_after_balance = isset($slotData["agent_after_balance"]) ? floatval($slotData["agent_after_balance"]) : 0;

            $txn_id = $slotData["txn_id"];
            $created_at = $slotData["created_at"];
            $user_code = $req["user_code"];

            // Consulta o usuário com base no `user_code`
            $sql = "SELECT id, invitation_code FROM usuarios WHERE mobile = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("s", $user_code);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                // Usuário não encontrado
                $errorMsg = "INVALID_USER";
                $response = ["status" => 0, "msg2" => $errorMsg, "user_code" => $req['user_code']];
                registrarLog(['action' => 'error', 'message' => $errorMsg, 'data' => $req], $response);
                return json_encode($response);
            }

            $row = $result->fetch_assoc();
            $id_user = $row["id"];
            $invitation_code = $row["invitation_code"]; // ID do afiliado

            // Inserir os dados no histórico de jogos
            $sqlInsert = "INSERT INTO historico_play (id_user, nome_game, bet_money, win_money, txn_id, created_at, status_play) 
                          VALUES (?, ?, ?, ?, ?, NOW(), ?)";
            $stmtInsert = $mysqli->prepare($sqlInsert);
            $nome_game = $slotData["game_code"];
            $status_play = 1;  // Considera status como '1' para jogada bem-sucedida

            $stmtInsert->bind_param("isddsi", $id_user, $nome_game, $bet, $win, $txn_id, $status_play);
            $stmtInsert->execute();

            // Atualizar o saldo do usuário
            $update_saldo_query = "UPDATE usuarios SET saldo = ? WHERE id = ?";
            $stmtUpdate = $mysqli->prepare($update_saldo_query);
            $stmtUpdate->bind_param("di", $user_after_balance, $id_user);
            $stmtUpdate->execute();

            // Se o código de convite estiver vazio, não faz consulta com base no código de convite
            if (!empty($invitation_code)) {
                // Consulta o usuário com base no `invitation_code`
                $sql = "SELECT * FROM usuarios WHERE invite_code = ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("s", $invitation_code);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 0) {
                    // Usuário não encontrado
                    $errorMsg = "INVALID_USER";
                    $response = ["status" => 0, "msg2" => $errorMsg, "user_code" => $req['user_code']];
                    registrarLog(['action' => 'error', 'message' => $errorMsg, 'data' => $req], $response);
                    return json_encode($response);
                }

                $row2 = $result->fetch_assoc();

                // Verificar se o usuário tem afiliado
                if ($invitation_code && ($row2['tipo_pagamento'] == 2 || $row2['tipo_pagamento'] == 0)) {
                    // Chama a função para obter os dados de comissão
                    $data_afiliados_cpa_rev = data_afiliados_cpa_rev();

                    // Verifica se a chave revShareLvl1 existe no array
                    if (isset($data_afiliados_cpa_rev['revShareLvl1'])) {
                        // Atribui a porcentagem para a comissão
                        $porcentagem1 = $data_afiliados_cpa_rev['revShareLvl1'] / 100;

                        // Log para depuração
                        error_log("Retorno da funcao: " . $porcentagem1);

                        // Calcula a comissão
                        $comissao_afiliado = $bet * $porcentagem1; // Usando a porcentagem para calcular a comissão

                        // Se o indicado tiver ganho, o valor do ganho será descontado da comissão
                        if ($win > 0) {
                            // Desconta o ganho do indicado da comissão do afiliado
                            $comissao_afiliado -= $win;
                        }

                        // Log para verificação
                        error_log("Comissão após desconto do ganho: " . $comissao_afiliado);

                        $comissao_afiliado = floatval($comissao_afiliado);

                        // Atualiza o saldo do afiliado
                        $atualizar_afiliado_saldo_query = "UPDATE usuarios SET saldo = saldo + ?, rev = rev + ?, total_rev = total_rev + ? WHERE invite_code = ?";
                        $stmtafiliado = $mysqli->prepare($atualizar_afiliado_saldo_query);
                        $stmtafiliado->bind_param("ddds", $comissao_afiliado, $comissao_afiliado, $comissao_afiliado, $invitation_code);
                        $stmtafiliado->execute();
                    } else {
                        error_log("A chave 'revShareLvl1' não foi encontrada nos dados de configuração.");
                    }
                }
            }


            // Registrar e retornar a resposta de sucesso
            $response = ["status" => 1, "user_balance" => floatval($user_after_balance)];
            registrarLog(['action' => 'update_balance_success', 'new_balance' => $user_after_balance, 'user_id' => $id_user], $response);
            return json_encode($response);
        } else {
            // Tipo de jogo não suportado
            $errorMsg = "UNSUPPORTED_GAME_TYPE";
            $response = ["status" => 0, "msg" => $errorMsg];
            registrarLog(['action' => 'error', 'message' => $errorMsg, 'data' => $req], $response);
            return json_encode($response);
        }
    } catch (Exception $e) {
        $response = ["status" => 0, "msg" => "ERROR2", "error" => $e->getMessage()];
        registrarLog(['action' => 'exception', 'message' => $e->getMessage(), 'data' => $req], $response);
        return json_encode($response);
    }
}

# Configurar o recebimento de requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pegar o corpo da requisição (JSON)
    $input = file_get_contents('php://input');
    $reqData = json_decode($input, true);

    // Chamar a função gameCallback com os dados da requisição
    $response = gameCallback($reqData);

    // Retornar a resposta
    echo $response;
}
