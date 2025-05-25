<?php
session_start();
require 'db.php'; // Connessione $mysqli (mysqli)

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$famiglia_id = intval($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_sostanza_id = isset($_POST['tipo_sostanza_id']) ? intval($_POST['tipo_sostanza_id']) : 0;
    $quantita = isset($_POST['quantita']) ? floatval($_POST['quantita']) : 0;

    if ($tipo_sostanza_id <= 0 || $quantita <= 0) {
        die("Errore: dati mancanti o non validi.");
    }

    // Controlla che la sostanza sia abilitata
    $stmt = $mysqli->prepare("SELECT abilitata FROM tipi_sostanze WHERE id = ?");
    $stmt->bind_param("i", $tipo_sostanza_id);
    $stmt->execute();
    $stmt->bind_result($abilitata);
    if (!$stmt->fetch()) {
        $stmt->close();
        die("Errore: sostanza non trovata.");
    }
    $stmt->close();

    if (!$abilitata) {
        die("Errore: la sostanza non è abilitata.");
    }

    // Inserisci la sostanza con quantità
    $stmt = $mysqli->prepare("INSERT INTO sostanze (famiglia_id, tipo_sostanza_id, quantita, data_inserimento) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iid", $famiglia_id, $tipo_sostanza_id, $quantita);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: lista_sostanze.php?msg=aggiunta_successo");
        exit();
    } else {
        echo "Errore nell'inserimento: " . htmlspecialchars($stmt->error);
    }
}
?>
