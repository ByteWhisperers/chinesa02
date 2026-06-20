<?php
date_default_timezone_set('America/Sao_Paulo');
include_once('database.php');
include_once('funcao.php');
#=====================================================#
# DATA CONFIG
function data_config()
{
	global $mysqli;
	$qry = "SELECT * FROM config WHERE id=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}
$dataconfig = data_config();
#=====================================================#
# DATA POPUPS
function data_popups($id)
{
	global $mysqli;
	$qry = "SELECT * FROM popups WHERE id = '" . $id . "'";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}
#=====================================================#
# DATA CONFIG
function data_fiverscanPanel()
{
	global $mysqli;
	$qry = "SELECT * FROM fiverscan WHERE id=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}
$data_fiverscanpanel = data_fiverscanPanel();


function data_apiphill()
{
	global $mysqli;
	$qry = "SELECT * FROM apiphillyps WHERE id=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}
$data_apiphill = data_apiphill();
function data_edbanking()
{
	global $mysqli;
	$qry = "SELECT * FROM edbanking WHERE id=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}
$data_edbanking = data_edbanking();

function data_versellpay()
{
	global $mysqli;
	$qry = "SELECT * FROM versellpay WHERE id=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}
$data_versellpay = data_versellpay();

function data_akadpay()
{
	global $mysqli;
	$qry = "SELECT * FROM akadpay WHERE id=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}
$data_akadpay = data_akadpay();

function data_syncpay()
{
	global $mysqli;
	$qry = "SELECT * FROM syncpay WHERE id=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}
$data_syncpay = data_syncpay();
function enviarSaldo($email, $saldo)
{
	global $mysqli;

	// Monta a query de atualização
	$qry = "UPDATE usuarios SET saldo = saldo + '" . $saldo . "' WHERE mobile = '" . $email . "'";

	// Executa a consulta
	if (mysqli_query($mysqli, $qry)) {
		return 1;  // Sucesso
	} else {
		return 0;  // Falha
	}
}

#diminuir saldo na api da fiverscan
function withdrawSaldo($email, $saldo)
{
	global $mysqli;

	// Verifica o saldo atual do usuário
	$qryCheckSaldo = "SELECT saldo FROM usuarios WHERE mobile = '" . $email . "'";
	$result = mysqli_query($mysqli, $qryCheckSaldo);

	if ($result && mysqli_num_rows($result) > 0) {
		$row = mysqli_fetch_assoc($result);
		$saldoAtual = $row['saldo'];

		// Verifica se o saldo é suficiente para o saque
		if ($saldoAtual >= $saldo) {
			// Monta a query de atualização do saldo
			$qry = "UPDATE usuarios SET saldo = saldo - '" . $saldo . "' WHERE mobile = '" . $email . "'";

			// Executa a consulta de atualização
			if (mysqli_query($mysqli, $qry)) {
				return 1;  // Sucesso
			} else {
				return 0;  // Falha na execução da query
			}
		} else {
			return -1;  // Saldo insuficiente
		}
	} else {
		return 0;  // Falha ao buscar o saldo ou usuário não encontrado
	}
}

/**
 * Adiciona saldo na conta do usuário formatando corretamente os valores
 * @param string $email Email ou mobile do usuário
 * @param float $valor Valor a ser adicionado ao saldo
 * @return int Retorna 1 em caso de sucesso, 0 em caso de falha
 */
function adicionarSaldoRecusa($email, $valor)
{
	global $mysqli;

	$valor_formatado = str_replace(',', '.', $valor);

	if (!is_numeric($valor_formatado)) {
		return 0;
	}

	$sql = $mysqli->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE mobile = ?");
	$sql->bind_param("ds", $valor_formatado, $email);

	if ($sql->execute()) {
		return 1;
	} else {
		return 0;
	}
}

function pegarLinkJogo($provedor, $game, $email, $saldo)
{
	global $data_fiverscanpanel, $ids;

	$keys = $data_fiverscanpanel;
	// Dados para o corpo da requisição em formato JSON
	$data = array(
		"method" => "game_launch",
		'agent_code' => $keys['agent_code'],
		'agent_token' => $keys['agent_token'],
		'user_code' => $email,
		"provider_code" => $provedor,
		"game_code" => $game,
		"lang" => "en"
		//"user_balance" => intval($saldo)
	);
	$json_data = json_encode($data);
	$response = enviarRequest($keys['url'], $json_data);
	$data = json_decode($response, true);
	//var_dump($data, $response);
	$games = array('gameURL' => $data['launch_url']);
	//$urlgamee = $games;
	return $games;
}
function generateTokenDrakon()
{
	global $data_drakon;

	// Verifica se as configurações estão definidas
	if (!isset($data_drakon['agent_token'], $data_drakon['agent_secret'], $data_drakon['url'])) {
		throw new Exception("Parâmetros de configuração ausentes em \$data_drakon.");
	}

	$curl = curl_init();

	$client_id = $data_drakon['agent_token'];
	$client_secret = $data_drakon['agent_secret'];
	$credentials = $client_id . ':' . $client_secret;
	$base64_credentials = base64_encode($credentials);

	curl_setopt_array($curl, [
		CURLOPT_URL => $data_drakon['url'] . "auth/authentication",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_POSTFIELDS => "                \n{\n    \"typeTransaction\": \"PIX\",\n\t\"statusTransaction\":\"PAID_OUT\",\n    \"idTransaction\": \"c5d4542d-54a6-4bd3-a3bf-447f2fc8f408\"\n}",
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_HTTPHEADER => [
			"Authorization: Bearer " . $base64_credentials,
			"Content-Type: application/json"
		],
	]);

	$response = curl_exec($curl);
	if ($response === false) {
		$err = curl_error($curl);
		curl_close($curl);
		throw new Exception("cURL Error: " . $err);
	}
	curl_close($curl);

	// Depuração: exibe a resposta bruta

	$responseDecoded = json_decode($response, true);
	if (json_last_error() !== JSON_ERROR_NONE) {
		throw new Exception("Erro ao decodificar JSON: " . json_last_error_msg());
	}

	if (isset($responseDecoded['access_token'])) {
		return $responseDecoded['access_token'];
	} else {
		throw new Exception('Falha ao obter o token de autenticação: ' . $response);
	}
}

function generateTokenRoyal()
{
	global $data_royal;

	// Verifica se as configurações estão definidas
	if (!isset($data_royal['agent_token'], $data_royal['agent_secret'], $data_royal['url'])) {
		throw new Exception("Parâmetros de configuração ausentes em \$data_royal.");
	}

	$curl = curl_init();

	$client_id = $data_royal['agent_token'];
	$client_secret = $data_royal['agent_secret'];
	$credentials = $client_id . ':' . $client_secret;
	$base64_credentials = base64_encode($credentials);

	curl_setopt_array($curl, [
		CURLOPT_URL => $data_royal['url'] . "auth/authentication",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_POSTFIELDS => "                \n{\n    \"typeTransaction\": \"PIX\",\n\t\"statusTransaction\":\"PAID_OUT\",\n    \"idTransaction\": \"c5d4542d-54a6-4bd3-a3bf-447f2fc8f408\"\n}",
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_HTTPHEADER => [
			"Authorization: Bearer " . $base64_credentials,
			"Content-Type: application/json"
		],
	]);

	$response = curl_exec($curl);
	if ($response === false) {
		$err = curl_error($curl);
		curl_close($curl);
		throw new Exception("cURL Error: " . $err);
	}
	curl_close($curl);

	// Depuração: exibe a resposta bruta

	$responseDecoded = json_decode($response, true);
	if (json_last_error() !== JSON_ERROR_NONE) {
		throw new Exception("Erro ao decodificar JSON: " . json_last_error_msg());
	}

	if (isset($responseDecoded['access_token'])) {
		return $responseDecoded['access_token'];
	} else {
		throw new Exception('Falha ao obter o token de autenticação: ' . $response);
	}
}
function pegarLinkJogoPlayFiver($provedor, $game, $email, $saldo)
{
	global $data_fiverscanpanel;
	$keys = $data_fiverscanpanel;
	$dataRequest = array(
		"agentToken" => $keys['agent_token'],
		"secretKey" => $keys['agent_secret'],
		"user_code" => $email,
		"game_code" => $game,
		"user_balance" => $saldo
	);

	$json_data = json_encode($dataRequest);
	$response = enviarRequest($keys['url'] . '/api/v2/game_launch', $json_data);
	$data = json_decode($response, true);
	$games = array('gameURL' => $data['launch_url']);
	return $games;
}
function pegarLinkJogoApiPhillyps($provedor, $game, $email, $saldo)
{
	global $data_apiphill, $ids;

	$keys = $data_apiphill;
	// var_dump($keys);
	// Dados para o corpo da requisição em formato JSON
	$data = array(
		'agentToken' => $keys['agent_token'],
		'secretKey' => $keys['agent_code'],
		'user_code' => $email,
		"provider_code" => $provedor,
		"game_code" => $game,
		"user_balance" => intval($saldo)
	);
	$json_data = json_encode($data);
	$response = enviarRequest($keys['url'] . 'game_launch', $json_data);
	// var_dump($response);
	$data = json_decode($response, true);
	//var_dump($data);
	$games = array('gameURL' => $data['launch_url']);
	//$urlgamee = $games;
	return $games;
}
//  CRIAR USER API FIVERSCAN
function enviarRequestDrakon($url, $config, $token)
{
	$ch = curl_init();
	$headerArray = [
		'Authorization: Bearer ' . $token,
		'Content-Type: application/json'
	];

	curl_setopt_array($ch, [
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_POSTFIELDS => $config,
		CURLOPT_HTTPHEADER => $headerArray
	]);

	$response = curl_exec($ch);
	if ($response === false) {
		$err = curl_error($ch);
		curl_close($ch);
		throw new Exception("cURL Error: " . $err);
	}
	curl_close($ch);
	return $response;
}

function pegarLinkDrakon($provedor, $game, $email, $saldo)
{
	global $data_drakon;

	$keys = $data_drakon;

	$token = generateTokenDrakon();
	if (!$token) {
		throw new Exception("Falha ao obter o token de autenticação.");
	}

	$data = array(
		'agent_code' => $keys['agent_code'],
		'agent_token' => $keys['agent_token'],
		'game_id' => $game,
		'type' => 'CHARGED',
		'currency' => 'BRL',
		'lang' => 'pt_BR',
		'user_id' => $email,
	);

	$json_data = json_encode($data);

	$url = rtrim($keys['url'], '/') . '/games/game_launch';

	$response = enviarRequestDrakon($url, $json_data, $token);

	$responseDecoded = json_decode($response, true);
	if (json_last_error() !== JSON_ERROR_NONE) {
		throw new Exception("Erro ao decodificar JSON: " . json_last_error_msg());
	}

	if (isset($responseDecoded['game_url'])) {
		return array('gameURL' => $responseDecoded['game_url']);
	}

	return false;
}
function pegarLinkJogoRoyal($provedor, $game, $email, $saldo)
{
	global $data_royal;

	$keys = $data_royal;

	$token = generateTokenRoyal();

	if (!$token) {
		throw new Exception("Falha ao obter o token de autenticação.");
	}

	$data = array(
		'agent_code' => $keys['agent_code'],
		'agent_token' => $keys['agent_token'],
		'game_code' => $game,
		'currency' => 'BRL',
		'lang' => 'pt_BR',
		'user_code' => $email,
		"user_balance" => intval($saldo)

	);

	$json_data = json_encode($data);

	$url = rtrim($keys['url'], '/') . '/games/game_launch';

	$response = enviarRequestDrakon($url, $json_data, $token);
	// var_dump($response);

	$responseDecoded = json_decode($response, true);
	if (json_last_error() !== JSON_ERROR_NONE) {
		throw new Exception("Erro ao decodificar JSON: " . json_last_error_msg());
	}

	if (isset($responseDecoded['gameUrl'])) {
		return array('gameURL' => $responseDecoded['gameUrl']);
	}

	return false;
}

function criarUsuarioAPI($email)
{
	global $data_fiverscanpanel;
	$keys = $data_fiverscanpanel;
	$postArray = [
		"method" => "game_launch",
		'agent_code' => $keys['agent_code'],
		'agent_token' => $keys['agent_token'],
		'user_code' => $email,
		"provider_code" => "PGSOFT",
		"game_code" => "fortune-tiger",
		"lang" => "en"
	];
	$jsonData = json_encode($postArray);
	$headerArray = ['Content-Type: application/json'];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $keys['url']);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$res = curl_exec($ch);
	curl_close($ch);
	// Verifique se houve algum erro durante a solicitação
	//$json = '{"status":1,"msg":"SUCCESS","fc_code":"fc104688","user_code":"claudio.web.dev@gmail.com","user_balance":0}';
	$data = json_decode($res, true);
	//var_dump($data);
	// Verifica se a decodificação foi bem-sucedida
	if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
		$SF = 0;
		die('Erro na decodificação JSON: ' . json_last_error_msg());
	}
	if ($data['status'] == 1 and $data['msg'] == "SUCCESS") {
		$SF = 1;
	} else {
		$SF = 0;
	}
	return $SF;
}
#=====================================================#
function afiliado_de_quem($refer)
{
	global $mysqli;
	$qry = "SELECT real_name FROM usuarios WHERE invite_code='" . $refer . "'";
	$res = mysqli_query($mysqli, $qry);
	$dinheiro = 'Sem afiliação'; // Valor padrão

	if ($res) {
		while ($row = mysqli_fetch_assoc($res)) {
			if (!empty($row['real_name'])) {
				$dinheiro = $row['real_name'];
			}
		}
	}

	return $dinheiro;
}
#=====================================================#
# DATA CONFIG SUITPAY
# saldo api fiverscan
function data_drakon()
{
	global $mysqli;
	$qry = "SELECT * FROM apidrakon WHERE id=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}
$data_drakon = data_drakon();


function data_royal()
{
	global $mysqli;
	$qry = "SELECT * FROM apiroyalgames WHERE id=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}
$data_royal = data_royal();
#=====================================================#
# DATA CONFIG
function data_afiliados_cpa_rev()
{
	global $mysqli;
	$qry = "SELECT * FROM afiliados_config WHERE id=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}
$data_afiliados_cpa_rev = data_afiliados_cpa_rev();
#=====================================================#
#criar financeiro
function criar_financeiro($id)
{
	global $mysqli;
	$sql1 = $mysqli->prepare("INSERT INTO financeiro (usuario,saldo,bonus) VALUES (?,0,0)");
	$sql1->bind_param("i", $id);
	if ($sql1->execute()) {
		$tr = 1; //certo
	} else {
		$tr = 0; //erro
	}
	return $tr;
}

# count saque
function tabelasaldouser($id)
{
	global $mysqli;
	$qry = "SELECT * FROM usuarios WHERE id='" . intval($id) . "'";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['saldo'] > 0) {
			$dinheiro = $row['saldo'];
		} else {
			$dinheiro = '0.00';
		}
	}
	return $dinheiro;
}
#=====================================================#
#criar financeiro
function criar_tokenrefer($id)
{
	global $mysqli;
	$aftoken = 'af' . $id . token_aff();
	$sql = $mysqli->prepare("UPDATE usuarios SET token_refer=? WHERE id=?");
	$sql->bind_param("si", $aftoken, $id);
	if ($sql->execute()) {
		$tr = 1; //certo
	} else {
		$tr = 0; //erro

	}
	return $tr;
}
#=====================================================#
// request curl (fiverscan)
function enviarRequest($url, $config)
{
	$ch = curl_init();
	$headerArray = ['Content-Type: application/json'];
	// Configurando as opções do cURL
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $config);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// Executando a requisição e obtendo a resposta
	$response = curl_exec($ch);
	// Fechando a conexão cURL
	curl_close($ch);
	return $response;
}
/**
 * Função que realiza a chamada GET para o endpoint de game_launch da API Drakon.
 *
 * @param array $game Associativo contendo ao menos o índice 'game_id'.
 * @return string|false Retorna a URL do jogo (game_url) se obtida, ou false em caso de falha.
 */
function gameLaunchDrakon($game)
{
	global $data_drakon; // Certifique-se de que $data_drakon esteja definido com, por exemplo, a URL base.

	// Obtém o token
	$token = generateTokenDrakon();
	if (!$token) {
		return false;
	}

	// Obtém o gateway
	$gateway = getGatewayDrakon();
	if (empty($gateway)) {
		return false;
	}

	// Prepara os parâmetros de consulta
	$params = [
		'agent_code' => $gateway['drakon_agent_code'],
		'agent_token' => $gateway['drakon_agent_token'],
		'game_id' => $game['game_id'],
		'type' => 'CHARGED',
		'currency' => 'BRL',
		'lang' => 'pt_BR',
		'user_id' => getAuthenticatedUserId(),
	];

	// Constrói a URL com os parâmetros (garanta que a URL base termine ou não com a barra conforme necessário)
	$url = rtrim($data_drakon['url'], '/') . '/games/game_launch?' . http_build_query($params);

	// Inicializa o cURL e configura a requisição GET
	$ch = curl_init();
	$headerArray = ['Authorization: Bearer ' . $token];
	curl_setopt_array($ch, [
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => $headerArray,
		CURLOPT_SSL_VERIFYPEER => false,
	]);

	$response = curl_exec($ch);
	if ($response === false) {
		$err = curl_error($ch);
		curl_close($ch);
		throw new Exception("cURL Error: " . $err);
	}
	curl_close($ch);

	// Decodifica a resposta JSON
	$responseDecoded = json_decode($response, true);
	if (json_last_error() !== JSON_ERROR_NONE) {
		throw new Exception("Erro ao decodificar JSON: " . json_last_error_msg());
	}
	// Debug: Você pode fazer var_dump($responseDecoded) para inspecionar a resposta
	// var_dump($responseDecoded);

	// Se a resposta contiver 'game_url', retorna-a; caso contrário, retorna false.
	if (!empty($responseDecoded['game_url'])) {
		return $responseDecoded['game_url'];
	}
	return false;
}
#=====================================================#
// saldo atual do user
function saldo_user($id)
{
	global $mysqli;
	$qry = "SELECT * FROM usuarios WHERE id='" . intval($id) . "'";
	$res = mysqli_query($mysqli, $qry);
	if (mysqli_num_rows($res) > 0) {
		$data = mysqli_fetch_assoc($res);
		$saldo_arr = array(
			"saldo" => $data['saldo'],
			"saldo_afiliado" => $data['saldo_afiliados']
		);
	} else {
		$saldo_arr = array(
			"saldo" => 0,
			"saldo_afiliado" => 0
		);
	}
	return $saldo_arr;
}
#=====================================================#
// atualiza saldo do user
function att_saldo_user($saldo, $id)
{
	global $mysqli;
	$id_user = intval($id);
	$sql = $mysqli->prepare("UPDATE usuarios SET saldo=? WHERE id=?");
	$sql->bind_param("di", $saldo, $id_user);
	if ($sql->execute()) {
		$rt = 1;
	} else {
		$rt = 0;
	}
	return $rt;
}
#=====================================================#
// financeiro user atual do user
function financeiro_saldo_user($id)
{
	global $mysqli;
	$qry = "SELECT * FROM financeiro WHERE usuario='" . intval($id) . "'";
	$res = mysqli_query($mysqli, $qry);
	if (mysqli_num_rows($res) > 0) {
		$saldo = mysqli_fetch_assoc($res);
	} else {
		$saldo = 0;
	}
	return $saldo;
}
#=====================================================#
//  se exisitr refer 1
function pegar_refer($refer)
{
	global $mysqli;
	$qry = "SELECT * FROM usuarios WHERE token_refer='" . $refer . "'";
	$res = mysqli_query($mysqli, $qry);
	if (mysqli_num_rows($res) > 0) {
		$ex_refer = 1;
	} else {
		$ex_refer = 0;
	}
	return $ex_refer;
}
#=====================================================#
#=====================================================#
//  DELETAR USER
function deletar_user($id)
{
	global $mysqli;
	$sql = $mysqli->prepare("DELETE FROM  usuarios WHERE id=?");
	$sql->bind_param("i", $id);
	$sql->execute();

	$sql99 = $mysqli->prepare("DELETE FROM  financeiro WHERE usuario=?");
	$sql99->bind_param("i", $id);
	$sql99->execute();
}
#=====================================================#
function enviarRequest_PAYMENT($url, $header, $data = null)
{
	$ch = curl_init();
	$data_json = json_encode($data);

	// Configurando as opções do cURL
	curl_setopt($ch, CURLOPT_URL, $url);
	if (!$data == null) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
	}
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// Executando a requisição e obtendo a resposta
	$response = curl_exec($ch);

	// Fechando a conexão cURL
	curl_close($ch);

	return $response;
}
#=====================================================#
function requestToken_PAYMENT($url, $header, $data)
{
	$ch = curl_init();

	// Configurando as opções do cURL
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// Executando a requisição e obtendo a resposta
	$response = curl_exec($ch);

	// Fechando a conexão cURL
	curl_close($ch);

	return $response;
}
#=====================================================#
#request pix
function request_paymentPIX($transactionId)
{
	global $data_suitpay, $tipoAPI_SUITPAY;
	if ($tipoAPI_SUITPAY == 0) {
		$url = 'https://sandbox.ws.suitpay.app/api/v1/gateway/consult-status-transaction';
		$data = array(
			'typeTransaction' => "PIX",
			'idTransaction' => $transactionId
		);
		$header = array(
			'ci: testesandbox_1687443996536',
			'cs: 5b7d6ed3407bc8c7efd45ac9d4c277004145afb96752e1252c2082d3211fe901177e09493c0d4f57b650d2b2fc1b062d',
			'Content-Type: application/json',
		);
	} else {
		$url = $data_suitpay['url'] . '/api/v1/gateway/consult-status-transaction';
		$data = array(
			'typeTransaction' => "PIX",
			'idTransaction' => $transactionId
		);
		$header = array(
			'ci: ' . $data_suitpay['client_id'],
			'cs: ' . $data_suitpay['client_secret'],
			'Content-Type: application/json'
		);
	}
	$response = enviarRequest_PAYMENT($url, $header, $data);
	$dados = json_decode($response, true);
	return $dados;
}
#=====================================================#
# coun refer direto
function count_refer_direto($refer)
{
	global $mysqli;
	$qry = "SELECT * FROM usuarios WHERE invitation_code='" . $refer . "'";
	$res = mysqli_query($mysqli, $qry);
	$ex_refer = mysqli_num_rows($res);
	return $ex_refer;
}
#=====================================================#
# count saque
function total_saques_id($id)
{
	global $mysqli;
	$qry = "SELECT SUM(valor) as total_soma FROM solicitacao_saques WHERE id_user='" . $id . "'";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_soma'] > 0) {
			$dinheiro = $row['total_soma'];
		} else {
			$dinheiro = '0.00';
		}
	}
	return $dinheiro;
}
#=====================================================#
# count depositos
function total_dep_id($id)
{
	global $mysqli;
	$qry = "SELECT SUM(valor) as total_soma FROM transacoes WHERE usuario='" . $id . "' AND tipo='deposito'";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_soma'] > 0) {
			$dinheiro = $row['total_soma'];
		} else {
			$dinheiro = '0.00';
		}
	}
	return $dinheiro;
}

