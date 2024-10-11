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

    $add_id = $_POST['add_id'];
    $id_pessoa = $_SESSION['user_id'];

    // Insere o Pokémon na tabela pessoa_pokemon
    $add_sql = "INSERT INTO pessoa_pokemon (id_pessoa, pokedex_number) VALUES (?, ?)";
    $stmt = $conn->prepare($add_sql);
    $stmt->bind_param("ii", $id_pessoa, $add_id);
    
    if ($stmt->execute()) {
        echo "Pokémon adicionado à sua Pokédex!";
    } else {
        echo "Erro ao adicionar Pokémon: " . $conn->error;
    }
    $stmt->close();
    $conn->close();

    // Redirecionar de volta para a lista de Pokémons
    header("Location: index.php");
    exit();
}
?>
