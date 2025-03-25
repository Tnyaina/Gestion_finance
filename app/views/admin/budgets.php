<!-- app/views/admin/budgets.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des budgets - Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css">
    <style>
        .container { max-width: 1200px; margin: 50px auto; }
        .budget-details { margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Gestion des budgets</h2>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <h4>Budgets en attente de validation</h4>
        <?php if (empty($budgets)): ?>
            <p class="text-center">Aucun budget en attente de validation.</p>
        <?php else: ?>
            <?php foreach ($budgets as $budget): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        Budget - <?php echo htmlspecialchars($budget['nom_departement']); ?> - 
                        <?php echo (new \DateTime())->setDate($budget['annee'], $budget['mois'], 1)->format('F Y'); ?>
                    </div>
                    <div class="card-body">
                        <p><strong>Solde de départ :</strong> <?php echo number_format($budget['solde_depart'], 2); ?> €</p>
                        <p><strong>Solde final prévu :</strong> <?php echo number_format($budget['solde_final'], 2); ?> €</p>
                        <h6>Détails :</h6>
                        <?php if (empty($budget['details'])): ?>
                            <p class="budget-details">Aucun détail disponible.</p>
                        <?php else: ?>
                            <table class="table table-bordered budget-details">
                                <thead>
                                    <tr>
                                        <th>Catégorie</th>
                                        <th>Type</th>
                                        <th>Montant (€)</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($budget['details'] as $detail): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($detail['nom_categorie']); ?></td>
                                            <td><?php echo htmlspecialchars($detail['type_categorie']); ?></td>
                                            <td><?php echo number_format($detail['montant'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($detail['description'] ?: 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                        <div class="mt-2">
                            <a href="<?php echo BASE_URL; ?>/admin/budgets/approve/<?php echo $budget['id_budget']; ?>" 
                               class="btn btn-success btn-sm" 
                               onclick="return confirm('Êtes-vous sûr de vouloir approuver ce budget ?');">Approuver</a>
                            <a href="<?php echo BASE_URL; ?>/admin/budgets/reject/<?php echo $budget['id_budget']; ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('Êtes-vous sûr de vouloir rejeter ce budget ?');">Rejeter</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>/admin/dashboard" class="btn btn-secondary">Retour au tableau de bord</a>
    </div>
</body>
</html>