function total_dep_pagos_id($id)
{
	global $mysqli;
	$qry = "SELECT SUM(valor) as total_soma FROM transacoes WHERE usuario='" . $id . "' AND tipo='deposito' AND status='pago'";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_soma'] > 0) {
			$dinheiro = $row['total_soma'];
		} else {
			$dinheiro = '0.00';
		}
	}
	return $dinheiro;
}

function total_dep_afiliado($id)
{
	global $mysqli;
	$qry = "SELECT SUM(valor) as total_soma FROM transacoes WHERE usuario IN (SELECT id FROM usuarios where invitation_code = '" . $id . "') AND tipo='deposito' AND status='pago'";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_soma'] > 0) {
			$dinheiro = $row['total_soma'];
		} else {
			$dinheiro = '0.00';
		}
	}
	return $dinheiro;
}
#=====================================================#
# SUM TOTAL ID CPA/REV
function total_CPA_REV_id($id)
{
	global $mysqli;
	$qry = "SELECT SUM(valor) as total_soma FROM pay_valores_cassino WHERE id_user='" . $id . "' AND tipo=0 OR tipo=1";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_soma'] > 0) {
			$dinheiro = $row['total_soma'];
		} else {
			$dinheiro = '0.00';
		}
	}
	return $dinheiro;
}

