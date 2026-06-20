<?php
include_once "../dash/services-prod/prod.php";
include_once "../dash/services/database.php";
include_once "../dash/services/funcao.php";
include_once "../dash/services/crud.php";

global $mysqli;

// Carrega o arquivo JSON
$jsonString = file_get_contents("playfiver.json");
$data = json_decode($jsonString, true);

if (!$data || !isset($data['games'])) {
    die("JSON inválido ou estrutura incorreta");
}

$mysqli->set_charset('latin1');

foreach ($data['games'] as $game) {
    if (!isset($game['game_name'], $game['game_code'], $game['img_url'], $game['provider'])) {
        error_log("Jogo incompleto: " . print_r($game, true));
        continue;
    }

    $gameName = iconv("UTF-8", "latin1//TRANSLIT", $game['game_name']);
    $gameCode = $game['game_code'];
    $provider = iconv("UTF-8", "latin1//TRANSLIT", $game['provider']);
    $banner = $game['img_url'];

    $status = 1;
    $popular = 0;
    $type = '';
    $gameType = 3;

    $stmt = $mysqli->prepare("SELECT id FROM games WHERE game_code = ?");
    $stmt->bind_param("s", $gameCode);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        echo "Jogo já existe: {$gameCode} - {$gameName}\n";
        $stmt->close();
        continue;
    }
    $stmt->close();

    $insert = $mysqli->prepare("
        INSERT INTO games (
            game_code, 
            game_name, 
            banner, 
            status, 
            provider, 
            popular, 
            type, 
            game_type
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$insert) {
        error_log("Erro no prepare (INSERT): " . $mysqli->error);
        continue;
    }

    $insert->bind_param(
        "sssisssi",  // Tipos: s=string, i=integer
        $gameCode,
        $gameName,
        $banner,
        $status,
        $provider,
        $popular,
        $type,
        $gameType
    );

    if ($insert->execute()) {
        echo "Inserido: {$gameCode} - {$gameName}\n";
    } else {
        error_log("Erro ao inserir: " . $insert->error);
    }
    $insert->close();
}

echo "Processo concluído! Total de jogos processados: " . count($data['games']);
?>