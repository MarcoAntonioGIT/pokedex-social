<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "pokemons_dataset";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("Falha na conexão: " . $conn->connect_error);
    }

    $delete_id = $_POST['delete_id'];
    $id_pessoa = $_SESSION['user_id'];

    $delete_sql = "DELETE FROM pessoa_pokemon WHERE pokedex_number = ? AND id_pessoa = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("ii", $delete_id, $id_pessoa);
    
    if ($stmt->execute()) {
        echo "Pokémon removido da sua Pokédex.";
    } else {
        echo "Erro ao remover Pokémon: " . $conn->error;
    }
    $stmt->close();
    $conn->close();

    header("Location: index.php");
    exit();
}
?>
