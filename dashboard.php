<?php
session_start();
require 'db.php';

if (!isset($_SESSION["username"])) {
    header("Location: index.php");
    exit;
}

$isAdmin = isset($_SESSION["is_admin"]) && $_SESSION["is_admin"];
$nomeFamiglia = isset($_SESSION["nomeFamiglia"]) ? $_SESSION["nomeFamiglia"] : "";

$successMessage = "";
$errorMessage = "";

// Esempio PDO
$stmt = $pdo->prepare("SELECT * FROM tipi_sostanze WHERE abilitata = 1 ORDER BY nome");
$stmt->execute();
$sostanze_abilitate = $stmt->fetchAll(PDO::FETCH_ASSOC);



// Gestione cancellazione utente (solo admin)
if ($isAdmin && isset($_GET['delete_user_id'])) {
    $deleteUserId = intval($_GET['delete_user_id']);
    if ($deleteUserId !== $_SESSION["user_id"]) {
        $delStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $delStmt->execute([$deleteUserId]);
        header("Location: dashboard.php");
        exit;
    } else {
        $errorMessage = "Non puoi cancellare il tuo account mentre sei loggato.";
    }
}

// Aggiunta sostanza utente
if (!$isAdmin && $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'add_sostanza') {
    $tipoSostanzaId = intval($_POST['tipo_sostanza_id']);
    $quantita = intval($_POST['quantita']);

    if ($tipoSostanzaId > 0 && $quantita > 0) {
        try {
            $insertStmt = $pdo->prepare("INSERT INTO sostanze (user_id , tipo_sostanza_id, quantita, data_inserimento) VALUES (?, ?, ?, NOW())");
            $insertStmt->execute([$_SESSION['user_id'], $tipoSostanzaId, $quantita]);
            $successMessage = "Sostanza aggiunta con successo.";
        } catch (PDOException $e) {
            $errorMessage = "Errore nell'inserimento della sostanza: " . $e->getMessage();
        }
    } else {
        $errorMessage = "Dati non validi per aggiungere la sostanza.";
    }
}



// Gestione toggle abilitazione sostanza (solo admin)
if ($isAdmin && isset($_POST['toggle_sostanza_id'], $_POST['toggle_sostanza_action'])) {
    $id = intval($_POST['toggle_sostanza_id']);
    $action = $_POST['toggle_sostanza_action'];
    if ($action === 'abilita') {
        $stmt = $pdo->prepare("UPDATE tipi_sostanze SET abilitata = 1 WHERE id = ?");
        $stmt->execute([$id]);
        $successMessage = "Sostanza abilitata.";
    } elseif ($action === 'disabilita') {
        $stmt = $pdo->prepare("UPDATE tipi_sostanze SET abilitata = 0 WHERE id = ?");
        $stmt->execute([$id]);
        $successMessage = "Sostanza disabilitata.";
    }
    // Ricarica la pagina per vedere effetto
    header("Location: dashboard.php");
    exit;
}

// Gestione POST admin (creazione famiglie, toggle funzionalità insert_drug)
if ($isAdmin && $_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_user') {
        $newUser = trim($_POST["new_username"]);
        $newNomeFamiglia = trim($_POST["new_nomeFamiglia"]);
        $newPassword = password_hash($_POST["new_password"], PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, nomeFamiglia, password) VALUES (?, ?, ?)");
            $stmt->execute([$newUser, $newNomeFamiglia, $newPassword]);
            $successMessage = "✅ Famiglia creata con successo.";
        } catch (PDOException $e) {
            $errorMessage = "❌ Errore: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'toggle_insert_drug') {
        $newStatus = isset($_POST['toggle_insert_drug']) && $_POST['toggle_insert_drug'] === 'on' ? 1 : 0;
        $update = $pdo->prepare("UPDATE settings SET is_enabled = ? WHERE feature_key = 'insert_drug'");
        $update->execute([$newStatus]);
        $successMessage = "✅ Funzionalità di inserimento droghe aggiornata.";
    }
}

