<?php
include('../login/verifica_sessao.php');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pokemons_dataset";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

$id_pessoa = $_SESSION['user_id'];

$email = isset($_SESSION['email']) ? $_SESSION['email'] : "Email não definido";  

$orderByTreinador = isset($_GET['orderByTreinador']) ? $_GET['orderByTreinador'] : 'Attack';
$directionTreinador = isset($_GET['directionTreinador']) ? $_GET['directionTreinador'] : 'DESC';
$orderByAll = isset($_GET['orderByAll']) ? $_GET['orderByAll'] : 'Attack';
$directionAll = isset($_GET['directionAll']) ? $_GET['directionAll'] : 'DESC';

$invertDirectionTreinador = $directionTreinador == 'ASC' ? 'DESC' : 'ASC';
$invertDirectionAll = $directionAll == 'ASC' ? 'DESC' : 'ASC';

echo "<h1>Bem-vindo, " . htmlspecialchars($email) . "!</h1>";

$sql = "SELECT pokemon.*, type.text AS tipo 
        FROM pessoa_pokemon 
        JOIN pokemon ON pessoa_pokemon.pokedex_number = pokemon.Pokedex_number
        JOIN type ON pokemon.Type = type.id_type
        WHERE pessoa_pokemon.id_pessoa = ?
        ORDER BY $orderByTreinador $directionTreinador";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_pessoa);
$stmt->execute();
$result = $stmt->get_result();

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: ../login/login.php");
    exit();
}

echo "<form method='POST' class='logout-form'>
         <button type='submit' name='logout'>Logout</button>
      </form>";

echo "<div class='search-trainer'>
        <h2>Buscar Treinador</h2>
        <form method='GET'>
            <input type='text' name='email_search' placeholder='Email do treinador' class='email-search-input'>
            <button type='submit' id='idBuscar'>Buscar</button>
        </form>
      </div>";

$email_search = isset($_GET['email_search']) ? $_GET['email_search'] : '';
$sql_trainers = "SELECT id_pessoa, email FROM pessoa WHERE email LIKE ?";
$stmt_trainers = $conn->prepare($sql_trainers);
$search_param = "%{$email_search}%";
$stmt_trainers->bind_param("s", $search_param);
$stmt_trainers->execute();
$result_trainers = $stmt_trainers->get_result();

echo "<table border='1' class='trainers-table'>
        <tr><th>Email</th></tr>";

if ($result_trainers->num_rows > 0) {
    while ($row_trainers = $result_trainers->fetch_assoc()) {
        echo "<tr><td><a href='?view_trainer_id={$row_trainers['id_pessoa']}' class='trainer-email-link'>{$row_trainers['email']}</a></td></tr>";
    }
} else {
    echo "<tr><td>Nenhum treinador encontrado.</td></tr>";
}
echo "</table>";

if (isset($_GET['view_trainer_id'])) {
    $view_trainer_id = $_GET['view_trainer_id'];

    $sql_trainer_pokedex = "SELECT p.email, pokemon.*, type.text AS tipo 
                            FROM pessoa_pokemon 
                            JOIN pokemon ON pessoa_pokemon.pokedex_number = pokemon.Pokedex_number
                            JOIN type ON pokemon.Type = type.id_type
                            JOIN pessoa p ON pessoa_pokemon.id_pessoa = p.id_pessoa
                            WHERE pessoa_pokemon.id_pessoa = ?
                            ORDER BY pokemon.Name";
    $stmt_trainer_pokedex = $conn->prepare($sql_trainer_pokedex);
    $stmt_trainer_pokedex->bind_param("i", $view_trainer_id);
    $stmt_trainer_pokedex->execute();
    $result_trainer_pokedex = $stmt_trainer_pokedex->get_result();

    $totalAttackTrainer = 0;
    $totalDefenseTrainer = 0;
    $countPokemonTrainer = 0;

    if ($result_trainer_pokedex->num_rows > 0) {
        if ($row_pokedex = $result_trainer_pokedex->fetch_assoc()) {
            $searched_trainer_email = $row_pokedex['email'];
            echo "<h2>Pokédex do Treinador: " . htmlspecialchars($searched_trainer_email) . "</h2>";
        }

        echo "<table border='1' class='pokedex-table'>
                <tr>
                    <th>Nome</th><th>Número</th><th>Tipo</th><th>Legendário?</th>
                </tr>";
        
        do {
            $isLegendary = $row_pokedex['Is_legendary'] == 1 ? "Sim" : "Não";
            echo "<tr>
                    <td>{$row_pokedex['Name']}</td>
                    <td>{$row_pokedex['Pokedex_number']}</td>
                    <td>{$row_pokedex['tipo']}</td>
                    <td>{$isLegendary}</td>
                  </tr>";
            
            $totalAttackTrainer += $row_pokedex['Attack'];
            $totalDefenseTrainer += $row_pokedex['Defense'];
            $countPokemonTrainer++;
        } while ($row_pokedex = $result_trainer_pokedex->fetch_assoc());

        echo "</table>";

        $averageAttackTrainer = $totalAttackTrainer / $countPokemonTrainer;
        $averageDefenseTrainer = $totalDefenseTrainer / $countPokemonTrainer;

        echo "<p class='average-attack'>Média de Ataque do treinador: " . number_format($averageAttackTrainer, 2) . "</p>";
        echo "<p class='average-defense'>Média de Defesa do treinador: " . number_format($averageDefenseTrainer, 2) . "</p>";
    } else {
        echo "<h2>Nenhum Pokémon encontrado para este treinador.</h2>";
    }

    $stmt_trainer_pokedex->close();
}

