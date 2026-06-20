<?php
include_once "../dash/services-prod/prod.php";
include_once "../dash/services/database.php";
include_once "../dash/services/funcao.php";
include_once "../dash/services/crud.php";

global $mysqli;

// Carrega o arquivo JSON
$jsonString = file_get_contents("games.json");
$data = json_decode($jsonString, true);

if (!$data || !isset($data['data']['labels'])) {
    die("JSON inválido ou estrutura incorreta");
}

$mysqli->set_charset('latin1');

// Percorre todas as labels do JSON
foreach ($data['data']['labels'] as $label) {
    // Verifica se existe uma lista de jogos na label
    if (!isset($label['list']))
        continue;

    // Percorre cada jogo na lista
    foreach ($label['list'] as $game) {
        if (!isset($game['game_name']) || !isset($game['img_url']))
            continue;

        // Converte o nome para o charset do banco
        $gameNameJson = iconv("UTF-8", "latin1//TRANSLIT", $game['game_name']);
        $imgUrl = $game['img_url'];

        // Busca o jogo no banco pelo nome
        $stmt = $mysqli->prepare("SELECT id FROM games WHERE game_name = ?");
        if (!$stmt) {
            error_log("Erro no prepare (SELECT): " . $mysqli->error);
            continue;
        }

        $stmt->bind_param("s", $gameNameJson);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $gameId = $row['id'];

            // Atualiza o banner
            $updateStmt = $mysqli->prepare("UPDATE games SET banner = ? WHERE id = ?");
            if (!$updateStmt) {
                error_log("Erro no prepare (UPDATE): " . $mysqli->error);
                continue;
            }

            $updateStmt->bind_param("si", $imgUrl, $gameId);
            if ($updateStmt->execute()) {
                echo "Banner atualizado para [ID: {$gameId}] - [URL: {$imgUrl}]\n";
            } else {
                error_log("Erro ao atualizar: " . $updateStmt->error);
            }
            $updateStmt->close();
        } else {
            echo "Jogo não encontrado: {$gameNameJson}\n";
        }

        $stmt->close();
    }
}

echo "Processo concluído!";
?>