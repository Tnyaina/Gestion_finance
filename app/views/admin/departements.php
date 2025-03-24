<!-- app/views/admin/departements.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des départements - Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css">
    <style>
        .container { max-width: 800px; margin: 50px auto; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Gestion des départements</h2>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>/admin/departements/add" class="btn btn-primary mb-3">Ajouter un département</a>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($departements)): ?>
                    <tr>
                        <td colspan="3" class="text-center">Aucun département trouvé.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($departements as $departement): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($departement['id_departement']); ?></td>
                            <td><?php echo htmlspecialchars($departement['nom']); ?></td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>/admin/departements/edit/<?php echo $departement['id_departement']; ?>" class="btn btn-sm btn-warning">Modifier</a>
                                <a href="<?php echo BASE_URL; ?>/admin/departements/delete/<?php echo $departement['id_departement']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce département ? Attention : cela peut affecter les utilisateurs associés.');">Supprimer</a>
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