<!DOCTYPE html>
<html lang="it">


<head>
    <meta charset="UTF-8">
    <title>Visualizza - Admin</title>
    <style>
        #bottone {}

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

        form {
            margin-top: 40px;
            text-align: left;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 8px #aaa;
        }

        form label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        form select,
        form input[type="number"],
        form input[type="date"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        form input[type="submit"] {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .message {
            margin-top: 15px;
            color: green;
            font-weight: bold;
        }

        .error {
            color: red;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php
        $data_da = htmlspecialchars($_GET['data_da'] ?? '');
        $data_a = htmlspecialchars($_GET['data_a'] ?? '');
        $refresh_url = "visualizza.php?data_da=$data_da&data_a=$data_a";
        ?>

        <h1>Resoconto:</h1>
        <a href="<?= $refresh_url ?>" style="
    padding: 10px 15px;
    background-color: #007BFF;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: bold;
    display: inline-block;
    margin-top: 20px;
">🔄 Ricarica pagina</a>

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
        $message = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_id = $_POST['user_id'] ?? null;
            $tipo_sostanza_id = $_POST['tipo_sostanza_id'] ?? null;
            $quantita = $_POST['quantita'] ?? null;
            $data_inserimento = $_POST['data_inserimento'] ?? null;
            $is_sanzione = isset($_POST['is_sanzione']) ? 1 : 0;

            if ($user_id && $tipo_sostanza_id && $quantita && $data_inserimento) {
                $stmt = $pdo->prepare("INSERT INTO sostanze (user_id, tipo_sostanza_id, quantita, data_inserimento, is_sanzione) VALUES (?, ?, ?, ?, ?)");
                $success = $stmt->execute([$user_id, $tipo_sostanza_id, $quantita, $data_inserimento . ' 00:00:00', $is_sanzione]);
                if ($success) {
                    $message = "Inserimento avvenuto con successo.";
                } else {
                    $message = "Errore nell'inserimento.";
                }
            } else {
                $message = "Compila tutti i campi.";
            }
        }

        $famiglie = [];

        if (isset($_GET['data_da']) && isset($_GET['data_a'])) {
            $data_da = $_GET['data_da'];
            $data_a = $_GET['data_a'];

            if ($data_da <= $data_a) {
                // Sostanze NON sanzione
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
        AND (s.is_sanzione IS NULL OR s.is_sanzione = 0)
        ORDER BY nomeFamiglia ASC";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$data_da . ' 00:00:00', $data_a . ' 23:59:59']);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Sanzioni
                $sqlSanzioni = "
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
        AND s.is_sanzione = 1
        ORDER BY nomeFamiglia ASC";

                $stmtS = $pdo->prepare($sqlSanzioni);
                $stmtS->execute([$data_da . ' 00:00:00', $data_a . ' 23:59:59']);
                $resultSanzioni = $stmtS->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        // Elaborazione sostanze normali
        foreach ($result as $row) {
            $fam = $row['nomeFamiglia'];
            $droga = $row['droghe'];
            $quantita = $row['quantita'];
            $prezzo = $row['prezzo_totale'];

            if (!isset($famiglie[$fam])) {
                $famiglie[$fam] = [
                    'dettagli' => '',
                    'sanzioni_droghe' => '',
                    'totaleSanzioni' => 0,
                    'totaleNetto' => 0,
                    'quantitaTotale' => 0
                ];
            }

            $famiglie[$fam]['dettagli'] .= "• {$quantita} {$droga} (" . number_format($prezzo, 2, ',', '.') . "€)<br>";
            $famiglie[$fam]['totaleNetto'] += $prezzo;
            $famiglie[$fam]['quantitaTotale'] += $quantita;
        }

        // Elaborazione sanzioni
        foreach ($resultSanzioni as $row) {
            $fam = $row['nomeFamiglia'];
            $droga = $row['droghe'];
            $quantita = $row['quantita'];
            $prezzo = $row['prezzo_totale'];

            if (!isset($famiglie[$fam])) {
                $famiglie[$fam] = [
                    'dettagli' => '',
                    'sanzioni_droghe' => '',
                    'totaleSanzioni' => 0,
                    'totaleNetto' => 0,
                    'quantitaTotale' => 0
                ];
            }

            $famiglie[$fam]['sanzioni_droghe'] .= "• {$quantita} {$droga} (GRATIS)<br>";
            $famiglie[$fam]['totaleSanzioni'] += $prezzo * 0.8; // Applica l'80%
        }

        // Ordina le famiglie
        uasort($famiglie, function ($a, $b) {
            return $b['quantitaTotale'] <=> $a['quantitaTotale'];
        });

        // Output HTML
        echo "<div id='result'>";
        foreach ($famiglie as $nomeFamiglia => $fam) {
            if (isset($fam['totaleSanzioni'])) {
                $fam['totaleNetto'] -= $fam['totaleSanzioni'];

            }
            $totale = number_format($fam['totaleNetto'], 2, ',', '.');
            $totSanzioni = number_format($fam['totaleSanzioni'], 2, ',', '.');

            echo "<b><div class='famiglia'>{$nomeFamiglia} – {$totale}€</div></b>";
            echo "<div class='dettagli'>{$fam['dettagli']}</div>";

            if (!empty($fam['sanzioni_droghe'])) {

                echo $fam['sanzioni_droghe'];
                echo "💸 Sanzione:  {$totSanzioni}€<br>";
            }
            echo "➡️ Totale netto: <b>{$totale}€</b><br><br>";
        }
        echo "</div>";



        $users_stmt = $pdo->query("SELECT id, nomeFamiglia FROM users WHERE nomeFamiglia NOT LIKE '%Cartello%' ORDER BY nomeFamiglia ASC");
        $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lista tipi sostanze
        $tipi_stmt = $pdo->query("SELECT id, nome FROM tipi_sostanze ORDER BY nome ASC");
        $tipi = $tipi_stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>


        <form method="POST" action="">
            <h2>Aggiungi sostanza/sanzione</h2>


            <label for="user_id">Famiglia:</label>
            <select name="user_id" id="user_id" required>
                <option value="">-- Seleziona famiglia --</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= htmlspecialchars($user['id']) ?>"><?= htmlspecialchars($user['nomeFamiglia']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="tipo_sostanza_id">Tipo sostanza:</label>
            <select name="tipo_sostanza_id" id="tipo_sostanza_id" required>
                <option value="">-- Seleziona sostanza --</option>
                <?php foreach ($tipi as $tipo): ?>
                    <option value="<?= htmlspecialchars($tipo['id']) ?>"><?= htmlspecialchars($tipo['nome']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="quantita">Quantità:</label>
            <input type="number" id="quantita" name="quantita" min="1" required>

            <label for="data_inserimento">Data inserimento:</label>
            <input type="date" id="data_inserimento" name="data_inserimento"
                value="<?= isset($_GET['data_a']) ? htmlspecialchars($_GET['data_a']) : date('Y-m-d') ?>" required>


            <label><input type="checkbox" name="is_sanzione" value="1"> È una sanzione</label>

            <input type="submit" value="Aggiungi">
        </form>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>






        <a href="dashboard.php">← Torna alla Dashboard</a>
    </div>
</body>

</html>