<!-- app/views/admin/users.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs - Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css">
    <style>
        .container { max-width: 1000px; margin: 50px auto; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Gestion des utilisateurs</h2>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>/admin/users/add" class="btn btn-primary mb-3">Ajouter un utilisateur</a>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom d'utilisateur</th>
                    <th>Département</th>
                    <th>Rôle</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($utilisateurs)): ?>
                    <tr>
                        <td colspan="5" class="text-center">Aucun utilisateur trouvé.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($utilisateurs as $utilisateur): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($utilisateur['id_utilisateur']); ?></td>
                            <td><?php echo htmlspecialchars($utilisateur['nom_utilisateur']); ?></td>
                            <td><?php echo htmlspecialchars($utilisateur['nom_departement']); ?></td>                            <td><?php echo htmlspecialchars($utilisateur['role']); ?></td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>/admin/users/edit/<?php echo $utilisateur['id_utilisateur']; ?>" class="btn btn-sm btn-warning">Modifier</a>
                                <a href="<?php echo BASE_URL; ?>/admin/users/delete/<?php echo $utilisateur['id_utilisateur']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="<?php echo BASE_URL; ?>/admin/dashboard" class="btn btn-secondary">Retour au tableau de bord</a>
    </div>
</body>
</html>