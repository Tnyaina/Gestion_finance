<!-- app/views/user/edit_transaction.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une Transaction - Gestion Financière</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css">
    <style>
        .container { max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Modifier une Transaction</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($error as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="POST" action="<?php echo BASE_URL; ?>/user/finances/edit/<?php echo $transaction['id_transaction']; ?>">
            <div class="form-group">
                <label for="id_categorie">Catégorie</label>
                <select class="form-control" id="id_categorie" name="id_categorie" required>
                    <option value="">Sélectionner une catégorie</option>
                    <?php foreach ($categories as $categorie): ?>
                        <option value="<?php echo $categorie['id_categorie']; ?>" <?php echo $transaction['id_categorie'] == $categorie['id_categorie'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($categorie['nom']) . ' (' . $categorie['type'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="montant">Montant (€)</label>
                <input type="number" step="0.01" class="form-control" id="montant" name="montant" value="<?php echo htmlspecialchars($transaction['montant']); ?>" required>
            </div>
            <div class="form-group">
                <label for="date_transaction">Date</label>
                <?php $date_transaction = $transaction['annee'] . '-' . sprintf("%02d", $transaction['mois']) . '-01'; ?>
                <input type="date" class="form-control" id="date_transaction" name="date_transaction" value="<?php echo htmlspecialchars($date_transaction); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <input type="text" class="form-control" id="description" name="description" value="<?php echo htmlspecialchars($transaction['description']); ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Mettre à jour</button>
        </form>
        <p class="text-center mt-3">
            <a href="<?php echo BASE_URL; ?>/user/finances">Retour à la gestion des finances</a>
        </p>
    </div>
</body>
</html>