// Recupera utenti (solo admin)
$users = [];
if ($isAdmin) {
    $stmt = $pdo->query("SELECT id, username, nomeFamiglia FROM users ORDER BY id ASC");
    $users = $stmt->fetchAll();

    // Recupera stato insert_drug
    $stmt = $pdo->prepare("SELECT is_enabled FROM settings WHERE feature_key = 'insert_drug'");
    $stmt->execute();
    $insertDrugSetting = $stmt->fetchColumn();

    // Recupera sostanze
    $stmt = $pdo->query("SELECT id, nome, abilitata FROM tipi_sostanze ORDER BY nome ASC");
    $sostanze = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 20px;
        }

        .box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
            text-align: center;
            width: 650px;
        }

        input,
        textarea {
            padding: 8px;
            width: 100%;
            margin: 5px 0 10px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        textarea {
            resize: vertical;
        }

        .admin {
            color: green;
            font-weight: bold;
        }

        .success {
            color: green;
            margin-top: 10px;
        }

        .error {
            color: red;
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 0.9em;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        a {
            color: red;
            text-decoration: none;
            cursor: pointer;
        }

        a:hover {
            text-decoration: underline;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
            vertical-align: middle;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            background-color: #ccc;
            border-radius: 24px;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            transition: 0.4s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            border-radius: 50%;
            transition: 0.4s;
        }

        input:checked+.slider {
            background-color: #4CAF50;
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        button.toggle-btn {
            background: none;
            border: none;
            color: blue;
            cursor: pointer;
            font-size: 0.9em;
            padding: 0;
            margin: 0;
        }

        button.toggle-btn:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="box">
        <h2>Ciao, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h2>
        <?php if ($nomeFamiglia)
            echo "<p>Famiglia: " . htmlspecialchars($nomeFamiglia) . "</p>"; ?>

        <p>Benvenuto nella tua dashboard.</p>

        <?php if ($isAdmin): ?>
            <p class="admin">✅ Hai i privilegi di amministratore</p>

            <hr>
            <h3>Crea una nuova famiglia</h3>
            <form method="post" autocomplete="off">
                <input type="hidden" name="action" value="create_user">
                <input type="text" name="new_username" placeholder="Username" required><br>
                <input type="text" name="new_nomeFamiglia" placeholder="Nome Famiglia" required><br>
                <input type="password" name="new_password" placeholder="Password" required><br>
                <input type="submit" value="Crea Famiglia">
            </form>

            <hr>
            <h3>Gestione famiglie</h3>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Nome Famiglia</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['nomeFamiglia']); ?></td>
                            <td>
                                <?php if ($user['id'] !== $_SESSION["user_id"]): ?>
                                    <a href="dashboard.php?delete_user_id=<?php echo $user['id']; ?>"
                                        onclick="return confirm('Sei sicuro di voler cancellare questa famiglia?');">Elimina</a>
                                <?php else: ?>
                                    <em>Non puoi cancellarti</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <hr>
            <h3>Gestione sostanze</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Abilitata</th>
                        <th>Azione</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sostanze as $sostanza): ?>
                        <tr>
                            <td><?= htmlspecialchars($sostanza['nome']) ?></td>
                            <td><?= $sostanza['abilitata'] ? 'Sì' : 'No' ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="toggle_sostanza_id" value="<?= $sostanza['id'] ?>">
                                    <button type="submit" name="toggle_sostanza_action"
                                        value="<?= $sostanza['abilitata'] ? 'disabilita' : 'abilita' ?>">
                                        <?= $sostanza['abilitata'] ? 'Disabilita' : 'Abilita' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>


           

            <hr>

            
            <h3>Resoconto droghe</h3>
            <form method="GET" action="visualizza.php" style="margin-bottom: 20px;">
                <label for="data_da">Data da:</label>
                <input type="date" id="data_da" name="data_da"
                    value="<?php echo isset($_GET['data_da']) ? htmlspecialchars($_GET['data_da']) : ''; ?>" required>

                <label for="data_a">Data a:</label>
                <input type="date" id="data_a" name="data_a"
                    value="<?php echo isset($_GET['data_a']) ? htmlspecialchars($_GET['data_a']) : ''; ?>" required>

                 <button type="submit"
                    style="margin-top: 20px; padding: 10px 20px; background-color: #007BFF; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">
                    Visualizza droghe di questa settimana 🔍
                </button>
            </form>

             <hr>
            <h3>Gestione funzionalità</h3>
            <form method="post" style="margin-top: 10px;">
                <input type="hidden" name="action" value="toggle_insert_drug">
                <label for="insert_drug_toggle">Abilita inserimento droghe:</label>
                <label class="switch">
                    <input type="checkbox" id="insert_drug_toggle" name="toggle_insert_drug" <?php echo $insertDrugSetting ? 'checked' : ''; ?> onchange="this.form.submit()">
                    <span class="slider"></span>
                </label>
            </form>

            <br>

        <?php else: ?>
            <p>🔒 Accesso base</p>

            <form method="post" action="dashboard.php"
                style="border:1px solid #ccc; padding: 15px; border-radius: 8px; max-width: 400px;">

                <input type="hidden" name="action" value="add_sostanza">

                <p><strong>Scegli la sostanza:</strong></p>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <?php foreach ($sostanze_abilitate as $sostanza): ?>
                        <label
                            style="cursor:pointer; border: 2px solid #888; border-radius: 6px; padding: 8px 12px; flex: 1 0 45%; text-align:center; background:#f7f7f7; user-select:none;">
                            <input type="radio" name="tipo_sostanza_id" value="<?= htmlspecialchars($sostanza['id']) ?>"
                                required style="margin-right: 6px;">
                            <?= htmlspecialchars($sostanza['nome']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <label for="quantita" style="margin-top: 15px; display: block;">Quantità (min 1):</label>
                <input type="number" name="quantita" id="quantita" min="1" placeholder="Es. 200" required
                    style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #aaa;">

                <button type="submit"
                    style="margin-top: 15px; background-color: #007BFF; border:none; color:white; padding: 10px 15px; border-radius: 6px; cursor:pointer; width: 100%;">Aggiungi
                    sostanza</button>
            </form>



        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <p id="error-message" class="error"><?php echo htmlspecialchars($errorMessage); ?></p>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <p id="success-message" class="success"><?php echo htmlspecialchars($successMessage); ?></p>
        <?php endif; ?>

        <br><a href="logout.php">Logout</a>
    </div>

    <script>
        setTimeout(() => {
            const errorMsg = document.getElementById('error-message');
            if (errorMsg) {
                errorMsg.style.transition = "opacity 0.5s ease";
                errorMsg.style.opacity = 0;
                setTimeout(() => errorMsg.remove(), 500);
            }
            const successMsg = document.getElementById('success-message');
            if (successMsg) {
                successMsg.style.transition = "opacity 0.5s ease";
                successMsg.style.opacity = 0;
                setTimeout(() => successMsg.remove(), 500);
            }
        }, 5000);
    </script>

</body>

</html>