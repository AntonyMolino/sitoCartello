<?php
require 'db.php';
session_start();

$errore = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    // Recupera l'utente dal database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["username"] = $username;
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["is_admin"] = $user["administrator"];
        $_SESSION["nomeFamiglia"] = $user["nomeFamiglia"];
        $_SESSION["must_change_password"] = $user["must_change_password"];  // aggiungi qui

        if ($user["must_change_password"]) {
            header("Location: change_password.php");
            exit;
        } else {
            header("Location: dashboard.php");
            exit;
        }
    } else {
        $errore = "Credenziali non valide.";
    }
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Login</title>
    
    <style>
        body {
            font-family: Arial;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
            width: 300px;
        }

        input {
            width: 100%;
            padding: 8px;
            margin: 8px 0;
        }

        .error {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="box">
        <h2>Login</h2>
        <?php if ($errore !== ""): ?>
            <p class="error"><?php echo htmlspecialchars($errore); ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="submit" value="Accedi">
        </form>
        <p>Non sei registrato? Chiedi a un admin di crearti un account</p>
    </div>
</body>

</html>
