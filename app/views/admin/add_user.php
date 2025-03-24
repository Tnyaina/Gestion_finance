<!-- app/views/admin/add_user.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un utilisateur - Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css">
    <style>
        .container { max-width: 500px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Ajouter un utilisateur</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($error as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="POST" action="<?php echo BASE_URL; ?>/admin/users/add">
            <div class="form-group">
                <label for="nom_utilisateur">Nom d'utilisateur</label>
                <input type="text" class="form-control" id="nom_utilisateur" name="nom_utilisateur" required>
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <div class="form-group">
                <label for="confirm_mot_de_passe">Confirmer le mot de passe</label>
                <input type="password" class="form-control" id="confirm_mot_de_passe" name="confirm_mot_de_passe" required>
            </div>
            <div class="form-group">
                <label for="id_departement">Département</label>
                <select class="form-control" id="id_departement" name="id_departement" required>
                    <option value="">Sélectionner un département</option>
                    <?php foreach ($departements as $departement): ?>
                        <option value="<?php echo $departement['id_departement']; ?>">
                            <?php echo htmlspecialchars($departement['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="role">Rôle</label>
                <select class="form-control" id="role" name="role" required>
                    <option value="">Sélectionner un rôle</option>
                    <option value="admin">Admin</option>
                    <option value="utilisateur_departement">Utilisateur Département</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Ajouter l'utilisateur</button>
        </form>
        <p class="text-center mt-3">
            <a href="<?php echo BASE_URL; ?>/admin/users">Retour à la liste des utilisateurs</a>
        </p>
    </div>
</body>
</html>