function total_CPA_id($id)
{
	global $mysqli;
	$qry = "SELECT SUM(valor) as total_soma FROM pay_valores_cassino WHERE id_user='" . $id . "' AND tipo=0";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_soma'] > 0) {
			$dinheiro = $row['total_soma'];
		} else {
			$dinheiro = '0.00';
		}
	}
	return $dinheiro;
}

function total_REV_id($id)
{
	global $mysqli;
	$qry = "SELECT SUM(valor) as total_soma FROM pay_valores_cassino WHERE id_user='" . $id . "' AND tipo=1";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_soma'] > 0) {
			$dinheiro = $row['total_soma'];
		} else {
			$dinheiro = '0.00';
		}
	}
	return $dinheiro;
}

#=====================================================#
# DATA USER ID
function data_user_id($id)
{
	global $mysqli;
	$qry = "SELECT * FROM usuarios WHERE id='" . $id . "'";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}

function gamecode($id)
{
	global $mysqli;
	$qry = "SELECT game_code FROM games WHERE id='" . $id . "'";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data['game_code'];
}

function gameprovider($id)
{
	global $mysqli;
	$qry = "SELECT provider FROM games WHERE game_code='" . $id . "'";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data['provider'];
}
function gametype($id)
{
	global $mysqli;
	$qry = "SELECT type FROM games WHERE game_code='" . $id . "'";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data['type'];
}

