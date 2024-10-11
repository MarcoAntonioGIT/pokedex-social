<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: ../pokemons/index.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pokemons_dataset";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT id_pessoa, senha, email FROM pessoa WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $hash_senha = $row['senha'];

        if (password_verify($password, $hash_senha)) {
            $_SESSION['user_id'] = $row['id_pessoa'];
            $_SESSION['email'] = $row['email'];
            header("Location: ../pokemons/index.php");
            exit();
        } else {
            $error_message = "Senha incorreta!";
        }
    } else {
        $error_message = "Usuário não encontrado!";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2 class="title">Login</h2>
        <form action="login.php" method="post" class="form">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required><br>
            
            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit" class="button">Entrar</button>
        </form>
        <?php if (!empty($error_message)): ?>
            <p class="error"><?= $error_message ?></p>
        <?php endif; ?>
        <a href="../createuser/createuser.php" class="link">Criar Conta</a>
    </div>
</body>
</html>
