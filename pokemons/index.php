<?php
include('../login/verifica_sessao.php');

// Conectar ao banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pokemons_dataset";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Obtém o id do treinador logado
$id_pessoa = $_SESSION['user_id'];

// Verifica se o email está configurado na sessão
$email = isset($_SESSION['email']) ? $_SESSION['email'] : "Email não definido";  

// Definir a ordenação para ataques e defesas
$orderByTreinador = isset($_GET['orderByTreinador']) ? $_GET['orderByTreinador'] : 'Attack';
$directionTreinador = isset($_GET['directionTreinador']) ? $_GET['directionTreinador'] : 'DESC';
$orderByAll = isset($_GET['orderByAll']) ? $_GET['orderByAll'] : 'Attack';
$directionAll = isset($_GET['directionAll']) ? $_GET['directionAll'] : 'DESC';

// Inverter direção ao clicar
$invertDirectionTreinador = $directionTreinador == 'ASC' ? 'DESC' : 'ASC';
$invertDirectionAll = $directionAll == 'ASC' ? 'DESC' : 'ASC';

// Exibe o e-mail no cabeçalho
echo "<h1>Bem-vindo, " . htmlspecialchars($email) . "!</h1>";

// Consulta para buscar os Pokémons adicionados pelo treinador na tabela pessoa_pokemon
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

// Função para fazer logout
if (isset($_POST['logout'])) {
    session_destroy(); // Destrói a sessão
    header("Location: ../login/login.php"); // Redireciona para a página de login
    exit();
}

echo "<form method='POST'>
         <button type='submit' name='logout'>Logout</button>
      </form>";

echo "<h2>Buscar Treinador</h2>";
echo "<form method='GET'>
        <input type='text' name='email_search' placeholder='Email do treinador'>
        <button type='submit' id='idBuscar'>Buscar</button>
      </form>";

$email_search = isset($_GET['email_search']) ? $_GET['email_search'] : '';
$sql_trainers = "SELECT id_pessoa, email FROM pessoa WHERE email LIKE ?";
$stmt_trainers = $conn->prepare($sql_trainers);
$search_param = "%{$email_search}%";
$stmt_trainers->bind_param("s", $search_param);
$stmt_trainers->execute();
$result_trainers = $stmt_trainers->get_result();

echo "<table border='1'>
        <tr><th>Email</th></tr>";

if ($result_trainers->num_rows > 0) {
    while ($row_trainers = $result_trainers->fetch_assoc()) {
        // Torna o email clicável, passando o ID da pessoa como parâmetro
        echo "<tr><td><a href='?view_trainer_id={$row_trainers['id_pessoa']}'>{$row_trainers['email']}</a></td></tr>";
    }
} else {
    echo "<tr><td>Nenhum treinador encontrado.</td></tr>";
}
echo "</table>";

// Exibir a Pokédex do treinador clicado e o email
if (isset($_GET['view_trainer_id'])) {
    $view_trainer_id = $_GET['view_trainer_id'];

    // Consulta para obter o email e a pokedex
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

    // Inicializa variáveis para soma de ataques e defesas
    $totalAttackTrainer = 0;
    $totalDefenseTrainer = 0;
    $countPokemonTrainer = 0;

    // Verifica se há resultados
    if ($result_trainer_pokedex->num_rows > 0) {
        // Pegar o email do treinador buscado
        if ($row_pokedex = $result_trainer_pokedex->fetch_assoc()) {
            $searched_trainer_email = $row_pokedex['email']; // Armazena o email do treinador buscado
            echo "<h2>Pokédex do Treinador: " . htmlspecialchars($searched_trainer_email) . "</h2>";
        }

        // Exibir os Pokémon do treinador
        echo "<table border='1'>
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
            
            // Soma dos ataques e defesas
            $totalAttackTrainer += $row_pokedex['Attack'];
            $totalDefenseTrainer += $row_pokedex['Defense'];
            $countPokemonTrainer++;
        } while ($row_pokedex = $result_trainer_pokedex->fetch_assoc());

        echo "</table>";

        // Calcular médias
        $averageAttackTrainer = $totalAttackTrainer / $countPokemonTrainer;
        $averageDefenseTrainer = $totalDefenseTrainer / $countPokemonTrainer;

        echo "<p>Média de Ataque do treinador: " . number_format($averageAttackTrainer, 2) . "</p>";
        echo "<p>Média de Defesa do treinador: " . number_format($averageDefenseTrainer, 2) . "</p>";
    } else {
        echo "<h2>Nenhum Pokémon encontrado para este treinador.</h2>";
    }

    $stmt_trainer_pokedex->close();
}



// Tabela de Pokémon do treinador
echo "<h2>Sua Pokédex</h2>";
echo "<table border='1'>
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
                    <form action='delete.php' method='post'>
                        <input type='hidden' name='delete_id' value='{$row['Pokedex_number']}'>
                        <button type='submit'>Excluir</button>
                    </form>
                </td>
              </tr>";
        
        // Soma dos ataques e defesas
        $totalAttack += $row['Attack'];
        $totalDefense += $row['Defense'];
        $countPokemon++;
    }

    // Calcular médias
    $averageAttack = $totalAttack / $countPokemon;
    $averageDefense = $totalDefense / $countPokemon;

    echo "</table>";
    echo "<p>Média de Ataque: " . number_format($averageAttack, 2) . "</p>";
    echo "<p>Média de Defesa: " . number_format($averageDefense, 2) . "</p>";
} else {
    echo "<tr><td colspan='7'>Você ainda não adicionou nenhum Pokémon à sua Pokédex.</td></tr>";
    echo "</table>";
}


// Exibir todos os Pokémons disponíveis na tabela "pokemon"
$sql_all = "SELECT pokemon.*, type.text AS tipo 
            FROM pokemon 
            JOIN type ON pokemon.Type = type.id_type 
            ORDER BY $orderByAll $directionAll";
$result_all = $conn->query($sql_all);

echo "<h2>Lista de todos os Pokémons</h2>";
echo "<table border='1'>
        <tr>
          <th><a href='?orderByAll=Attack&directionAll=$invertDirectionAll'>Ataque</a></th>
          <th><a href='?orderByAll=Defense&directionAll=$invertDirectionAll'>Defesa</a></th>
          <th>Nome</th><th>Número</th><th>Tipo</th><th>Lendário?</th><th>Adicionar</th>
        </tr>";

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
                <form action='add.php' method='post'>
                    <input type='hidden' name='add_id' value='{$row_all['Pokedex_number']}'>
                    <button type='submit'>Adicionar à Pokédex</button>
                </form>
            </td>
          </tr>";
}
echo "</table>";

// Exibir todos os emails dos treinadores cadastrados
$stmt->close();
$conn->close();
?>