function localizarchavepix($id)
{
	global $mysqli;
	$qry = "SELECT pix_account FROM metodos_pagamentos WHERE id='" . $id . "'";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data['pix_account'];
}

function localizarchavepixall($id)
{
	global $mysqli;
	$qry = "SELECT pix_account,pix_id,realname,flag FROM metodos_pagamentos WHERE id='" . $id . "'";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}

#=====================================================#
#inserir saldo
function adicionarsaldo($id, $valor)
{
	global $mysqli;
	$qry = "UPDATE financeiro SET saldo= saldo + '" . $valor . "' WHERE usuario='" . $id . "'";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}

function requestaddsaldo($email, $valor)
{
	$data = array(
		'user_code' => $email,
		'valor' => $valor
	);
	$json_data = json_encode($data);
	$response = enviarRequest('https://api.zenbet.online/api/v1/adicionarsaldo', $json_data);
	$dados = json_decode($response, true);
	return $dados;
}

#=====================================================#
#inserir saldo
function insert_payment_adm($id, $email, $valor)
{
	global $mysqli;
	$tokentrans = '#pixdinamic-' . rand(99, 99999);
	$data_hora = date('Y-m-d H:i:s');
	$sql1 = $mysqli->prepare("INSERT INTO transacoes (transacao_id,usuario,valor,data_hora,tipo,status,code) VALUES (?,?,?,?,'deposito','pago','dinamico')");
	$sql1->bind_param("ssss", $tokentrans, $id, $valor, $data_hora);
	#ENVIA SALDO VIA API
	$retorna_insert_saldo_suit_pay = enviarSaldo($email, $valor);
	if ($retorna_insert_saldo_suit_pay['status'] == 1 and $retorna_insert_saldo_suit_pay['msg'] == "SUCCESS" and $sql1->execute()) {
		$ert = 1;
	} else {
		$ert = 0;
	}
	return $ert;
}


