<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID du lieu est fourni dans l'URL
if (!isset($_GET['id_pays'])) {
    $_SESSION['error'] = "ID du lieu manquant.";
    header("Location: manage-countries.php");
    exit();
}

$id_pays = filter_input(INPUT_GET, 'id_pays', FILTER_VALIDATE_INT);

// Vérifiez si l'ID du lieu est un entier valide
if (!$id_pays && $id_pays !== 0) {
    $_SESSION['error'] = "ID de lieu invalide.";
    header("Location: manage-countries.php");
    exit();
}

// Vider les messages de succès précédents
if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
}

// Récupérez les informations du lieu pour affichage dans le formulaire
try {
    $queryCountries = "SELECT nom_pays FROM PAYS WHERE id_pays = :idPays";
    $statementCountries = $connexion->prepare($queryCountries);
    $statementCountries->bindParam(":idPays", $id_pays, PDO::PARAM_INT); // Correction de l'ordre des appels
    $statementCountries->execute();

    if ($statementCountries->rowCount() > 0) {
        $pays = $statementCountries->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Lieu non trouvé.";
        header("Location: manage-countries.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: manage-countries.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nomPays = filter_input(INPUT_POST, 'nomPays', FILTER_SANITIZE_SPECIAL_CHARS);
  
    // Vérifiez si le nom du lieu est vide
    if (empty($nomPays)) {
        $_SESSION['error'] = "Tout les champs doivent être remplis.";
        header("Location: modify-countries.php?id_pays=$id_pays");
        exit();
    }

    try {
        // Vérifiez si le nom du lieu existe déjà
        $queryCheck = "SELECT id_pays FROM PAYS WHERE nom_pays = :nomPays AND id_pays <> :idPays";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":nomPays", $nomLieu, PDO::PARAM_STR);
        $statementCheck->bindParam(":idPays", $id_lieu, PDO::PARAM_INT);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "Le lieu existe déjà.";
            header("Location: modify-countries.php?id_pays=$id_pays");
            exit();
        }

        // Requête pour mettre à jour le lieu
        $query = "UPDATE PAYS SET nom_pays = :nomPays WHERE id_pays = :idPays";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":nomPays", $nomPays, PDO::PARAM_STR);
        $statement->bindParam(":idPays", $id_pays, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "Le lieu a été modifié avec succès.";
            header("Location: manage-countries.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification du lieu.";
            header("Location: modify-countries.php?id_pays=$id_pays");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: modify-countries.php?id_pays=$id_pays");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../css/normalize.css">
    <link rel="stylesheet" href="../../../css/styles-computer.css">
    <link rel="stylesheet" href="../../../css/styles-responsive.css">
    <link rel="shortcut icon" href="../../../img/favicon.ico" type="image/x-icon">
    <title>Modifier un Lieu - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
    <header>
        <nav>
            <!-- Menu vers les pages sports, events, et results -->
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="manage-sports.php">Gestion Sports</a></li>
                <li><a href="manage-places.php">Gestion Lieux</a></li>
                <li><a href="manage-countries.php">Gestion Pays</a></li>
                <li><a href="manage-events.php">Gestion Calendrier</a></li>
                <li><a href="manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Modifier un Lieu</h1>
        
        <!-- Affichage des messages d'erreur ou de succès -->
        <?php
        if (isset($_SESSION['error'])) {
            echo '<p style="color: red;">' . $_SESSION['error'] . '</p>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<p style="color: green;">' . $_SESSION['success'] . '</p>';
            unset($_SESSION['success']);
        }
        ?>

        <form action="modify-countries.php?id_pays=<?php echo $id_pays; ?>" method="post"
            onsubmit="return confirm('Êtes-vous sûr de vouloir modifier ce lieu?')">
            <label for="nomPays">Nom du Lieu :</label>
            <input type="text" name="nomPays" id="nomPays"
                value="<?php echo htmlspecialchars($lieu['nom_pays'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

            <input type="submit" value="Modifier le Lieu">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-countries.php">Retour à la gestion des lieux</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>

</html>
