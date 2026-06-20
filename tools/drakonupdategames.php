<?php
include_once "../dash/services-prod/prod.php";
include_once "../dash/services/database.php";
include_once "../dash/services/funcao.php";
include_once "../dash/services/crud.php";

global $mysqli;

set_time_limit(30);

function normalizeGameName($name)
{
    $name = strtolower($name);
    $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
    $name = preg_replace('/[^a-z0-9]/', '', $name);
    return $name;
}

$jsonString = file_get_contents("resultado.json");
$data = json_decode($jsonString, true);

if (!$data || !isset($data['games'])) {
    die("JSON inválido ou estrutura incorreta");
}

$mysqli->set_charset('latin1');

$stmt = $mysqli->prepare("SELECT id, game_name, banner, provider, game_code FROM games");
if (!$stmt) {
    die("Erro ao preparar consulta: " . $mysqli->error);
}

$stmt->execute();
$result = $stmt->get_result();
$gamesIndex = [];

while ($row = $result->fetch_assoc()) {
    $normalizedName = normalizeGameName($row['game_name']);
    $normalizedProvider = strtoupper(str_replace(' ', '', $row['provider']));
    $gamesIndex[$normalizedName . '_' . $normalizedProvider] = $row;
}

$stmt->close();

foreach ($data['games'] as $game) {
    if (!isset($game['game_name']) || !isset($game['banner']) || !isset($game['provider_game']) || !isset($game['game_code'])) {
        continue;
    }

    $gameNameJson = iconv("UTF-8", "latin1//TRANSLIT", $game['game_name']);
    $normalizedGameName = normalizeGameName($game['game_name']);
    $banner = $game['banner'];
    $provider = strtoupper(str_replace(' ', '', $game['provider_game']));
    $gameCode = $game['game_code'];

    $indexKey = $normalizedGameName . '_' . $provider;

    if (isset($gamesIndex[$indexKey])) {
        $row = $gamesIndex[$indexKey];

        if ($row['game_code'] === $gameCode) {
            echo "Jogo já existe com mesmo game_code [ID: {$row['id']}] - [Nome: {$gameNameJson}]\n";
            continue;
        }

        if (!empty($row['banner'])) {
            $banner = $row['banner'];
        }

        $updateStmt = $mysqli->prepare("UPDATE games SET type = 'DRAKON', game_code = ?, provider = ?, banner = ? WHERE id = ?");
        if (!$updateStmt) {
            error_log("Erro no prepare (UPDATE): " . $mysqli->error);
            continue;
        }

        $updateStmt->bind_param("sssi", $gameCode, $provider, $banner, $row['id']);
        if ($updateStmt->execute()) {
            echo "Jogo atualizado para DRAKON [ID: {$row['id']}] - [Nome: {$gameNameJson}]\n";
        } else {
            error_log("Erro ao atualizar: " . $updateStmt->error);
        }
        $updateStmt->close();
    } else {
        $insertStmt = $mysqli->prepare("INSERT INTO games (game_name, banner, status, provider, popular, type, game_type, game_code) VALUES (?, ?, 1, ?, 0, 'DRAKON', 3, ?)");
        if (!$insertStmt) {
            error_log("Erro no prepare (INSERT): " . $mysqli->error);
            continue;
        }

        $insertStmt->bind_param("ssss", $gameNameJson, $banner, $provider, $gameCode);
        if ($insertStmt->execute()) {
            $newId = $mysqli->insert_id;
            echo "Novo jogo criado [ID: {$newId}] - [Nome: {$gameNameJson}]\n";
        } else {
            error_log("Erro ao inserir: " . $insertStmt->error);
        }
        $insertStmt->close();
    }
}

echo "Processo concluído!";
?>