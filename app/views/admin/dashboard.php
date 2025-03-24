<!-- app/views/admin/dashboard.php -->
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Admin - Gestion Financière</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css">
    <style>
        .dashboard-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="dashboard-container">
            <h2 class="text-center">Tableau de bord Admin</h2>
            <p>Bienvenue, <?php echo htmlspecialchars($utilisateur['nom_utilisateur']); ?> !</p>
            <p>Vous êtes un administrateur. Voici les actions que vous pouvez effectuer :</p>
            <ul>
                <li><a href="<?php echo BASE_URL; ?>/admin/users">Gérer les utilisateurs</a></li>
                <li><a href="<?php echo BASE_URL; ?>/admin/departements">Gérer les départements</a></li>
                <li><a href="<?php echo BASE_URL; ?>/admin/budgets">Valider les budgets</a></li>
            </ul>
            <a href="<?php echo BASE_URL; ?>/logout" class="btn btn-danger">Se déconnecter</a>
        </div>
    </div>
</body>

</html>