function numero_total_dep($id)
{
	global $mysqli;
	$qry = "SELECT COUNT(*) as total_count FROM transacoes WHERE usuario IN (SELECT id FROM usuarios WHERE invitation_code = '" . $id . "') AND tipo='deposito' AND status='pago'";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_count'] > 0) {
			$total_count = $row['total_count'];
		} else {
			$total_count = 0;
		}
	}
	return $total_count;
}

#retirar saldo
function retirarsaldo($email, $valor)
{
	$data = array(
		'user_code' => $email,
		'valor' => $valor
	);
	$json_data = json_encode($data);
	$response = enviarRequest('https://api.zenbet.online/api/v1/removersaldo', $json_data);
	$dados = json_decode($response, true);
	return $dados;
}
#=====================================================#
#contar visitas
function visitas_count($tipo)
{
	global $mysqli;
	$data_hoje = date("Y-m-d");
	if ($tipo == 'diario') {
		$qry = "SELECT * FROM visita_site WHERE data_cad='" . $data_hoje . "'";
		$res = mysqli_query($mysqli, $qry);
		$count = mysqli_num_rows($res);
	} elseif ($tipo == 'total') {
		$qry = "SELECT * FROM visita_site";
		$res = mysqli_query($mysqli, $qry);
		$count = mysqli_num_rows($res);
	} else {
		$count = 0;
	}
	return $count;
}
function visitas_count2($tipo)
{
	global $mysqli;

	if ($tipo == 'diario') {
		$data_hoje = date("Y-m-d");
		$qry = "SELECT cidade, estado, COUNT(*) as total 
                FROM visita_site 
                WHERE data_cad='$data_hoje' 
                GROUP BY cidade, estado 
                ORDER BY total DESC 
                LIMIT 1";
	} elseif ($tipo == 'total') {
		$qry = "SELECT cidade, estado, COUNT(*) as total 
                FROM visita_site 
                GROUP BY cidade, estado 
                ORDER BY total DESC 
                LIMIT 1";
	} else {
		return ['cidade' => null, 'estado' => null, 'total' => 0];
	}

	$res = mysqli_query($mysqli, $qry);

	if ($res && mysqli_num_rows($res) > 0) {
		$row = mysqli_fetch_assoc($res);
		return [
			'cidade' => $row['cidade'],
			'estado' => $row['estado'],
			'total' => $row['total']
		];
	} else {
		return ['cidade' => null, 'estado' => null, 'total' => 0];
	}
}

