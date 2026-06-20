<?php

/*-----------------------------------------------------------------------------------------------*/
/* Main Settings REST API ROYAL */

// Debugar Erros No Código / 1 = ON, 0 = OFF
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Obter Dados Enviados Via Req
parse_str(file_get_contents("php://input"), $data);

// Verificar se o JSON foi decodificado com sucesso
if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Erro na decodificação do JSON.']);
    exit;
}

// Definir Tipo De Conteúdo Da Resposta
header('Content-Type: application/json');

/*-----------------------------------------------------------------------------------------------*/
/* Main Functions REST API PHILLYPS V3 */

// Função para lidar com a resposta de erro
function sendError($code, $message)
{
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

// Verificação de rotas
$rotaEncontrada = false;

// Método Da Requisição
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Url De Origem Da Req
$requestURI = $_SERVER['REQUEST_URI'];

/*-----------------------------------------------------------------------------------------------*/

/* Dependencias Da Api */

include_once "./../../dash/services-prod/prod.php";
include_once "./../../dash/services/database.php";
include_once "./../../dash/services/funcao.php";
include_once "./../../dash/services/crud.php";
function getValidReferralCount($mysqli, $inviteCode, $deposito, $apostas)
{
    $validCount = 0;

    $qry = "SELECT id FROM usuarios WHERE invitation_code = '$inviteCode'";
    $result = mysqli_query($mysqli, $qry);

    while ($row = mysqli_fetch_assoc($result)) {
        $invitedUserId = $row['id'];

        $qryDeposits = "SELECT SUM(valor) AS total_deposit FROM transacoes 
                        WHERE usuario = '$invitedUserId' AND status = 'pago'";
        $resDeposits = mysqli_query($mysqli, $qryDeposits);
        $depositRow = mysqli_fetch_assoc($resDeposits);
        $totalDeposit = isset($depositRow['total_deposit']) ? $depositRow['total_deposit'] : 0;

        $qryBets = "SELECT SUM(bet_money) AS total_bet FROM historico_play 
                    WHERE id_user = '$invitedUserId'";
        $resBets = mysqli_query($mysqli, $qryBets);
        $betRow = mysqli_fetch_assoc($resBets);
        $totalBet = isset($betRow['total_bet']) ? $betRow['total_bet'] : 0;

        if ($totalDeposit >= $deposito && $totalBet >= $apostas) {
            $validCount++;
        }
    }

    return $validCount;
}
/*-----------------------------------------------------------------------------------------------*/

/* Function Baú */

function getBoxList($mysqli, $token)
{
    $qry = "SELECT * FROM usuarios WHERE token = '$token'";
    $resp = mysqli_query($mysqli, $qry);

    if (mysqli_num_rows($resp) > 0) {
        $user = mysqli_fetch_assoc($resp);

        // Obter os valores dos baús da tabela config
        $config_qry = "SELECT niveisbau, qntsbaus, nvlbau, pessoasbau,apostas_validas,depositos_validos FROM config";
        $config_resp = mysqli_query($mysqli, $config_qry);
        $config = mysqli_fetch_assoc($config_resp);

        if ($user['tipo_pagamento'] == 2) {
            $total_mem_count = 0;
        } else {
            $total_mem_count = getValidReferralCount($mysqli, $user['invite_code'], $config['depositos_validos'], $config['apostas_validas']);
        }
        $invite_count = $user['pessoas_convidadas'];

        $qry = "SELECT num FROM bau WHERE token = '$token'";
        $resp = mysqli_query($mysqli, $qry);
        $row = mysqli_fetch_assoc($resp);
        $nums = $row['num'];

        $numsArray = !empty($nums) ? explode(',', $nums) : [];

        $niveis_bau = explode(',', $config['niveisbau']);
        $quantidade_baus = $config['qntsbaus'];
        $pessoas_bau = $config['pessoasbau'];

        $baus_por_nivel = ceil($quantidade_baus / count($niveis_bau));

        $baus = [];
        for ($i = 1; $i <= $quantidade_baus; $i++) {
            $nivel_index = floor(($i - 1) / $baus_por_nivel);
            $money = isset($niveis_bau[$nivel_index]) ? (float) $niveis_bau[$nivel_index] : (float) end($niveis_bau);

            $condition = $i * $pessoas_bau; // $i (1) multiplicado por $pessoas_bau
            $is_get = 1;

            if (in_array($condition, $numsArray)) {
                $is_get = 3; // Baú já resgatado
            } elseif ($total_mem_count >= $condition) {
                $is_get = 2; // Baú disponível para resgate
            }


            $baus[] = [
                "mem_count" => $condition,
                "bonus_amount" => $money,
                "sort" => $i,
                "state" => $is_get,
            ];
        }

        return [
            "status" => true, // indica sucesso
            "data" => [
                "list" => $baus, // Aninha $baus dentro da chave "list"
                "total_mem_count" => $total_mem_count, // Aqui está a modificação
                "deposit_limit" => $config['depositos_validos'],
                "valid_bet_amount" => $config['apostas_validas'],
                "title" => "Recomende amigos e ganhe bônus",
                "promo_content_json" => [
                    [
                        "title" => "222",
                        "content" => "33333",
                    ],
                ],
                "promo_rule_json" => [
                    [
                        "content" => "Somente o subordinado recem-registrado,os subordinados atendem aos requisitos de atividade e concluir Configure o metodo de retirada.",
                    ],
                    [
                        "content" => "Recomende amigos e ganhe bônus。Convidar diferentes números de amigos pode gerar bônus correspondentes. O número máximo de amigos convidados é 50.000. Quanto mais você convidar, maior será uma recompensa.",
                    ],
                    [
                        "content" => "Esta atividade é um presente extra da plataforma, você pode desfrutar de outras recompensas e comissões de agentes ao mesmo tempo e desfrutar de múltiplas alegrias.",
                    ],
                    [
                        "content" => "As recompensas incluem coleta manual em IOS, Android, H5 e PC e serão reabastecidas automaticamente durante a transição.",
                    ],
                    [
                        "content" => "O bónus atribuído neste evento (excluindo o prémio principal) requer 5 apostas válidas antes de poder ser levantado.As apostas estão limitadas a: slot machines (todos os jogos), pesca (todos os jogos) e cartas (todos os jogos).",
                    ],
                    [
                        "content" => "Esta atividade está limitada às operações normais dos correntistas. É proibido o leasing, a utilização de plug-ins, as apostas com contas diferentes, a escovagem mútua, a exploração de lacunas e outros meios técnicos. Caso contrário, as recompensas serão canceladas ou deduzidas, a conta será congelada ou mesmo colocada na lista negra.",
                    ],
                    [
                        "content" => "Para evitar diferenças na compreensão do texto, a plataforma reserva-se o direito de interpretação final deste evento.",
                    ],
                ],
                "is_manual" => 1,
                "avail_amount" => 0
            ],
        ];
    } else {
        return [
            "status" => false,
            "data" => "1003",
            "msg" => null,
        ];
    }
}

/*-----------------------------------------------------------------------------------------------*/

switch ($requestMethod) {
    case 'POST':
        /* Rotas POST */
        // Rota De Cadastro
        if ($requestURI === '/api/member/reg') {
            $rotaEncontrada = true; // Rota encontrada
            function filterUrl($url)
            {
                // Use uma expressão regular para encontrar o parâmetro 'id' na URL
                preg_match('/id=([^&#]*)/', $url, $matches);

                // Se o parâmetro 'id' estiver presente, reconstrua a URL
                if (isset($matches[1])) {
                    $id = $matches[1];
                    // Divida a URL na posição do fragmento (hash)
                    $parts = parse_url($url);
                    // Construa a URL base (sem query e fragment)
                    $baseUrl = $parts['scheme'] . '://' . $parts['host'];
                    if (isset($parts['path'])) {
                        $baseUrl .= $parts['path'];
                    }
                    // Reconstrua a URL com o formato desejado
                    return $baseUrl . "?id=" . $id . "#/index";
                }

                // Se o parâmetro 'id' não estiver presente, retorne a URL original
                return $url;
            }
            $jsonDataModificado = $data;
            $password = PHP_SEGURO($data['password']);

            // Validação de mínimo 6 caracteres
            if (strlen($password) < 6) {
                $response = [
                    'code' => 0,
                    'msg' => 'A senha deve conter pelo menos 6 caracteres.',
                ];
                echo json_encode($response);
                exit;
            }

            //$spassword = PHP_SEGURO($data['spassword']);
            $nome_user = PHP_SEGURO($data['username']);
            $real_name = $data['realname'] ?? 'Sem nome';
            //  $cpf = $data['cpf'] ?? '000.000.000-00';
            $celular = $data['phone'] ?? '+555555555';
            $url = $url_base;
            $afiliado = PHP_SEGURO($data['link_id']);

            if (empty($data['link_id']) || $data['link_id'] == null) {
                $afiliado = null;
            } else {
                $afiliado = $afiliado;
            }
            // ← Aqui entra a validação de “pelo menos 6 caracteres, qualquer caractere”
            if (strlen($password) < 6) {
                $response = [
                    'code' => 0,
                    'msg'  => 'A senha deve conter pelo menos 6 caracteres.',
                ];
                echo json_encode($response);
                exit;
            }
            $query = "SELECT * FROM usuarios WHERE mobile = '$nome_user'";
            $result = mysqli_query($mysqli, $query) or die(mysqli_error($mysqli));

            if (mysqli_num_rows($result) > 0) {
                // Já tem cadastro
                $response = [
                    'code' => 0,
                    'msg' => 'Conta Duplicada',
                ];
                echo json_encode($response);
                exit;
            } else {
                // Criar usuário com base na API
                $code_api = trim((string) $real_name); // Garante que $real_name é uma string
                // $criar_user_api = criarUsuarioAPI($nome_user); // Cria user na API fiver

                $datadia = date('Y-m-d H:i:s');
                $token = md5($real_name . sha1(mt_rand()) . $datadia);
                // Gerar código de convite com um limite de 7 caracteres
                $afinveted = 'AF' . substr(md5($real_name . sha1(mt_rand()) . $datadia), 0, 5);
                $senha = password_hash($password, PASSWORD_DEFAULT, ["cost" => 10]);

                $tipo_pagamento = 1; // 1 SOMENTE CPA, 2 SOMENTE REV, 0 CPA+REV

                // Obtém o saldo inicial configurado
                $saldo_inicial = 0.00;

                $sqlSaldo = $mysqli->query("SELECT saldo_inicial FROM config WHERE id = 1");

                if ($sqlSaldo && $sqlSaldo->num_rows > 0) {
                    $rowSaldo = $sqlSaldo->fetch_assoc();
                    $saldo_inicial = isset($rowSaldo['saldo_inicial']) ? floatval($rowSaldo['saldo_inicial']) : 0.00;
                }

                // Agora insere com esse saldo
                $sql1 = $mysqli->prepare("INSERT INTO usuarios (mobile, celular, password, real_name, spassword, url, token, data_cad, invite_code, invitation_code, tipo_pagamento, saldo) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $sql1->bind_param(
                    "sssssssssssd",
                    $nome_user,
                    $celular,
                    $senha,
                    $real_name,
                    $senha,
                    $url,
                    $token,
                    $datadia,
                    $afinveted,
                    $afiliado,
                    $tipo_pagamento,
                    $saldo_inicial
                );




                if ($sql1->execute()) {
                    // Atualizar a contagem de pessoas convidadas do afiliado
                    if ($afiliado) {
                        $queryAfiliado = "SELECT * FROM usuarios WHERE invite_code = '$afiliado'";
                        $resultAfiliado = mysqli_query($mysqli, $queryAfiliado);
                        if (mysqli_num_rows($resultAfiliado) > 0) {
                            $afiliadoData = mysqli_fetch_assoc($resultAfiliado);
                            $pessoasConvidadas = $afiliadoData['pessoas_convidadas'] + 1;
                            $sqlUpdateAfiliado = $mysqli->prepare("UPDATE usuarios SET pessoas_convidadas = ? WHERE invite_code = ?");
                            $sqlUpdateAfiliado->bind_param("is", $pessoasConvidadas, $afiliado);
                            $sqlUpdateAfiliado->execute();
                        }
                    }
                    // Criar registro na tabela 'bau'
                    $sql2 = $mysqli->prepare("INSERT INTO bau (num, status, token) VALUES ('', 'user novo', ?)");
                    $sql2->bind_param("s", $token);
                    if ($sql2->execute()) {
                        // Gera um ID único para o cabeçalho
                        $uniqueId = generateUniqueId();

                        // Define o cabeçalho 'id' com o token
                        header("id: f51:" . $token);
                        setcookie('token_user', $token, time() + (86400 * 30), "/"); // Definir cookie por 30 dias
                        $response = [
                            'status' => true,
                            'data' => '1000',
                        ];
                        echo json_encode($response);
                        exit;
                    } else {
                        $response = [
                            'code' => 0,
                            'msg' => 'Conta criada, mas falha ao criar registro em bau.',
                        ];
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response = [
                        'code' => 0,
                        'msg' => 'Não foi possível criar sua conta.',
                    ];
                    echo json_encode($response);
                    exit;
                }
            }
        }
        // Rota De Login
        if ($requestURI === '/api/member/login') {
            $rotaEncontrada = true; // Rota encontrada
            $jsonDataModificado = $data;
            $data_user = PHP_SEGURO($data['username']);
            $password = PHP_SEGURO($data['password']);
            $query = "SELECT * FROM usuarios WHERE mobile = '$data_user'";
            $result = mysqli_query($mysqli, $query) or die(mysqli_error($mysqli));
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_array($result);
                $pass = $row['password'];
                $token = $row['token'];
                if (password_verify($password, $pass)) {
                    // Gera um ID único para o cabeçalho
                    $uniqueId = generateUniqueId();

                    // Define o cabeçalho 'id' com o token
                    header("id: f51:" . $token);
                    setcookie('token_user', $token, time() + (86400 * 30), "/"); // Definir cookie por 30 dias
                    $response = [
                        'status' => true, // Sucesso
                        'msg' => null,
                        'data' => '1000',
                    ];
                    echo json_encode($response);
                    exit;
                } else {
                    $response = [
                        "code" => 0, // Indica falha
                        "data" => '1007', // Mensagem de erro
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0, // Indica falha
                    "data" => '1006', // Mensagem de erro
                ];
                echo json_encode($response);
                exit;
            }
        }
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/promo/invite/record/detail') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $token = mysqli_real_escape_string($mysqli, $_COOKIE['token_user']);
                $qry = "SELECT * FROM usuarios WHERE token='$token'";
                $resp = mysqli_query($mysqli, $qry);
                if (mysqli_num_rows($resp) > 0) {
                    $datres = mysqli_fetch_assoc($resp);
                    $inviteCode = $datres['invite_code'];
                    // Busca os usuários convidados pelo usuário principal
                    $invitedUsersQuery = "SELECT id FROM usuarios WHERE invitation_code = '$inviteCode'";
                    $invitedUsersResult = mysqli_query($mysqli, $invitedUsersQuery);

                    $invitedUserIds = [];
                    while ($row = mysqli_fetch_assoc($invitedUsersResult)) {
                        $invitedUserIds[] = $row['id'];
                    }

                    // Consulta para obter os usuários que entraram pelo invite_code do usuário autenticado
                    $qry_subs = "SELECT * FROM usuarios WHERE invitation_code='" . $inviteCode . "'";
                    $resp_subs = mysqli_query($mysqli, $qry_subs);

                    $sub_users = [];
                    while ($sub = mysqli_fetch_assoc($resp_subs)) {
                        $transacoesQuery = "SELECT SUM(valor) AS total_deposit FROM transacoes WHERE usuario = ? AND status = 'pago'";
                        $transacoesStmt = $mysqli->prepare($transacoesQuery);
                        $transacoesStmt->bind_param("i", $sub['id']);
                        $transacoesStmt->execute();
                        $transacoesResult = $transacoesStmt->get_result();
                        $transacoesRow = $transacoesResult->fetch_assoc();
                        $depositAmount = $transacoesRow['total_deposit'] ?? 0.00;

                        $betQuery = "SELECT SUM(bet_money) AS total_bet FROM historico_play WHERE id_user = ?";
                        $betStmt = $mysqli->prepare($betQuery);
                        $betStmt->bind_param("i", $sub['id']);
                        $betStmt->execute();
                        $betResult = $betStmt->get_result();
                        $betRow = $betResult->fetch_assoc();
                        $betAmount = $betRow['total_bet'] ?? 0.00;

                        $sub_users[] = [
                            'id' => $sub['id'],
                            'owner_inviter_id' => $datres['id'],
                            'member_id' => $datres['id'],
                            'username' => $sub['mobile'],
                            'valid_bet_amount' => number_format($betAmount, 2, '.', ''),
                            'deposit_amount' => number_format($depositAmount, 2, '.', ''),
                            'is_active' => 1,
                            'created_at' => $sub['data_cad'],
                        ];
                    }
                    $response = [
                        "status" => true,
                        "data" => [
                            "d" => $sub_users,
                            "t" => count($sub_users)
                        ],
                        "msg" => null,
                    ];
                    $response_json = json_encode($response, JSON_PRETTY_PRINT);
                    echo $response_json;
                } else {

                    $response = [
                        "status" => true,
                        "data" => [],
                        "msg" => null,
                    ];
                    $response_json = json_encode($response, JSON_PRETTY_PRINT);
                    echo $response_json;
                }
            } else {
                $response = [
                    "status" => true,
                    "data" => [],
                    "msg" => null,
                ];
                $response_json = json_encode($response, JSON_PRETTY_PRINT);
                echo $response_json;
            }
        }
        // Rota Platform/list
        if ($requestURI === '/api/member/platform/list') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [
                    [
                        "id" => "26595015200105",
                        "game_type" => 1,
                        "name" => "Ao Vivo",
                    ],
                    [
                        "id" => "26595015200115",
                        "game_type" => 1,
                        "name" => "DB Cassino",
                    ],
                    [
                        "id" => "26595015200201",
                        "game_type" => 2,
                        "name" => "",
                    ],
                    [
                        "id" => "26595015200203",
                        "game_type" => 2,
                        "name" => "JDB Pescaria",
                    ],
                    [
                        "id" => "26595015200206",
                        "game_type" => 2,
                        "name" => "SG Pescaria",
                    ],
                    [
                        "id" => "26595015200210",
                        "game_type" => 2,
                        "name" => "JDB Pescaria",
                    ],
                    [
                        "id" => "26595015200304",
                        "game_type" => 3,
                        "name" => "JDB Slots",
                    ],
                    [
                        "id" => "26595015200305",
                        "game_type" => 3,
                        "name" => "PG Slots",
                    ],
                    [
                        "id" => "26595015200306",
                        "game_type" => 3,
                        "name" => "JL Slots",
                    ],
                    [
                        "id" => "26595015200309",
                        "game_type" => 3,
                        "name" => "SG Slots",
                    ],
                    [
                        "id" => "26595015200310",
                        "game_type" => 3,
                        "name" => "PP Slots",
                    ],
                    [
                        "id" => "PGSOFT",
                        "game_type" => 3,
                        "name" => "PG Slots",
                    ],
                    [
                        "id" => "26595015200314",
                        "game_type" => 3,
                        "name" => "ACEWIN Slots",
                    ],
                    [
                        "id" => "26595015200315",
                        "game_type" => 3,
                        "name" => "CG Slots",
                    ],
                    [
                        "id" => "26595015200316",
                        "game_type" => 3,
                        "name" => "CQ9 Slots",
                    ],
                    [
                        "id" => "26595015200317",
                        "game_type" => 3,
                        "name" => "FC Slots",
                    ],
                    [
                        "id" => "26595015200321",
                        "game_type" => 3,
                        "name" => "JDB Slots",
                    ],
                    [
                        "id" => "26595015200329",
                        "game_type" => 3,
                        "name" => "WG Slots",
                    ],
                    [
                        "id" => "26595015200407",
                        "game_type" => 4,
                        "name" => "DB Sport",
                    ],
                    [
                        "id" => "26595015200503",
                        "game_type" => 5,
                        "name" => "JL Cartas",
                    ],
                    [
                        "id" => "26595015200505",
                        "game_type" => 5,
                        "name" => "JDB Cartas",
                    ],
                    [
                        "id" => "26595015200511",
                        "game_type" => 5,
                        "name" => "JDB Cartas",
                    ],
                    [
                        "id" => "26595015200604",
                        "game_type" => 6,
                        "name" => "DB Esport",
                    ],
                    [
                        "id" => "26595015200702",
                        "game_type" => 7,
                        "name" => "DB Loteria",
                    ],
                    [
                        "id" => "26595015200902",
                        "game_type" => 9,
                        "name" => "JDB Blockchain",
                    ],
                    [
                        "id" => "26595015200905",
                        "game_type" => 9,
                        "name" => "JDB Blockchain",
                    ],
                ],
                "msg" => null,
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Attbalance
        if ($requestURI === '/api/atualizar/saldo/phillyps') {
            if (isset($_COOKIE['token_user']) and !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);
                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);
                    $obt_saldo = pegarSaldo($datares['mobile'], $datares['id']);
                    $response = array(
                        "code" => 1,
                        "msg" => "ok",
                        "saldo" => $obt_saldo,
                        "time" => time(),
                    );
                }
            } else {
                $response = array(
                    "code" => 0,
                    "msg" => "Usuário não logado",
                    "time" => time(),
                );
            }
            echo json_encode($response);
            exit;
        }
        // Rota Recall/balance
        if ($requestURI === '/api/member/recall/balance') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => "1000",
                "msg" => null,
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Member/Password/update
        if ($requestURI === '/api/member/password/update') {
            $rotaEncontrada = true; // Rota encontrada

            // Verifica se o cookie 'token_user' está presente e não vazio
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                error_log("Token encontrado: " . $_COOKIE['token_user']);  // Log do Token

                // Consulta ao banco de dados
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);

                if (!$resp) {
                    error_log("Erro na consulta SQL (token): " . mysqli_error($mysqli));
                }

                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);
                    error_log("Usuário encontrado: " . print_r($datares, true));  // Log do usuário encontrado

                    // Verifica se o campo 'ty' foi enviado na requisição
                    if (isset($data['ty'])) {
                        // Se 'ty' for 2, trata a atualização de senha para saque
                        if ($data['ty'] == 2) {
                            // Atualiza o campo 'senhaparasacar' e 'senha_saque' no banco
                            $sql = $mysqli->prepare("UPDATE usuarios SET senhaparasacar = ?, senha_saque = 1 WHERE id = ?");
                            $sql->bind_param("si", $data['password'], $datares['id']);

                            if ($sql->execute()) {
                                $response = [
                                    "status" => true,
                                    "data" => '1000',
                                    "msg" => null,
                                ];
                                echo json_encode($response);
                                exit;
                            } else {
                                // Log de erro para o SQL do tipo 2
                                error_log("Erro ao executar SQL para atualização de senha para saque: " . $sql->error);
                                $response = [
                                    "code" => 0,
                                    "msg" => "Erro ao realizar saque.",
                                ];
                                echo json_encode($response);
                                exit;
                            }
                        }

                        // Se 'ty' for 1, trata a atualização da senha principal
                        if ($data['ty'] == 1) {
                            if (isset($data['old_password']) && isset($data['password']) && !empty($data['password'])) {
                                // Verifica se a senha antiga enviada corresponde à senha armazenada no banco
                                $senha_enviada = $data['old_password'];
                                $senha_armazenada = $datares['password'];  // senha armazenada no banco

                                // Verifica a senha enviada com a senha armazenada
                                $senha_correta = password_verify($senha_enviada, $senha_armazenada);

                                if ($senha_correta) {
                                    // Criptografa a nova senha
                                    $senhacrip = password_hash($data['password'], PASSWORD_DEFAULT, ["cost" => 10]);

                                    // Atualiza a senha no banco de dados
                                    $sql = $mysqli->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                                    $sql->bind_param("si", $senhacrip, $datares['id']);

                                    if ($sql->execute()) {
                                        $response = [
                                            "status" => true,
                                            "data" => '1000',
                                            "msg" => null,
                                        ];
                                        echo json_encode($response);
                                        exit;
                                    } else {
                                        // Log de erro para o SQL do tipo 1
                                        error_log("Erro ao executar SQL para atualização de senha principal: " . $sql->error);
                                        $response = [
                                            "code" => 0,
                                            "msg" => "Erro ao atualizar senha.",
                                        ];
                                        echo json_encode($response);
                                        exit;
                                    }
                                } else {
                                    // Caso a senha antiga não seja correta
                                    $response = [
                                        "status" => false,
                                        "data" => "1251", // Código de erro para senha incorreta
                                        "msg" => "Senha antiga incorreta.",
                                    ];
                                    echo json_encode($response);
                                    exit;
                                }
                            } else {
                                // Caso o campo 'old_password' ou 'password' não esteja presente
                                $response = [
                                    "code" => 0,
                                    "msg" => "Senha antiga ou nova não fornecida.",
                                ];
                                echo json_encode($response);
                                exit;
                            }
                        }
                    } else {
                        // Caso o campo 'ty' não esteja presente na requisição
                        $response = [
                            "code" => 0,
                            "msg" => "Parâmetro 'ty' não encontrado.",
                        ];
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    // Caso o usuário não seja encontrado
                    $response = [
                        "code" => 0,
                        "msg" => "Usuário não encontrado ou não logado.",
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                // Caso o cookie 'token_user' não esteja presente
                $response = [
                    "code" => 0,
                    "data" => null,
                    "data" => 1003,
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota Member/wpw/check
        if ($requestURI === '/api/member/wpw/check') {

            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token=?";
                $stmt = $mysqli->prepare($qry);
                $stmt->bind_param("s", $_COOKIE['token_user']);
                $stmt->execute();
                $resp = $stmt->get_result();

                if ($resp->num_rows > 0) {
                    $datares = $resp->fetch_assoc();

                    // Verificação da senha de pagamento
                    if (isset($data['password']) && !empty($data['password'])) {
                        $senha_enviada = $data['password'];
                        $senha_armazenada = $datares['senhaparasacar'];

                        // Verificação direta de senha em texto simples
                        $senha_correta = ($senha_enviada === $senha_armazenada);

                        if ($senha_correta) {
                            $response = [
                                "status" => true,
                                "data" => "1000",
                                "msg" => null,
                            ];
                            echo json_encode($response);
                            exit;
                        } else {
                            $response = [
                                "status" => false,
                                "data" => "1251",
                                "msg" => null,
                            ];
                            echo json_encode($response);
                            exit;
                        }
                    } else {
                        // Se o campo password estiver vazio ou ausente, retorne o JSON solicitado
                        $response = [
                            "status" => true,
                            "data" => "1249",
                            "msg" => null,
                        ];
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response = [
                        "code" => 0, // Falha
                        "msg" => "Usuário sem efetuar login",
                        "time" => time(),
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "msg" => "Usuário ou senha incorretos",
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota Member/bankcard/insert
        if ($requestURI === '/api/member/bankcard/insert') {
            if (isset($_COOKIE['token_user']) and !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);
                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);

                    $bank_card = isset($data['content']) && !empty($data['content']) ? $data['content'] : $data['bank_card'];

                    // Insere os dados na tabela 'payment_methods'
                    $sql = $mysqli->prepare("INSERT INTO metodos_pagamentos (user_id, realname, pix_id, flag, pix_account) VALUES (?, ?, ?, ?, ?)");
                    $sql->bind_param("issss", $datares['id'], $data['realname'], $data['bank_card'], $data['ty'], $bank_card);

                    if ($sql->execute()) {
                        $response = [
                            "status" => true,
                            "data" => '1000',
                            "msg" => null,
                        ];
                        echo json_encode($response);
                        exit;
                    } else {
                        $response = [
                            "code" => 0,
                            "msg" => "Erro ao inserir conta de pagamento.",
                        ];
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response = [
                        "code" => 0,
                        "msg" => "Usuário sem efetuar login",
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "msg" => "Usuario ou senha incorretos",
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota Member/bind/email
        if ($requestURI === '/api/member/bind/email') {
            if (isset($_COOKIE['token_user']) and !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);
                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);

                    $sql = $mysqli->prepare("UPDATE usuarios SET email = ? WHERE id = ?");
                    $sql->bind_param("si", $data['email'], $datares['id']);
                    if ($sql->execute()) {
                        $response = [
                            "status" => true,
                            "data" => '1000',
                            "msg" => null,
                        ];
                        echo json_encode($response);
                        exit;
                    } else {
                        $response = [
                            "code" => 0,
                            "msg" => "Erro ao inserir email.",
                        ];
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response = [
                        "code" => 0,
                        "msg" => "Usuário sem efetuar login",
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "msg" => "Usuario ou senha incorretos",
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota Member/bind/email
        if ($requestURI === '/api/member/update') {
            if (isset($_COOKIE['token_user']) and !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);
                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);


                    $birthDate = strtotime($data['birth']);

                    $sql = $mysqli->prepare("UPDATE usuarios SET whatsapp = ?, facebook = ?, telegram = ?, twitter = ?, data_nascimento = ? WHERE id = ?");
                    $sql->bind_param("sssssi", $data['whatsapp'], $data['facebook'], $data['telegram'], $data['twitter'], $birthDate, $datares['id']);

                    if ($sql->execute()) {
                        $response = [
                            "status" => true,
                            "data" => '1000',
                            "msg" => null,
                        ];
                        echo json_encode($response);
                        exit;
                    } else {
                        $response = [
                            "code" => 0,
                            "msg" => "Erro ao inserir dados.",
                        ];
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response = [
                        "code" => 0,
                        "msg" => "Usuário sem efetuar login",
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "msg" => "Usuario ou senha incorretos",
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota Finance/Withdraw
        if ($requestURI === '/api/finance/withdraw') {

            if (isset($_COOKIE['token_user']) and !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token=?";
                $stmt = $mysqli->prepare($qry);
                $stmt->bind_param("s", $_COOKIE['token_user']);
                $stmt->execute();
                $resp = $stmt->get_result();

                if ($resp->num_rows > 0) {
                    $datares = $resp->fetch_assoc();

                    // Verificação da senha de pagamento
                    if (isset($data['password']) && !empty($data['password'])) {
                        $senha_enviada = $data['password'];
                        $senha_armazenada = $datares['senhaparasacar'];

                        // Supondo que a senha esteja armazenada como um hash
                        // Se a senha estiver armazenada em texto simples, remova ou comente a linha abaixo
                        //$senha_correta = password_verify($senha_enviada, $senha_armazenada);

                        // Verificação direta de senha em texto simples
                        $senha_correta = ($senha_enviada === $senha_armazenada);

                        if ($senha_correta) {
                            $data = date('Y-m-d'); // Data no formato Y-m-d

                            // Verificação do limite diário de saque
                            $qry = "SELECT COUNT(*) as saques_hoje, SUM(valor) as total_saque_hoje FROM solicitacao_saques WHERE id_user = ? AND DATE(data_cad) = ?";
                            $stmt = $mysqli->prepare($qry);
                            $stmt->bind_param("is", $datares['id'], $data);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            $row = $res->fetch_assoc();

                            $saques_hoje = $row['saques_hoje'];
                            $total_saque_hoje = $row['total_saque_hoje'] ? $row['total_saque_hoje'] : 0;

                            parse_str(file_get_contents("php://input"), $data);
                            $valor_saque = isset($data['amount']) ? $data['amount'] : 0;

                            if ($saques_hoje >= $dataconfig['limite_saque'] || ($saques_hoje == 1 && $total_saque_hoje + $valor_saque > 500)) {
                                $response = [
                                    "code" => 0,
                                    "msg" => "Limite de saques diários atingido, tente amanhã novamente.",
                                    "time" => time(),
                                ];
                                echo json_encode($response);
                                exit;
                            } else {
                                // Calculando o total de depósitos
                                $qry = "SELECT SUM(valor) as total_depositos FROM transacoes WHERE usuario=? AND tipo='deposito' AND status='pago'";
                                $stmt = $mysqli->prepare($qry);
                                $stmt->bind_param("i", $datares['id']);
                                $stmt->execute();
                                $resultado = $stmt->get_result();
                                $row = $resultado->fetch_assoc();
                                $total_depositos = ($row['total_depositos'] > 0) ? $row['total_depositos'] : 0;

                                // Verificando se o valor do saque é maior que o valor total dos depósitos multiplicado pelo rollover
                                if ($valor_saque < $total_depositos * $dataconfig['rollover']) {
                                    $response = [
                                        "code" => 0,
                                        "msg" => "O valor do saque não pode ser menor ou igual ao valor total dos depósitos multiplicado pelo rollover.",
                                        "time" => time(),
                                    ];
                                    echo json_encode($response);
                                    exit;
                                } else {
                                    //var_dump($valor_saque, $datares['saldo'], $dataconfig['minsaque']);

                                    if ($valor_saque <= $datares['saldo'] && $valor_saque >= $dataconfig['minsaque']) {
                                        $datadia = date('Y-m-d H:i:s');
                                        $dataX = date('Y-m-d');
                                        $data_hora = date('H:i:s');
                                        $tokenSaque = md5($datares['mobile'] . sha1(mt_rand()) . $datadia);
                                        $restapi = withdrawSaldo($datares['mobile'], $valor_saque);

                                        if ($restapi == 1) {
                                            $RANDOMSAQUE = md5($tokenSaque);
                                            $sql12 = $mysqli->prepare("INSERT INTO solicitacao_saques (id_user, valor, tipo, pix, telefone, data_cad, data_hora, transacao_id) VALUES (?,?,?,?,?,?,?,?)");
                                            $sql12->bind_param("ssssssss", $datares['id'], $valor_saque, $data['bank_id'], $data['bank_id'], $data['flag'], $dataX, $data_hora, $RANDOMSAQUE);

                                            //$sql = $mysqli->prepare("UPDATE usuarios SET saldo = saldo - ? WHERE id = ?");
                                            // $sql->bind_param("si", $valor_saque, $datares['id']);

                                            //if ($sql->execute() and $sql12->execute()) {
                                            if ($sql12->execute()) {
                                                if ($valor_saque <= $dataconfig['saque_automatico']) {
                                                    $api_url = $url_base . "dash/services-gateway/payment_auto.php";

                                                    // $chavepix2 = localizarchavepix($data['bank_id']);

                                                    $api_data = array(
                                                        'chavepix' => $data['bank_id'],
                                                        'valor' => $valor_saque,
                                                        'id' => md5(token_id_transacao()),
                                                    );

                                                    $curl = curl_init($api_url);
                                                    curl_setopt($curl, CURLOPT_POST, true);
                                                    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($api_data));
                                                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                                                    $api_response = curl_exec($curl);

                                                    curl_close($curl);

                                                    if ($api_response === "Pagamento realizado com sucesso") {
                                                        $qry = "UPDATE solicitacao_saques SET status = '1' WHERE transacao_id = ?";
                                                        $stmt = $mysqli->prepare($qry);
                                                        $stmt->bind_param("s", $RANDOMSAQUE);
                                                        $stmt->execute();
                                                    }

                                                    //var_dump($api_url);
                                                }

                                                $response = [
                                                    "status" => true,
                                                    "data" => '10000',
                                                    "time" => time(),
                                                ];
                                                echo json_encode($response);
                                                exit;
                                            } else {
                                                $response = [
                                                    "code" => 0, // Falha
                                                    "msg" => "Erro ao realizar saque.",
                                                    "time" => time(),
                                                ];
                                                echo json_encode($response);
                                                exit;
                                            }
                                        } else {
                                            $response = [
                                                "code" => 0, // Falha
                                                "msg" => "Erro na API de saque.",
                                                "time" => time(),
                                            ];
                                            echo json_encode($response);
                                            exit;
                                        }
                                    } else {
                                        $response = [
                                            "code" => 0,
                                            "msg" => "Valor do saque fora dos limites permitidos.",
                                            "time" => time(),
                                        ];
                                        echo json_encode($response);
                                        exit;
                                    }
                                }
                            }
                        } else {
                            $response = [
                                "status" => false,
                                "data" => "1026",
                                "msg" => null,
                            ];
                            echo json_encode($response);
                            exit;
                        }
                    } else {
                        $response = [
                            "status" => false,
                            "data" => "1251",
                            "msg" => null,
                        ];
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response = [
                        "code" => 0, // Falha
                        "msg" => "Usuário sem efetuar login",
                        "time" => time(),
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "msg" => "Usuario ou senha incorretos",
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota Finance/Third/Deposit
        if ($requestURI === '/api/finance/third/deposit') {
            if (isset($_COOKIE['token_user']) and !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);
                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);

                    $return_data_pix = _qrcode(
                        $data['amount'] ?? 0,
                        $datares['real_name'] ?? $data['r_name'] ?? 'Joao Silva',
                        $datares['id'] ?? 0,
                        $data['r_no'] ?? '41118948106',
                        $data['third_id'] ?? 'edbanking'
                    );

                    if (!empty($return_data_pix) && $return_data_pix != null) {
                        $response = [
                            "status" => true,
                            "data" => [
                                "id" => $return_data_pix['id'],
                                "o_id" => $return_data_pix['id'],
                                "status" => 360,
                                "url" => "",
                                "useLink" => "1",
                                "msg" => "",
                                "qr_code_image" => $return_data_pix['qrcode'],
                                "qr_code_data" => $return_data_pix['code'],
                                "expire_time" => "",
                                "amount" => $return_data_pix['amount'],
                                "created_at" => time(),
                            ],
                            "msg" => null
                        ];
                    } else {
                        $response = [
                            "status" => false,
                            "msg" => "Erro PixApi",
                            "data" => "1415"
                        ];
                    }
                } else {
                    $response = array(
                        "status" => false,
                        "data" => "1003",
                        "msg" => null,
                    );
                }
            } else {
                $response = array(
                    "status" => false,
                    "data" => "1003",
                    "msg" => null,
                );
            }

            echo json_encode($response);
            exit;
        }
        // Rota Member/Agent/Sub/Member
        if ($requestURI === '/api/member/agent/sub/member') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true;
                $token = mysqli_real_escape_string($mysqli, $_COOKIE['token_user']);

                $qry = "SELECT * FROM usuarios WHERE token='$token'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datres = mysqli_fetch_assoc($resp);
                    $inviteCode = $datres['invite_code'];

                    $directUsersQuery = "SELECT id, invite_code, data_cad, vip FROM usuarios WHERE invitation_code = '$inviteCode'";
                    $directUsersResult = mysqli_query($mysqli, $directUsersQuery);

                    $directUserIds = [];
                    $directUserInviteCodes = [];
                    $sub_users = [];

                    while ($row = mysqli_fetch_assoc($directUsersResult)) {
                        $directUserIds[] = $row['id'];
                        $directUserInviteCodes[] = $row['invite_code'];

                        $referralsQuery = "SELECT COUNT(*) AS count FROM usuarios WHERE invitation_code = ?";
                        $referralsStmt = $mysqli->prepare($referralsQuery);
                        $referralsStmt->bind_param("s", $row['invite_code']);
                        $referralsStmt->execute();
                        $referralsResult = $referralsStmt->get_result();
                        $referralsRow = $referralsResult->fetch_assoc();
                        $lvl1_num = $referralsRow['count'] ?? 0;

                        $transacoesQuery = "SELECT SUM(valor) AS total_deposit FROM transacoes WHERE usuario = ? AND status = 'pago'";
                        $transacoesStmt = $mysqli->prepare($transacoesQuery);
                        $transacoesStmt->bind_param("i", $row['id']);
                        $transacoesStmt->execute();
                        $transacoesResult = $transacoesStmt->get_result();
                        $transacoesRow = $transacoesResult->fetch_assoc();
                        $depositAmount = $transacoesRow['total_deposit'] ?? 0.00;

                        $betQuery = "SELECT SUM(bet_money) AS total_bet FROM historico_play WHERE id_user = ?";
                        $betStmt = $mysqli->prepare($betQuery);
                        $betStmt->bind_param("i", $row['id']);
                        $betStmt->execute();
                        $betResult = $betStmt->get_result();
                        $betRow = $betResult->fetch_assoc();
                        $betAmount = $betRow['total_bet'] ?? 0.00;

                        $sub_users[] = [
                            'uid' => $row['id'],
                            'level' => $row['vip'],
                            'money' => "0.00",
                            'lvl1_num' => $lvl1_num,
                            'bet_amount' => number_format($betAmount, 2, '.', ''),
                            'deposit_amount' => number_format($depositAmount, 2, '.', ''),
                            'is_recharge' => 0,
                            'is_good' => 0,
                            'created_at' => $row['data_cad'],
                        ];
                    }


                    $level2UserIds = [];
                    $level2Users = [];
                    if (!empty($directUserInviteCodes)) {
                        $directUserInviteCodesEscaped = array_map(function ($code) use ($mysqli) {
                            return mysqli_real_escape_string($mysqli, $code);
                        }, $directUserInviteCodes);
                        $inCodes = "'" . implode("','", $directUserInviteCodesEscaped) . "'";
                        $level2Query = "SELECT id, invite_code FROM usuarios WHERE invitation_code IN ($inCodes)";
                        $level2Result = mysqli_query($mysqli, $level2Query);
                        while ($row = mysqli_fetch_assoc($level2Result)) {
                            $level2UserIds[] = $row['id'];
                            $level2Users[] = $row;
                        }
                    }

                    $level3UserIds = [];
                    if (!empty($level2Users)) {
                        $level2InviteCodes = array_map(function ($user) {
                            return $user['invite_code'];
                        }, $level2Users);
                        $level2InviteCodesEscaped = array_map(function ($code) use ($mysqli) {
                            return mysqli_real_escape_string($mysqli, $code);
                        }, $level2InviteCodes);
                        $inCodesLevel2 = "'" . implode("','", $level2InviteCodesEscaped) . "'";
                        $level3Query = "SELECT id FROM usuarios WHERE invitation_code IN ($inCodesLevel2)";
                        $level3Result = mysqli_query($mysqli, $level3Query);
                        while ($row = mysqli_fetch_assoc($level3Result)) {
                            $level3UserIds[] = $row['id'];
                        }
                    }

                    if (count($directUserIds) > 0) {
                        $directIdsStr = implode(',', $directUserIds);

                        $directDepositQuery = "
                            SELECT COUNT(*) as depositCount, SUM(valor) as totalDeposits
                            FROM transacoes
                            WHERE usuario IN ($directIdsStr)
                              AND status = 'pago'
                        ";
                        $directDepositResult = mysqli_query($mysqli, $directDepositQuery);
                        $directDepositData = mysqli_fetch_assoc($directDepositResult);
                        $direct_deposit_amount = $directDepositData['totalDeposits'] ? $directDepositData['totalDeposits'] : 0;
                        $direct_deposit_count = $directDepositData['depositCount'] ? $directDepositData['depositCount'] : 0;

                        $directFirstDepositQuery = "
                            SELECT COUNT(DISTINCT usuario) as firstDepositCount
                            FROM transacoes
                            WHERE usuario IN ($directIdsStr)
                              AND status = 'pago'
                        ";
                        $directFirstDepositResult = mysqli_query($mysqli, $directFirstDepositQuery);
                        $direct_first_deposit_num = mysqli_fetch_assoc($directFirstDepositResult)['firstDepositCount'];
                    } else {
                        $direct_deposit_amount = 0;
                        $direct_deposit_count = 0;
                        $direct_first_deposit_num = 0;
                    }

                    $combinedLevel2_3Ids = array_merge($level2UserIds, $level3UserIds);
                    if (count($combinedLevel2_3Ids) > 0) {
                        $combinedIdsStr = implode(',', $combinedLevel2_3Ids);

                        $combinedDepositQuery = "
                            SELECT COUNT(*) as depositCount, SUM(valor) as totalDeposits
                            FROM transacoes
                            WHERE usuario IN ($combinedIdsStr)
                              AND status = 'pago'
                        ";
                        $combinedDepositResult = mysqli_query($mysqli, $combinedDepositQuery);
                        $combinedDepositData = mysqli_fetch_assoc($combinedDepositResult);
                        $other_deposit_amount = $combinedDepositData['totalDeposits'] ? $combinedDepositData['totalDeposits'] : 0;
                        $total_lvl2_deposit_num = $combinedDepositData['depositCount'] ? $combinedDepositData['depositCount'] : 0;

                        $combinedFirstDepositQuery = "
                            SELECT COUNT(DISTINCT usuario) as firstDepositCount
                            FROM transacoes
                            WHERE usuario IN ($combinedIdsStr)
                              AND status = 'pago'
                        ";
                        $combinedFirstDepositResult = mysqli_query($mysqli, $combinedFirstDepositQuery);
                        $other_first_deposit_num = mysqli_fetch_assoc($combinedFirstDepositResult)['firstDepositCount'];
                    } else {
                        $other_deposit_amount = 0;
                        $other_first_deposit_num = 0;
                        $total_lvl2_deposit_num = 0;
                    }

                    $total_deposit_amount = $direct_deposit_amount + $other_deposit_amount;
                    $total_deposits_count = $direct_deposit_count + $total_lvl2_deposit_num;
                    $total_first_deposit_num = $direct_first_deposit_num + $other_first_deposit_num;

                    $userData = array(
                        "status" => true,
                        "data" => [
                            "t" => 0,
                            "d" => $sub_users,
                            "extra" => [
                                "direct_deposit_amount_lvl1" => $direct_deposit_amount,
                                "total_deposits_lvl1_lvl2_lvl3_num" => $total_deposits_count,
                                "first_deposits_lvl1_lvl2__num" => $total_first_deposit_num,
                                "firs_deposit_lvl1_num" => $direct_first_deposit_num,
                                "total_deposits_lvl1_amount" => number_format($direct_deposit_amount, 2, '.', ''),
                                "deposit_amount_lvl2_lvl3" => number_format($other_deposit_amount, 2, '.', ''),
                            ],
                        ],
                        "msg" => null,
                    );

                    echo json_encode($userData);
                } else {
                    $response = [
                        "status" => false,
                        "data" => "1003",
                        "msg" => null,
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "status" => false,
                    "data" => null,
                    "msg" => "Token não encontrado ou inválido",
                ];
                echo json_encode($response);
                exit;
            }
        }





        // Rota member/agency/subinfo/list
        if ($requestURI === '/api/member/agency/subinfo/list') {
            // Verifica se o cookie 'token_user' está definido e não está vazio
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $token = mysqli_real_escape_string($mysqli, $_COOKIE['token_user']);

                $response = [
                    "status" => true,
                    "data" => [
                        "d" => [],
                        "t" => 0,
                        "s" => 15,
                        "agg" => [
                            "level" => 0,
                            "username" => "",
                            "deposit" => 0,
                            "win" => 0,
                            "created_at" => 0,
                            "uid" => "",
                            "withdraw" => 0,
                            "dividend" => 0,
                            "water" => 0
                        ]
                    ],
                    "msg" => null
                ];
                echo json_encode($response);
            } else {
                $response = [
                    "status" => false,
                    "data" => null,
                    "msg" => "Token não encontrado ou inválido",
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota Member/Agency/Alldata
        if ($requestURI === '/api/member/agency/alldata') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $token = mysqli_real_escape_string($mysqli, $_COOKIE['token_user']);

                $qry = "SELECT * FROM usuarios WHERE token='$token'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datres = mysqli_fetch_assoc($resp);
                    $inviteCode = $datres['invite_code'];


                    $invitedUsersQuery = "SELECT id, invite_code FROM usuarios WHERE invitation_code = '$inviteCode'";
                    $invitedUsersResult = mysqli_query($mysqli, $invitedUsersQuery);

                    $level1UserIds = [];
                    $level1InviteCodes = [];
                    while ($row = mysqli_fetch_assoc($invitedUsersResult)) {
                        $level1UserIds[] = $row['id'];
                        $level1InviteCodes[] = $row['invite_code'];
                    }
                    $level2UserIds = [];
                    if (!empty($level1InviteCodes)) {
                        $level1InviteCodesEscaped = array_map(function ($code) use ($mysqli) {
                            return mysqli_real_escape_string($mysqli, $code);
                        }, $level1InviteCodes);
                        $inCodes = "'" . implode("','", $level1InviteCodesEscaped) . "'";
                        $level2Query = "SELECT id FROM usuarios WHERE invitation_code IN ($inCodes)";
                        $level2Result = mysqli_query($mysqli, $level2Query);
                        while ($row = mysqli_fetch_assoc($level2Result)) {
                            $level2UserIds[] = $row['id'];
                        }
                    }


                    if (!empty($level1UserIds)) {
                        $level1IdsStr = implode(',', $level1UserIds);
                        $depositQueryLevel1 = "
                    SELECT COUNT(*) as depositCount, SUM(valor) as totalDeposits
                    FROM transacoes
                    WHERE usuario IN ($level1IdsStr)
                      AND status = 'pago'
                ";
                        $depositResultLevel1 = mysqli_query($mysqli, $depositQueryLevel1);
                        $depositDataLevel1 = mysqli_fetch_assoc($depositResultLevel1);
                        $totalDepositsLevel1 = $depositDataLevel1['totalDeposits'] ? $depositDataLevel1['totalDeposits'] : 0;
                    } else {
                        $totalDepositsLevel1 = 0;
                    }


                    if (!empty($level2UserIds)) {
                        $level2IdsStr = implode(',', $level2UserIds);
                        $depositQueryLevel2 = "
                    SELECT COUNT(*) as depositCount, SUM(valor) as totalDeposits
                    FROM transacoes
                    WHERE usuario IN ($level2IdsStr)
                      AND status = 'pago'
                ";
                        $depositResultLevel2 = mysqli_query($mysqli, $depositQueryLevel2);
                        $depositDataLevel2 = mysqli_fetch_assoc($depositResultLevel2);
                        $totalDepositsLevel2 = $depositDataLevel2['totalDeposits'] ? $depositDataLevel2['totalDeposits'] : 0;

                        $betsQueryLevel2 = "
                    SELECT COUNT(*) as betsCount, SUM(bet_money) as totalBets
                    FROM historico_play
                    WHERE id_user IN ($level2IdsStr)
                ";
                        $betsResultLevel2 = mysqli_query($mysqli, $betsQueryLevel2);
                        $betsDataLevel2 = mysqli_fetch_assoc($betsResultLevel2);
                        $betsCountLevel2 = $betsDataLevel2['betsCount'] ? $betsDataLevel2['betsCount'] : 0;
                        $totalBetsLevel2 = $betsDataLevel2['totalBets'] ? $betsDataLevel2['totalBets'] : 0;
                    } else {
                        $totalDepositsLevel2 = 0;
                        $betsCountLevel2 = 0;
                        $totalBetsLevel2 = 0;
                    }

                    $totalDepositsAll = $totalDepositsLevel1 + $totalDepositsLevel2;

                    $userData = array(
                        "status" => true,
                        "data" => array(
                            "child_num" => count($level1UserIds),          // Número de usuários de nível 1
                            "child_lvl1_num" => count($level1UserIds),          // Afiliados de primeiro nível
                            "child_other_num" => count($level2UserIds),          // Número de usuários de nível 2
                            "child_validbet" => $totalDepositsAll,              // Soma total dos depósitos
                            "child_lvl1_validbet" => $totalDepositsLevel1,           // Depósitos do nível 1
                            "child_other_validbet" => $totalBetsLevel2,               // Apostas dos usuários de nível 2
                            "rebate_all" => $datres['total_rev'],           // Rebate total (ajuste conforme necessário)
                            "rebate_lvl1" => $datres['rev'],                 // Rebate do nível 1 (ajuste conforme necessário)
                            "rebate_other" => 0,                            // Ajuste se houver rebate para outros níveis
                        ),
                        "msg" => null,
                    );
                    echo json_encode($userData);
                } else {
                    $response = [
                        "status" => false,
                        "data" => null,
                        "msg" => "Usuário sem efetuar login",
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "status" => false,
                    "data" => null,
                    "msg" => "Token não encontrado ou inválido",
                ];
                echo json_encode($response);
                exit;
            }
        }

        // Rota Member/Agency/Mydata
        if ($requestURI === '/api/member/agency/mydata') {
            // Verifica se o cookie 'token_user' está definido e não está vazio
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $token = mysqli_real_escape_string($mysqli, $_COOKIE['token_user']);

                // Busca o usuário principal com base no token
                $qry = "SELECT * FROM usuarios WHERE token='$token'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datres = mysqli_fetch_assoc($resp);
                    $inviteCode = $datres['invite_code'];

                    // Busca os usuários convidados pelo usuário principal
                    $invitedUsersQuery = "SELECT id FROM usuarios WHERE invitation_code = '$inviteCode'";
                    $invitedUsersResult = mysqli_query($mysqli, $invitedUsersQuery);

                    $invitedUserIds = [];
                    while ($row = mysqli_fetch_assoc($invitedUsersResult)) {
                        $invitedUserIds[] = $row['id'];
                    }

                    if (count($invitedUserIds) > 0) {
                        $invitedUserIdsStr = implode(',', $invitedUserIds);

                        // Exemplo: Quantidade de primeiros depósitos pagos (um por cada afiliado)
                        $firstDepositQuery = "
                            SELECT COUNT(DISTINCT usuario) as firstDepositCount
                            FROM transacoes
                            WHERE usuario IN ($invitedUserIdsStr)
                            AND status = 'pago'
                        ";
                        $firstDepositResult = mysqli_query($mysqli, $firstDepositQuery);
                        $firstDepositCount = mysqli_fetch_assoc($firstDepositResult)['firstDepositCount'];

                        // Exemplo: Total de depósitos pagos e soma dos valores
                        $depositQuery = "
                            SELECT COUNT(*) as depositCount, SUM(valor) as totalDeposits
                            FROM transacoes
                            WHERE usuario IN ($invitedUserIdsStr)
                            AND status = 'pago'
                        ";
                        $depositResult = mysqli_query($mysqli, $depositQuery);
                        $depositData = mysqli_fetch_assoc($depositResult);

                        // Verificar se os dados foram retornados corretamente
                        $depositCount = isset($depositData['depositCount']) ? $depositData['depositCount'] : 0;
                        $totalDeposits = isset($depositData['totalDeposits']) ? $depositData['totalDeposits'] : 0;
                    } else {
                        // Se não houver usuários convidados, os valores serão zero
                        $firstDepositCount = 0;
                        $depositCount = 0;
                        $totalDeposits = 0.00;
                    }

                    // Construindo a resposta com os valores zeros como solicitado
                    $userData = array(
                        "status" => true,
                        "data" => array(
                            "add_lvl1_num" => count($invitedUserIds),
                            "first_deposit_count" => $firstDepositCount,
                            "deposit_mem_count" => $depositCount,
                            "deposit_amount" => $totalDeposits,
                            "valid_bet_amount" => $datres['total_rev'],
                            "cg_rebate" => $datres['rev'],
                        ),
                        "msg" => null,
                    );

                    // Converte o array associativo para JSON
                    echo json_encode($userData);
                } else {
                    $response = [
                        "status" => false,
                        "data" => null,
                        "msg" => "Usuário sem efetuar login",
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "status" => false,
                    "data" => null,
                    "msg" => "Token não encontrado ou inválido",
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota Member/Rebate/Agency/Brief
        if ($requestURI === '/api/member/rebate/agency/brief') {
            // Verifica se o cookie 'token_user' está definido e não está vazio
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $token = mysqli_real_escape_string($mysqli, $_COOKIE['token_user']);

                // Busca o usuário principal com base no token
                $qry = "SELECT * FROM usuarios WHERE token='$token'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datres = mysqli_fetch_assoc($resp);
                    $inviteCode = $datres['invite_code'];

                    // Busca os usuários convidados pelo usuário principal
                    $invitedUsersQuery = "SELECT id FROM usuarios WHERE invitation_code = '$inviteCode'";
                    $invitedUsersResult = mysqli_query($mysqli, $invitedUsersQuery);

                    $invitedUserIds = [];
                    while ($row = mysqli_fetch_assoc($invitedUsersResult)) {
                        $invitedUserIds[] = $row['id'];
                    }

                    if (count($invitedUserIds) > 0) {
                        $invitedUserIdsStr = implode(',', $invitedUserIds);

                        // Exemplo: Quantidade de primeiros depósitos pagos (um por cada afiliado)
                        $firstDepositQuery = "
                            SELECT COUNT(DISTINCT usuario) as firstDepositCount
                            FROM transacoes
                            WHERE usuario IN ($invitedUserIdsStr)
                            AND status = 'pago'
                        ";
                        $firstDepositResult = mysqli_query($mysqli, $firstDepositQuery);
                        $firstDepositCount = mysqli_fetch_assoc($firstDepositResult)['firstDepositCount'];

                        // Exemplo: Total de depósitos pagos e soma dos valores
                        $depositQuery = "
                            SELECT COUNT(*) as depositCount, SUM(valor) as totalDeposits
                            FROM transacoes
                            WHERE usuario IN ($invitedUserIdsStr)
                            AND status = 'pago'
                        ";
                        $depositResult = mysqli_query($mysqli, $depositQuery);
                        $depositData = mysqli_fetch_assoc($depositResult);

                        // Verificar se os dados foram retornados corretamente
                        $depositCount = isset($depositData['depositCount']) ? $depositData['depositCount'] : 0;
                        $totalDeposits = isset($depositData['totalDeposits']) ? $depositData['totalDeposits'] : 0;
                    } else {
                        // Se não houver usuários convidados, os valores serão zero
                        $firstDepositCount = 0;
                        $depositCount = 0;
                        $totalDeposits = 0.00;
                    }

                    // Construindo a resposta com os valores zeros como solicitado
                    $userData = array(
                        "status" => true,
                        "data" => [
                            "parent_uid" => 0,
                            "paid_amount" => 0,
                            "total_bet_amount" => 0,
                            "total_amount" => 0,
                            "last_paid_amount" => 0,
                            "last_total_amount" => 0,
                            "total_num" => 0,
                            "child1_total_num" => isset($datres['pessoas_convidadas']) ? (int) $datres['pessoas_convidadas'] : 0,
                            "child1_total_amount" => 0,
                            "other_total_num" => 0,
                            "other_total_amount" => 0,
                            "net_amount" => 0,
                            "valid_bet_amount" => 0,
                            "bet_num" => 0,
                        ],
                        "msg" => null,

                    );

                    // Converte o array associativo para JSON
                    echo json_encode($userData);
                } else {
                    $response = [
                        "status" => false,
                        "data" => null,
                        "msg" => "Usuário sem efetuar login",
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "status" => false,
                    "data" => null,
                    "msg" => "Token não encontrado ou inválido",
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota Member/History/Save
        if ($requestURI === '/api/member/history/save') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);
                    $userId = $datares['id'];

                    // Consultar o histórico atual do usuário
                    $sql = "SELECT historico FROM usuarios WHERE id = ?";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result && $result->num_rows > 0) {
                        $historicoAtual = $result->fetch_assoc()['historico'];

                        // Verifica se já existe histórico
                        if ($historicoAtual) {
                            $historicogay = explode(',', $historicoAtual);
                            $historicogay = array_map('trim', $historicogay);

                            if (!in_array($data['code'], $historicogay)) {
                                // se nao ta no historico, adiciona o novo jogo com virgula 
                                $novoHistorico = $historicoAtual . ',' . $data['code'];
                            } else {
                                // se ja tem nao faz nd
                                $novoHistorico = $historicoAtual;
                            }

                            // Se já existe, adiciona o novo código com vírgula
                            //  $novoHistorico = $historicoAtual . ',' . $data['code'];
                        } else {
                            // Se não existe, apenas usa o novo código
                            $novoHistorico = $data['code'];
                        }
                    } else {
                        // Se não houver registro no histórico, apenas adiciona o novo código
                        $novoHistorico = $data['code'];
                    }

                    // Atualizar o histórico do usuário
                    $sqlUpdate = $mysqli->prepare("UPDATE usuarios SET historico = ? WHERE id = ?");
                    $sqlUpdate->bind_param("si", $novoHistorico, $userId);
                    if ($sqlUpdate->execute()) {
                        $response = [
                            "status" => true,
                            "data" => '1000',
                            "msg" => null,
                        ];
                        echo json_encode($response);
                        exit;
                    } else {
                        $response = [
                            "code" => 0,
                            "msg" => "Erro ao atualizar histórico.",
                        ];
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response = [
                        "code" => 0, // Indica falha
                        "msg" => "Usuário sem efetuar login", // Mensagem de erro
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "msg" => "Usuário ou senha incorretos",
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota Member/Favorites/Save
        if ($requestURI === '/api/member/favorites/save') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);
                    $userId = $datares['id'];

                    // Consultar o histórico atual do usuário
                    $sql = "SELECT favoritos FROM usuarios WHERE id = ?";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result && $result->num_rows > 0) {
                        $historicoAtual = $result->fetch_assoc()['favoritos'];

                        // Verifica se já existe histórico
                        if ($historicoAtual) {
                            // Se já existe, adiciona o novo código com vírgula
                            $novoHistorico = $historicoAtual . ',' . $data['code'];
                        } else {
                            // Se não existe, apenas usa o novo código
                            $novoHistorico = $data['code'];
                        }
                    } else {
                        // Se não houver registro no histórico, apenas adiciona o novo código
                        $novoHistorico = $data['code'];
                    }

                    // Atualizar o histórico do usuário
                    $sqlUpdate = $mysqli->prepare("UPDATE usuarios SET favoritos = ? WHERE id = ?");
                    $sqlUpdate->bind_param("si", $novoHistorico, $userId);
                    if ($sqlUpdate->execute()) {
                        $response = [
                            "status" => true,
                            "data" => '1000',
                            "msg" => null,
                        ];
                        echo json_encode($response);
                        exit;
                    } else {
                        $response = [
                            "code" => 0,
                            "msg" => "Erro ao atualizar histórico.",
                        ];
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response = [
                        "code" => 0, // Indica falha
                        "msg" => "Usuário sem efetuar login", // Mensagem de erro
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "msg" => "Usuário ou senha incorretos",
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota Promo/invite/list
        if ($requestURI === '/api/promo/invite/list') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $response = getBoxList($mysqli, $_COOKIE['token_user']); // A função getBoxList serve para obter a lista de baús disponíveis para um usuário específico, com base no token do usuário.
                echo json_encode($response);
                exit; // Retornar o resultado da função getBoxList
            } else {
                $response = [
                    "status" => true,
                    "data" => [
                        "list" => [
                            [
                                "mem_count" => 5,
                                "bonus_amount" => 10,
                                "sort" => 1,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 10,
                                "bonus_amount" => 10,
                                "sort" => 2,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 15,
                                "bonus_amount" => 10,
                                "sort" => 3,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 20,
                                "bonus_amount" => 10,
                                "sort" => 4,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 25,
                                "bonus_amount" => 10,
                                "sort" => 5,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 30,
                                "bonus_amount" => 10,
                                "sort" => 6,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 35,
                                "bonus_amount" => 10,
                                "sort" => 7,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 40,
                                "bonus_amount" => 10,
                                "sort" => 8,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 45,
                                "bonus_amount" => 10,
                                "sort" => 9,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 50,
                                "bonus_amount" => 10,
                                "sort" => 10,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 55,
                                "bonus_amount" => 10,
                                "sort" => 11,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 60,
                                "bonus_amount" => 10,
                                "sort" => 12,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 65,
                                "bonus_amount" => 10,
                                "sort" => 13,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 70,
                                "bonus_amount" => 10,
                                "sort" => 14,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 75,
                                "bonus_amount" => 10,
                                "sort" => 15,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 80,
                                "bonus_amount" => 10,
                                "sort" => 16,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 85,
                                "bonus_amount" => 10,
                                "sort" => 17,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 90,
                                "bonus_amount" => 10,
                                "sort" => 18,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 95,
                                "bonus_amount" => 10,
                                "sort" => 19,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 100,
                                "bonus_amount" => 10,
                                "sort" => 20,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 105,
                                "bonus_amount" => 20,
                                "sort" => 21,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 110,
                                "bonus_amount" => 20,
                                "sort" => 22,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 115,
                                "bonus_amount" => 20,
                                "sort" => 23,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 120,
                                "bonus_amount" => 20,
                                "sort" => 24,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 125,
                                "bonus_amount" => 20,
                                "sort" => 25,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 130,
                                "bonus_amount" => 20,
                                "sort" => 26,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 135,
                                "bonus_amount" => 20,
                                "sort" => 27,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 140,
                                "bonus_amount" => 20,
                                "sort" => 28,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 145,
                                "bonus_amount" => 20,
                                "sort" => 29,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 150,
                                "bonus_amount" => 20,
                                "sort" => 30,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 155,
                                "bonus_amount" => 20,
                                "sort" => 31,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 160,
                                "bonus_amount" => 20,
                                "sort" => 32,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 165,
                                "bonus_amount" => 20,
                                "sort" => 33,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 170,
                                "bonus_amount" => 20,
                                "sort" => 34,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 175,
                                "bonus_amount" => 20,
                                "sort" => 35,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 180,
                                "bonus_amount" => 20,
                                "sort" => 36,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 185,
                                "bonus_amount" => 20,
                                "sort" => 37,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 190,
                                "bonus_amount" => 20,
                                "sort" => 38,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 195,
                                "bonus_amount" => 20,
                                "sort" => 39,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 200,
                                "bonus_amount" => 20,
                                "sort" => 40,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 205,
                                "bonus_amount" => 30,
                                "sort" => 41,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 210,
                                "bonus_amount" => 30,
                                "sort" => 42,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 215,
                                "bonus_amount" => 30,
                                "sort" => 43,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 220,
                                "bonus_amount" => 30,
                                "sort" => 44,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 225,
                                "bonus_amount" => 30,
                                "sort" => 45,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 230,
                                "bonus_amount" => 30,
                                "sort" => 46,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 235,
                                "bonus_amount" => 30,
                                "sort" => 47,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 240,
                                "bonus_amount" => 30,
                                "sort" => 48,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 245,
                                "bonus_amount" => 30,
                                "sort" => 49,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 250,
                                "bonus_amount" => 30,
                                "sort" => 50,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 255,
                                "bonus_amount" => 30,
                                "sort" => 51,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 260,
                                "bonus_amount" => 30,
                                "sort" => 52,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 265,
                                "bonus_amount" => 30,
                                "sort" => 53,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 270,
                                "bonus_amount" => 30,
                                "sort" => 54,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 275,
                                "bonus_amount" => 30,
                                "sort" => 55,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 280,
                                "bonus_amount" => 30,
                                "sort" => 56,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 285,
                                "bonus_amount" => 30,
                                "sort" => 57,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 290,
                                "bonus_amount" => 30,
                                "sort" => 58,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 295,
                                "bonus_amount" => 30,
                                "sort" => 59,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 300,
                                "bonus_amount" => 30,
                                "sort" => 60,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 305,
                                "bonus_amount" => 40,
                                "sort" => 61,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 310,
                                "bonus_amount" => 40,
                                "sort" => 62,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 315,
                                "bonus_amount" => 40,
                                "sort" => 63,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 320,
                                "bonus_amount" => 40,
                                "sort" => 64,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 325,
                                "bonus_amount" => 40,
                                "sort" => 65,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 330,
                                "bonus_amount" => 40,
                                "sort" => 66,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 335,
                                "bonus_amount" => 40,
                                "sort" => 67,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 340,
                                "bonus_amount" => 40,
                                "sort" => 68,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 345,
                                "bonus_amount" => 40,
                                "sort" => 69,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 350,
                                "bonus_amount" => 40,
                                "sort" => 70,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 355,
                                "bonus_amount" => 40,
                                "sort" => 71,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 360,
                                "bonus_amount" => 40,
                                "sort" => 72,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 365,
                                "bonus_amount" => 40,
                                "sort" => 73,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 370,
                                "bonus_amount" => 40,
                                "sort" => 74,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 375,
                                "bonus_amount" => 40,
                                "sort" => 75,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 380,
                                "bonus_amount" => 40,
                                "sort" => 76,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 385,
                                "bonus_amount" => 40,
                                "sort" => 77,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 390,
                                "bonus_amount" => 40,
                                "sort" => 78,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 395,
                                "bonus_amount" => 40,
                                "sort" => 79,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 400,
                                "bonus_amount" => 40,
                                "sort" => 80,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 405,
                                "bonus_amount" => 50,
                                "sort" => 81,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 410,
                                "bonus_amount" => 50,
                                "sort" => 82,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 415,
                                "bonus_amount" => 50,
                                "sort" => 83,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 420,
                                "bonus_amount" => 50,
                                "sort" => 84,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 425,
                                "bonus_amount" => 50,
                                "sort" => 85,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 430,
                                "bonus_amount" => 50,
                                "sort" => 86,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 435,
                                "bonus_amount" => 50,
                                "sort" => 87,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 440,
                                "bonus_amount" => 50,
                                "sort" => 88,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 445,
                                "bonus_amount" => 50,
                                "sort" => 89,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 450,
                                "bonus_amount" => 50,
                                "sort" => 90,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 455,
                                "bonus_amount" => 50,
                                "sort" => 91,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 460,
                                "bonus_amount" => 50,
                                "sort" => 92,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 465,
                                "bonus_amount" => 50,
                                "sort" => 93,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 470,
                                "bonus_amount" => 50,
                                "sort" => 94,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 475,
                                "bonus_amount" => 50,
                                "sort" => 95,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 480,
                                "bonus_amount" => 50,
                                "sort" => 96,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 485,
                                "bonus_amount" => 50,
                                "sort" => 97,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 490,
                                "bonus_amount" => 50,
                                "sort" => 98,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 495,
                                "bonus_amount" => 50,
                                "sort" => 99,
                                "state" => 1
                            ],
                            [
                                "mem_count" => 500,
                                "bonus_amount" => 50,
                                "sort" => 100,
                                "state" => 1
                            ]
                        ],
                        "total_mem_count" => "0",
                        "deposit_limit" => 20,
                        "valid_bet_amount" => 100,
                        "title" => "Recomende amigos e ganhe bônus",
                        "promo_content_json" => [
                            [
                                "title" => "222",
                                "content" => "33333"
                            ]
                        ],
                        "promo_rule_json" => [
                            [
                                "content" => "Somente o subordinado recem-registrado,os subordinados atendem aos requisitos de atividade e concluir Configure o metodo de retirada."
                            ],
                            [
                                "content" => "Recomende amigos e ganhe bônus。Convidar diferentes números de amigos pode gerar bônus correspondentes. O número máximo de amigos convidados é 50.000. Quanto mais você convidar, maior será uma recompensa."
                            ],
                            [
                                "content" => "Esta atividade é um presente extra da plataforma, você pode desfrutar de outras recompensas e comissões de agentes ao mesmo tempo e desfrutar de múltiplas alegrias."
                            ],
                            [
                                "content" => "As recompensas incluem coleta manual em IOS, Android, H5 e PC e serão reabastecidas automaticamente durante a transição."
                            ],
                            [
                                "content" => "O bónus atribuído neste evento (excluindo o prémio principal) requer 5 apostas válidas antes de poder ser levantado.As apostas estão limitadas a: slot machines (todos os jogos), pesca (todos os jogos) e cartas (todos os jogos)."
                            ],
                            [
                                "content" => "Esta atividade está limitada às operações normais dos correntistas. É proibido o leasing, a utilização de plug-ins, as apostas com contas diferentes, a escovagem mútua, a exploração de lacunas e outros meios técnicos. Caso contrário, as recompensas serão canceladas ou deduzidas, a conta será congelada ou mesmo colocada na lista negra."
                            ],
                            [
                                "content" => "Para evitar diferenças na compreensão do texto, a plataforma reserva-se o direito de interpretação final deste evento."
                            ]
                        ],
                        "is_manual" => 1,
                        "avail_amount" => 0
                    ]
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota Member/slot/search
        if ($requestURI === '/api/member/slot/search') {
            $rotaEncontrada = true;
            // Mapeamento de PIDs para providers
            $providerMap = [
                '26595015200313' => 'PGSOFT',
                '26595015200310' => 'PRAGMATIC',
                '26595015200317' => 'MICROGAMING',
                '36595015200314' => 'NETENT',
                '26595015100306' => 'TADA',
                '26595015200309' => 'EVOPLAY'
            ];

            $provedor = (string) ($data['pid'] ?? '0'); // Converter para string para comparação exata
            $keyword = isset($data['keyword']) ? $data['keyword'] : '';
            $searchTerm = "%" . $keyword . "%";

            // Variáveis para controle de favoritos
            $userId = null;
            $historicoAtual = [];

            // Verifica se o usuário está logado
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);
                    $userId = $datares['id'];

                    // Consultar favoritos apenas se o usuário for válido
                    $sql = "SELECT favoritos FROM usuarios WHERE id = ?";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result && $result->num_rows > 0) {
                        $historicoRow = $result->fetch_assoc();
                        $historicoAtual = !empty($historicoRow['favoritos']) ? explode(',', $historicoRow['favoritos']) : [];
                    }
                }
            }

            if ($provedor === '0') {
                $sql = "SELECT id, game_code, game_name, provider, banner FROM games WHERE game_name LIKE ? ORDER BY popular DESC";
            } else {
                $providerName = $providerMap[$provedor] ?? 'INVALID_PROVIDER';
                $sql = "SELECT id, game_code, game_name, provider, banner FROM games WHERE provider = ? AND game_name LIKE ? ORDER BY popular DESC";
            }


            $stmt = $mysqli->prepare($sql);
            $searchTerm = "%" . $keyword . "%";

            if ($provedor === '0') {
                $stmt->bind_param("s", $searchTerm);
            } else {
                $stmt->bind_param("ss", $providerName, $searchTerm);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $games_data = [];

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Determina se é favorito apenas para usuários logados
                    $isFavorite = ($userId !== null) ? (in_array($row['id'], $historicoAtual) ? 1 : 0) : 0;

                    $games_data[] = [
                        "id" => $row['id'],
                        "platform_id" => $row['provider'], // Valor fixo, você pode ajustar conforme necessário
                        "en_name" => $row['game_name'],
                        "client_type" => '',
                        "game_type" => '3',
                        "game_id" => $row['id'],
                        "img" => $row['banner'],
                        "is_hot" => 1,
                        "is_new" => 1,
                        "name" => $row['game_name'],
                        "sorting" => 99, // Você pode ajustar o valor conforme a necessidade
                        "vn_alias" => "Hổ May Mắn", // Exemplo, ajuste conforme necessário
                        "prefix" => "f51",
                        "game_code" => "",
                        "updated_at" => 0,
                        "updated_name" => "",
                        "currency" => "BRL",
                        "is_recommend" => 1,
                        "maintained" => 1,
                        "min_admission" => 1,
                        "is_lobby" => 0,
                        "hot_sort" => 99,
                        "is_favorites" => $isFavorite // Define is_favorites com base no histórico
                    ];
                }
            }

            // Monta resposta padrão para ambos os casos
            $response = [
                "status" => true,
                "data" => [
                    "d" => $games_data,
                    "t" => count($games_data)
                ]
            ];

            $mysqli->close();
            echo json_encode($response, JSON_UNESCAPED_SLASHES);
        }

        // Rota de member/agency/mypick //
        if ($requestURI === '/api/member/agency/mypick') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);

                    $response = [
                        "status" => true,
                        "data" => [
                            "d" => null,
                            "t" => 0,
                            "s" => 15,
                            "agg" => [
                                "username" => "",
                                "uid" => "",
                                "level" => 0,
                                "sub_num" => 0,
                                "water" => 0,
                                "vip_upgrade" => 0,
                                "total_get" => 0
                            ]
                        ],
                        "msg" => null
                    ];
                    echo json_encode($response);
                    exit;
                } else {
                    $response = [
                        "code" => 0,
                        "data" => 1003,
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "data" => 1003,
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota de member/direct/agency/finance //
        if ($requestURI === '/api/member/direct/agency/finance') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);

                    $response = [
                        "status" => true,
                        "data" => [
                            "d" => null,
                            "t" => 0,
                            "s" => 15,
                            "agg" => [
                                "total_dept_amount" => 0,
                                "first_deposit_amount" => 0,
                                "first_deposit_num" => 0,
                                "deposit_num" => 0
                            ]
                        ],
                        "msg" => null
                    ];
                    echo json_encode($response);
                    exit;
                } else {
                    $response = [
                        "code" => 0,
                        "data" => 1003,
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "data" => 1003,
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota de member/direct/agency/bet //
        if ($requestURI === '/api/member/direct/agency/bet') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);

                    $response = [
                        "status" => true,
                        "data" => [
                            "d" => null,
                            "t" => 0,
                            "s" => 15,
                            "agg" => [
                                "valid_bet_amount" => 0,
                                "total_valid_bet_amount" => 0,
                                "other_valid_bet_amount" => 0
                            ]
                        ],
                        "msg" => null
                    ];
                    echo json_encode($response);
                    exit;
                } else {
                    $response = [
                        "code" => 0,
                        "data" => 1003,
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "data" => 1003,
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota de member/direct/agency/state //
        if ($requestURI === '/api/member/direct/agency/state') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);

                    $response = [
                        "status" => true,
                        "data" => [
                            "d" => null,
                            "t" => 0,
                            "s" => 15,
                            "agg" => [
                                "reg_num" => 0,
                                "deposit_num" => 0,
                                "first_deposit_num" => 0,
                                "first_deposit_amount" => 0,
                                "total_dept_amount" => 0,
                                "valid_bet_amount" => 0
                            ]
                        ],
                        "msg" => null
                    ];
                    echo json_encode($response);
                    exit;
                } else {
                    $response = [
                        "code" => 0,
                        "data" => 1003,
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "data" => 1003,
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota de member/agency/mybet //
        if ($requestURI === '/api/member/agency/mybet') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);

                    $response = [
                        "status" => true,
                        "data" => [
                            "d" => null,
                            "t" => 0,
                            "s" => 0,
                            "agg" => [
                                "child_validbet" => 0,
                                "child_lvl1_validbet" => 0,
                                "child_other_validbet" => 0,
                                "report_time" => 0
                            ]
                        ],
                        "msg" => null
                    ];
                    echo json_encode($response);
                    exit;
                } else {
                    $response = [
                        "code" => 0,
                        "data" => 1003,
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "data" => 1003,
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota Abrir Bau
        if ($requestURI === '/api/promo/invite/open') {

            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);

                    if ($datares['active_percentage'] === "1") {
                        $response = [
                            "code" => 0,
                            "data" => "Usuario recebe por porcentagem."
                        ];
                        echo json_encode($response);
                        exit;
                    }

                    if (!isset($data['mem_count'])) {
                        $response = [
                            "code" => 0,
                            "msg" => "mem_count não informado."
                        ];
                        echo json_encode($response);
                        exit;
                    }
                    $mem_count = $data['mem_count'];

                    $config_qry = "SELECT niveisbau, qntsbaus, nvlbau, pessoasbau, apostas_validas, depositos_validos FROM config";
                    $resConfig = mysqli_query($mysqli, $config_qry);
                    if (!$resConfig || mysqli_num_rows($resConfig) === 0) {
                        $response = [
                            "code" => 0,
                            "msg" => "Erro na configuração."
                        ];
                        echo json_encode($response);
                        exit;
                    }
                    $config = mysqli_fetch_assoc($resConfig);

                    $pessoas_bau = $config['pessoasbau'];
                    if ($pessoas_bau == 0) {
                        $response = [
                            "code" => 0,
                            "msg" => "Configuração inválida: pessoasbau não pode ser zero."
                        ];
                        echo json_encode($response);
                        exit;
                    }

                    $validReferralCount = getValidReferralCount($mysqli, $datares['invite_code'], $config['depositos_validos'], $config['apostas_validas']);
                    if ($validReferralCount < $mem_count) {
                        $response = [
                            "code" => 0,
                            "msg" => "Condições não atendidas para abrir o baú."
                        ];
                        echo json_encode($response);
                        exit;
                    }

                    $qryBau = "SELECT num FROM bau WHERE token='" . $datares['token'] . "'";
                    $resBau = mysqli_query($mysqli, $qryBau);
                    $openedChests = "";
                    if ($resBau && mysqli_num_rows($resBau) > 0) {
                        $bauData = mysqli_fetch_assoc($resBau);
                        $openedChests = $bauData['num']; // ex: "20,40,60"
                        $openedChestsArray = !empty($openedChests) ? explode(',', $openedChests) : [];
                        if (in_array($mem_count, $openedChestsArray)) {
                            $response = [
                                "code" => 0,
                                "msg" => "Baú já foi resgatado."
                            ];
                            echo json_encode($response);
                            exit;
                        }
                    } else {
                        $openedChestsArray = [];
                    }

                    if ($mem_count % $pessoas_bau !== 0) {
                        $response = [
                            "code" => 0,
                            "msg" => "mem_count inválido."
                        ];
                        echo json_encode($response);
                        exit;
                    }
                    $i = $mem_count / $pessoas_bau;
                    $qntsbaus = $config['qntsbaus'];
                    if ($i < 1 || $i > $qntsbaus) {
                        $response = [
                            "code" => 0,
                            "msg" => "Baú inexistente."
                        ];
                        echo json_encode($response);
                        exit;
                    }

                    $niveis_bau = explode(',', $config['niveisbau']);
                    $baus_por_nivel = ceil($qntsbaus / count($niveis_bau));
                    $nivel_index = floor(($i - 1) / $baus_por_nivel);
                    $bonus = isset($niveis_bau[$nivel_index]) ? (float) $niveis_bau[$nivel_index] : (float) end($niveis_bau);

                    if ($resBau && mysqli_num_rows($resBau) > 0) {
                        $newOpenedChests = empty($openedChests) ? $mem_count : $openedChests . ',' . $mem_count;
                        $qryUpdateBau = "UPDATE bau SET num = '$newOpenedChests' WHERE token = '" . $datares['token'] . "'";
                        mysqli_query($mysqli, $qryUpdateBau);
                    } else {
                        $qryInsertBau = "INSERT INTO bau (token, num) VALUES ('" . $datares['token'] . "', '$mem_count')";
                        mysqli_query($mysqli, $qryInsertBau);
                    }

                    $qryUpdateUser = "UPDATE usuarios SET saldo = saldo + $bonus WHERE token = '" . $datares['token'] . "'";
                    mysqli_query($mysqli, $qryUpdateUser);

                    $response = [
                        "status" => true,
                        "data" => "1000"
                    ];
                    echo json_encode($response);
                    exit;
                } else {
                    $response = [
                        "status" => false,
                        "msg" => "Usuário não autenticado."
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "data" => 1003,
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota Webtrack
        if ($requestURI === '/api/webtrack/addLogin') {
            $rotaEncontrada = true; // Rota encontrada

            $response = [
                "code" => 0,
                "message" => "success",
                "data" => []
            ];

            echo json_encode($response);
        }
        // Rota Member/agency/config/list
        if ($requestURI === '/api/member/agency/config/list') {
            $rotaEncontrada = true; // Rota encontrada

            $response = [
                "status" => true,
                "data" => [
                    [
                        "id" => "1",
                        "module_name" => "我的业绩",
                        "api_path" => "Income",
                        "is_show" => 1,
                        "is_top" => 0,
                        "bottom_field" => "child_validbet,child_lvl1_validbet,child_other_validbet",
                        "updated_at" => 1731666395,
                        "bottom_field_all" => "child_validbet,child_lvl1_validbet,child_other_validbet",
                        "flag" => 0,
                        "sort" => 1
                    ],
                    [
                        "id" => "2",
                        "module_name" => "我的佣金",
                        "api_path" => "Rebate",
                        "is_show" => 1,
                        "is_top" => 0,
                        "bottom_field" => "total_paid_amount,paid_amount",
                        "updated_at" => 1731666398,
                        "bottom_field_all" => "total_paid_amount,paid_amount",
                        "flag" => 0,
                        "sort" => 2
                    ],
                    [
                        "id" => "3",
                        "module_name" => "下级数据",
                        "api_path" => "Member",
                        "is_show" => 1,
                        "is_top" => 0,
                        "bottom_field" => "sub_num,total_dept_amount,created_at,valid_bet_amount,last_login_at,online,state",
                        "updated_at" => 1731666404,
                        "bottom_field_all" => "sub_num,total_dept_amount,created_at,valid_bet_amount,last_login_at,online,state",
                        "flag" => 0,
                        "sort" => 3
                    ],
                    [
                        "id" => "4",
                        "module_name" => "下级投注",
                        "api_path" => "Transaction",
                        "is_show" => 1,
                        "is_top" => 0,
                        "bottom_field" => "valid_bet_amount,sub_num",
                        "updated_at" => 1731666410,
                        "bottom_field_all" => "valid_bet_amount,sub_num",
                        "flag" => 0,
                        "sort" => 5
                    ],
                    [
                        "id" => "5",
                        "module_name" => "下级财务",
                        "api_path" => "SubordinateIncome",
                        "is_show" => 1,
                        "is_top" => 0,
                        "bottom_field" => "total_dept_amount,balance,sub_num",
                        "updated_at" => 1731666408,
                        "bottom_field_all" => "total_dept_amount,balance,sub_num",
                        "flag" => 0,
                        "sort" => 4
                    ],
                    [
                        "id" => "6",
                        "module_name" => "下级领取",
                        "api_path" => "DirectlyGet",
                        "is_show" => 1,
                        "is_top" => 0,
                        "bottom_field" => "sub_num,water,vip_upgrade,total_get",
                        "updated_at" => 1731666414,
                        "bottom_field_all" => "sub_num,water,vip_upgrade,total_get",
                        "flag" => 0,
                        "sort" => 6
                    ],
                    [
                        "id" => "7",
                        "module_name" => "下级汇总",
                        "api_path" => "AgentOverview",
                        "is_show" => 1,
                        "is_top" => 0,
                        "bottom_field" => "deposit,dividend,water",
                        "updated_at" => 1732744237,
                        "bottom_field_all" => "deposit,withdraw,win,dividend,water",
                        "flag" => 0,
                        "sort" => 7
                    ],
                    [
                        "id" => "8",
                        "module_name" => "下级展示设置",
                        "api_path" => "sub_show_switch",
                        "is_show" => 0,
                        "is_top" => 0,
                        "bottom_field" => "",
                        "updated_at" => 1732752967,
                        "bottom_field_all" => "",
                        "flag" => 1,
                        "sort" => 0
                    ],
                    [
                        "id" => "9",
                        "module_name" => "直属下级人数",
                        "api_path" => "super_switch",
                        "is_show" => 1,
                        "is_top" => 0,
                        "bottom_field" => "",
                        "updated_at" => 1731575618,
                        "bottom_field_all" => "",
                        "flag" => 1,
                        "sort" => 0
                    ],
                    [
                        "id" => "10",
                        "module_name" => "教程展示",
                        "api_path" => "tutorial_switch",
                        "is_show" => 1,
                        "is_top" => 0,
                        "bottom_field" => "",
                        "updated_at" => 1731575620,
                        "bottom_field_all" => "",
                        "flag" => 1,
                        "sort" => 0
                    ]
                ],
                "msg" => null
            ];

            echo json_encode($response);
        }
        // Rota promo/sync/dept/promo
        if ($requestURI === '/api/promo/sync/dept/promo') {
            $rotaEncontrada = true; // Rota encontrada

            $response = [
                "status" => true,
                "data" => null,
                "msg" => null
            ];

            echo json_encode($response);
        }
        break;

    case 'GET':
        /* Rotas GET */
        // Rota Member/balance
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/balance') {
            $rotaEncontrada = true; // Rota encontrada
            // Verifica se o cookie 'token_user' está definido e não está vazio
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $token = mysqli_real_escape_string($mysqli, $_COOKIE['token_user']);

                // Usando prepared statement para segurança
                $stmt = $mysqli->prepare("SELECT * FROM usuarios WHERE token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                $resp = $stmt->get_result();

                if ($resp->num_rows > 0) {
                    $datres = $resp->fetch_assoc();
                    $userData = array(
                        "status" => true,
                        "data" => [
                            "uid" => $datres['id'],
                            "balance" => $datres['saldo'],
                            "lock_amount" => "0.00",
                        ],
                    );

                    // Converte o array associativo para JSON e retorna com código 200
                    http_response_code(200);
                    echo json_encode($userData);
                } else {
                    $response = [
                        "code" => 0, // Indica falha
                        "msg" => "Usuário sem efetuar login", // Mensagem de erro
                    ];
                    http_response_code(401); // Unauthorized
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "msg" => "Token não encontrado ou inválido",
                    "time" => time(),
                ];
                http_response_code(400); // Bad Request
                echo json_encode($response);
                exit;
            }
        }
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/finance/third/order/status') {
            $rotaEncontrada = true;

            $id = isset($_GET['id']) ? intval($_GET['id']) : null;

            if (!$id) {
                $response = ["status" => true, "data" => 361, "msg" => null];
                echo json_encode($response);
                exit;
            }

            $query = "SELECT transacao_id, status FROM transacoes WHERE id = ?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $transacao = $result->fetch_assoc();
                $transacaoId = $transacao['transacao_id'];
                $statusAtual = $transacao['status'];

                if ($statusAtual !== 'pago') {
                    $response = ["status" => true, "data" => 361, "msg" => null];
                } else {
                    $response = ["status" => true, "data" => 362, "msg" => null];
                }
            } else {
                $response = ["status" => true, "data" => 361, "msg" => null];
            }

            $stmt->close();

            echo json_encode($response);
            exit;
        }
        // Rota Withdraw/fee
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/finance/withdraw/fee?') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [
                    [
                        "id" => "",
                        "tag_id" => "",
                        "fmin" => 0,
                        "fmax" => 20,
                        "amount" => 0,
                        "flags" => 1,
                        "updated_name" => "",
                        "updated_at" => 0,
                    ],
                ],
                "msg" => null,
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Webset/list
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/webset/list') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [
                    "pages_turning_style" => "1",
                    "currencyRequired" => "0",
                    "banner_bottom_switch" => "0",
                    "footerJson" => '{"styleDisplay":"1","quickNavigateToggle":"1","cassinoAry":"返水,VIP,邀请/代理,任务中心,活动,待领取,历史","gameAry":"捕鱼,电子","suporteAry":"在线客服","快速跳转地址":"历史","shareSettingsToggle":"1","officialChannelToggle":"1","licenseImgToggle":"0","partnerInfoToggle":"1","licenseToggle":"0","hzValue":"","pzValue":"","partnerInfoData":[{"image":"/image/sigap-logo.png","name":"SIGAP","operationTime":"2024-08-21 03:02:09","operator":"mango01","id":"_2fgwmvp70"},{"image":"/image/1724220135199..webp","name":"PG","operationTime":"2024-08-21 03:02:18","operator":"mango01","id":"_4drxwjogn"},{"image":"/image/1724220142647..webp","name":"GC","operationTime":"2024-08-21 03:02:26","operator":"mango01","id":"_gqmphdwhj"},{"image":"/image/1724220149222..webp","name":"PIX","operationTime":"2024-08-21 03:02:32","operator":"mango01","id":"_o1mtg5mic"}],"licenseInfo":[{"image":"/image/1724220587697..webp","name":"MGA","operationTime":"2024-08-21 03:09:52","operator":"mango01","id":"_f26kelw6d"},{"image":"/image/1724220597985..webp","name":"GLI","operationTime":"2024-08-21 03:10:35","operator":"mango01","id":"_x3pqd4zva"},{"image":"/image/1724220640166..webp","name":"GC","operationTime":"2024-08-21 03:10:43","operator":"mango01","id":"_3fjlt8bhm"},{"image":"/image/1724220646502..webp","name":"PAGCOR","operationTime":"2024-08-21 03:10:53","operator":"mango01","id":"_ibe2y2dng"}],"licenseImgData":[],"copyrightInfo":["Copyright © 2025","@Todos os direitos reservados  2020-2030"],"companyInfoHtml":"<p style=\"line-height: 1.5;\"><span style=\"font-size: 14px;\">O Grupo 7K é uma das empresas operadoras de cassino online mais famosa do mundo. A empresa foi autorizada e regulamentada pelo governo brasileiro. O Grupo foi aprovado em todas as auditorias de conformidade e está legalmente autorizada a operar todos os jogos de azar e apostas.</span></p><p style=\"line-height: 1.5;\"><span style=\"font-size: 14px;\"><br></span></p>"}',
                    "deposit_to" => "/activity-detail/17395548563954431/deposit",
                    "pool_money_style" => "5",
                    "pop" => [
                        [
                            "id" => "168545903842044476",
                            "ty" => "",
                            "name" => "Telegram:",
                            "portal" => [
                                "pc",
                                "h5",
                                "app"
                            ],
                            "img" => "/image/1734184357603..webp",
                            "link" => "https://telegram.me/",
                            "oper" => "",
                            "sway" => 0,
                            "sort" => 3,
                            "state" => 1,
                            "op_at" => 1734184365,
                            "login_bf" => 2,
                            "login_af" => 2,
                            "close_today" => 0,
                            "recipient_type" => 0,
                            "recipient" => ""
                        ]

                    ],
                    "nav_bottom_app" => '[{"id":"100001","icon":"HomeIcon_1","name":"首页","title":"首页","action":"Home","is_before":1,"is_after":1,"client":"app","area":0,"sort_by":0,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1},{"id":"100002","icon":"FavorableIcon_1","name":"优惠","title":"优惠","action":"favorable","is_before":1,"is_after":1,"client":"app","area":0,"sort_by":1,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1},{"id":"100006","icon":"PromotionIcon_1","name":"推广","title":"推广","action":"promotion","is_before":1,"is_after":1,"client":"app","area":0,"sort_by":2,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1},{"id":"100005","icon":"RegisterIcon_1","name":"注册","title":"注册","action":"register","is_before":1,"is_after":0,"client":"app","area":0,"sort_by":3,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1},{"id":"100007","icon":"RechargeIcon_1","name":"充值","title":"充值","action":"recharge","is_before":0,"is_after":1,"client":"app","area":0,"sort_by":4,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1},{"id":"100003","icon":"ProfileIcon_1","name":"我的","title":"我的","action":"profile","is_before":1,"is_after":1,"client":"app","area":0,"sort_by":5,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1},{"id":"100004","icon":"LoginIcon_1","name":"登录","title":"登录","action":"login","is_before":0,"is_after":0,"client":"app","area":0,"sort_by":6,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1},{"id":"100009","icon":"SupportIcon_1","name":"客服","title":"客服","action":"support","is_before":0,"is_after":0,"client":"app","area":0,"sort_by":7,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1},{"id":"100008","icon":"WithdrawIcon_1","name":"提现","title":"提现","action":"withdraw","is_before":0,"is_after":0,"client":"app","area":0,"sort_by":8,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1},{"id":"100010","icon":"VIPIcon_1","name":"VIP","title":"VIP","action":"vip","is_before":0,"is_after":0,"client":"app","area":0,"sort_by":9,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1}]',
                    "googleH5AppID" => "759104052409-kjfinnfn3f2e518dcitvoro92t7d2pvo.apps.googleusercontent.com",
                    "deposit_img_h5" => "/image/1731785642797..webp",
                    "marqueeType" => "2",
                    "facebookQuickLogin" => "0",
                    "facebookH5AppID" => "1031588484922166",
                    "snapchatRequired" => "0",
                    "guide_title" => "PRIME-Group",
                    "realNameRequired" => "0",
                    "official_channels" => '[{"id":"2352249","prefix":"","name":"instagram","icon":"/image/1734184510598..gif","link":"https://www.instagram.com/","status":1,"remark":"","sort_order":1,"operator":"","operation_time":1734184516},{"id":"2352248","prefix":"","name":"telegram.me","icon":"/image/1734184493767..webp","link":"https://telegram.me/","status":1,"remark":"","sort_order":1,"operator":"","operation_time":1734184495}]',
                    "pool_forward_flag" => "/",
                    "logRegisterStyle" => "3", // ESTILO DO POPUP DE LOGIN E REGISTRO 1 AO 3
                    "player_switch" => "1",
                    "lang_switch" => "1",
                    "phoneRequired" => "0",
                    "pool_forward_jump_type" => "1",
                    "auto_withdraw_channels" => "2501,2061",
                    "pool_custom_style" => "/uploads/jackpot_custom.png",
                    "t_limits" => '[{"id":"538923381501373445","tag_id":"0","fmin":10,"fmax":50000,"updated_name":"lws01","updated_at":1712137324}]',
                    "facebookH5Secret" => "8ec5f9a24e59c46e3d0ba37c21f7c1f4",
                    "s_wdraw_fst_deptamount" => "0",
                    "share" => [
                        [
                            "id" => "14797924984634028",
                            "ty" => "",
                            "name" => "Instagram",
                            "portal" => [
                                ""
                            ],
                            "img" => "/image/1729570442485..gif",
                            "link" => "https://www.instagram.com",
                            "oper" => "",
                            "sway" => 0,
                            "sort" => 1,
                            "state" => 1,
                            "op_at" => 1729570444,
                            "login_bf" => 0,
                            "login_af" => 0,
                            "close_today" => 0,
                            "recipient_type" => 0,
                            "recipient" => ""
                        ],
                        [
                            "id" => "507339199868328987",
                            "ty" => "",
                            "name" => "Facebook",
                            "portal" => [
                                ""
                            ],
                            "img" => "/image/1710154419122..webp",
                            "link" => "https://www.facebook.com/sharer/sharer.php?u=xxxxx",
                            "oper" => "",
                            "sway" => 0,
                            "sort" => 2,
                            "state" => 1,
                            "op_at" => 1712855731,
                            "login_bf" => 0,
                            "login_af" => 0,
                            "close_today" => 0,
                            "recipient_type" => 0,
                            "recipient" => ""
                        ],
                        [
                            "id" => "507346558646967565",
                            "ty" => "",
                            "name" => "Telegram",
                            "portal" => [
                                ""
                            ],
                            "img" => "/image/1728706304768..webp",
                            "link" => "https://telegram.me",
                            "oper" => "",
                            "sway" => 0,
                            "sort" => 2,
                            "state" => 1,
                            "op_at" => 1729570399,
                            "login_bf" => 0,
                            "login_af" => 0,
                            "close_today" => 0,
                            "recipient_type" => 0,
                            "recipient" => ""
                        ],
                        [
                            "id" => "507347921991100188",
                            "ty" => "",
                            "name" => "Youtube",
                            "portal" => [
                                ""
                            ],
                            "img" => "/image/1710154410968..webp",
                            "link" => "https://www.youtube.com/",
                            "oper" => "",
                            "sway" => 0,
                            "sort" => 4,
                            "state" => 1,
                            "op_at" => 1710154412,
                            "login_bf" => 0,
                            "login_af" => 0,
                            "close_today" => 0,
                            "recipient_type" => 0,
                            "recipient" => ""
                        ],
                        [
                            "id" => "507350188537890161",
                            "ty" => "",
                            "name" => "Whatsapp",
                            "portal" => [
                                ""
                            ],
                            "img" => "/image/1710154463790..webp",
                            "link" => "https://wa.me/?text=xxxxx",
                            "oper" => "",
                            "sway" => 0,
                            "sort" => 1,
                            "state" => 1,
                            "op_at" => 1712855692,
                            "login_bf" => 0,
                            "login_af" => 0,
                            "close_today" => 0,
                            "recipient_type" => 0,
                            "recipient" => ""
                        ],
                        [
                            "id" => "507352469121437887",
                            "ty" => "",
                            "name" => "Twitter",
                            "portal" => [
                                ""
                            ],
                            "img" => "/image/1712855638183..webp",
                            "link" => "https://twitter.com/intent/tweet",
                            "oper" => "",
                            "sway" => 0,
                            "sort" => 6,
                            "state" => 1,
                            "op_at" => 1712855644,
                            "login_bf" => 0,
                            "login_af" => 0,
                            "close_today" => 0,
                            "recipient_type" => 0,
                            "recipient" => ""
                        ]
                    ],
                    "game_recommend" => "1",
                    "img_shape" => "1", // TIPO DA IMAGEM, NORMAL 1 OU PEQUENA 2
                    "authLogReg" => "0",
                    "banner_switch" => "1",
                    "homePopupStyle" => "2", // TIPO DO POPUP DE ANUNCIOS, NORMAL 1, TABS EM CIMA 2, TABS EM BAIXO 3, 4 NORMAL COM AS TABS NA DIREITA
                    "pCenterStyle" => "1",
                    "player_autoplay" => "1",
                    "googleH5Secret" => "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",
                    "banner_img" => "https://ik.imagekit.io/esqaugmss/BAIXAR%20APP%20(1).png",
                    "pool_forward_name" => "/",
                    "banner_hidden_proxy" => "",
                    "authLogRegType" => "slide",
                    "decimalPlaces" => "2",
                    "nav_bottom_h5" => '[{"id":"100001","icon":"HomeIcon_1","name":"首页","title":"首页","action":"Home","is_before":1,"is_after":1,"client":"h5","area":0,"sort_by":0,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1},{"id":"100002","icon":"FavorableIcon_1","name":"优惠","title":"优惠","action":"favorable","is_before":1,"is_after":1,"client":"h5","area":0,"sort_by":1,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1},{"id":"100006","icon":"PromotionIcon_1","name":"推广","title":"推广","action":"promotion","is_before":1,"is_after":1,"client":"h5","area":0,"sort_by":2,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1},{"id":"100005","icon":"RegisterIcon_1","name":"注册","title":"注册","action":"register","is_before":1,"is_after":0,"client":"h5","area":0,"sort_by":3,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1},{"id":"100007","icon":"RechargeIcon_1","name":"充值","title":"充值","action":"recharge","is_before":0,"is_after":1,"client":"h5","area":0,"sort_by":4,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1},{"id":"100003","icon":"ProfileIcon_1","name":"我的","title":"我的","action":"profile","is_before":1,"is_after":1,"client":"h5","area":0,"sort_by":5,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1},{"id":"100009","icon":"SupportIcon_1","name":"客服","title":"客服","action":"support","is_before":0,"is_after":0,"client":"h5","area":0,"sort_by":6,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1},{"id":"100008","icon":"WithdrawIcon_1","name":"提现","title":"提现","action":"withdraw","is_before":0,"is_after":0,"client":"h5","area":0,"sort_by":7,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1},{"id":"100010","icon":"VIPIcon_1","name":"VIP","title":"VIP","action":"vip","is_before":0,"is_after":0,"client":"h5","area":0,"sort_by":8,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1},{"id":"100004","icon":"LoginIcon_1","name":"登录","title":"登录","action":"login","is_before":0,"is_after":0,"client":"h5","area":0,"sort_by":9,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1},{"id":"100011","icon":"DownloadIcon_1","name":"APP下载","title":"APP下载","action":"download","is_before":0,"is_after":0,"client":"h5","area":0,"sort_by":10,"created_at":1720676435,"updated_at":1720676435,"iconStyle":1}]',
                    "Redirect_Url" => $url_base,
                    "pool_style" => $dataconfig['jackpot'],
                    "pool_forward_id" => "/",
                    "personal_information_real_name" => "1",
                    "hot_game_record_num" => "20000",
                    "marqueeTxt" => $dataconfig['marquee'],
                    "pool_switch" => "1",
                    "quickLogin" => "google",
                    "register_need_snapchat_switch" => "1",
                    "netsignal_switch" => "1",
                    "register_need_name_switch" => "0",
                    "favicon_img" => "/uploads/favicon.png.webp",
                    "group_name" => "egitopg777.com",
                    "deposit_img_pc" => "/image/1708935841207.webp",
                    "FloatingStyle" => "1",
                    "logo_img" => "/uploads/logo.png.webp",
                    "web_title" => "EGITOPG777",
                    "isShowCPF" => "0",
                    "holiday_theme_end" => "",
                    "holiday_theme_name" => "",
                    "holiday_theme_switch" => "0",
                    "holiday_theme_start" => "",
                    "holidayTheme" => '[{"theme_name":"圣诞节","theme_switch":"1","start_time":"2024-12-09 00:00:00","end_time":"2024-12-31 23:59:59","value":"christmas"},{"theme_name":"元旦","theme_switch":"0","start_time":"2024-12-09 00:00:00","end_time":"2024-12-31 23:59:59","value":"newYear"}]',
                    "reg_need_phone" => "0",
                    "prefix" => "n01",
                    "googleQuickLogin" => "0",
                    "t_fees" => '[{"id":"1","tag_id":"","fmin":0,"fmax":20,"amount":0,"flags":1,"updated_name":"lws01","updated_at":1712137324}]'
                ],
                "msg" => null
            ];
            // Use JSON_UNESCAPED_UNICODE to avoid escaping Unicode characters
            $response_json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            echo $response_json;
        }
        // Rota Member/nav
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/nav') {
            $rotaEncontrada = true; // Rota encontrada
            // // SQL para obter os dados dos jogos principais
            // $sql = "SELECT * FROM provedores";
            // $result = $mysqli->query($sql);
            // $games_data = [];

            // if ($result->num_rows > 0) {
            //     while ($row = $result->fetch_assoc()) {
            //         // Criando um array para jogos filhos ("l")
            //         $sub_games = [
            //             [
            //                 "id" => "PGSOFT",
            //                 "name" => "PG Slots",
            //                 "wallet_id" => "PGSOFT",
            //                 "wallet_name" => "PG",
            //                 "sub" => ["PGSOFT", "26595015200305"],
            //                 "game_type" => 3,
            //                 "maintained" => 1,
            //                 "maintained_start" => 0,
            //                 "maintained_end" => 0,
            //                 "flags" => 3,
            //                 "state" => 1,
            //                 "seq" => 99,
            //                 "share_wallet" => 0,
            //                 "platform_is_hot" => 1,
            //                 "min_admission" => 0,
            //                 "jump_type" => 0,
            //                 "currency" => "BRL",
            //                 "promo_image" => "",
            //                 "popular_image" => "",
            //                 "games_count" => 92,
            //                 "code" => "",
            //                 "pid" => "PGSOFT",
            //                 "img" => "/images-br/plat/Slots-PG.png.webp",
            //                 "automatic" => 1,
            //             ],
            //             [
            //                 "id" => "26595015200310",
            //                 "name" => "PP Slots",
            //                 "wallet_id" => "26595015200309",
            //                 "wallet_name" => "SG/JL/JDB/PG/PP",
            //                 "sub" => ["36595015200324"],
            //                 "game_type" => 3,
            //                 "maintained" => 1,
            //                 "maintained_start" => 0,
            //                 "maintained_end" => 0,
            //                 "flags" => 3,
            //                 "state" => 0,
            //                 "seq" => 8,
            //                 "share_wallet" => 0,
            //                 "platform_is_hot" => 0,
            //                 "min_admission" => 0,
            //                 "jump_type" => 0,
            //                 "currency" => "BRL",
            //                 "promo_image" => "",
            //                 "popular_image" => "",
            //                 "games_count" => 347,
            //                 "code" => "",
            //                 "pid" => "36595015200324",
            //                 "img" => "/images-br/plat/Slots-PP.png.webp",
            //                 "automatic" => 1,
            //             ],
            //         ];

            //         // Montando a estrutura conforme o JSON solicitado
            //         $games_data[] = [
            //             "id" => $row['id'], // Usando o ID do banco
            //             "code" => $row['id'], // Código do jogo
            //             "name" => $row['name'], // Nome do jogo
            //             "title" => "热门", // Título fixo
            //             "icon" => "", // Pode adicionar um valor se houver um campo de ícone no DB
            //             "url" => "", // Pode adicionar a URL se disponível
            //             "currency" => "BRL", // Moeda fixada
            //             "state" => 1, // Estado fixo
            //             "sort" => $row['id'], // Classificação fixa
            //             "show_by" => "{\"sh\":3,\"ss\":3,\"fh\":3,\"fs\":3}", // Valor fixo conforme o exemplo
            //             "open_by" => "", // Valor vazio
            //             "created_at" => 1721440897, // Usando o timestamp fornecido no exemplo
            //             "updated_at" => 1722501993, // Timestamp para atualização
            //             "operator_id" => 4662433674467505, // Operador fixo
            //             "operator_name" => "mango01", // Nome do operador fixo
            //             "prefix" => "f51", // Prefixo fixo
            //             "img" => '', // Imagem/banner do banco de dados
            //             //"l" => $sub_games, // Lista de sub-jogos
            //         ];
            //     }

            //     // Envolvendo os dados no formato solicitado
            //     $response = [
            //         "status" => true,
            //         "data" => $games_data, // Retornando o array diretamente como no JSON solicitado
            //     ];
            // } else {
            //     // Caso não haja resultados
            //     $response = [
            //         "status" => false,
            //         "msg" => "Nenhum jogo encontrado",
            //     ];
            // }

            // // Fechando a conexão com o banco de dados
            // $mysqli->close();

            $response = [
                "status" => true,
                "data" => [
                    [
                        "id" => 0,
                        "code" => 0,
                        "name" => "Hot",
                        "title" => "热门",
                        "icon" => "",
                        "url" => "",
                        "currency" => "",
                        "state" => 1,
                        "sort" => 0,
                        "show_by" => '{"sh":3,"ss":3,"fh":3,"fs":3}',
                        "open_by" => 0,
                        "created_at" => 1721440897,
                        "updated_at" => 1722501993,
                        "operator_id" => 4662433674467505,
                        "operator_name" => "admin",
                        "prefix" => "n01",
                        "img" => "",
                        "l" => []
                    ],
                    [
                        "id" => 3,
                        "code" => 3,
                        "name" => "Slots",
                        "title" => "电子",
                        "icon" => "",
                        "url" => "",
                        "currency" => "",
                        "state" => 1,
                        "sort" => 2,
                        "show_by" => '{"sh":2,"ss":2,"fh":2,"fs":2}',
                        "open_by" => 0,
                        "created_at" => 1721440897,
                        "updated_at" => 1722502399,
                        "operator_id" => 4662433674467505,
                        "operator_name" => "admin",
                        "prefix" => "n01",
                        "img" => "",
                        "l" => [
                            [
                                "id" => "26595015200313",
                                "name" => "",
                                "wallet_id" => "26595015200313",
                                "wallet_name" => "PG",
                                "sub" => [
                                    "26595015200313"
                                ],
                                "game_type" => 3,
                                "maintained" => 1,
                                "maintained_start" => 0,
                                "maintained_end" => 0,
                                "flags" => 3,
                                "state" => 1,
                                "seq" => 100,
                                "share_wallet" => 0,
                                "platform_is_hot" => 1,
                                "min_admission" => 0,
                                "jump_type" => 0,
                                "currency" => "",
                                "promo_image" => "/image/1734459547297..webp",
                                "popular_image" => "/image/1734459557618..webp",
                                "games_count" => 108,
                                "code" => "",
                                "pid" => "PGSOFT",
                                "img" => "https://i.postimg.cc/W4XxVKw5/tigerbet.png",
                                "automatic" => 1
                            ],
                            [
                                "id" => "26595015200310",
                                "name" => "",
                                "wallet_id" => "26595015200309",
                                "wallet_name" => "SG/JL/JDB/PG/PP",
                                "sub" => ["26595015200310"],
                                "game_type" => 3,
                                "maintained" => 1,
                                "maintained_start" => 0,
                                "maintained_end" => 0,
                                "flags" => 3,
                                "state" => 1,
                                "seq" => 8,
                                "share_wallet" => 0,
                                "platform_is_hot" => 0,
                                "min_admission" => 0,
                                "jump_type" => 0,
                                "promo_image" => "",
                                "popular_image" => "",
                                "games_count" => 347,
                                "code" => "",
                                "pid" => "26595015200310",
                                "img" => "https://i.postimg.cc/nVBbjdFh/gateof.png",
                                "automatic" => 1,
                            ],
                            [
                                "id" => "26595015200317",
                                "name" => "",
                                "wallet_id" => "26595015200317",
                                "wallet_name" => "gf_fcslot_wallet",
                                "sub" => [
                                    "26595015200317"
                                ],
                                "game_type" => 3,
                                "maintained" => 1,
                                "maintained_start" => 0,
                                "maintained_end" => 0,
                                "flags" => 3,
                                "state" => 1,
                                "seq" => 92,
                                "share_wallet" => 0,
                                "platform_is_hot" => 0,
                                "min_admission" => 0,
                                "jump_type" => 0,
                                "currency" => "",
                                "promo_image" => "",
                                "popular_image" => "",
                                "games_count" => 41,
                                "code" => "",
                                "pid" => "26595015200317",
                                "img" => "https://i.postimg.cc/s2vbVmHy/jdb.avif",
                                "automatic" => 1
                            ],
                            [
                                "id" => "36595015200314",
                                "name" => "",
                                "wallet_id" => "36595015200314",
                                "wallet_name" => "ace_win_single_wallet",
                                "sub" => [
                                    "36595015200314"
                                ],
                                "game_type" => 3,
                                "maintained" => 1,
                                "maintained_start" => 0,
                                "maintained_end" => 0,
                                "flags" => 3,
                                "state" => 1,
                                "seq" => 91,
                                "share_wallet" => 0,
                                "platform_is_hot" => 0,
                                "min_admission" => 0,
                                "jump_type" => 0,
                                "currency" => "BRL",
                                "promo_image" => "",
                                "popular_image" => "",
                                "games_count" => 56,
                                "code" => "",
                                "pid" => "36595015200314",
                                "img" => "https://i.postimg.cc/DzJYVZmw/evo.png",
                                "automatic" => 0
                            ],
                            [
                                "id" => "26595015100306",
                                "name" => "",
                                "wallet_id" => "26595015100306",
                                "wallet_name" => "tada_wallet",
                                "sub" => [
                                    "26595015100306"
                                ],
                                "game_type" => 3,
                                "maintained" => 1,
                                "maintained_start" => 0,
                                "maintained_end" => 0,
                                "flags" => 3,
                                "state" => 1,
                                "seq" => 89,
                                "share_wallet" => 0,
                                "platform_is_hot" => 0,
                                "min_admission" => 0,
                                "jump_type" => 0,
                                "currency" => "",
                                "promo_image" => "",
                                "popular_image" => "",
                                "games_count" => 89,
                                "code" => "",
                                "pid" => "26595015100306",
                                "img" => "https://i.postimg.cc/8cDyrm47/kaslot.png",
                                "automatic" => 1
                            ],
                            [
                                "id" => "26595015200309",
                                "name" => "",
                                "wallet_id" => "26595015200309",
                                "wallet_name" => "SG/JL/JDB/PG/PP",
                                "sub" => [
                                    "26595015200309"
                                ],
                                "game_type" => 3,
                                "maintained" => 1,
                                "maintained_start" => 0,
                                "maintained_end" => 0,
                                "flags" => 3,
                                "state" => 1,
                                "seq" => 88,
                                "share_wallet" => 0,
                                "platform_is_hot" => 0,
                                "min_admission" => 0,
                                "jump_type" => 0,
                                "currency" => "",
                                "promo_image" => "",
                                "popular_image" => "",
                                "games_count" => 117,
                                "code" => "",
                                "pid" => "26595015200309",
                                "img" => "https://i.postimg.cc/vZj3vsXF/tada.png",
                                "automatic" => 1

                            ]
                        ]
                    ]
                ]
            ];

            // Retornando o JSON no formato correto
            echo json_encode($response, JSON_UNESCAPED_SLASHES);
        }
        // Rota Promo/welfare/getconf
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/promo/welfare/getconf') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [
                    "entrance" => "1,2",
                    "limited" => '""',
                    "pick" => "1",
                    "login_before" => '""',
                    "login_after" => '""',
                    "flow_multiple" => "1",
                    "is_audit" => 1
                ],
                "msg" => null
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Promo/welfare/config
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/promo/welfare/config') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [
                    [
                        "id" => "345849624849413116",
                        "promo_id" => "85100667711113",
                        "welfare_id" => "1",
                        "prefix" => "bs8",
                        "title" => "Faça o download do APP, instale e faça login no aplicativo pela primeira vez",
                        "uid" => "473430934",
                        "username" => "phillyps",
                        "flag" => 1,
                        "state" => 501,
                        "limited_at" => 0,
                        "expired_at" => 0,
                        "receipt_at" => 1734915102,
                        "remark" => "",
                        "device" => "0",
                        "device_ty" => "",
                        "ip" => "",
                        "amount" => 1.99,
                        "flow_multiple" => 1,
                        "created_at" => 1734915102,
                        "check_deposit" => 0,
                        "first_deposit_done" => 0,
                        "temp_add_record" => 0
                    ],
                    [
                        "id" => "345849625233464831",
                        "promo_id" => "85100667711113",
                        "welfare_id" => "2",
                        "prefix" => "bs8",
                        "title" => "Salve atalho de mesa",
                        "uid" => "473430934",
                        "username" => "phillyps",
                        "flag" => 1,
                        "state" => 501,
                        "limited_at" => 0,
                        "expired_at" => 0,
                        "receipt_at" => 1734915102,
                        "remark" => "",
                        "device" => "0",
                        "device_ty" => "",
                        "ip" => "",
                        "amount" => 0.99,
                        "flow_multiple" => 1,
                        "created_at" => 1734915102,
                        "check_deposit" => 0,
                        "first_deposit_done" => 0,
                        "temp_add_record" => 0
                    ],
                    [
                        "id" => "345849625582694236",
                        "promo_id" => "85100667711113",
                        "welfare_id" => "3",
                        "prefix" => "bs8",
                        "title" => "Primeira retirada",
                        "uid" => "473430934",
                        "username" => "phillyps",
                        "flag" => 1,
                        "state" => 501,
                        "limited_at" => 0,
                        "expired_at" => 0,
                        "receipt_at" => 1734915102,
                        "remark" => "",
                        "device" => "0",
                        "device_ty" => "",
                        "ip" => "",
                        "amount" => 1.99,
                        "flow_multiple" => 1,
                        "created_at" => 1734915102,
                        "check_deposit" => 1,
                        "first_deposit_done" => 0,
                        "temp_add_record" => 0
                    ],
                    [
                        "id" => "345849625944054656",
                        "promo_id" => "85100667711113",
                        "welfare_id" => "4",
                        "prefix" => "bs8",
                        "title" => "Definir aniversário",
                        "uid" => "473430934",
                        "username" => "phillyps",
                        "flag" => 1,
                        "state" => 502,
                        "limited_at" => 0,
                        "expired_at" => 0,
                        "receipt_at" => 1734915102,
                        "remark" => "",
                        "device" => "0",
                        "device_ty" => "",
                        "ip" => "",
                        "amount" => 1.99,
                        "flow_multiple" => 1,
                        "created_at" => 1734915102,
                        "check_deposit" => 1,
                        "first_deposit_done" => 0,
                        "temp_add_record" => 0
                    ],
                    [
                        "id" => "345849626301815197",
                        "promo_id" => "85100667711113",
                        "welfare_id" => "5",
                        "prefix" => "bs8",
                        "title" => "Definir senha de saque",
                        "uid" => "473430934",
                        "username" => "phillyps",
                        "flag" => 1,
                        "state" => 502,
                        "limited_at" => 0,
                        "expired_at" => 0,
                        "receipt_at" => 1734915102,
                        "remark" => "",
                        "device" => "0",
                        "device_ty" => "",
                        "ip" => "",
                        "amount" => 1.99,
                        "flow_multiple" => 1,
                        "created_at" => 1734915102,
                        "check_deposit" => 1,
                        "first_deposit_done" => 0,
                        "temp_add_record" => 0
                    ],
                    [
                        "id" => "345849626640789612",
                        "promo_id" => "85100667711113",
                        "welfare_id" => "6",
                        "prefix" => "bs8",
                        "title" => "Adicionar conta de saque",
                        "uid" => "473430934",
                        "username" => "phillyps",
                        "flag" => 1,
                        "state" => 502,
                        "limited_at" => 0,
                        "expired_at" => 0,
                        "receipt_at" => 1734915102,
                        "remark" => "",
                        "device" => "0",
                        "device_ty" => "",
                        "ip" => "",
                        "amount" => 1.99,
                        "flow_multiple" => 1,
                        "created_at" => 1734915102,
                        "check_deposit" => 1,
                        "first_deposit_done" => 0,
                        "temp_add_record" => 0
                    ],
                    [
                        "id" => "345849627025428435",
                        "promo_id" => "85100667711113",
                        "welfare_id" => "7",
                        "prefix" => "bs8",
                        "title" => "Definir avatar",
                        "uid" => "473430934",
                        "username" => "phillyps",
                        "flag" => 1,
                        "state" => 502,
                        "limited_at" => 0,
                        "expired_at" => 0,
                        "receipt_at" => 1734915102,
                        "remark" => "",
                        "device" => "0",
                        "device_ty" => "",
                        "ip" => "",
                        "amount" => 1.99,
                        "flow_multiple" => 1,
                        "created_at" => 1734915102,
                        "check_deposit" => 1,
                        "first_deposit_done" => 0,
                        "temp_add_record" => 0
                    ],
                    [
                        "id" => "345849627402720113",
                        "promo_id" => "85100667711113",
                        "welfare_id" => "8",
                        "prefix" => "bs8",
                        "title" => "Set up contact details",
                        "uid" => "473430934",
                        "username" => "phillyps",
                        "flag" => 1,
                        "state" => 502,
                        "limited_at" => 0,
                        "expired_at" => 0,
                        "receipt_at" => 1734915102,
                        "remark" => "",
                        "device" => "0",
                        "device_ty" => "",
                        "ip" => "",
                        "amount" => 1.99,
                        "flow_multiple" => 1,
                        "created_at" => 1734915102,
                        "check_deposit" => 1,
                        "first_deposit_done" => 0,
                        "temp_add_record" => 0
                    ],
                    [
                        "id" => "345849188113477103",
                        "promo_id" => "85100667711113",
                        "welfare_id" => "9",
                        "prefix" => "bs8",
                        "title" => "Cadastre-se e ganhe 1.99",
                        "uid" => "473430934",
                        "username" => "phillyps",
                        "flag" => 1,
                        "state" => 502,
                        "limited_at" => 0,
                        "expired_at" => 0,
                        "receipt_at" => 1735024844,
                        "remark" => "",
                        "device" => "0",
                        "device_ty" => "h5",
                        "ip" => "2804:15fc:102f:d701:e15d:2165:ef65:e086",
                        "amount" => 1.99,
                        "flow_multiple" => 1,
                        "created_at" => 1734915101,
                        "check_deposit" => 1,
                        "first_deposit_done" => 0,
                        "temp_add_record" => 0
                    ],
                    [
                        "id" => "345849188013746605",
                        "promo_id" => "85100667711113",
                        "welfare_id" => "10",
                        "prefix" => "bs8",
                        "title" => "Vincular número de celular",
                        "uid" => "473430934",
                        "username" => "phillyps",
                        "flag" => 1,
                        "state" => 502,
                        "limited_at" => 0,
                        "expired_at" => 0,
                        "receipt_at" => 1734915101,
                        "remark" => "",
                        "device" => "0",
                        "device_ty" => "",
                        "ip" => "",
                        "amount" => 2.01,
                        "flow_multiple" => 1,
                        "created_at" => 1734915101,
                        "check_deposit" => 1,
                        "first_deposit_done" => 0,
                        "temp_add_record" => 0
                    ]
                ],
                "msg" => null
            ];
            // Use JSON_UNESCAPED_UNICODE to avoid escaping Unicode characters
            $response_json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            echo $response_json;
        }
        // Rota Member/award
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/award') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [
                    "amount" => 11575274645,
                    "num" => 0,
                    "prefix" => "f51",
                ],
                "msg" => null,
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Member/Banner
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/banner') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [
                    [
                        "id" => "12135081375969104",
                        "title" => "开宝箱",
                        "content" => "",
                        "url" => "/activity/recommend-friends",
                        "sort" => "1",
                        "images" => "/uploads/banner1.png.webp",
                        "flags" => "1",
                        "kf_type" => 0,
                    ],
                    [
                        "id" => "7621850545701900",
                        "title" => "首存1",
                        "content" => "",
                        "url" => "/activity-detail/17395548563954431/deposit",
                        "sort" => "2",
                        "images" => "/uploads/banner2.png.webp",
                        "flags" => "1",
                        "kf_type" => 0,
                    ],
                    [
                        "id" => "12141996033465223",
                        "title" => "VIP",
                        "content" => "",
                        "url" => "/activity-detail/12052671887318748/static",
                        "sort" => "3",
                        "images" => "/uploads/banner3.png.webp",
                        "flags" => "1",
                        "kf_type" => 0,
                    ],
                ],
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Member/Slot/Hotgame
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/slot/hotgame') {
            header("Cache-Control: no-cache, must-revalidate");
            header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

            $response = [];

            // Obtenha os parâmetros page e page_size da URL, com valores padrão
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Página atual
            $page_size = isset($_GET['page_size']) ? intval($_GET['page_size']) : 12; // Número de itens por página

            // Calcular o offset para a consulta SQL
            $offset = ($page - 1) * $page_size;

            // SQL para obter o total de jogos
            $total_sql = "SELECT COUNT(*) AS total FROM games";
            $total_result = $mysqli->query($total_sql);
            if (!$total_result) {
                error_log("Erro na consulta SQL de total de jogos: " . mysqli_error($mysqli));
                $response = [
                    "status" => false,
                    "msg" => "Erro ao acessar os jogos.",
                ];
                echo json_encode($response);
                exit;
            }
            $total_row = $total_result->fetch_assoc();
            $total_games = $total_row['total'];

            // SQL para obter os dados dos jogos com paginação
            $sql = "SELECT id, game_code, game_name, provider, banner FROM games ORDER BY popular DESC LIMIT ?, ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ii", $offset, $page_size);
            $stmt->execute();
            $result = $stmt->get_result();

            // Verifica se a consulta falhou
            if (!$result) {
                error_log("Erro na consulta SQL: " . mysqli_error($mysqli));
                $response = [
                    "status" => false,
                    "msg" => "Erro ao acessar os jogos.",
                ];
                echo json_encode($response);
                exit;
            }

            $games_data = [];

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $isFavorite = 0;

                    // Verifica se o usuário está logado
                    if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                        $qry = "SELECT id, favoritos FROM usuarios WHERE token = ?";
                        $stmt = $mysqli->prepare($qry);
                        $stmt->bind_param("s", $_COOKIE['token_user']);
                        $stmt->execute();
                        $resp = $stmt->get_result();

                        if ($resp && $resp->num_rows > 0) {
                            $datares = $resp->fetch_assoc();
                            $userId = $datares['id'];
                            $historicoAtual = !empty($datares['favoritos']) ? explode(',', $datares['favoritos']) : [];

                            // Verifica se o jogo está no histórico
                            $isFavorite = in_array($row['id'], $historicoAtual) ? 1 : 0;
                        }
                    }

                    // Adiciona os dados do jogo ao array
                    $games_data[] = [
                        "id" => $row['id'],
                        "platform_id" => '36595015200324',
                        "client_type" => '',
                        "game_type" => '3',
                        "game_id" => $row['id'],
                        "img" => $row['banner'],
                        "is_hot" => 1,
                        "is_new" => 1,
                        "sorting" => 99,
                        "vn_alias" => "Hổ May Mắn",
                        "prefix" => "f51",
                        "game_code" => "",
                        "updated_at" => 0,
                        "updated_name" => "",
                        "currency" => "BRL",
                        "is_recommend" => 1,
                        "maintained" => 0,
                        "min_admission" => 1,
                        "is_lobby" => 0,
                        "hot_sort" => 99,
                        "is_favorites" => $isFavorite
                    ];
                }

                // Calcular o número total de páginas
                $total_pages = ceil($total_games / $page_size);

                // Retornar os dados com paginação
                $response = [
                    "status" => true,
                    "data" => [
                        "d" => $games_data,
                        "t" => $total_games,
                        "total_pages" => $total_pages, // Número total de páginas
                        "current_page" => $page,      // Página atual
                    ],
                ];
            } else {
                $response = [
                    "status" => false,
                    "msg" => "Nenhum jogo encontrado",
                ];
            }

            $mysqli->close();

            echo json_encode($response, JSON_UNESCAPED_SLASHES);
            exit;
        }

        // Rota Promo/List
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/promo/list') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [
                    [
                        "static" => [
                            "list_web" => "/uploads/promocao1.webp",
                            "list_h5" => "/uploads/promocao1.webp",
                            "title_web" => "/uploads/promocao1.webp",
                            "title_h5" => "/uploads/promocao1.webp",
                            "share_h5" => "",
                            "display_mode" => 1,
                            "link_mode" => 1,
                            "link_url" => "web_login"
                        ],
                        "id" => "17395548563954431",
                        "title" => "Os primeiros 3 bônus de recarga do novo membro",
                        "state" => 2,
                        "flag" => "deposit",
                        "grade" => "1019,1010,1009,2,2000,1001,1002,1003,1004,1005,1006,1007,1008,2005",
                        "login_af" => 0,
                        "login_bf" => 0,
                        "show_at" => 1734766593,
                        "show_end_at" => 0,
                        "apply_terminal" => "1"
                    ],
                    [
                        "static" => [
                            "list_web" => "/uploads/promocao2.webp",
                            "list_h5" => "/uploads/promocao2.webp",
                            "title_web" => "/uploads/promocao2.webp",
                            "title_h5" => "/uploads/promocao2.webp",
                            "share_h5" => "/",
                            "display_mode" => 1,
                            "link_mode" => 1,
                            "link_url" => "web_login"
                        ],
                        "id" => "17395548563955151",
                        "title" => "Recomende amigos e ganhe bônus",
                        "state" => 2,
                        "flag" => "invite",
                        "grade" => "1019,1010,1009,2,2000,1001,1002,1003,1004,1005,1006,1007,1008",
                        "login_af" => 0,
                        "login_bf" => 0,
                        "show_at" => 1733130110,
                        "show_end_at" => 0,
                        "apply_terminal" => "1"
                    ],
                    [
                        "static" => [
                            "list_web" => "/uploads/promocao4.webp",
                            "list_h5" => "/uploads/promocao4.webp",
                            "title_web" => "/uploads/promocao4.webp",
                            "title_h5" => "/uploads/promocao4.webp",
                            "share_h5" => "",
                            "display_mode" => 1,
                            "link_mode" => 1,
                            "link_url" => "web_login"
                        ],
                        "id" => "231401050555112237",
                        "title" => "Red envelope",
                        "state" => 2,
                        "flag" => "red_packet",
                        "grade" => "2,2000,1001,1002,1003,1004,1006",
                        "login_af" => 0,
                        "login_bf" => 0,
                        "show_at" => 1731583800,
                        "show_end_at" => 1735625880,
                        "apply_terminal" => ""
                    ],
                    [
                        "static" => [
                            "list_web" => "/uploads/promocao5.webp",
                            "list_h5" => "/uploads/promocao5.webp",
                            "title_web" => "/uploads/promocao5.webp",
                            "title_h5" => "/uploads/promocao5.webp",
                            "display_mode" => 2,
                            "link_mode" => 1,
                            "link_url" => "web_agent"
                        ],
                        "id" => "15593891930952523",
                        "title" => "VIP",
                        "state" => 2,
                        "flag" => "static",
                        "grade" => "1009,2,1001,1002,1003,1004,1005,1006,1007",
                        "login_af" => 0,
                        "login_bf" => 0,
                        "show_at" => 1727866754,
                        "show_end_at" => 0,
                        "apply_terminal" => "1"
                    ],
                    // [
                    //     "static" => [
                    //         "list_web" => "/image/1710299499958..webp",
                    //         "list_h5" => "/image/1727866768244..webp",
                    //         "title_web" => "/image/1710299051318..webp",
                    //         "title_h5" => "/image/1727866876725..webp",
                    //         "share_h5" => "/1657197245034666.png",
                    //         "display_mode" => 1,
                    //         "link_mode" => 1,
                    //         "link_url" => "web_login"
                    //     ],
                    //     "id" => "17405392470691661",
                    //     "title" => "fundo de ajuda",
                    //     "state" => 2,
                    //     "flag" => "rescue",
                    //     "grade" => "1010,2,1001,1002,1003,1004,1005,1006,1007,1008",
                    //     "login_af" => 0,
                    //     "login_bf" => 0,
                    //     "show_at" => 1727866878,
                    //     "show_end_at" => 0,
                    //     "apply_terminal" => "1"
                    // ],
                    [
                        "static" => [
                            "list_web" => "/uploads/promocao6.webp",
                            "list_h5" => "/uploads/promocao6.webp",
                            "title_web" => "/uploads/promocao6.webp",
                            "title_h5" => "/uploads/promocao6.webp",
                            "display_mode" => 1,
                            "link_mode" => 1,
                            "link_url" => "web_login"
                        ],
                        "id" => "231401050555112235",
                        "title" => "Gratuito para recarga única",
                        "state" => 2,
                        "flag" => "single_topup",
                        "grade" => "1019,1010,1009,2,2000,1001,1002,1003,1004,1005,1006,1007,1008,2005",
                        "login_af" => 3,
                        "login_bf" => 1,
                        "show_at" => 1720717200,
                        "show_end_at" => 0,
                        "apply_terminal" => "1"
                    ],
                    [
                        "static" => [
                            "list_web" => "/uploads/promocao7.webp",
                            "list_h5" => "/uploads/promocao7.webp",
                            "title_web" => "/uploads/promocao7.webp",
                            "title_h5" => "/uploads/promocao7.webp",
                            "display_mode" => 1,
                            "link_mode" => 1,
                            "link_url" => "web_login"
                        ],
                        "id" => "189610577209407602",
                        "title" => "Aposta diária acumulada",
                        "state" => 2,
                        "flag" => "bet",
                        "grade" => "1019,1010,1009,2,1001,1002,1003,1004,1005,1006,1007,1008",
                        "login_af" => 0,
                        "login_bf" => 0,
                        "show_at" => 1727866849,
                        "show_end_at" => 0,
                        "apply_terminal" => "1"
                    ],
                ],
                "msg" => null
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Member/Notices
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/notices') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [
                    "d" => [
                        [
                            "id" => "247374610871941453",
                            "title" => "Aviso Importante da Plataforma:",
                            "content" => "Devido à atualização das políticas bancárias, alguns canais da nossa plataforma agora requerem verificação KYC:
             
             Falha na criação de pedidos: Para que o processo seja bem-sucedido, é obrigatório informar o CPF correto.
             Falha nos saques: Usuários que realizam saques via número de telefone ou e-mail também precisam fornecer o CPF correspondente.
             Para evitar problemas, pedimos que todos os usuários se certifiquem de enviar o CPF verdadeiro conforme solicitado. Agradecemos sinceramente sua colaboração e apoio!",
                            "redirect" => 2,
                            "redirect_url" => "",
                            "state" => 2,
                            "created_at" => 1731367362,
                            "created_uid" => "224241695755407081",
                            "created_name" => "superadmin",
                            "prefix" => "l37",
                            "is_pop" => 1,
                            "login_bf" => 2,
                            "login_af" => 2,
                            "is_read" => 1
                        ]
                    ],
                    "t" => 0
                ]
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Member/Player/list
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/player/list') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [
                    "d" => [
                        [
                            "id" => "1", // ADICIONE SEMPRE O PROXIMO NUMERO. EX: ID ANTERIOR 266, NESSE ID COLOCA 267
                            "music_name" => "You Spin Me Round", // NOME DA MUSICA
                            "size" => "163000", // TAMANHO DA MUSICA, COLOCA UM TAMANHO APROXIMADO EM BYTES. 367000 BYTES DA UNS 3,67MB
                            "src" => "/br-music/siteadmin_upload_music_You+Spin+Me+Round.mp3", // CAMINHO DE ONDE ESTA O ARQUIVO DA MUSICA
                            "sort" => 0,
                            "status" => 0,
                            "update_name" => "",
                            "update_at" => 0,
                            "create_at" => 0,
                            // RESTO É SÓ PRA ENCHER LINGUIÇA
                        ],
                        [
                            "id" => "2",
                            "music_name" => "Se Mordendo De Raiva",
                            "size" => "167000",
                            "src" => "/br-music/se mordendo de raiva.mp3", // CAMINHO DE ONDE ESTA O ARQUIVO DA MUSICA
                            "sort" => 0,
                            "status" => 0,
                            "update_name" => "",
                            "update_at" => 0,
                            "create_at" => 0,
                        ],
                        [
                            "id" => "3", // ID 2
                            "music_name" => "Yesterday-The Beatles",
                            "size" => "367000",
                            "src" => "/br-music/yesterday.mp3",
                            "sort" => 0,
                            "status" => 0,
                            "update_name" => "",
                            "update_at" => 0,
                            "create_at" => 0,
                        ],
                        [
                            "id" => "4", // ID 2
                            "music_name" => "See You Again-Wiz+Khalifa",
                            "size" => "362000",
                            "src" => "/br-music/seeyou.mp3",
                            "sort" => 0,
                            "status" => 0,
                            "update_name" => "",
                            "update_at" => 0,
                            "create_at" => 0,
                        ],
                        [
                            "id" => "5", // ID 2
                            "music_name" => "Without You-Mariah Carey",
                            "size" => "92000",
                            "src" => "/br-music/mariah.mp3",
                            "sort" => 0,
                            "status" => 0,
                            "update_name" => "",
                            "update_at" => 0,
                            "create_at" => 0,
                        ],
                        [
                            "id" => "6", // ID 2
                            "music_name" => "Live It Up",
                            "size" => "317000",
                            "src" => "/br-music/live.mp3",
                            "sort" => 0,
                            "status" => 0,
                            "update_name" => "",
                            "update_at" => 0,
                            "create_at" => 0,
                        ],
                        [
                            "id" => "7", // ID 2
                            "music_name" => "Waiting for Love",
                            "size" => "351000",
                            "src" => "/br-music/love.mp3",
                            "sort" => 0,
                            "status" => 0,
                            "update_name" => "",
                            "update_at" => 0,
                            "create_at" => 0,
                        ],
                        [
                            "id" => "8", // ID 2
                            "music_name" => "Wait Wait Wait",
                            "size" => "321000",
                            "src" => "/br-music/wait.mp3",
                            "sort" => 0,
                            "status" => 0,
                            "update_name" => "",
                            "update_at" => 0,
                            "create_at" => 0,
                        ],
                        [
                            "id" => "9", // ID 2
                            "music_name" => "Victory-anonymous",
                            "size" => "495000",
                            "src" => "/br-music/victory.mp3",
                            "sort" => 0,
                            "status" => 0,
                            "update_name" => "",
                            "update_at" => 0,
                            "create_at" => 0,
                        ],
                        [
                            "id" => "10", // ID 2
                            "music_name" => "The Nights(Remix)",
                            "size" => "295000",
                            "src" => "/br-music/remix.mp3",
                            "sort" => 0,
                            "status" => 0,
                            "update_name" => "",
                            "update_at" => 0,
                            "create_at" => 0,
                        ],
                    ],
                    "t" => 8,
                    "config" => [
                        "player_switch" => 1,
                        "player_autoplay" => 0,
                    ],
                ],
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Member/Marquee
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/marquee') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [],
                "msg" => null,
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota App/Upgrade
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/app/upgrade') {
            // Captura a query string
            $queryString = parse_url($requestURI, PHP_URL_QUERY);
            parse_str($queryString, $queryParams);

            // Verifica se a query string contém a chave 'dv'
            if (isset($queryParams['dv']) && $queryParams['dv'] == 35) {
                $rotaEncontrada = true; // Rota encontrada

                // Aqui você pode definir a resposta como antes
                $response = [
                    "status" => true,
                    "data" => [
                        "id" => "",
                        "platform" => "android",
                        "version" => $dataconfig['versao_app_android'],
                        "is_force" => 0,
                        "content" => "
                 1 Otimização de campanha
                 ",
                        "url" => $dataconfig['link_app_android'],
                        "updated_at" => 0,
                        "updated_uid" => "",
                        "updated_name" => "",
                        "prefix" => "",
                        "model_type" => 0,
                    ],
                ];

                // Use JSON_UNESCAPED_UNICODE to avoid escaping Unicode characters
                $response_json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                echo $response_json;
            }
            // Verifica se a query string contém a chave 'dv'
            if (isset($queryParams['dv']) && $queryParams['dv'] == 36) {
                $rotaEncontrada = true; // Rota encontrada

                // Aqui você pode definir a resposta como antes
                $response = [
                    "status" => true,
                    "data" => [
                        "id" => "",
                        "platform" => "ios",
                        "version" => $dataconfig['versao_app_ios'],
                        "is_force" => 0,
                        "content" => "
                    1 Otimização de campanha
                    ",
                        "url" => $dataconfig['link_app_ios'],
                        "updated_at" => 0,
                        "updated_uid" => "",
                        "updated_name" => "",
                        "prefix" => "",
                        "model_type" => 0,
                    ],
                ];

                // Use JSON_UNESCAPED_UNICODE to avoid escaping Unicode characters
                $response_json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                echo $response_json;
            }
        }
        // Rota Member/Point/Statistics
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/point/statistics') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [
                    "facebook" => "",
                    "kwai" => "",
                    "tiktok" => "",
                    "google" => "",
                ],
                "msg" => null,
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Member/Rebate/Config
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/rebate/config') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [
                    [
                        "id" => 3117973535451604,
                        "game_type" => 3,
                        "bet_amount" => 0,
                        "rebate_amount" => 0.1,
                        "ty" => 1,
                    ],
                    [
                        "id" => 3118183146219625,
                        "game_type" => 3,
                        "bet_amount" => 1,
                        "rebate_amount" => 0.15,
                        "ty" => 1,
                    ],
                    [
                        "id" => 3118402711650122,
                        "game_type" => 3,
                        "bet_amount" => 5,
                        "rebate_amount" => 0.18,
                        "ty" => 1,
                    ],
                    [
                        "id" => 3118603221577640,
                        "game_type" => 3,
                        "bet_amount" => 10,
                        "rebate_amount" => 0.2,
                        "ty" => 1,
                    ],
                    [
                        "id" => 3118811288439380,
                        "game_type" => 3,
                        "bet_amount" => 50,
                        "rebate_amount" => 0.3,
                        "ty" => 1,
                    ],
                    [
                        "id" => 3119007217308780,
                        "game_type" => 3,
                        "bet_amount" => 100,
                        "rebate_amount" => 0.4,
                        "ty" => 1,
                    ],
                    [
                        "id" => 3119159847745440,
                        "game_type" => 3,
                        "bet_amount" => 200,
                        "rebate_amount" => 0.6,
                        "ty" => 1,
                    ],
                    [
                        "id" => 3119305445801466,
                        "game_type" => 3,
                        "bet_amount" => 500,
                        "rebate_amount" => 1,
                        "ty" => 1,
                    ],
                    [
                        "id" => 3119507707122527,
                        "game_type" => 3,
                        "bet_amount" => 1000,
                        "rebate_amount" => 1.5,
                        "ty" => 1,
                    ],
                ],
                "msg" => null,
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Member/Info & Member/Short/Info
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/info' || parse_url($requestURI, PHP_URL_PATH) === '/api/member/short/info') {
            $rotaEncontrada = true; // Rota encontrada
            // Verifica se o cookie 'token_user' está definido e não está vazio
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $token = mysqli_real_escape_string($mysqli, $_COOKIE['token_user']);
                $qry = "SELECT * FROM usuarios WHERE token='$token'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datres = mysqli_fetch_assoc($resp);

                    // Calculando o total de bet
                    $qry = "SELECT SUM(bet_money) as total_bets FROM historico_play WHERE id_user=?";
                    $stmt = $mysqli->prepare($qry);
                    $stmt->bind_param("i", $datres['id']);
                    $stmt->execute();
                    $resultado = $stmt->get_result();
                    $row = $resultado->fetch_assoc();
                    $total_bets = ($row['total_bets'] > 0) ? $row['total_bets'] : 0;

                    $bonusvip = 0; // inicializar com 0
                    $recompensa_vip = $datres['recompensa_vip'];

                    //var_dump($total_bets);

                    if ($total_bets >= 1000000000) {
                        $bonusvip = 6515.00;
                    } elseif ($total_bets >= 100000000) {
                        $bonusvip = 3000.00;
                    } elseif ($total_bets >= 20000000) {
                        $bonusvip = 1500.00;
                    } elseif ($total_bets >= 10000000) {
                        $bonusvip = 1250.00;
                    } elseif ($total_bets >= 7000000) {
                        $bonusvip = 800.00;
                    } elseif ($total_bets >= 5000000) {
                        $bonusvip = 600.00;
                    } elseif ($total_bets >= 3000000) {
                        $bonusvip = 400.00;
                    } elseif ($total_bets >= 1000000) {
                        $bonusvip = 200.00;
                    } elseif ($total_bets >= 600000) {
                        $bonusvip = 100.00;
                    } elseif ($total_bets >= 300000) {
                        $bonusvip = 50.00;
                    } elseif ($total_bets >= 100000) {
                        $bonusvip = 20.00;
                    } elseif ($total_bets >= 30000) {
                        $bonusvip = 10.00;
                    } elseif ($total_bets >= 10000) {
                        $bonusvip = 5.00;
                    }


                    if ($bonusvip > 0 && $recompensa_vip != $bonusvip) {
                        $saldo_novo = $datres['saldo'] + $bonusvip;
                        $nivel_vip_novo = $datres['vip'] + 1;
                        $total_recompensas_vip = $datres['total_recompensa_vip'] + $bonusvip;
                        $atualizarsaldoqry = $mysqli->prepare("UPDATE usuarios SET saldo = ?, vip = ?, recompensa_vip = ?, total_recompensa_vip = ? WHERE id = ?");
                        $atualizarsaldoqry->bind_param("diddi", $saldo_novo, $nivel_vip_novo, $bonusvip, $total_recompensas_vip, $datres["id"]);
                        $atualizarsaldoqry->execute();
                    }

                    $proxvip = $datres['vip'] + 1;

                    $valor_vip_novo = 0; // iniciz o valor como 0

                    switch ($datres['vip']) {
                        case 0:
                            $valor_vip_novo = 10000;
                            break;
                        case 1:
                            $valor_vip_novo = 30000;
                            break;
                        case 2:
                            $valor_vip_novo = 100000;
                            break;
                        case 3:
                            $valor_vip_novo = 300000;
                            break;
                        case 4:
                            $valor_vip_novo = 600000;
                            break;
                        case 5:
                            $valor_vip_novo = 1000000;
                            break;
                        case 6:
                            $valor_vip_novo = 3000000;
                            break;
                        case 7:
                            $valor_vip_novo = 5000000;
                            break;
                        case 8:
                            $valor_vip_novo = 7000000;
                            break;
                        case 9:
                            $valor_vip_novo = 10000000;
                            break;
                        case 10:
                            $valor_vip_novo = 13000000;
                            break;
                        case 11:
                            $valor_vip_novo = 16000000;
                            break;
                        case 12:
                            $valor_vip_novo = 20000000;
                            break;
                        case 13:
                            $valor_vip_novo = 100000000;
                            break;
                        case 14:
                            $valor_vip_novo = 1000000000;
                            break;
                        case 15:
                            $valor_vip_novo = 1000000000;
                            break;
                    }

                    $userData = array(
                        "status" => true,
                        "data" => [
                            "uid" => $datres['id'],
                            "username" => $datres['mobile'],
                            "password" => "0",
                            "birth" => $datres['data_nascimento'],
                            "realname" => "",
                            "email" => $datres['email'],
                            "phone" => $datres['celular'],
                            "zalo" => "",
                            "prefix" => "f51",
                            "tester" => "1",
                            "withdraw_pwd" => $datres['senha_saque'],
                            "regip" => "2804:15fc:1013:7601:d9b5:26b6:ded1:f82a",
                            "reg_device" => "xbwrlskpkz4b67ygeadbpj08hkssujif",
                            "reg_url" => "/?id=205158614",
                            "created_at" => 1725613453,
                            "last_login_ip" => "2804:15fc:1013:7601:d9b5:26b6:ded1:f82a",
                            "last_login_at" => 1725618194,
                            "source_id" => 1,
                            "first_deposit_at" => 0,
                            "first_deposit_amount" => "0.000",
                            "first_bet_at" => 0,
                            "first_bet_amount" => "0.000",
                            "second_deposit_at" => 0,
                            "second_deposit_amount" => "0.000",
                            "top_uid" => "174474690",
                            "top_name" => "xingchencaowin",
                            "parent_uid" => "205158614",
                            "parent_name" => "marciotb",
                            "bankcard_total" => 0,
                            "last_login_device" => "y4670ybncamy9l2mf59zvzlgme50cwaj",
                            "last_login_source" => 24,
                            "remarks" => "",
                            "state" => 1,
                            "level" => $datres['vip'],
                            "balance" => "0.0000",
                            "lock_amount" => "0.0000",
                            "commission" => "0.0000",
                            "group_name" => "xingchencaowin",
                            "agency_type" => 391,
                            "address" => "",
                            "avatar" => $datres['avatar'],
                            "last_withdraw_at" => "0",
                            "automatic" => 1,
                            "facebook" => $datres['facebook'],
                            "whatsapp" => $datres['whatsapp'],
                            "telegram" => $datres['telegram'],
                            "twitter" => $datres['twitter'],
                            "referer" => "",
                            "link_id" => "",
                            "device" => 0,
                            "fphone" => "",
                            "total_dept_amount" => "10.000",
                            "total_wdraw_amount" => "20.000",
                            "link_black_list" => 0,
                            "next_bet_requirement" => $valor_vip_novo, // campo do proximo valor do vip
                            "current_bet" => $total_bets,
                            "rate" => "30.00000",
                            "next_level" => $proxvip,
                            "rebate_amount" => "",
                            "agency_amount" => "",
                            "token" => $datres['token'],
                        ],
                        "msg" => null,
                    );

                    // Converte o array associativo para JSON
                    echo json_encode($userData);
                } else {
                    $response = [
                        "code" => 0, // Indica falha
                        "msg" => "Usuário sem efetuar login", // Mensagem de erro
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "msg" => "Token não encontrado ou inválido",
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota Member/Message/num
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/message/num') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => 0,
                "msg" => null,
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Member/Password/Check
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/password/check') {
            $rotaEncontrada = true; // Rota encontrada
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {

                $qry = "SELECT * FROM usuarios WHERE token=?";
                $stmt = $mysqli->prepare($qry);
                $stmt->bind_param("s", $_COOKIE['token_user']);
                $stmt->execute();
                $resp = $stmt->get_result();

                if ($resp->num_rows > 0) {
                    $datares = $resp->fetch_assoc();

                    // Verificação do campo senha_saque
                    if (isset($datares['senha_saque'])) {
                        if ($datares['senha_saque'] == 1) {
                            $response = [
                                "status" => true,
                                "data" => "1000",
                                "msg" => null,
                            ];
                        } else {
                            $response = [
                                "status" => true,
                                "data" => "1249",
                                "msg" => null,
                            ];
                        }
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response = [
                        "code" => 0, // Falha
                        "msg" => "Usuário sem efetuar login",
                        "time" => time(),
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "msg" => "Usuário ou senha incorretos",
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota Member/Link/list
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/link/list') {
            $rotaEncontrada = true; // Rota encontrada
            // Verifica se o cookie 'token_user' está definido e não está vazio
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $token = mysqli_real_escape_string($mysqli, $_COOKIE['token_user']);
                $qry = "SELECT * FROM usuarios WHERE token='$token'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datres = mysqli_fetch_assoc($resp);
                    $userData = [
                        "status" => true,
                        "data" => [
                            [
                                "id" => $datres["id"],
                                "uid" => $datres["id"],
                                "username" => $datres["mobile"],
                                "short_url" => "id=" . $datres["invite_code"],
                                "code" => $datres["invite_code"],
                                "prefix" => "",
                                "created_at" => "",
                                "tester" => "",
                            ],
                        ],
                        "msg" => null,
                    ];

                    // Converte o array associativo para JSON
                    echo json_encode($userData);
                } else {
                    $response = [
                        "code" => 0, // Indica falha
                        "msg" => "Usuário sem efetuar login", // Mensagem de erro
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "msg" => "Token não encontrado ou inválido",
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota Promo/Promo/Wait/Pick
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/promo/promo/wait/pick') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [
                    "d" => null,
                    "agg" => "0",
                ],
                "msg" => null,
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Member/Point/Statistics/Deposit
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/point/statistics/deposit') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [],
                "msg" => null,
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Finance/Channel/type
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/finance/channel/type') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [
                    // [
                    //     "id" => "18",
                    //     "name" => "Moeda digital",
                    //     "alias" => "Moeda digital",
                    //     "state" => 0,
                    //     "sort" => 5,
                    //     "flow_multiple" => 1
                    // ],
                    [
                        "id" => "8",
                        "name" => "PIX",
                        "alias" => "PIX",
                        "state" => 1,
                        "sort" => 100,
                        "flow_multiple" => 1
                    ]
                ]
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Finance/Channel/list
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/finance/channel/list') {
            $rotaEncontrada = true; // Rota encontrada
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $token = mysqli_real_escape_string($mysqli, $_COOKIE['token_user']);
                $qry = "SELECT * FROM usuarios WHERE token='$token'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datres = mysqli_fetch_assoc($resp);
                    $qry = "SELECT SUM(valor) as total_depositos FROM transacoes WHERE usuario=? AND tipo='deposito' AND status='pago'";
                    $stmt = $mysqli->prepare($qry);
                    $stmt->bind_param("i", $datres['id']);
                    $stmt->execute();
                    $resultado = $stmt->get_result();
                    $row = $resultado->fetch_assoc();


                    $total_depositos = ($row['total_depositos'] > 0) ? $row['total_depositos'] : 0;


                    $bonus_list = [];
                    $mindep = isset($dataconfig['mindep']) ? explode(',', $dataconfig['mindep']) : [];

                    if ($total_depositos === 0) {
                        foreach ($mindep as $deposit_amount) {
                            if (!is_numeric($deposit_amount) || $deposit_amount <= 0) {
                                continue;
                            }

                            $bonus_list[] = [
                                "deposit_amount" => (int) $deposit_amount,
                                "bonus_amount" => round($deposit_amount * ($dataconfig['porcentage_bonus_deposit'] / 100), 2),
                                "rate" => 0,
                                "levels" => "",
                                "bonus_type" => 0
                            ];
                        }
                    } else {
                        foreach ($mindep as $deposit_amount) {
                            if (!is_numeric($deposit_amount) || $deposit_amount <= 0) {
                                continue;
                            }

                            $bonus_list[] = [
                                "deposit_amount" => (int) $deposit_amount,
                                "bonus_amount" => round($deposit_amount * ($dataconfig['porcentage_bonus_deposit'] / 100), 2),
                                "rate" => 0,
                                "levels" => "",
                                "bonus_type" => 1
                            ];
                        }
                    }

                    $tabelas = ['edbanking', 'syncpay'];
                    $pix_list = [];
                    $pix_count = 0;

                    foreach ($tabelas as $tabela) {
                        $query = "SELECT * FROM {$tabela} WHERE ativo = 1";
                        $result = mysqli_query($mysqli, $query);

                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $pix_count++;
                                $pix_list[] = [
                                    "show_name" => "PIX" . ($pix_count > 0 ? $pix_count : ""),
                                    "third_code" => $tabela,
                                    "comment" => $tabela . '代收',
                                    "bank_name" => $tabela,
                                    "factory_id" => $tabela,
                                ];
                            }
                        }
                    }

                    $base_data = [
                        "daily_max_amount" => 999999,
                        "flag" => 1,
                        "crowd" => 0,
                        "transfer_note_type" => 0,
                        "account_holder" => "",
                        "bonus_list2" => [],
                        "channel_type_id" => "8",
                        "fmax" => 50000,
                        "updated_at" => 1732837897,
                        "h5_img" => "https://dl-br-cf.sadslj88.com/image-prod/bximages/h5/home/icons/deposit_pix.webp",
                        "protocol" => "",
                        "grade_list" => "2025,1009,48,47,46,44,43,42,41,40,39,38,37,36,35,34,1001,1002,1003,1004,1005,1006,1007,1008",
                        "currency" => "BRL",
                        "bonus_list" => $bonus_list,
                        "bonus_list1" => $bonus_list,
                        "list" => '[{"flag": 1, "rate": "0"}]',
                        "id" => "10090",
                        "created_at" => 1727847776,
                        "is_zone" => 1,
                        "app_img" => "https://dl-br-cf.sadslj88.com/image-prod/bximages/h5/home/icons/deposit_pix.webp",
                        "receipt_address" => "",
                        "fmin" => $mindep[0],
                        "web_img" => "https://dl-br-cf.sadslj88.com/image-prod/bximages/h5/home/icons/deposit_pix.webp",
                        "balance" => "",
                        "account_number" => "",
                        "factory_name" => "",
                        "deposit" => 0,
                        "amount_list" => "10,30,50,100,500,1000,3000,5000,10000,20000,50000",
                        "state" => "1",
                        "sort" => 1,
                        "discount" => "0.00",
                        "offline_flag" => 0,
                        "is_verify_member" => 0,
                        "vip_list" => "1,2,3,4,5,6,7,8,9,10",
                        "is_fast" => 1,
                        "is_rang" => 2,
                        "daily_finish_amount" => 0,
                        "operator" => ""
                    ];
                    $response = [
                        "status" => true,
                        "data" => []
                    ];

                    foreach ($pix_list as $pix) {
                        $data_item = $base_data;
                        $data_item["show_name"] = $pix["show_name"];
                        $data_item["third_code"] = $pix["third_code"];
                        $data_item["third_id"] = $pix["third_code"];
                        $data_item["comment"] = $pix["comment"];
                        $data_item["bank_name"] = $pix["bank_name"];
                        $data_item["factory_id"] = $pix["factory_id"];
                        $data_item["id"] = $pix["factory_id"];

                        $response["data"][] = $data_item;
                    }

                    if (empty($response["data"])) {
                        $base_data["show_name"] = "PIX";
                        $base_data["third_code"] = "";
                        $response["data"][] = $base_data;
                    }

                    $response_json = json_encode($response, JSON_PRETTY_PRINT);
                    echo $response_json;
                } else {
                    $response = [
                        "status" => false,
                        "data" => "1003",
                        "msg" => null,
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "msg" => "Token não encontrado ou inválido",
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota Member/Record/Trade
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/record/trade') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                // Supondo que a variável $mysqli é a conexão com o banco de dados já existente

                // Obter o token do usuário
                $token_user = $_COOKIE['token_user'];

                // Captura a query string
                $queryString = parse_url($requestURI, PHP_URL_QUERY);
                parse_str($queryString, $queryParams);

                // Verifica se a query string contém a chave 'dv'
                if (isset($queryParams['flag']) && $queryParams['flag'] == 272) {
                    // Consultar o banco de dados para obter o ID do usuário associado ao token
                    $qry = "SELECT * FROM usuarios WHERE token=?";
                    $stmt = $mysqli->prepare($qry);
                    $stmt->bind_param("s", $token_user);
                    $stmt->execute();
                    $result_user = $stmt->get_result();

                    if ($result_user->num_rows > 0) {
                        $user = $result_user->fetch_assoc();
                        $user_id = $user['id'];

                        // Consultar os saques do usuário específico
                        $sql = "SELECT * FROM solicitacao_saques WHERE id_user = ?";
                        $stmt = $mysqli->prepare($sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result) {
                            if ($result->num_rows > 0) {
                                // Armazenar os dados dos saques em um array
                                $saques = [];
                                while ($row = $result->fetch_assoc()) {
                                    // Determinar o valor de "state" com base no "status"
                                    $state = 0;
                                    if ($row['status'] === '0') {
                                        $state = 361;
                                    } elseif ($row['status'] === '1') {
                                        $state = 374;
                                    }

                                    $chaveph = localizarchavepix($row['pix']);

                                    // Filtrar os dados retornados para incluir apenas os campos desejados
                                    $saques[] = [
                                        "flag" => 271,
                                        "id" => $row['id'], // Substitua com o campo correspondente
                                        "ty" => 1,
                                        // Exibir apenas os primeiros 12 caracteres do transacao_id
                                        "bill_no" => substr($row['transacao_id'], 0, 12),
                                        "platform_id" => "",
                                        "transfer_type" => 1,
                                        "amount" => $row['valor'], // Substitua com o campo correspondente
                                        "created_at" => $row['data_cad'], // Substitua com o campo correspondente
                                        "state" => $state, // Estado com base no status
                                        "remark" => $chaveph, // Substitua com o campo correspondente
                                        "ptitle" => "",
                                        "username" => $user['mobile'], // Substitua com o campo correspondente
                                        "parent_name" => $user['invitation_code'] || 'phillypsdev', // Substitua com o campo correspondente
                                        "balance" => "",
                                        "channel_id" => "8",
                                        "channel_name" => "PIX",
                                        "pay_name" => "PIX7",
                                        "real_name" => $user['mobile'], // Substitua com o campo correspondente
                                        "account" => "",
                                        "updated_at" => 0,
                                        "ramount" => "",
                                        "discount" => "",
                                        "bank_ty" => 0,
                                        "channel_type_name" => "PIX",
                                    ];
                                }

                                // Preparar a resposta com os dados coletados e o número total de linhas
                                $response = [
                                    "status" => true,
                                    "data" => [
                                        "t" => $result->num_rows, // Número total de rows
                                        "d" => $saques,
                                    ],
                                    "s" => 0,
                                    "agg" => null,
                                    "msg" => null,
                                ];
                            } else {
                                $response = [
                                    "status" => true,
                                    "data" => [
                                        "t" => 0, // Sem transações
                                        "d" => [],
                                    ],
                                    "s" => 0,
                                    "agg" => null,
                                    "msg" => null,
                                ];
                            }
                        } else {
                            // Erro na execução da consulta
                            $response = [
                                "status" => false,
                                "message" => "Erro ao consultar a tabela de transações.",
                            ];
                        }
                    } else {
                        // Usuário não encontrado para o token fornecido
                        $response = [
                            "status" => false,
                            "message" => "Usuário não encontrado para o token fornecido.",
                        ];
                    }

                    $stmt->close();
                    // Exibir a resposta em formato JSON
                    $response_json = json_encode($response, JSON_PRETTY_PRINT);
                    echo $response_json;
                    exit;
                }
                if (isset($queryParams['ty']) && $queryParams['ty'] == 0) {
                    // Consultar o banco de dados para obter o ID do usuário associado ao token
                    $qry = "SELECT * FROM usuarios WHERE token=?";
                    $stmt = $mysqli->prepare($qry);
                    $stmt->bind_param("s", $token_user);
                    $stmt->execute();
                    $result_user = $stmt->get_result();

                    if ($result_user->num_rows > 0) {
                        $user = $result_user->fetch_assoc();
                        $user_id = $user['id'];

                        // Consultar os saques do usuário específico
                        $sql = "SELECT * FROM transacoes WHERE usuario = ?";
                        $stmt = $mysqli->prepare($sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result) {
                            if ($result->num_rows > 0) {
                                // Armazenar os dados dos saques em um array
                                $saques = [];
                                while ($row = $result->fetch_assoc()) {
                                    // Determinar o valor de "state" com base no "status"
                                    $state = 0;
                                    if ($row['status'] === 'processamento') {
                                        $state = 361;
                                    } elseif ($row['status'] === 'pago') {
                                        $state = 362;
                                    }

                                    // Filtrar os dados retornados para incluir apenas os campos desejados
                                    $saques[] = [
                                        "flag" => 271,
                                        "id" => $row['id'], // Substitua com o campo correspondente
                                        "ty" => 1,
                                        // Exibir apenas os primeiros 12 caracteres do transacao_id
                                        "bill_no" => substr($row['transacao_id'], 0, 12),
                                        "platform_id" => "",
                                        "transfer_type" => 1,
                                        "amount" => $row['valor'], // Substitua com o campo correspondente
                                        "created_at" => $row['data_hora'], // Substitua com o campo correspondente
                                        "state" => $state, // Estado com base no status
                                        "remark" => $row['tipo'], // Substitua com o campo correspondente
                                        "ptitle" => "",
                                        "username" => $user['mobile'], // Substitua com o campo correspondente
                                        "parent_name" => $user['invitation_code'] || 'phillypsdev', // Substitua com o campo correspondente
                                        "balance" => "",
                                        "channel_id" => "8",
                                        "channel_name" => "PIX",
                                        "pay_name" => "PIX7",
                                        "real_name" => $user['mobile'], // Substitua com o campo correspondente
                                        "account" => "",
                                        "updated_at" => 0,
                                        "ramount" => "",
                                        "discount" => "",
                                        "bank_ty" => 0,
                                        "channel_type_name" => "PIX",
                                    ];
                                }

                                // Preparar a resposta com os dados coletados e o número total de linhas
                                $response = [
                                    "status" => true,
                                    "data" => [
                                        "t" => $result->num_rows, // Número total de rows
                                        "d" => $saques,
                                    ],
                                    "s" => 0,
                                    "agg" => null,
                                    "msg" => null,
                                ];
                            } else {
                                $response = [
                                    "status" => true,
                                    "data" => [
                                        "t" => 0, // Sem transações
                                        "d" => [],
                                    ],
                                    "s" => 0,
                                    "agg" => null,
                                    "msg" => null,
                                ];
                            }
                        } else {
                            // Erro na execução da consulta
                            $response = [
                                "status" => false,
                                "message" => "Erro ao consultar a tabela de transações.",
                            ];
                        }
                    } else {
                        // Usuário não encontrado para o token fornecido
                        $response = [
                            "status" => false,
                            "message" => "Usuário não encontrado para o token fornecido.",
                        ];
                    }

                    $stmt->close();
                    // Exibir a resposta em formato JSON
                    $response_json = json_encode($response, JSON_PRETTY_PRINT);
                    echo $response_json;
                    exit;
                }
            } else {
                // Token do usuário não está presente ou é vazio
                $response = [
                    "status" => false,
                    "message" => "Token de usuário inválido ou ausente.",
                ];
                $response_json = json_encode($response, JSON_PRETTY_PRINT);
                echo $response_json;
            }
        }
        // Rota Member/Record/Trade/Detail
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/record/trade/detail') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                // Supondo que a variável $mysqli é a conexão com o banco de dados já existente

                // Obter o token do usuário
                $token_user = $_COOKIE['token_user'];


                $saque_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

                if ($saque_id <= 0) {
                    $response = [
                        "status" => false,
                        "message" => "ID do saque não fornecido ou inválido.",
                    ];
                    echo json_encode($response, JSON_PRETTY_PRINT);
                    exit;
                }

                // Consultar o banco de dados para obter o ID do usuário associado ao token
                $qry = "SELECT * FROM usuarios WHERE token=?";
                $stmt = $mysqli->prepare($qry);
                $stmt->bind_param("s", $token_user);
                $stmt->execute();
                $result_user = $stmt->get_result();

                if ($result_user->num_rows > 0) {
                    $user = $result_user->fetch_assoc();
                    $user_id = $user['id'];

                    // Consultar o saque específico pelo ID e verificar se pertence ao usuário
                    $sql = "SELECT * FROM solicitacao_saques WHERE id = ? AND id_user = ?";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param("ii", $saque_id, $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result) {
                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();

                            // Determinar o valor de "state" com base no "status"
                            $state = 0;
                            if ($row['status'] === 0) {
                                $state = 371;
                            } elseif ($row['status'] === 1) {
                                $state = 374;
                            }

                            $chaveph = localizarchavepix($row['pix']);

                            // Dados do saque específico
                            $saque = [
                                "flag" => 271,
                                "id" => $row['id'],
                                "ty" => 1,
                                "bill_no" => substr($row['transacao_id'], 0, 12),
                                "platform_id" => "",
                                "transfer_type" => 1,
                                "amount" => $row['valor'],
                                "created_at" => $row['data_cad'],
                                "state" => $state,
                                "remark" => $chaveph,
                                "ptitle" => "",
                                "username" => $user['mobile'],
                                "parent_name" => $user['invitation_code'] ?: 'phillypsdev',
                                "balance" => "",
                                "channel_id" => "8",
                                "channel_name" => "PIX",
                                "pay_name" => "PIX7",
                                "real_name" => $user['mobile'],
                                "account" => "",
                                "updated_at" => 0,
                                "ramount" => "",
                                "discount" => "",
                                "bank_ty" => 0,
                                "channel_type_name" => "PIX",
                            ];

                            // Preparar a resposta com os dados do saque
                            $response = [
                                "status" => true,
                                "data" => [
                                    "t" => $result->num_rows, // Número total de rows
                                    "d" => [$saque],
                                ],
                                "msg" => null,
                            ];
                        } else {
                            $response = [
                                "status" => false,
                                "message" => "Saque não encontrado ou não pertence ao usuário.",
                            ];
                        }
                    } else {
                        // Erro na execução da consulta
                        $response = [
                            "status" => false,
                            "message" => "Erro ao consultar a tabela de saques.",
                        ];
                    }
                } else {
                    // Usuário não encontrado para o token fornecido
                    $response = [
                        "status" => false,
                        "message" => "Usuário não encontrado para o token fornecido.",
                    ];
                }

                $stmt->close();
                // Exibir a resposta em formato JSON
                echo json_encode($response, JSON_PRETTY_PRINT);
            } else {
                // Token do usuário não está presente ou é vazio
                $response = [
                    "status" => false,
                    "message" => "Token de usuário inválido ou ausente.",
                ];
                echo json_encode($response, JSON_PRETTY_PRINT);
            }
        }
        // Rota Member/Bankcard/Pixtypelist
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/bankcard/withdrawmethods') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [
                    [
                        "displayName" => "CPF", // 
                        "ty" => 3,
                        "enable" => true, // DESATIVAR E ATIVAR METODO PIX CPF
                        "num" => 1,
                    ],
                    [
                        "displayName" => "PNONE",
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
                ],
                "msg" => null,
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Finance/Withdraw/Processing
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/finance/withdraw/processing') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [
                    "id" => "",
                    "bid" => "",
                    "amount" => "",
                    "ramount" => "",
                    "state" => "",
                    "created_at" => "",
                    "min_amount" => $dataconfig['minsaque'],
                    "max_amount" => $dataconfig['maxsaque'],
                ],
                "msg" => null,
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Member/bankcard/list
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/bankcard/list') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                // Consulta para obter informações do usuário com base no token
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);

                    // Consulta as contas de pagamento do usuário na tabela 'metodos_pagamentos'
                    $paymentQuery = "SELECT * FROM metodos_pagamentos WHERE user_id='" . $datares['id'] . "'";
                    $paymentResult = mysqli_query($mysqli, $paymentQuery);

                    if (mysqli_num_rows($paymentResult) > 0) {
                        $paymentMethods = [];
                        while ($row = mysqli_fetch_assoc($paymentResult)) {
                            $paymentMethods[] = [
                                "id" => $row['id'],
                                "uid" => $datares['id'], // Use o id do usuário para 'uid'
                                "username" => $datares['mobile'],
                                "bank_card" => $row['pix_id'],
                                "created_at" => (int) $row['created_at'], // Certifique-se de que 'created_at' seja retornado como inteiro
                                "state" => (int) $row['state'], // 'state' também como inteiro
                                "updated_at" => (int) $row['created_at'], // 'updated_at' usa o mesmo valor de 'created_at'
                                "realname" => $row['realname'],
                                "content" => $row['pix_account'],
                                "ty" => (int) $row['flag'], // Valor fixo 'ty' como 3
                            ];
                        }

                        // Prepara a resposta no formato JSON solicitado
                        $response = [
                            "status" => true,
                            "data" => $paymentMethods, // Removido array extra para retornar corretamente a lista de métodos de pagamento
                            "msg" => null,
                        ];
                    } else {
                        // Se não houver métodos de pagamento encontrados
                        $response = [
                            "status" => true,
                            "data" => [],
                            "msg" => null,
                        ];
                    }
                } else {
                    // Caso o usuário não seja encontrado no banco de dados
                    $response = [
                        "status" => false,
                        "msg" => "Usuário não encontrado",
                    ];
                }
            } else {
                // Caso o token do usuário não esteja presente ou esteja vazio
                $response = [
                    "status" => false,
                    "msg" => "Usuário ou senha incorretos",
                    "time" => time(),
                ];
            }

            // Envia a resposta em JSON
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Finance/Withdraw/Fee
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/finance/withdraw/fee') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [
                    [
                        "id" => "",
                        "tag_id" => "",
                        "fmin" => 1,
                        "fmax" => 20,
                        "amount" => 0,
                        "flags" => 1,
                        "updated_name" => "",
                        "updated_at" => 0,
                    ],
                ],
                "msg" => null,
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Member/Record/Game
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/record/game') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                // Consulta para obter informações do usuário com base no token
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);

                    // Consulta as contas de pagamento do usuário na tabela 'metodos_pagamentos'
                    $paymentQuery = "SELECT * FROM historico_play WHERE id_user='" . $datares['id'] . "'";
                    $paymentResult = mysqli_query($mysqli, $paymentQuery);

                    if (mysqli_num_rows($paymentResult) > 0) {
                        $paymentMethods = [];
                        while ($row = mysqli_fetch_assoc($paymentResult)) {
                            $paymentMethods[] = [
                                "bill_no" => $row['txn_id'],
                                "api_type" => $row['txn_id'], // Use o id do usuário para 'uid'
                                "api_types" => $row['txn_id'],
                                "player_name" => $datares['mobile'],
                                "name" => $datares['mobile'], // Certifique-se de que 'created_at' seja retornado como inteiro
                                "net_amount" => (int) $row['bet_money'], // 'state' também como inteiro
                                "bet_time" => $row['created_at'], // 'updated_at' usa o mesmo valor de 'created_at'
                                "game_type" => "3",
                                "bet_amount" => $row['bet_money'],
                                "valid_bet_amount" => (int) $row['bet_money'], // Valor fixo 'ty' como 3
                                "flag" => 1, // Valor fixo 'ty' como 3
                                "play_type" => $row['nome_game'], // Valor fixo 'ty' como 3
                                "prefix" => "f51", // Valor fixo 'ty' como 3
                                "result" => 'gameName:' . $row['nome_game'] . '|Hand number:126', // Valor fixo 'ty' como 3
                                "api_name" => 'Slots', // Valor fixo 'ty' como 3
                                "api_bill_no" => $row['txn_id'], // Valor fixo 'ty' como 3
                                "game_name" => $row['nome_game'], // Valor fixo 'ty' como 3
                            ];
                        }

                        // Prepara a resposta no formato JSON solicitado
                        $response = [
                            "status" => true,
                            "data" => [
                                "t" => count($paymentMethods),
                                "d" => $paymentMethods,
                            ], // Removido array extra para retornar corretamente a lista de métodos de pagamento
                            "msg" => null,
                        ];
                    } else {
                        // Se não houver métodos de pagamento encontrados
                        $response = [
                            "status" => true,
                            "data" => [],
                            "msg" => null,
                        ];
                    }
                } else {
                    // Caso o usuário não seja encontrado no banco de dados
                    $response = [
                        "status" => false,
                        "msg" => "Usuário não encontrado",
                    ];
                }
            } else {
                // Caso o token do usuário não esteja presente ou esteja vazio
                $response = [
                    "status" => false,
                    "msg" => "Usuário ou senha incorretos",
                    "time" => time(),
                ];
            }

            // Envia a resposta em JSON
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Phillyps/launch (Iniciar Jogo)
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/phillyps/launch/') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                // Capturar os parâmetros 'id' e 'code' usando $_REQUEST para maior flexibilidade
                $code = isset($_REQUEST['code']) ? $_REQUEST['code'] : 'null';
                $game_type = isset($_REQUEST['id']) ? $_REQUEST['id'] : 'null';

                // Verifica se ambos os parâmetros estão presentes e não estão vazios
                if (!empty($code) && !empty($game_type)) {
                    // Continua com a lógica de buscar o usuário pelo token
                    $qry = "SELECT * FROM usuarios WHERE token='" . mysqli_real_escape_string($mysqli, $_COOKIE['token_user']) . "'";
                    $resp = mysqli_query($mysqli, $qry);

                    if (mysqli_num_rows($resp) > 0) {
                        $datares = mysqli_fetch_assoc($resp);

                        // Chama a função gameprovider com base no code
                        $game_code = gamecode($code);
                        $provedor = gameprovider($game_code);
                        $type = gametype($game_code);

                        // Debug
                        //var_dump($game_code . "|". $provedor);

                        if ($type == "API30") {
                            // Debug
                            //var_dump("Pegando link jogo payigaming lixo");
                            $gameretur = pegarLinkJogoApiPhillyps('PROVEDOR', $game_code, $datares['mobile'], $datares['saldo']);
                            // Monta a resposta
                            $response = array(
                                "status" => true,
                                "data" => $gameretur['gameURL'],
                            );
                        } else if ($type == "DRAKON") {
                            // Debug
                            //var_dump("Pegando link jogo payigaming lixo");
                            $gameretur = pegarLinkDrakon('PROVEDOR', $game_code, $datares['id'], $datares['saldo']);
                            // Monta a resposta
                            $response = array(
                                "status" => true,
                                "data" => $gameretur['gameURL'],
                            );
                        } else if ($type == "ROYAL") {
                            // Debug
                            //var_dump("Pegando link jogo api de 10 de pobre");
                            $gameretur = pegarLinkJogoRoyal($provedor, $game_code, $datares['mobile'], $datares['saldo']);

                            // Monta a resposta
                            $response = array(
                                "status" => true,
                                "data" => $gameretur['gameURL'],
                            );
                        } else if ($type == "PLAYFIVER") {
                            // Debug
                            //var_dump("Pegando link jogo api de 10 de pobre");
                            $gameretur = pegarLinkJogoPlayFiver($provedor, $game_code, $datares['mobile'], $datares['saldo']);


                            // Monta a resposta
                            $response = array(
                                "status" => true,
                                "data" => $gameretur['gameURL'],
                            );
                        } else {
                            $response = array(
                                "status" => 0,
                                "msg" => "SEM API CONFIGURADA",
                            );
                        }
                    } else {
                        $response = array(
                            "status" => 0,
                            "msg" => "Usuário não logado [2]",
                        );
                    }
                } else {
                    $response = array(
                        "status" => 0,
                        "msg" => "Parâmetro 'code' ou 'id' não encontrado",
                    );
                }
            } else {
                $response = array(
                    "status" => 0,
                    "msg" => "Usuário não logado [1]",
                );
            }
            echo json_encode($response);
            exit;
        }
        // Rota Member/history/detail
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/history/detail') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                // Supondo que a variável $mysqli é a conexão com o banco de dados já existente

                // Obter o token do usuário
                $token_user = $_COOKIE['token_user'];

                // Consultar o banco de dados para obter o ID do usuário associado ao token
                $qry = "SELECT * FROM usuarios WHERE token=?";
                $stmt = $mysqli->prepare($qry);
                $stmt->bind_param("s", $token_user);
                $stmt->execute();
                $result_user = $stmt->get_result();

                if ($result_user->num_rows > 0) {
                    $user = $result_user->fetch_assoc();
                    $user_id = $user['id'];

                    // Consultar o histórico do usuário específico
                    $sql = "SELECT historico FROM usuarios WHERE id = ?";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result && $result->num_rows > 0) {
                        // Armazenar os dados do histórico em um array
                        $historico = $result->fetch_assoc()['historico'];
                        // Verifica se o histórico não é nulo
                        if ($historico) {
                            // Transforma a string de IDs em um array
                            $historico_ids = explode(',', $historico);
                            // Remove espaços em branco ao redor dos IDs
                            $historico_ids = array_map('trim', $historico_ids);

                            $gamesData = []; // Array para armazenar os dados dos jogos

                            // Iterar sobre o histórico para buscar informações dos jogos
                            foreach ($historico_ids as $gameCode) { // Supondo que $historico_array contém os códigos dos jogos
                                $gameQuery = "SELECT * FROM games WHERE id = ?"; // Substitua 'code' pelo nome da coluna que armazena o código do jogo
                                $stmt = $mysqli->prepare($gameQuery);
                                $stmt->bind_param("s", $gameCode);
                                $stmt->execute();
                                $gameResult = $stmt->get_result();

                                if ($gameResult && $gameResult->num_rows > 0) {
                                    while ($gameRow = $gameResult->fetch_assoc()) {
                                        // Adicione os dados do jogo ao array
                                        $gamesData[] = [
                                            "id" => $gameRow['id'],
                                            "platform_id" => $gameRow['id'], // Ajuste conforme os nomes das colunas
                                            "en_name" => $gameRow['game_name'],
                                            "client_type" => 0,
                                            "game_type" => 3,
                                            "game_id" => $gameRow['id'],
                                            "img" => $gameRow['banner'],
                                            "is_hot" => $gameRow['popular'],
                                            "is_new" => $gameRow['popular'],
                                            "name" => $gameRow['game_name'],
                                            "sorting" => $gameRow['id'],
                                            "vn_alias" => $gameRow['game_code'],
                                        ];
                                    }
                                }
                            }
                        }

                        // Preparar a resposta
                        $response = [
                            "status" => true,
                            "data" => [
                                "t" => $result->num_rows, // Número total de rows
                                "d" => $gamesData,
                            ],
                        ];
                        echo json_encode($response);
                    } else {
                        http_response_code(404);
                        echo json_encode(['error' => 'Histórico não encontrado']);
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Usuário não encontrado']);
                }
            } else {
                http_response_code(403);
                echo json_encode(['error' => 'Token não encontrado ou inválido']);
            }
        }
        // Rota Member/favorites/detail
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/favorites/detail') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                // Supondo que a variável $mysqli é a conexão com o banco de dados já existente

                // Obter o token do usuário
                $token_user = $_COOKIE['token_user'];

                // Consultar o banco de dados para obter o ID do usuário associado ao token
                $qry = "SELECT * FROM usuarios WHERE token=?";
                $stmt = $mysqli->prepare($qry);
                $stmt->bind_param("s", $token_user);
                $stmt->execute();
                $result_user = $stmt->get_result();

                if ($result_user->num_rows > 0) {
                    $user = $result_user->fetch_assoc();
                    $user_id = $user['id'];

                    // Consultar o histórico do usuário específico
                    $sql = "SELECT favoritos FROM usuarios WHERE id = ?";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result && $result->num_rows > 0) {
                        // Armazenar os dados do histórico em um array
                        $historico = $result->fetch_assoc()['favoritos'];
                        // Verifica se o histórico não é nulo
                        if ($historico) {
                            // Transforma a string de IDs em um array
                            $historico_ids = explode(',', $historico);
                            // Remove espaços em branco ao redor dos IDs
                            $historico_ids = array_map('trim', $historico_ids);

                            $gamesData = []; // Array para armazenar os dados dos jogos

                            // Iterar sobre o histórico para buscar informações dos jogos
                            foreach ($historico_ids as $gameCode) { // Supondo que $historico_array contém os códigos dos jogos
                                $gameQuery = "SELECT * FROM games WHERE id = ?"; // Substitua 'code' pelo nome da coluna que armazena o código do jogo
                                $stmt = $mysqli->prepare($gameQuery);
                                $stmt->bind_param("s", $gameCode);
                                $stmt->execute();
                                $gameResult = $stmt->get_result();

                                if ($gameResult && $gameResult->num_rows > 0) {
                                    while ($gameRow = $gameResult->fetch_assoc()) {
                                        // Adicione os dados do jogo ao array
                                        $gamesData[] = [
                                            "id" => $gameRow['id'],
                                            "platform_id" => $gameRow['id'], // Ajuste conforme os nomes das colunas
                                            "en_name" => $gameRow['game_name'],
                                            "client_type" => 0,
                                            "game_type" => 3,
                                            "game_id" => $gameRow['id'],
                                            "img" => $gameRow['banner'],
                                            "is_hot" => $gameRow['popular'],
                                            "is_new" => $gameRow['popular'],
                                            "name" => $gameRow['game_name'],
                                            "sorting" => $gameRow['id'],
                                            "vn_alias" => $gameRow['game_code'],
                                        ];
                                    }
                                }
                            }
                        }

                        // Preparar a resposta
                        $response = [
                            "status" => true,
                            "data" => [
                                "t" => $result->num_rows, // Número total de rows
                                "d" => $gamesData,
                            ],
                        ];
                        echo json_encode($response);
                    } else {
                        http_response_code(404);
                        echo json_encode(['error' => 'Histórico não encontrado']);
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Usuário não encontrado']);
                }
            } else {
                http_response_code(403);
                echo json_encode(['error' => 'Token não encontrado ou inválido']);
            }
        }
        // Rota Finance/Channel/type
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/promo/list/sort') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [
                    [
                        [
                            "static" => [
                                "list_web" => "/image/1730417410128..webp",
                                "list_h5" => "/image/1730417410128..webp",
                                "title_web" => "/image/redeem_title_web.png",
                                "title_h5" => "/image/redeem_title_h5.png",
                                "share_h5" => "/",
                                "display_mode" => 1,
                                "link_mode" => 2,
                                "link_url" => "/active/redeem"
                            ],
                            "id" => "231401050555112233",
                            "title" => "Resgatar atividade de código",
                            "state" => 2,
                            "flag" => "redeem_code",
                            "grade" => "2025,35,34,1001,1002,1003,1004,1005,1006,1007,1008",
                            "login_af" => 0,
                            "login_bf" => 0,
                            "show_at" => 1730757057,
                            "show_end_at" => 0,
                            "apply_terminal" => ""
                        ]
                    ],
                    [
                        [
                            "static" => [
                                "list_web" => "/image/1730417410128..webp",
                                "list_h5" => "/image/1730417410128..webp",
                                "title_web" => "/image/redeem_title_web.png",
                                "title_h5" => "/image/redeem_title_h5.png",
                                "share_h5" => "/",
                                "display_mode" => 1,
                                "link_mode" => 2,
                                "link_url" => "/active/redeem"
                            ],
                            "id" => "231401050555112233",
                            "title" => "Resgatar atividade de código",
                            "state" => 2,
                            "flag" => "redeem_code",
                            "grade" => "2025,35,34,1001,1002,1003,1004,1005,1006,1007,1008",
                            "login_af" => 0,
                            "login_bf" => 0,
                            "show_at" => 1730757057,
                            "show_end_at" => 0,
                            "apply_terminal" => ""
                        ]
                    ],
                    [
                        [
                            "static" => [
                                "list_web" => "/image/1730417410128..webp",
                                "list_h5" => "/image/1730417410128..webp",
                                "title_web" => "/image/redeem_title_web.png",
                                "title_h5" => "/image/redeem_title_h5.png",
                                "share_h5" => "/",
                                "display_mode" => 1,
                                "link_mode" => 2,
                                "link_url" => "/active/redeem"
                            ],
                            "id" => "231401050555112233",
                            "title" => "Resgatar atividade de código",
                            "state" => 2,
                            "flag" => "redeem_code",
                            "grade" => "2025,35,34,1001,1002,1003,1004,1005,1006,1007,1008",
                            "login_af" => 0,
                            "login_bf" => 0,
                            "show_at" => 1730757057,
                            "show_end_at" => 0,
                            "apply_terminal" => ""
                        ]
                    ],
                    [
                        [
                            "static" => [
                                "list_web" => "/image/1730417410128..webp",
                                "list_h5" => "/image/1730417410128..webp",
                                "title_web" => "/image/redeem_title_web.png",
                                "title_h5" => "/image/redeem_title_h5.png",
                                "share_h5" => "/",
                                "display_mode" => 1,
                                "link_mode" => 2,
                                "link_url" => "/active/redeem"
                            ],
                            "id" => "231401050555112233",
                            "title" => "Resgatar atividade de código",
                            "state" => 2,
                            "flag" => "redeem_code",
                            "grade" => "2025,35,34,1001,1002,1003,1004,1005,1006,1007,1008",
                            "login_af" => 0,
                            "login_bf" => 0,
                            "show_at" => 1730757057,
                            "show_end_at" => 0,
                            "apply_terminal" => ""
                        ]
                    ],
                    [
                        [
                            "static" => [
                                "list_web" => "/image/1721428630725..webp",
                                "list_h5" => "/image/1730417424536..webp",
                                "title_web" => "/image/1705398003881.webp",
                                "title_h5" => "/image/1705398015397.webp",
                                "share_h5" => "/",
                                "display_mode" => 1,
                                "link_mode" => 1,
                                "link_url" => "web_login"
                            ],
                            "id" => "17395548563955151",
                            "title" => "Recomende amigos e ganhe bônus",
                            "state" => 2,
                            "flag" => "invite",
                            "grade" => "35,34,1001,1002,1003,1004,1005,1006,1007,1008",
                            "login_af" => 0,
                            "login_bf" => 0,
                            "show_at" => 1730417432,
                            "show_end_at" => 0,
                            "apply_terminal" => ""
                        ],
                        [
                            "static" => [
                                "list_web" => "/image/1721428690103..webp",
                                "list_h5" => "/image/1730417443496..webp",
                                "title_web" => "/image/1721428693817..webp",
                                "title_h5" => "/image/1728704565090..webp",
                                "share_h5" => "",
                                "display_mode" => 1,
                                "link_mode" => 1,
                                "link_url" => "web_login"
                            ],
                            "id" => "17395548563954431",
                            "title" => "Primeiro bônus de recarga para novos membros",
                            "state" => 2,
                            "flag" => "deposit",
                            "grade" => "2025,47,35,34,1001,1002,1003,1004,1005,1006,1007,1008",
                            "login_af" => 0,
                            "login_bf" => 0,
                            "show_at" => 1730417451,
                            "show_end_at" => 0,
                            "apply_terminal" => ""
                        ],
                        [
                            "static" => [
                                "list_web" => "/image/1721428725055..webp",
                                "list_h5" => "/image/1730417461324..webp",
                                "title_web" => "/image/1721428728732..webp",
                                "title_h5" => "/image/1728704581144..webp",
                                "share_h5" => "/undefined",
                                "display_mode" => 1,
                                "link_mode" => 1,
                                "link_url" => "web_login"
                            ],
                            "id" => "17405392470691661",
                            "title" => "fundo de ajuda",
                            "state" => 2,
                            "flag" => "rescue",
                            "grade" => "2025,47,35,34,1001,1002,1003,1004,1005,1006,1007,1008",
                            "login_af" => 0,
                            "login_bf" => 0,
                            "show_at" => 1730417463,
                            "show_end_at" => 0,
                            "apply_terminal" => ""
                        ],
                        [
                            "static" => [
                                "list_web" => "",
                                "list_h5" => "/image/1730417474499..webp",
                                "title_web" => "",
                                "title_h5" => "",
                                "share_h5" => "",
                                "display_mode" => 1
                            ],
                            "id" => "12052671887318748",
                            "title" => "Anúncio VIP",
                            "state" => 2,
                            "flag" => "static",
                            "grade" => "2025,47,45,35,34,1001,1002,1003,1004,1005,1006,1007,1008",
                            "login_af" => 0,
                            "login_bf" => 0,
                            "show_at" => 1730417481,
                            "show_end_at" => 0,
                            "apply_terminal" => ""
                        ],
                        [
                            "static" => [
                                "list_web" => "",
                                "list_h5" => "/image/1730417490214..webp",
                                "title_web" => "",
                                "title_h5" => "",
                                "share_h5" => "",
                                "display_mode" => 2,
                                "link_mode" => 2,
                                "link_url" => "https://t.me/"
                            ],
                            "id" => "12093654993918560",
                            "title" => "especialista em telégrafo",
                            "state" => 2,
                            "flag" => "static",
                            "grade" => "2025,47,45,35,34,1001,1002,1003,1004,1005,1006,1007,1008",
                            "login_af" => 0,
                            "login_bf" => 0,
                            "show_at" => 1730417524,
                            "show_end_at" => 0,
                            "apply_terminal" => ""
                        ],
                        [
                            "static" => [
                                "list_web" => "",
                                "list_h5" => "/image/1730417534754..webp",
                                "title_web" => "",
                                "title_h5" => "",
                                "share_h5" => "",
                                "display_mode" => 1
                            ],
                            "id" => "7589294108015554",
                            "title" => "Bônus misterioso",
                            "state" => 2,
                            "flag" => "static",
                            "grade" => "2025,47,45,35,34,1001,1002,1003,1004,1005,1006,1007,1008",
                            "login_af" => 0,
                            "login_bf" => 0,
                            "show_at" => 1730417542,
                            "show_end_at" => 0,
                            "apply_terminal" => ""
                        ],
                        [
                            "static" => [
                                "list_web" => "",
                                "list_h5" => "/image/1730417559866..webp",
                                "title_web" => "",
                                "title_h5" => "",
                                "share_h5" => "",
                                "display_mode" => 2,
                                "link_mode" => 2,
                                "link_url" => "https://t.me/"
                            ],
                            "id" => "12117535491806041",
                            "title" => "telegram.VIP",
                            "state" => 2,
                            "flag" => "static",
                            "grade" => "2025,47,45,35,34,1001,1002,1003,1004,1005,1006,1007,1008",
                            "login_af" => 0,
                            "login_bf" => 0,
                            "show_at" => 1730874691,
                            "show_end_at" => 0,
                            "apply_terminal" => ""
                        ],
                        [
                            "static" => [
                                "list_web" => "/image/1730417410128..webp",
                                "list_h5" => "/image/1730417410128..webp",
                                "title_web" => "/image/redeem_title_web.png",
                                "title_h5" => "/image/redeem_title_h5.png",
                                "share_h5" => "/",
                                "display_mode" => 1,
                                "link_mode" => 2,
                                "link_url" => "/active/redeem"
                            ],
                            "id" => "231401050555112233",
                            "title" => "Resgatar atividade de código",
                            "state" => 2,
                            "flag" => "redeem_code",
                            "grade" => "2025,35,34,1001,1002,1003,1004,1005,1006,1007,1008",
                            "login_af" => 0,
                            "login_bf" => 0,
                            "show_at" => 1730757057,
                            "show_end_at" => 0,
                            "apply_terminal" => ""
                        ]
                    ],
                    [
                        [
                            "static" => [
                                "list_web" => "/image/1730417410128..webp",
                                "list_h5" => "/image/1730417410128..webp",
                                "title_web" => "/image/redeem_title_web.png",
                                "title_h5" => "/image/redeem_title_h5.png",
                                "share_h5" => "/",
                                "display_mode" => 1,
                                "link_mode" => 2,
                                "link_url" => "/active/redeem"
                            ],
                            "id" => "231401050555112233",
                            "title" => "Resgatar atividade de código",
                            "state" => 2,
                            "flag" => "redeem_code",
                            "grade" => "2025,35,34,1001,1002,1003,1004,1005,1006,1007,1008",
                            "login_af" => 0,
                            "login_bf" => 0,
                            "show_at" => 1730757057,
                            "show_end_at" => 0,
                            "apply_terminal" => ""
                        ]
                    ],
                    [
                        [
                            "static" => [
                                "list_web" => "/image/1730417410128..webp",
                                "list_h5" => "/image/1730417410128..webp",
                                "title_web" => "/image/redeem_title_web.png",
                                "title_h5" => "/image/redeem_title_h5.png",
                                "share_h5" => "/",
                                "display_mode" => 1,
                                "link_mode" => 2,
                                "link_url" => "/active/redeem"
                            ],
                            "id" => "231401050555112233",
                            "title" => "Resgatar atividade de código",
                            "state" => 2,
                            "flag" => "redeem_code",
                            "grade" => "2025,35,34,1001,1002,1003,1004,1005,1006,1007,1008",
                            "login_af" => 0,
                            "login_bf" => 0,
                            "show_at" => 1730757057,
                            "show_end_at" => 0,
                            "apply_terminal" => ""
                        ]
                    ],
                    [
                        [
                            "static" => [
                                "list_web" => "/image/1730417410128..webp",
                                "list_h5" => "/image/1730417410128..webp",
                                "title_web" => "/image/redeem_title_web.png",
                                "title_h5" => "/image/redeem_title_h5.png",
                                "share_h5" => "/",
                                "display_mode" => 1,
                                "link_mode" => 2,
                                "link_url" => "/active/redeem"
                            ],
                            "id" => "231401050555112233",
                            "title" => "Resgatar atividade de código",
                            "state" => 2,
                            "flag" => "redeem_code",
                            "grade" => "2025,35,34,1001,1002,1003,1004,1005,1006,1007,1008",
                            "login_af" => 0,
                            "login_bf" => 0,
                            "show_at" => 1730757057,
                            "show_end_at" => 0,
                            "apply_terminal" => ""
                        ]
                    ],
                    [
                        [
                            "static" => [
                                "list_web" => "/image/1730417410128..webp",
                                "list_h5" => "/image/1730417410128..webp",
                                "title_web" => "/image/redeem_title_web.png",
                                "title_h5" => "/image/redeem_title_h5.png",
                                "share_h5" => "/",
                                "display_mode" => 1,
                                "link_mode" => 2,
                                "link_url" => "/active/redeem"
                            ],
                            "id" => "231401050555112233",
                            "title" => "Resgatar atividade de código",
                            "state" => 2,
                            "flag" => "redeem_code",
                            "grade" => "2025,35,34,1001,1002,1003,1004,1005,1006,1007,1008",
                            "login_af" => 0,
                            "login_bf" => 0,
                            "show_at" => 1730757057,
                            "show_end_at" => 0,
                            "apply_terminal" => ""
                        ]
                    ]
                ],
                "msg" => null
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota Member/message/list
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/message/list') {
            $rotaEncontrada = true; // Rota encontrada
            // SQL para obter os dados dos jogos
            $sql = "SELECT * FROM mensagens WHERE status = 1";
            $result = $mysqli->query($sql);

            $games_data = [
                "d" => [],
                "t" => 0, // Ajuste conforme necessário
                "s" => 0, // Ajuste conforme necessário
            ];

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $games_data["d"][] = [
                        "id" => $row['id'],
                        "title" => $row['titulo'],
                        "content" => $row['content'],
                        "is_read" => 1,
                        "send_at" => strtotime($row['criado_em']),
                    ];
                }

                // Ajuste os valores de 't' e 's' conforme necessário
                $games_data["t"] = count($games_data); // Exemplo: número de resultados
                $games_data["s"] = count($games_data); // Exemplo: número total de resultados
            } else {
                // No games data, manter valores padrão ou ajustar conforme necessário
                $games_data["t"] = 0;
                $games_data["s"] = 0;
            }

            $mysqli->close();

            $response = [
                "status" => true,
                "data" => $games_data,
            ];

            echo json_encode($response, JSON_PRETTY_PRINT);
        }
        // Rota member/vip/config
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/vip/config') {
            $rotaEncontrada = true; // Rota encontrada
            $response = [
                "status" => true,
                "data" => [
                    [
                        "id" => "0",
                        "level" => 0,
                        "level_name" => "VIP0",
                        "recharge_num" => 0,
                        "upgrade_deposit" => 0,
                        "upgrade_record" => 0,
                        "relegation_flowing" => 0,
                        "upgrade_gift" => 0,
                        "birth_gift" => 0,
                        "withdraw_count" => 10,
                        "withdraw_max" => 1000000,
                        "early_month_packet" => 0,
                        "late_month_packet" => 0,
                        "created_at" => 0,
                        "updated_at" => 1721318682,
                        "user_count" => 62192,
                        "remark" => "",
                        "flow_multiple" => 1
                    ],
                    [
                        "id" => "1",
                        "level" => 1,
                        "level_name" => "VIP1",
                        "recharge_num" => 0,
                        "upgrade_deposit" => 0,
                        "upgrade_record" => 10000,
                        "relegation_flowing" => 0,
                        "upgrade_gift" => 5,
                        "birth_gift" => 0,
                        "withdraw_count" => 10,
                        "withdraw_max" => 1000000,
                        "early_month_packet" => 0,
                        "late_month_packet" => 0,
                        "created_at" => 0,
                        "updated_at" => 1721318677,
                        "user_count" => 56,
                        "remark" => "",
                        "flow_multiple" => 1
                    ],
                    [
                        "id" => "2",
                        "level" => 2,
                        "level_name" => "VIP2",
                        "recharge_num" => 0,
                        "upgrade_deposit" => 0,
                        "upgrade_record" => 30000,
                        "relegation_flowing" => 0,
                        "upgrade_gift" => 10,
                        "birth_gift" => 0,
                        "withdraw_count" => 10,
                        "withdraw_max" => 1000000,
                        "early_month_packet" => 0,
                        "late_month_packet" => 0,
                        "created_at" => 0,
                        "updated_at" => 1717205468,
                        "user_count" => 10,
                        "remark" => "",
                        "flow_multiple" => 1
                    ],
                    [
                        "id" => "3",
                        "level" => 3,
                        "level_name" => "VIP3",
                        "recharge_num" => 0,
                        "upgrade_deposit" => 0,
                        "upgrade_record" => 100000,
                        "relegation_flowing" => 0,
                        "upgrade_gift" => 20,
                        "birth_gift" => 0,
                        "withdraw_count" => 10,
                        "withdraw_max" => 1000000,
                        "early_month_packet" => 0,
                        "late_month_packet" => 0,
                        "created_at" => 0,
                        "updated_at" => 1717205472,
                        "user_count" => 0,
                        "remark" => "",
                        "flow_multiple" => 1
                    ],
                    [
                        "id" => "4",
                        "level" => 4,
                        "level_name" => "VIP4",
                        "recharge_num" => 0,
                        "upgrade_deposit" => 0,
                        "upgrade_record" => 300000,
                        "relegation_flowing" => 0,
                        "upgrade_gift" => 50,
                        "birth_gift" => 0,
                        "withdraw_count" => 10,
                        "withdraw_max" => 1000000,
                        "early_month_packet" => 0,
                        "late_month_packet" => 0,
                        "created_at" => 0,
                        "updated_at" => 1717205477,
                        "user_count" => 0,
                        "remark" => "",
                        "flow_multiple" => 1
                    ],
                    [
                        "id" => "5",
                        "level" => 5,
                        "level_name" => "VIP5",
                        "recharge_num" => 0,
                        "upgrade_deposit" => 0,
                        "upgrade_record" => 600000,
                        "relegation_flowing" => 0,
                        "upgrade_gift" => 100,
                        "birth_gift" => 0,
                        "withdraw_count" => 10,
                        "withdraw_max" => 1000000,
                        "early_month_packet" => 0,
                        "late_month_packet" => 0,
                        "created_at" => 0,
                        "updated_at" => 1717205481,
                        "user_count" => 0,
                        "remark" => "",
                        "flow_multiple" => 1
                    ],
                    [
                        "id" => "6",
                        "level" => 6,
                        "level_name" => "VIP6",
                        "recharge_num" => 0,
                        "upgrade_deposit" => 0,
                        "upgrade_record" => 1000000,
                        "relegation_flowing" => 0,
                        "upgrade_gift" => 200,
                        "birth_gift" => 0,
                        "withdraw_count" => 10,
                        "withdraw_max" => 1000000,
                        "early_month_packet" => 0,
                        "late_month_packet" => 0,
                        "created_at" => 0,
                        "updated_at" => 1717615192,
                        "user_count" => 0,
                        "remark" => "",
                        "flow_multiple" => 1
                    ],
                    [
                        "id" => "7",
                        "level" => 7,
                        "level_name" => "VIP7",
                        "recharge_num" => 0,
                        "upgrade_deposit" => 0,
                        "upgrade_record" => 3000000,
                        "relegation_flowing" => 0,
                        "upgrade_gift" => 400,
                        "birth_gift" => 0,
                        "withdraw_count" => 10,
                        "withdraw_max" => 1000000,
                        "early_month_packet" => 0,
                        "late_month_packet" => 0,
                        "created_at" => 0,
                        "updated_at" => 1717619261,
                        "user_count" => 0,
                        "remark" => "",
                        "flow_multiple" => 1
                    ],
                    [
                        "id" => "8",
                        "level" => 8,
                        "level_name" => "VIP8",
                        "recharge_num" => 0,
                        "upgrade_deposit" => 0,
                        "upgrade_record" => 5000000,
                        "relegation_flowing" => 0,
                        "upgrade_gift" => 600,
                        "birth_gift" => 0,
                        "withdraw_count" => 10,
                        "withdraw_max" => 1000000,
                        "early_month_packet" => 0,
                        "late_month_packet" => 0,
                        "created_at" => 0,
                        "updated_at" => 1717619266,
                        "user_count" => 1,
                        "remark" => "",
                        "flow_multiple" => 1
                    ],
                    [
                        "id" => "9",
                        "level" => 9,
                        "level_name" => "VIP9",
                        "recharge_num" => 0,
                        "upgrade_deposit" => 0,
                        "upgrade_record" => 7000000,
                        "relegation_flowing" => 0,
                        "upgrade_gift" => 800,
                        "birth_gift" => 0,
                        "withdraw_count" => 10,
                        "withdraw_max" => 1000000,
                        "early_month_packet" => 0,
                        "late_month_packet" => 0,
                        "created_at" => 0,
                        "updated_at" => 1717832417,
                        "user_count" => 0,
                        "remark" => "",
                        "flow_multiple" => 1
                    ],
                    [
                        "id" => "10",
                        "level" => 10,
                        "level_name" => "VIP10",
                        "recharge_num" => 0,
                        "upgrade_deposit" => 0,
                        "upgrade_record" => 10000000,
                        "relegation_flowing" => 0,
                        "upgrade_gift" => 950,
                        "birth_gift" => 0,
                        "withdraw_count" => 10,
                        "withdraw_max" => 1000000,
                        "early_month_packet" => 0,
                        "late_month_packet" => 0,
                        "created_at" => 0,
                        "updated_at" => 1717832425,
                        "user_count" => 0,
                        "remark" => "",
                        "flow_multiple" => 1
                    ],
                    [
                        "id" => "11",
                        "level" => 11,
                        "level_name" => "VIP11",
                        "recharge_num" => 0,
                        "upgrade_deposit" => 0,
                        "upgrade_record" => 13000000,
                        "relegation_flowing" => 0,
                        "upgrade_gift" => 1100,
                        "birth_gift" => 0,
                        "withdraw_count" => 10,
                        "withdraw_max" => 1000000,
                        "early_month_packet" => 0,
                        "late_month_packet" => 0,
                        "created_at" => 0,
                        "updated_at" => 1717832430,
                        "user_count" => 0,
                        "remark" => "",
                        "flow_multiple" => 1
                    ],
                    [
                        "id" => "12",
                        "level" => 12,
                        "level_name" => "VIP12",
                        "recharge_num" => 0,
                        "upgrade_deposit" => 0,
                        "upgrade_record" => 16000000,
                        "relegation_flowing" => 0,
                        "upgrade_gift" => 1250,
                        "birth_gift" => 0,
                        "withdraw_count" => 10,
                        "withdraw_max" => 1000000,
                        "early_month_packet" => 0,
                        "late_month_packet" => 0,
                        "created_at" => 0,
                        "updated_at" => 1717832435,
                        "user_count" => 0,
                        "remark" => "",
                        "flow_multiple" => 1
                    ],
                    [
                        "id" => "13",
                        "level" => 13,
                        "level_name" => "VIP13",
                        "recharge_num" => 0,
                        "upgrade_deposit" => 0,
                        "upgrade_record" => 20000000,
                        "relegation_flowing" => 0,
                        "upgrade_gift" => 1500,
                        "birth_gift" => 0,
                        "withdraw_count" => 0,
                        "withdraw_max" => 0,
                        "early_month_packet" => 0,
                        "late_month_packet" => 0,
                        "created_at" => 0,
                        "updated_at" => 1717832442,
                        "user_count" => 0,
                        "remark" => "",
                        "flow_multiple" => 1
                    ],
                    [
                        "id" => "14",
                        "level" => 14,
                        "level_name" => "VIP14",
                        "recharge_num" => 0,
                        "upgrade_deposit" => 0,
                        "upgrade_record" => 100000000,
                        "relegation_flowing" => 0,
                        "upgrade_gift" => 3000,
                        "birth_gift" => 0,
                        "withdraw_count" => 0,
                        "withdraw_max" => 0,
                        "early_month_packet" => 0,
                        "late_month_packet" => 0,
                        "created_at" => 0,
                        "updated_at" => 1717832518,
                        "user_count" => 0,
                        "remark" => "",
                        "flow_multiple" => 1
                    ],
                    [
                        "id" => "15",
                        "level" => 15,
                        "level_name" => "VIP15",
                        "recharge_num" => 0,
                        "upgrade_deposit" => 0,
                        "upgrade_record" => 1000000000,
                        "relegation_flowing" => 0,
                        "upgrade_gift" => 6515,
                        "birth_gift" => 0,
                        "withdraw_count" => 0,
                        "withdraw_max" => 0,
                        "early_month_packet" => 0,
                        "late_month_packet" => 0,
                        "created_at" => 0,
                        "updated_at" => 1714668744,
                        "user_count" => 14002,
                        "remark" => "",
                        "flow_multiple" => 1
                    ]
                ]
            ];
            $response_json = json_encode($response, JSON_PRETTY_PRINT);
            echo $response_json;
        }
        // Rota de troca de avatar
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/avatar/update') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);
                    $userId = $datares['id'];

                    // Pega o novo avatar do parâmetro 'id' na URL
                    $novoAvatar = $mysqli->real_escape_string($_GET['id'] ?? '');

                    if (!empty($novoAvatar)) {
                        // Atualiza o campo 'avatar' na tabela 'usuarios' para o usuário correspondente
                        $sql = $mysqli->prepare("UPDATE usuarios SET avatar = ? WHERE id = ?");
                        $sql->bind_param("si", $novoAvatar, $userId);

                        if ($sql->execute()) {
                            $response = [
                                "status" => true,
                                "data" => '1000',
                                "msg" => "Avatar atualizado com sucesso",
                            ];
                        } else {
                            $response = [
                                "code" => 0,
                                "msg" => "Erro ao atualizar avatar.",
                            ];
                        }
                    } else {
                        $response = [
                            "code" => 0,
                            "msg" => "Avatar não fornecido.",
                        ];
                    }

                    echo json_encode($response);
                    exit;
                } else {
                    $response = [
                        "code" => 0,
                        "data" => 1003,
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "data" => 1003,
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota de member/rebate/agency
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/rebate/agency/record') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);

                    $response = [
                        "status" => true,
                        "data" => [
                            "d" => null,
                            "t" => 0,
                            "agg" => [
                                "total_paid_amount" => $datares['total_rev'],
                                "paid_amount" => $datares['rev'],
                                "other_paid_amount" => 0
                            ],
                            "s" => 15
                        ],
                        "msg" => null
                    ];
                    echo json_encode($response);
                    exit;
                } else {
                    $response = [
                        "code" => 0,
                        "data" => 1003,
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "data" => 1003,
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota de member/commission/config
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/commission/config') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);

                    $response = [
                        "status" => true,
                        "data" => [
                            [
                                "id" => 70277770214313050,
                                "game_type" => 3,
                                "bet_amount" => 0,
                                "rebate_amount" => 5,
                                "ty" => 2
                            ],
                            [
                                "id" => 70278155580891617,
                                "game_type" => 2,
                                "bet_amount" => 0,
                                "rebate_amount" => 2,
                                "ty" => 2
                            ]
                        ],
                        "msg" => null
                    ];
                    echo json_encode($response);
                    exit;
                } else {
                    $response = [
                        "code" => 0,
                        "data" => 1003,
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "data" => 1003,
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota de promo/turntable/detail
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/promo/turntable/detail') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);

                    $response = [
                        "status" => true,
                        "data" => null,
                        "msg" => null
                    ];
                    echo json_encode($response);
                    exit;
                } else {
                    $response = [
                        "code" => 0,
                        "data" => 1003,
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "data" => 1003,
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }

        // Rota de promo/apply/status
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/promo/apply/status') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);

                    if ($_GET['id'] == '17395548563954431') {

                        $response = [
                            "status" => true,
                            "data" => [
                                false,
                                false,
                                false
                            ],
                            "msg" => null
                        ];
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response = [
                        "code" => 0,
                        "data" => 1003,
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "data" => 1003,
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }

        // Rota de promo/apply/status
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/promo/deposit/detail') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);

                    if ($_GET['id'] == '17395548563954431') {

                        $response = [
                            "status" => true,
                            "data" => [],
                            "msg" => null
                        ];
                        echo json_encode($response);
                        exit;
                    }
                } else {
                    $response = [
                        "code" => 0,
                        "data" => 1003,
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "data" => 1003,
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota de promo/turntable/prize/list
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/promo/turntable/prize/list') {
            if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
                $rotaEncontrada = true; // Rota encontrada
                $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
                $resp = mysqli_query($mysqli, $qry);

                if (mysqli_num_rows($resp) > 0) {
                    $datares = mysqli_fetch_assoc($resp);

                    $response = [
                        "status" => true,
                        "data" => [
                            "bonus_list" => [
                                [
                                    "id" => 1,
                                    "bonus_ty" => 4,
                                    "win_rate" => 80,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 2,
                                    "is_first" => false,
                                    "reward_amount" => 0
                                ],
                                [
                                    "id" => 2,
                                    "bonus_ty" => 1,
                                    "win_rate" => 5,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 2,
                                    "is_first" => false,
                                    "reward_amount" => 0
                                ],
                                [
                                    "id" => 3,
                                    "bonus_ty" => 1,
                                    "win_rate" => 5,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 2,
                                    "is_first" => false,
                                    "reward_amount" => 0
                                ],
                                [
                                    "id" => 4,
                                    "bonus_ty" => 1,
                                    "win_rate" => 5,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 2,
                                    "is_first" => false,
                                    "reward_amount" => 0
                                ],
                                [
                                    "id" => 5,
                                    "bonus_ty" => 1,
                                    "win_rate" => 3,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 2,
                                    "is_first" => false,
                                    "reward_amount" => 0
                                ],
                                [
                                    "id" => 6,
                                    "bonus_ty" => 1,
                                    "win_rate" => 2,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 2,
                                    "is_first" => false,
                                    "reward_amount" => 0
                                ],
                                [
                                    "id" => 7,
                                    "bonus_ty" => 2,
                                    "win_rate" => 0,
                                    "bonus_amount" => 50,
                                    "flow_multiple" => 5,
                                    "is_first" => false,
                                    "reward_amount" => 0
                                ],
                                [
                                    "id" => 8,
                                    "bonus_ty" => 3,
                                    "win_rate" => 0,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 2,
                                    "is_first" => false,
                                    "reward_amount" => 0
                                ]
                            ],
                            "state" => 2
                        ],
                        "msg" => null
                    ];
                    echo json_encode($response);
                    exit;
                } else {
                    $response = [
                        "code" => 0,
                        "data" => 1003,
                    ];
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response = [
                    "code" => 0,
                    "data" => null,
                    "data" => 1003,
                    "time" => time(),
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota Member/Customer/List
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/member/customer/list') {
            $rotaEncontrada = true; // Rota encontrada
            // Captura a query string
            $queryString = parse_url($requestURI, PHP_URL_QUERY);
            parse_str($queryString, $queryParams);

            // Verifica se a query string contém a chave 'dv'
            if (isset($queryParams['flag']) && $queryParams['flag'] == 2) {
                $rotaEncontrada = true; // Rota encontrada

                // Aqui você pode definir a resposta como antes
                $response = [
                    "status" => true,
                    "data" => [
                        [
                            "id" => "945086613296008162",
                            "imId" => "20",
                            "im" => "https://gdsa.petscarepgapp.com/active/ActiveImg3986087441138483.avif",
                            "name" => "Canal Oficial",
                            "link" => "https://instagram.com/",
                            "remark" => "",
                            "flag" => 2,
                            "sort" => 1,
                            "status" => 2,
                            "method" => 0,
                            "createdAt" => 1724975548,
                            "updatedAt" => 1730417907
                        ],
                        [
                            "id" => "764031310417011112",
                            "imId" => "20",
                            "im" => "https://gdsa.petscarepgapp.com/active/ActiveImg13601253215667300.avif",
                            "name" => "Telegram",
                            "link" => "https://t.me/",
                            "remark" => "",
                            "flag" => 2,
                            "sort" => 1,
                            "status" => 2,
                            "method" => 0,
                            "createdAt" => 1712838327,
                            "updatedAt" => 1730417860
                        ],
                        [
                            "id" => "150809726257797389",
                            "imId" => "8",
                            "im" => "https://gdsa.petscarepgapp.com/siteadmin/upload/marketing_medium_dx_1_05.avif",
                            "name" => "Telegram",
                            "link" => "https://t.me/",
                            "remark" => "",
                            "flag" => 2,
                            "sort" => 2,
                            "status" => 2,
                            "method" => 0,
                            "createdAt" => 1709636325,
                            "updatedAt" => 1730874676
                        ]
                    ],
                    "msg" => null
                ];

                // Use JSON_UNESCAPED_UNICODE to avoid escaping Unicode characters
                $response_json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                echo $response_json;
            }

            // Verifica se a query string contém a chave 'dv'
            if (isset($queryParams['flag']) && $queryParams['flag'] == 1) {
                $rotaEncontrada = true; // Rota encontrada

                // Aqui você pode definir a resposta como antes
                $response = [
                    "status" => true,
                    "data" => [
                        [
                            "id" => "4",
                            "title" => "Nosso Suporte",
                            "im" => "/image/1708679618427.webp",
                            "flag" => 1,
                            "sort" => 3,
                            "createdAt" => 0,
                            "updatedAt" => 0,
                            "items" => [
                                [
                                    "id" => "481783378761001989",
                                    "imId" => "4",
                                    "im" => "/image/1720066136856..webp",
                                    "name" => "Suporte Online 24/7",
                                    "link" => $dataconfig['telegram'],
                                    "remark" => "chat",
                                    "flag" => 1,
                                    "sort" => 0,
                                    "status" => 2,
                                    "method" => 0,
                                    "createdAt" => 0,
                                    "updatedAt" => 1721718218,
                                ],
                            ],
                        ],
                    ],
                    "msg" => null,
                ];

                // Use JSON_UNESCAPED_UNICODE to avoid escaping Unicode characters
                $response_json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                echo $response_json;
            }
            // Verifica se a query string contém a chave 'dv'
            if (isset($queryParams['flag']) && $queryParams['flag'] == 3) {
                $rotaEncontrada = true; // Rota encontrada

                // Aqui você pode definir a resposta como antes
                $response = [
                    "status" => true,
                    "data" => [
                        [
                            "id" => "11",
                            "title" => "Telegram Suporte",
                            "im" => "/image/1708679594041.webp",
                            "flag" => 3,
                            "sort" => 2,
                            "createdAt" => 0,
                            "updatedAt" => 0,
                            "items" => [
                                [
                                    "id" => "168772440975260479",
                                    "imId" => "11",
                                    "im" => "/image/1708679594041.webp",
                                    "name" => "telegram",
                                    "link" => $dataconfig['telegram'],
                                    "remark" => "telegram",
                                    "flag" => 3,
                                    "sort" => 1,
                                    "status" => 2,
                                    "method" => 0,
                                    "createdAt" => 1710255728,
                                    "updatedAt" => 1720066193,
                                ],
                            ],
                        ],
                    ],
                    "msg" => null,
                ];

                // Use JSON_UNESCAPED_UNICODE to avoid escaping Unicode characters
                $response_json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                echo $response_json;
            }
        }
        // Rota de api/promo/detail
        if (parse_url($requestURI, PHP_URL_PATH) === '/api/promo/detail') {
            // if (isset($_COOKIE['token_user']) && !empty($_COOKIE['token_user'])) {
            $rotaEncontrada = true; // Rota encontrada
            // $qry = "SELECT * FROM usuarios WHERE token='" . $_COOKIE['token_user'] . "'";
            //   $resp = mysqli_query($mysqli, $qry);

            if ($_GET['id'] == 17395548563954431) {

                $response = [
                    "status" => true,
                    "data" => [
                        "static" => [
                            "display_mode" => 1,
                            "link_mode" => 1,
                            "link_url" => "web_login",
                            "page_style" => "img",
                            "list_h5" => "/image/1727866779415..webp",
                            "title_h5" => "/image/1727866886742..webp",
                            "list_web" => "/image/1710299520110..webp",
                            "title_web" => "/image/1710299193585..webp",
                            "share_h5" => ""
                        ],
                        "rules" => [
                            [
                                [
                                    "deposit_amount" => 20,
                                    "bonus_rate" => 10,
                                    "limit_amount" => 10000,
                                    "bonus_amount" => 2,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 30,
                                    "bonus_rate" => 10,
                                    "limit_amount" => 10000,
                                    "bonus_amount" => 2,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 50,
                                    "bonus_rate" => 10,
                                    "limit_amount" => 10000,
                                    "bonus_amount" => 2,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 100,
                                    "bonus_rate" => 10,
                                    "limit_amount" => 10000,
                                    "bonus_amount" => 2,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 200,
                                    "bonus_rate" => 10,
                                    "limit_amount" => 0,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 300,
                                    "bonus_rate" => 10,
                                    "limit_amount" => 0,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 500,
                                    "bonus_rate" => 10,
                                    "limit_amount" => 0,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 1000,
                                    "bonus_rate" => 10,
                                    "limit_amount" => 0,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 2000,
                                    "bonus_rate" => 10,
                                    "limit_amount" => 0,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 3000,
                                    "bonus_rate" => 10,
                                    "limit_amount" => 0,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 5000,
                                    "bonus_rate" => 10,
                                    "limit_amount" => 0,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 10000,
                                    "bonus_rate" => 10,
                                    "limit_amount" => 0,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ]
                            ],
                            [
                                [
                                    "deposit_amount" => 30,
                                    "bonus_rate" => 20,
                                    "limit_amount" => 10000,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 300,
                                    "bonus_rate" => 20,
                                    "limit_amount" => 10000,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 500,
                                    "bonus_rate" => 20,
                                    "limit_amount" => 10000,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 1000,
                                    "bonus_rate" => 20,
                                    "limit_amount" => 10000,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 5000,
                                    "bonus_rate" => 20,
                                    "limit_amount" => 0,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 10000,
                                    "bonus_rate" => 20,
                                    "limit_amount" => 0,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 20000,
                                    "bonus_rate" => 20,
                                    "limit_amount" => 0,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 50000,
                                    "bonus_rate" => 20,
                                    "limit_amount" => 0,
                                    "bonus_amount" => 0,
                                    "flow_multiple" => 3,
                                    "bonus_type" => 2
                                ]
                            ],
                            [
                                [
                                    "lose_amount" => 0,
                                    "bonus_amount" => 0,
                                    "bl_rate" => 1,
                                    "bonus_rate" => 30,
                                    "rate" => 1,
                                    "flow_multiple" => 3,
                                    "limit_amount" => 0,
                                    "deposit_amount" => 30,
                                    "bonus_type" => 2
                                ],
                                [
                                    "lose_amount" => 0,
                                    "bonus_amount" => 0,
                                    "bl_rate" => 1,
                                    "bonus_rate" => 30,
                                    "rate" => 1,
                                    "flow_multiple" => 3,
                                    "limit_amount" => 0,
                                    "deposit_amount" => 300,
                                    "bonus_type" => 2
                                ],
                                [
                                    "lose_amount" => 0,
                                    "bonus_amount" => 0,
                                    "bl_rate" => 1,
                                    "bonus_rate" => 30,
                                    "rate" => 1,
                                    "flow_multiple" => 3,
                                    "limit_amount" => 0,
                                    "deposit_amount" => 500,
                                    "bonus_type" => 2
                                ],
                                [
                                    "lose_amount" => 0,
                                    "bonus_amount" => 0,
                                    "bl_rate" => 1,
                                    "bonus_rate" => 30,
                                    "rate" => 1,
                                    "flow_multiple" => 3,
                                    "limit_amount" => 0,
                                    "deposit_amount" => 1000,
                                    "bonus_type" => 2
                                ],
                                [
                                    "lose_amount" => 0,
                                    "bonus_amount" => 0,
                                    "bl_rate" => 1,
                                    "bonus_rate" => 30,
                                    "rate" => 1,
                                    "flow_multiple" => 3,
                                    "limit_amount" => 0,
                                    "deposit_amount" => 5000,
                                    "bonus_type" => 2
                                ],
                                [
                                    "lose_amount" => 0,
                                    "bonus_amount" => 0,
                                    "bl_rate" => 1,
                                    "bonus_rate" => 30,
                                    "rate" => 1,
                                    "flow_multiple" => 3,
                                    "limit_amount" => 0,
                                    "deposit_amount" => 10000,
                                    "bonus_type" => 2
                                ],
                                [
                                    "lose_amount" => 0,
                                    "bonus_amount" => 0,
                                    "bl_rate" => 1,
                                    "bonus_rate" => 30,
                                    "rate" => 1,
                                    "flow_multiple" => 3,
                                    "limit_amount" => 0,
                                    "deposit_amount" => 20000,
                                    "bonus_type" => 2
                                ],
                                [
                                    "lose_amount" => 0,
                                    "bonus_amount" => 0,
                                    "bl_rate" => 1,
                                    "bonus_rate" => 30,
                                    "rate" => 1,
                                    "flow_multiple" => 3,
                                    "limit_amount" => 0,
                                    "deposit_amount" => 50000,
                                    "bonus_type" => 2
                                ]
                            ]
                        ],
                        "config" => [
                            "wheelDisplayStyle" => 1,
                            "wheelPrizeCount" => 1,
                            "title" => "Os primeiros 3 bônus de recarga do novo membro",
                            "sort" => 1,
                            "h5_content" => [
                                "/image/1712273686251..webp",
                                "/image/1712273690803..webp",
                                "/image/1712273695075..webp"
                            ],
                            "list_richOriginContent" => "",
                            "list_richContent" => "",
                            "list_content" => [
                                "bgColor" => "",
                                "texture" => ""
                            ],
                            "start_time" => "2022-06-01 00:00:00",
                            "end_time" => "2032-12-31 23:59:59",
                            "period" => 2,
                            "show_time" => "2022-06-20 01:30:45",
                            "show_end_time" => "2024-07-31 23:59:59",
                            "is_limit_plat" => 1,
                            "is_audit" => 2,
                            "is_discount" => 1,
                            "rewardCycle" => 1,
                            "upper_limit" => "",
                            "is_manual" => 1,
                            "deposit_manual" => 2,
                            "pick" => 1,
                            "award" => [
                                "type" => 1,
                                "time" => "00:00:00"
                            ],
                            "bonus_type" => 2,
                            "auditMethod" => 1,
                            "flow_multiple" => 1,
                            "entrance" => [
                                "2",
                                "3",
                                "4"
                            ],
                            "limited" => [],
                            "rule_text" => [
                                "1. Por favor, preencha seus dados pessoais antes de participar do evento",
                                "2. Cada membro com o mesmo nome, mesmo endereço IP, mesmo código de dispositivo e mesmo número de telefone só poderá participar do evento uma vez. Se um membro registrar intencionalmente várias contas consecutivas, a empresa retirará ou revogará os direitos da agência e congelará permanentemente a conta e recuperará todos os ganhos.",
                                "3. Todas as atividades são preparadas para os membros, caso seja descoberto que um indivíduo ou organização cometeu fraude para lucrar com as atividades. A Empresa reserva-se o direito total de reter ou anular o valor total do prêmio desse indivíduo ou organização"
                            ],
                            "content_text" => [
                                "1. Requisitos de apostas válidos",
                                "a. Ao participar do evento, o saldo da conta < valor do depósito, aposta válida = (saldo + bônus) x 2, você pode sacar dinheiro",
                                "b. Ao participar do evento, o saldo da conta > o valor do depósito e a aposta efetiva = (valor do depósito + bônus) x 2, você      pode sacar o dinheiro",
                                "2. Após o depósito ser bem-sucedido, participe da atividade antes de fazer apostas.",
                                "3. O primeiro depósito, o segundo depósito e o terceiro depósito só podem ser participados uma vez."
                            ],
                            "activity_conditions" => 1,
                            "collection_method" => 1,
                            "recharge_method" => [
                                "1",
                                "2",
                                "3"
                            ],
                            "recharge_num" => 1,
                            "login_af" => 0,
                            "login_bf" => 0,
                            "web_content" => [
                                "/image/1712273659951..webp",
                                "/image/1712273667439..webp",
                                "/image/1712273673232..webp"
                            ],
                            "flag" => "deposit",
                            "hide_time" => "2032-12-31 23:59:59",
                            "activity_time" => "",
                            "platforms" => [
                                "26595015200313"
                            ],
                            "lv" => 3
                        ],
                        "deposit_manual" => 2
                    ]

                ];
                echo json_encode($response);
                exit;
            }
            if ($_GET['id'] == 231401050555112237) {

                $response = [
                    "status" => true,
                    "data" => [
                        "static" => [
                            "display_mode" => 1,
                            "link_mode" => 1,
                            "link_url" => "web_login",
                            "page_style" => "img",
                            "list_h5" => "/image/1732350240223..webp",
                            "title_h5" => "/image/1732350244038..webp",
                            "list_web" => "/image/xxx.webp",
                            "title_web" => "/image/xxx.webp"
                        ],
                        "rules" => [
                            "percentage_list" => [
                                [
                                    "min_percentage" => 0,
                                    "max_percentage" => 0,
                                    "amount" => 0
                                ]
                            ],
                            "amountLoss_list" => [
                                [
                                    "min_amount" => 10,
                                    "max_amount" => 50,
                                    "amount" => 5
                                ],
                                [
                                    "min_amount" => 51,
                                    "max_amount" => 100,
                                    "amount" => 10
                                ]
                            ],
                            "validBets_list" => [
                                [
                                    "min_amount" => 0,
                                    "max_amount" => 0,
                                    "amount" => 0
                                ]
                            ],
                            "rule_text" => [
                                "1. Durante o evento, uma única recarga diária receberá diferentes graus de recompensa. Quanto mais você recarregar, mais recompensas receberá. A recompensa máxima é de 888. Você pode obter uma recompensa para cada etapa. "
                            ],
                            "promo_content_json" => [
                                [
                                    "title" => "o evento, uma única recarga diária receberá diferentes graus de recompen",
                                    "content" => "1、Durante o evento, uma única recarga diária receberá diferentes graus de recompensa.
                                     2、Quanto mais você recarregar, mais recompensas receberá. 
                                     3、A recompensa máxima é de 888. Você pode obter uma recompensa para cada etapa. 
                                     1、Durante o evento, uma única recarga diária receberá diferentes graus de recompensa.
                                     2、Quanto mais você recarregar, mais recompensas receberá. 
                                     3、A recompensa máxima é de 888. Você pode obter uma recompensa para cada etapa. 
                                     1、Durante o evento, uma única recarga diária receberá diferentes graus de recompensa.
                                     2、Quanto mais você recarregar, mais recompensas receberá. 
                                     3、A recompensa máxima é de 888. Você pode obter uma recompensa para cada etapa. "
                                ]
                            ]
                        ],
                        "config" => [
                            "title" => "Red envelope",
                            "sort" => 1,
                            "start_time" => "2024-11-14 08:30",
                            "end_time" => "2024-12-31 03:18",
                            "is_limit_plat" => 1,
                            "is_audit" => 2,
                            "bonus_type" => 1,
                            "auditMethod" => 1,
                            "flow_multiple" => 3,
                            "entrance" => [
                                "2",
                                "3",
                                "4"
                            ],
                            "limited" => [],
                            "redType" => 1,
                            "resetTime" => "00:00",
                            "claim_conditions" => [
                                "type" => 1,
                                "recharge_amount" => 3.1,
                                "code_amount" => "5"
                            ],
                            "reward_type" => 1,
                            "single_amount" => 1,
                            "distribution_time" => [
                                [
                                    "startTime" => "00:00",
                                    "endTime" => "23:59"
                                ]
                            ],
                            "redRain" => [
                                [
                                    "startTime" => "00:00",
                                    "endTime" => "23:00"
                                ]
                            ],
                            "total_amount" => 100,
                            "red_total" => 200,
                            "redAmount" => [
                                "min_amount" => 2,
                                "max_amount" => 5
                            ],
                            "platforms" => [
                                "26595015200105",
                                "26595015200115"
                            ],
                            "platlimits" => [],
                            "max_day_count" => 1
                        ],
                        "deposit_manual" => 2
                    ]

                ];
                echo json_encode($response);
                exit;
            }

            if ($_GET['id'] == 231401050555112235) {

                $response = [
                    "status" => true,
                    "data" => [
                        "static" => [
                            "display_mode" => 1,
                            "link_mode" => 1,
                            "link_url" => "web_login",
                            "page_style" => "img",
                            "list_h5" => "/image/1727866741096..webp",
                            "title_h5" => "/image/1727866858523..webp",
                            "list_web" => "/image/xxx.webp",
                            "title_web" => "/image/xxx.webp",
                            "share_h5" => ""
                        ],
                        "rules" => [
                            "flow_multiple" => "2",
                            "bonus_type" => 2,
                            "bonus_list" => [
                                [
                                    "deposit_amount" => 50,
                                    "bonus_amount" => 3,
                                    "bonus_rate" => 3,
                                    "limit_amount" => 240,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 100,
                                    "bonus_amount" => 7,
                                    "bonus_rate" => 7,
                                    "limit_amount" => 0,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 500,
                                    "bonus_amount" => 29,
                                    "bonus_rate" => 37,
                                    "limit_amount" => 15,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 1000,
                                    "bonus_amount" => 59,
                                    "bonus_rate" => 77,
                                    00,
                                    "limit_amount" => 20,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 5000,
                                    "bonus_amount" => 399,
                                    "bonus_rate" => 377,
                                    "limit_amount" => 40,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 10000,
                                    "bonus_amount" => 799,
                                    "bonus_rate" => 777,
                                    "limit_amount" => 80,
                                    "bonus_type" => 2
                                ],
                                [
                                    "deposit_amount" => 50000,
                                    "bonus_amount" => 2999,
                                    "bonus_rate" => 3.777,
                                    "limit_amount" => 200,
                                    "bonus_type" => 2
                                ]
                            ]
                        ],
                        "config" => [
                            "title" => "Gratuito para recarga única",
                            "sort" => 23,
                            "h5_content" => "",
                            "list_richOriginContent" => "",
                            "list_richContent" => "",
                            "list_content" => [
                                "bgColor" => "",
                                "texture" => ""
                            ],
                            "start_time" => "2024-07-12 00:00:00",
                            "end_time" => "2024-07-31 23:59:59",
                            "period" => 2,
                            "show_time" => "2024-07-12 00:00:00",
                            "show_end_time" => "2024-07-31 23:59:59",
                            "is_limit_plat" => 1,
                            "is_audit" => 2,
                            "is_discount" => 1,
                            "rewardCycle" => 1,
                            "upper_limit" => "",
                            "is_manual" => 1,
                            "deposit_manual" => 2,
                            "pick" => 2,
                            "award" => [
                                "type" => 1,
                                "time" => "00:00:00"
                            ],
                            "bonus_type" => 2,
                            "auditMethod" => 2,
                            "flow_multiple" => "2",
                            "entrance" => [
                                "2",
                                "3"
                            ],
                            "limited" => [],
                            "rule_text" => [
                                " 
                    Durante o evento, uma única recarga diária receberá diferentes graus de recompensa. Quanto mais você recarregar, mais recompensas receberá. A recompensa máxima é de 888. Você pode obter uma recompensa para cada etapa. ",
                                " 
                    Não há limite para o método de recarga. Aguarde a distribuição da recompensa.",
                                "
                     As recompensas obtidas só podem ser coletadas após 00:00:00 do dia seguinte e só podem ser coletadas 
                    manualmente no IOS, Android, H5 e PC.",
                                " 
                    1 dia após o término de cada ciclo, as recompensas obtidas expirarão e serão invalidadas diretamente após a expiração. ",
                                " O bônus (excluindo o principal) dado neste evento requer 1 vez a aposta válida antes de poder ser sacado. A aposta não se limita aos locais de jogo. ",
                                "
                    Membros com o mesmo nome, mesmo endereço IP, mesmo código de dispositivo e mesmo número de telefone só podem participar do evento uma vez. Se um membro registrar intencionalmente várias contas seguidas, a empresa retirará ou revogará os direitos de agência e congelará permanentemente a conta e recuperará todos os lucros. ",
                                "
                    Todas as atividades são preparadas para membros para evitar que indivíduos ou organizações sejam encontrados cometendo fraudes para obter benefícios das atividades. A empresa reserva-se o direito de reter ou cancelar todos os bônus do indivíduo ou organização. ",
                                "
                    A empresa reserva-se o direito de modificar e encerrar o evento sem aviso prévio. A decisão final pertence à plataforma."
                            ],
                            "content_text" => [
                                ""
                            ],
                            "activity_conditions" => 1,
                            "collection_method" => 1,
                            "recharge_method" => [
                                "1"
                            ],
                            "recharge_num" => 1,
                            "web_content" => "",
                            "flag" => "single_topup",
                            "hide_time" => "2024-07-31 23:59:59",
                            "activity_time" => "",
                            "platforms" => [
                                "36595015200322",
                                "36595015200313",
                                "26595015200313",
                                "26595015200305",
                                "36595015200305",
                                "26595015200323"
                            ],
                            "lv" => 1,
                            "is_show" => 1,
                            "payment_method" => [
                                "1",
                                "2",
                                "3"
                            ],
                            "claim_num" => 2,
                            "claim_limit" => [
                                "1",
                                "3"
                            ],
                            "stat_time" => 1,
                            "award_type" => "00:00:00",
                            "cycleMode" => 0,
                            "login_af" => 3,
                            "login_bf" => 1
                        ],
                        "deposit_manual" => 2
                    ]
                ];
                echo json_encode($response);
                exit;
            }
        }
        // Rota de api/promo/detail

        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
}

// Se nenhuma rota foi encontrada, retorna 404
if (!$rotaEncontrada) {
    sendError(404, 'Rota não encontrada');
}