echo "<h2 class='sua-pokedex-title'>Sua Pokédex</h2>";
echo "<table border='1' class='sua-pokedex-table'>
        <tr>
          <th><a href='?orderByTreinador=Attack&directionTreinador=$invertDirectionTreinador'>Ataque</a></th>
          <th><a href='?orderByTreinador=Defense&directionTreinador=$invertDirectionTreinador'>Defesa</a></th>
          <th>Nome</th><th>Número</th><th>Tipo</th><th>Lendário?</th><th>Excluir</th>
        </tr>";

$totalAttack = 0;
$totalDefense = 0;
$countPokemon = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $isLegendary = $row['Is_legendary'] == 1 ? "Sim" : "Não";
        echo "<tr>
                <td>{$row['Attack']}</td>
                <td>{$row['Defense']}</td>
                <td>{$row['Name']}</td>
                <td>{$row['Pokedex_number']}</td>
                <td>{$row['tipo']}</td>
                <td>{$isLegendary}</td>
                <td>
                    <form action='delete.php' method='post' class='delete-form'>
                        <input type='hidden' name='delete_id' value='{$row['Pokedex_number']}'>
                        <button type='submit' class='delete-button'>Excluir</button>
                    </form>
                </td>
              </tr>";
        
        $totalAttack += $row['Attack'];
        $totalDefense += $row['Defense'];
        $countPokemon++;
    }

    $averageAttack = $totalAttack / $countPokemon;
    $averageDefense = $totalDefense / $countPokemon;

    echo "</table>";
    echo "<p class='average-attack'>Média de Ataque: " . number_format($averageAttack, 2) . "</p>";
    echo "<p class='average-defense'>Média de Defesa: " . number_format($averageDefense, 2) . "</p>";
} else {
    echo "<tr><td colspan='7'>Você ainda não adicionou nenhum Pokémon à sua Pokédex.</td></tr>";
    echo "</table>";
}

$sql_all = "SELECT pokemon.*, type.text AS tipo 
            FROM pokemon 
            JOIN type ON pokemon.Type = type.id_type
            WHERE pokemon.Pokedex_number NOT IN (
                SELECT pessoa_pokemon.pokedex_number 
                FROM pessoa_pokemon 
                WHERE pessoa_pokemon.id_pessoa = ?
            )
            ORDER BY $orderByAll $directionAll";

$stmt_all = $conn->prepare($sql_all);
$stmt_all->bind_param("i", $id_pessoa);
$stmt_all->execute();
$result_all = $stmt_all->get_result();

echo "<h2 class='all-pokemons-title'>Lista de todos os Pokémons</h2>";
echo "<table border='1' class='all-pokemons-table'>
        <tr>
          <th><a href='?orderByAll=Attack&directionAll=$invertDirectionAll'>Ataque</a></th>
          <th><a href='?orderByAll=Defense&directionAll=$invertDirectionAll'>Defesa</a></th>
          <th>Nome</th><th>Número</th><th>Tipo</th><th>Lendário?</th><th>Adicionar</th>
        </tr>";

if ($result_all->num_rows > 0) {
    while ($row_all = $result_all->fetch_assoc()) {
        $isLegendary = $row_all['Is_legendary'] == 1 ? "Sim" : "Não";
        echo "<tr>
                <td>{$row_all['Attack']}</td>
                <td>{$row_all['Defense']}</td>
                <td>{$row_all['Name']}</td>
                <td>{$row_all['Pokedex_number']}</td>
                <td>{$row_all['tipo']}</td>
                <td>{$isLegendary}</td>
                <td>
                    <form action='add.php' method='post' class='add-form'>
                        <input type='hidden' name='add_id' value='{$row_all['Pokedex_number']}'>
                        <button type='submit' class='add-button'>Adicionar</button>
                    </form>
                </td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<tr><td colspan='7'>Todos os Pokémons já foram adicionados à sua Pokédex.</td></tr>";
}

$stmt->close();
$stmt_all->close();
$conn->close();
?>