#=====================================================#
# busca por token retorn o id
function busca_id_por_refer($token)
{
	global $mysqli;

	$qry = "SELECT * FROM usuarios WHERE token_refer='" . $token . "'";
	$res = mysqli_query($mysqli, $qry);
	if (mysqli_num_rows($res) > 0) {
		$data = mysqli_fetch_assoc($res);
		$count = $data['id'];
	} else {
		$count = 0;
	}
	return $count;
}
#=====================================================#
function generateQRCode_pix($data)
{
	// Carregue a biblioteca PHP QR Code
	require_once __DIR__ . '/../libraries/phpqrcode/qrlib.php';
	// Caminho onde você deseja salvar o arquivo PNG do QRCode (opcional)
	$file = '../../uploads/qrcode.png';
	// Gere o QRCode
	QRcode::png($data, $file);
	// Carregue o arquivo PNG do QRCode
	$qrCodeImage = file_get_contents($file);
	// Converta a imagem para base64
	$base64QRCode = base64_encode($qrCodeImage);
	return $base64QRCode;
}
#=====================================================#
# busca por ALERT DEP PENDENTES id
function busca_dep_pendentes($id)
{
	global $mysqli;
	$qry = "SELECT * FROM transacoes WHERE usuario='" . $id . "' AND tipo='deposito' AND status='processamento'";
	$res = mysqli_query($mysqli, $qry);
	if (mysqli_num_rows($res) > 0) {
		$data = 1;
	} else {
		$data = 0;
	}
	return $data;
}

