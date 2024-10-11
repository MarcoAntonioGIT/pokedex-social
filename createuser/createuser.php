<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pokemons_dataset";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['password'];

    $sql_check = "SELECT email FROM pessoa WHERE email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $error_message = "Esse e-mail já está cadastrado!";
    } else {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $sql = "INSERT INTO pessoa (email, senha) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $senha_hash);

        if ($stmt->execute()) {
            header("Location: ../login/login.php");
            exit();
        } else {
            $error_message = "Erro: " . $stmt->error;
        }
        $stmt->close();
    }
    $stmt_check->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2 class="title">Criar Conta</h2>
        <form action="createuser.php" method="post" class="form">
            <label for="email" class="label">Email:</label>
            <input type="email" id="email" name="email" required class="input"><br>
            
            <label for="password" class="label">Senha:</label>
            <input type="password" id="password" name="password" required class="input">
            
            <button type="submit" class="button">Criar Conta</button>
        </form>
        <?php if (!empty($error_message)): ?>
            <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?> 
        <a href="../login/login.php" class="link">Já tem uma conta? Faça login.</a>
    </div>
</body>
</html>
