<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Fonction pour valider le mot de passe
function isValidPassword($password) {
    // Vérifie la longueur minimale
    if (strlen($password) < 8) {
        return false;
    }
    // Vérifie la présence d'une majuscule
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    // Vérifie la présence d'une minuscule
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    // Vérifie la présence d'un chiffre
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    // Vérifie la présence d'un caractère spécial
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        return false;
    }
    return true;
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nomUtilisateur = filter_input(INPUT_POST, 'nomUtilisateur', FILTER_SANITIZE_STRING);
    $prenomUtilisateur = filter_input(INPUT_POST, 'prenomUtilisateur', FILTER_SANITIZE_STRING);
    $login = filter_input(INPUT_POST, 'login', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $confirmPassword = filter_input(INPUT_POST, 'confirm_password', FILTER_SANITIZE_STRING);

    // Vérifiez si les mots de passe correspondent
    if ($password !== $confirmPassword) {
        $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
        header("Location: add-users.php");
        exit();
    }

    // Validation du mot de passe
    if (!isValidPassword($password)) {
        $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères, dont une majuscule, une minuscule, un chiffre et un caractère spécial.";
        header("Location: add-users.php");
        exit();
    }

    // Hachage du mot de passe
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Vérifiez si les champs obligatoires sont vides
    if (empty($nomUtilisateur) || empty($prenomUtilisateur) || empty($login) || empty($hashedPassword)) {
        $_SESSION['error'] = "Tous les champs doivent être remplis.";
        header("Location: add-users.php");
        exit();
    }

    try {
        // Vérifiez si l'utilisateur existe déjà
        $queryCheck = "SELECT id_utilisateur FROM UTILISATEUR WHERE login = :login";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":login", $login, PDO::PARAM_STR);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "L'utilisateur existe déjà.";
            header("Location: add-users.php");
            exit();
        } else {
            // Requête pour ajouter un utilisateur
            $query = "INSERT INTO UTILISATEUR (nom_utilisateur, prenom_utilisateur, login, password) VALUES (:nomUtilisateur, :prenomUtilisateur, :login, :password)";
            $statement = $connexion->prepare($query);
            $statement->bindParam(":nomUtilisateur", $nomUtilisateur, PDO::PARAM_STR);
            $statement->bindParam(":prenomUtilisateur", $prenomUtilisateur, PDO::PARAM_STR);
            $statement->bindParam(":login", $login, PDO::PARAM_STR);
            $statement->bindParam(":password", $hashedPassword, PDO::PARAM_STR);

            // Exécutez la requête
            if ($statement->execute()) {
                $_SESSION['success'] = "L'utilisateur a été ajouté avec succès.";
                header("Location: manage-users.php");
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de l'ajout de l'utilisateur.";
                header("Location: add-users.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: add-users.php");
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
    <title>Ajouter un Utilisateur - Jeux Olympiques 2028</title>

</head>

<style>
        /* Ajoutez votre style CSS ici */
        .valid {
            color: green;
        }

        .invalid {
            color: #d6c7a1; /* Beige */
        }
    </style>

<body>
    <header>
        <nav>
            <!-- Menu vers les pages sports, events, et results -->
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="manage-users.php">Gestion Utilisateurs</a></li>
                <li><a href="../admin-sports/manage-sports.php">Gestion Sports</a></li>
                <li><a href="../admin-places/manage-places.php">Gestion Lieux</a></li>
                <li><a href="../admin-events/manage-events.php">Gestion Calendrier</a></li>
                <li><a href="../admin-countries/manage-countries.php">Gestion Pays</a></li>
                <li><a href="../admin-gender/manage-gender.php">Gestion Genres</a></li>
                <li><a href="../admin-athletes/manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="../admin-results/manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h1>Ajouter un Utilisateur</h1>
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
        <form action="add-users.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir ajouter cet utilisateur?')">
            <label for="nomUtilisateur">Nom :</label>
            <input type="text" name="nomUtilisateur" id="nomUtilisateur" required>

            <label for="prenomUtilisateur">Prénom :</label>
            <input type="text" name="prenomUtilisateur" id="prenomUtilisateur" required>

            <label for="login">Identifiant :</label>
            <input type="text" name="login" id="login" required>

            <label for="password">Mot de passe :</label>
            <input type="password" name="password" id="password" required oninput="validatePassword()">
            

            <label for="confirm_password">Confirmer le mot de passe :</label>
            <input type="password" name="confirm_password" id="confirm_password" required oninput="validatePassword()">
            

            <ul id="password-requirements">
                <li id="length" class="invalid">8 caractères minimum</li>
                <li id="uppercase" class="invalid">Au moins une majuscule</li>
                <li id="lowercase" class="invalid">Au moins une minuscule</li>
                <li id="number" class="invalid">Au moins un chiffre</li>
                <li id="special" class="invalid">Au moins un caractère spécial</li>
            </ul>

            <input type="submit" value="Ajouter l'Utilisateur">
        </form>
        <p class="paragraph-link">
            <a class="link-home" href="manage-users.php">Retour à la gestion des utilisateurs</a>
        </p>
    </main>
    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo jeux olympiques 2028">
        </figure>
    </footer>

    <script>
        function validatePassword() {
            const password = document.getElementById('password').value;
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };

            Object.keys(requirements).forEach(req => {
                document.getElementById(req).className = requirements[req] ? 'valid' : 'invalid';
            });
        }
    </script>
</body>

</html>