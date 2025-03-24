<!-- app/views/user/finances.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Finances - Gestion Financière</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css">
    <style>
        .container { max-width: 1200px; margin: 50px auto; }
        .summary-card { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Gestion des Finances de votre Département</h2>

        <!-- Messages de succès ou d'erreur -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($error as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Résumé financier -->
        <div class="row summary-card">
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Total des Gains</h5>
                        <p class="card-text"><?php echo number_format($summary['total_gains'], 2); ?> €</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-danger">
                    <div class="card-body">
                        <h5 class="card-title">Total des Dépenses</h5>
                        <p class="card-text"><?php echo number_format($summary['total_depenses'], 2); ?> €</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white <?php echo $summary['solde_final'] >= 0 ? 'bg-info' : 'bg-warning'; ?>">
                    <div class="card-body">
                        <h5 class="card-title">Solde Final</h5>
                        <p class="card-text"><?php echo number_format($summary['solde_final'], 2); ?> €</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <h4>Filtrer les transactions</h4>
        <form method="GET" action="<?php echo BASE_URL; ?>/user/finances" class="mb-4">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="mois">Mois</label>
                    <select class="form-control" id="mois" name="mois">
                        <option value="">Tous les mois</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $mois == $i ? 'selected' : ''; ?>>
                                <?php echo sprintf("%02d", $i); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label for="annee">Année</label>
                    <select class="form-control" id="annee" name="annee">
                        <option value="">Toutes les années</option>
                        <?php for ($i = date('Y'); $i >= 2020; $i--): ?>
                            <option value="<?php echo $i; ?>" <?php echo $annee == $i ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group col-md-3 align-self-end">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                </div>
            </div>
        </form>

        <!-- Formulaire pour ajouter une transaction -->
        <h4>Ajouter une transaction</h4>
        <p class="text-info">Note : Vous ne pouvez ajouter une transaction que si un budget approuvé existe pour le mois et l'année correspondants. <a href="<?php echo BASE_URL; ?>/user/budgets">Voir les budgets</a></p>
        <form method="POST" action="<?php echo BASE_URL; ?>/user/finances/add" class="mb-5">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="id_categorie">Catégorie</label>
                    <select class="form-control" id="id_categorie" name="id_categorie" required>
                        <option value="">Sélectionner une catégorie</option>
                        <?php foreach ($categories as $categorie): ?>
                            <option value="<?php echo $categorie['id_categorie']; ?>">
                                <?php echo htmlspecialchars($categorie['nom']) . ' (' . $categorie['type'] . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label for="montant">Montant (€)</label>
                    <input type="number" step="0.01" class="form-control" id="montant" name="montant" required>
                </div>
                <div class="form-group col-md-2">
                    <label for="date_transaction">Date</label>
                    <input type="date" class="form-control" id="date_transaction" name="date_transaction" required>
                </div>
                <div class="form-group col-md-3">
                    <label for="description">Description</label>
                    <input type="text" class="form-control" id="description" name="description">
                </div>
                <div class="form-group col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary btn-block">Ajouter</button>
                </div>
            </div>
        </form>

        <!-- Liste des transactions -->
        <h4>Transactions du département</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Catégorie</th>
                    <th>Type</th>
                    <th>Montant (€)</th>
                    <th>Description</th>
                    <th>Mois</th>
                    <th>Année</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="8" class="text-center">Aucune transaction trouvée.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($transaction['id_transaction']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['nom_categorie']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['type_categorie']); ?></td>
                            <td><?php echo number_format($transaction['montant'], 2); ?></td>
                            <td><?php echo htmlspecialchars($transaction['description'] ?: 'N/A'); ?></td>
                            <td><?php echo sprintf("%02d", $transaction['mois']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['annee']); ?></td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>/user/finances/edit/<?php echo $transaction['id_transaction']; ?>" class="btn btn-sm btn-warning">Modifier</a>
                                <a href="<?php echo BASE_URL; ?>/user/finances/delete/<?php echo $transaction['id_transaction']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette transaction ?');">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <a href="<?php echo BASE_URL; ?>/dashboard" class="btn btn-secondary">Retour au tableau de bord</a>
    </div>
</body>
</html>