// Função para buscar depósitos por dia
function depositos_por_dia()
{
	global $mysqli;
	// Usamos DATE() para extrair apenas a data, ignorando a hora
	$qry = "SELECT DATE(data_hora) as dia, COUNT(*) as total FROM transacoes WHERE status = 'pago' AND tipo = 'deposito' GROUP BY DATE(data_hora) ORDER BY dia DESC LIMIT 7";
	$result = mysqli_query($mysqli, $qry);

	$dados = [];
	if ($result && mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			$dados[] = [
				'dia' => $row['dia'],          // Retorna a data no formato YYYY-MM-DD
				'total' => intval($row['total']) // Conta a quantidade de depósitos
			];
		}
	}
	return $dados;
}



// Função para buscar saques por dia
function saques_por_dia()
{
	global $mysqli;
	$qry = "SELECT DATE(data_cad) as dia, COUNT(*) as total FROM solicitacao_saques WHERE status = 1 GROUP BY DATE(data_cad) ORDER BY dia DESC LIMIT 7";
	$result = mysqli_query($mysqli, $qry);

	$dados = [];
	if ($result && mysqli_num_rows($result) > 0) {
		while ($row = mysqli_fetch_assoc($result)) {
			$dados[] = [
				'dia' => $row['dia'],
				'total' => intval($row['total'])  // Conta a quantidade de saques
			];
		}
	}
	return $dados;
}

