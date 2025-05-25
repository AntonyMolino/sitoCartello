<!DOCTYPE html>
<html lang="it">


<head>
    <meta charset="UTF-8">
    <title>Visualizza - Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            padding: 30px;
            text-align: center;
        }

        .container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
            max-width: 650px;
            margin: 0 auto;
        }

        a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

        #result {
            margin-top: 20px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            max-height: 60vh;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Resoconto:</h1>

        <?php
        session_start();
        require 'db.php';

        // Controllo login
        if (!isset($_SESSION["username"])) {
            header("Location: index.php");
            exit;
        }

        // Controllo admin
        if (!isset($_SESSION["is_admin"]) || !$_SESSION["is_admin"]) {
            echo "Accesso negato.";
            exit;
        }

        $famiglie = [];
        if (isset($_GET['data_da']) && isset($_GET['data_a'])) {
            $data_da = $_GET['data_da'];
            $data_a = $_GET['data_a'];

            // Controllo semplice: data_da deve essere <= data_a
            if ($data_da <= $data_a) {
                $sql = "
            SELECT 
            u.nomeFamiglia, 
            s.user_id,
            s.quantita,
            ts.nome AS droghe,
            (s.quantita * ts.prezzo) AS prezzo_totale,
             s.data_inserimento
                FROM sostanze s
                JOIN users u ON s.user_id = u.id
                JOIN tipi_sostanze ts ON s.tipo_sostanza_id = ts.id
                WHERE s.data_inserimento BETWEEN ? AND ?
                ORDER BY nomeFamiglia ASC
                ";
                $stmt = $pdo->prepare($sql);
                // Metto 00:00:00 per inizio giornata, 23:59:59 per fine giornata
                $stmt->execute([$data_da . ' 00:00:00', $data_a . ' 23:59:59']);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }



        foreach ($result as $row) {
            $fam = $row['nomeFamiglia'];
            $droga = $row['droghe'];
            $quantita = $row['quantita'];
            $prezzo = $row['prezzo_totale'];

            if (!isset($famiglie[$fam])) {
                $famiglie[$fam] = [
                    'dettagli' => '',
                    'totaleNetto' => 0,
                    'quantitaTotale' => 0
                ];
            }

            $famiglie[$fam]['dettagli'] .= "• {$quantita} {$droga} (" . number_format($prezzo, 2) . "€)<br>";
            $famiglie[$fam]['totaleNetto'] += $prezzo;
            $famiglie[$fam]['quantitaTotale'] += $quantita;
        }

        // Ordina le famiglie per quantità totale decrescente
        uasort($famiglie, function ($a, $b) {
            return $b['quantitaTotale'] <=> $a['quantitaTotale'];
        });

        echo "<div id='result'>";
        foreach ($famiglie as $nomeFamiglia => $fam) {
            $totale = number_format($fam['totaleNetto'], 2);
            echo "<b><div class='famiglia'>{$nomeFamiglia} - {$totale} €</div></b>";
            echo "<div class='dettagli'>{$fam['dettagli']}</div>";

            if ($fam['totaleNetto'] >= 0) {
                echo "<div class='totale-netto'>➡️ Totale netto: {$totale}€</div>";
            } else {
                echo "<div class='totale-netto'>🔻 Da prelevare: " . number_format(abs($fam['totaleNetto']), 2) . "€</div>";
            }

            echo "<br>";
        }
        echo "</div>";
        ?>

        <a href="dashboard.php">← Torna alla Dashboard</a>
    </div>
</body>

</html>