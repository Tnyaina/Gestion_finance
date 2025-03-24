<!-- app/views/admin/edit_departement.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un département - Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css">
    <style>
        .container { max-width: 500px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Modifier un département</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($error as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="POST" action="<?php echo BASE_URL; ?>/admin/departements/edit/<?php echo $departement['id_departement']; ?>">
            <div class="form-group">
                <label for="nom">Nom du département</label>
                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($departement['nom']); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Mettre à jour le département</button>
        </form>
        <p class="text-center mt-3">
            <a href="<?php echo BASE_URL; ?>/admin/departements">Retour à la liste des départements</a>
        </p>
    </div>
</body>
</html>