// ####################################################### //
##############      INT. PAYIGAMING     ##################

// function enviarSaldo($email, $saldo)
// {
// 	global $data_fiverscanpanel;
// 	$keys = $data_fiverscanpanel;
// 	$url = $keys['url'];
// 	$num = floatval($saldo);

// 	$data = array(
// 		"method" => "user_deposit",
// 		'agent_code' => $keys['agent_code'],
// 		'agent_token' => $keys['agent_token'],
// 		'user_code' => $email,
// 		"amount" => $num
// 	);

// 	$json_data = json_encode($data);
// 	$response = enviarRequest('https://api.payigaming.com.br/', $json_data);
// 	$data = json_decode($response, true);

// 	// Supondo que a API retorne um campo 'status' com 'success' ou 'error'
// 	if (isset($data['msg']) && $data['msg'] === 'SUCCESS') {
// 		return 1; // Sucesso
// 	} else {
// 		return 0; // Falha
// 	}
// }

// #diminuir saldo na api da fiverscan
// function withdrawSaldo($email, $saldo)
// {
// 	global $data_fiverscanpanel;
// 	$keys = $data_fiverscanpanel;
// 	$url = $keys['url'];
// 	$num = floatval($saldo);
// 	$data = array(
// 		"method" => "user_withdraw",
// 		'agent_code' => $keys['agent_code'],
// 		'agent_token' => $keys['agent_token'],
// 		'user_code' => $email,
// 		'amount' => $num
// 	);
// 	$json_data = json_encode($data);
// 	$response = enviarRequest('https://api.payigaming.com.br/', $json_data);
// 	$data = json_decode($response, true);
// 	return $data;
// }


// function pegarLinkJogo($provedor, $game, $email)
// {
// 	global $data_fiverscanpanel, $ids;
// 	//$saldo = saldoapi($_SESSION['data_user']['email']);
// 	// $saldo = saldoapi($_SESSION['data_user'][0]['email']);

// 	$keys = $data_fiverscanpanel;

// 	// Dados para o corpo da requisição em formato JSON
// 	$data = array(
// 		"method" => "game_launch",
// 		'agent_code' => $keys['agent_code'],
// 		'agent_token' => $keys['agent_token'],
// 		'user_code' => $email,
// 		"provider_code" => $provedor,
// 		"game_code" => $game,
// 		"lang" => "pt"
// 	);
// 	$json_data = json_encode($data);
// 	$response = enviarRequest('https://api.payigaming.com.br/', $json_data);
// 	$data = json_decode($response, true);
// 	//var_dump($data);
// 	$games = array('gameURL' => $data['launch_url']);
// 	//$urlgamee = $games;
// 	return $games;
// }

// //  CRIAR USER API FIVERSCAN
// function criarUsuarioAPI($email)
// {
// 	global $data_fiverscanpanel;

// 	$keys = $data_fiverscanpanel;

// 	$postArray = [
// 		"method" => "user_create",
// 		'agent_code' => $keys['agent_code'],
// 		'agent_token' => $keys['agent_token'],
// 		'user_code' => $email
// 	];
// 	$jsonData = json_encode($postArray);
// 	$headerArray = ['Content-Type: application/json'];
// 	$ch = curl_init();
// 	curl_setopt($ch, CURLOPT_URL, 'https://api.payigaming.com.br/');
// 	curl_setopt($ch, CURLOPT_POST, 1);
// 	curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
// 	curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
// 	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
// 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// 	$res = curl_exec($ch);
// 	curl_close($ch);
// 	// Verifique se houve algum erro durante a solicitação
// 	//$json = '{"status":1,"msg":"SUCCESS","fc_code":"fc104688","user_code":"claudio.web.dev@gmail.com","user_balance":0}';
// 	$data = json_decode($res, true);

// 	//var_dump($data);
// 	// Verifica se a decodificação foi bem-sucedida
// 	if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
// 		$SF = 0;
// 		die('Erro na decodificação JSON: ' . json_last_error_msg());
// 	}
// 	if ($data['status'] == 1 and $data['msg'] == "SUCCESS") {
// 		$SF = 1;
// 	} else {
// 		$SF = 0;
// 	}
// 	return $SF;
// }
