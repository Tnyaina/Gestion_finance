<!-- app/views/user/budgets.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budgets de votre Département - Gestion Financière</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/budgets.css">
    <script>
        function addDetailRow() {
            const container = document.getElementById('budget-details');
            const row = document.createElement('div');
            row.className = 'detail-row';
            row.innerHTML = `
                <div class="form-group">
                    <select class="form-control" name="categories[]" required>
                        <option value="">Sélectionner une catégorie</option>
                        <?php foreach ($categories as $categorie): ?>
                            <option value="<?php echo $categorie['id_categorie']; ?>">
                                <?php echo htmlspecialchars($categorie['nom']) . ' (' . $categorie['type'] . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <input type="number" step="0.01" class="form-control" name="montants[]" placeholder="Montant (€)" required>
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" name="descriptions[]" placeholder="Description">
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-danger" onclick="removeDetailRow(this)">Supprimer</button>
                </div>
            `;
            container.appendChild(row);
        }

        function removeDetailRow(button) {
            button.parentElement.parentElement.remove();
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h2 class="text-center">Budgets</h2>
            <div class="departement-title"><?php echo htmlspecialchars($nom_departement); ?></div>

            <!-- Formulaire pour proposer un budget -->
            <div class="budget-form">
                <h4>Proposer un budget</h4>
                <form method="POST" action="<?php echo BASE_URL; ?>/user/budgets/propose">
                    <div class="form-group">
                        <label for="mois">Mois</label>
                        <select class="form-control" id="mois" name="mois" required>
                            <option value="">Sélectionner un mois</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo sprintf("%02d", $i); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="annee">Année</label>
                        <select class="form-control" id="annee" name="annee" required>
                            <option value="">Sélectionner une année</option>
                            <?php for ($i = date('Y'); $i <= date('Y') + 5; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="solde_depart">Solde de départ (€)</label>
                        <input type="number" step="0.01" class="form-control" id="solde_depart" name="solde_depart" placeholder="Ex: 1000.00" required>
                    </div>
                    <h5>Détails du budget</h5>
                    <div id="budget-details">
                        <div class="detail-row">
                            <div class="form-group">
                                <select class="form-control" name="categories[]" required>
                                    <option value="">Sélectionner une catégorie</option>
                                    <?php foreach ($categories as $categorie): ?>
                                        <option value="<?php echo $categorie['id_categorie']; ?>">
                                            <?php echo htmlspecialchars($categorie['nom']) . ' (' . $categorie['type'] . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <input type="number" step="0.01" class="form-control" name="montants[]" placeholder="Montant (€)" required>
                            </div>
                            <div class="form-group">
                                <input type="text" class="form-control" name="descriptions[]" placeholder="Description">
                            </div>
                            <div class="form-group">
                                <button type="button" class="btn btn-danger" onclick="removeDetailRow(this)">Supprimer</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addDetailRow()">Ajouter un détail</button>
                    <button type="submit" class="btn btn-primary mt-2">Proposer</button>
                </form>
            </div>

            <!-- Filtres -->
            <div class="filters">
                <h4>Filtrer les budgets</h4>
                <form method="GET" action="<?php echo BASE_URL; ?>/user/budgets">
                    <div class="form-group">
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
                    <div class="form-group">
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
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                </form>
            </div>
        </div>

        <div class="main-content">
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

            <!-- Liste des budgets par période -->
            <h4>Budgets</h4>
            <?php if (empty($periodes)): ?>
                <p class="text-center">Aucun budget trouvé pour votre département.</p>
            <?php else: ?>
                <?php foreach ($periodes as $periode): ?>
                    <div class="periode-section">
                        <h5><?php echo (new \DateTime())->setDate($periode['annee'], $periode['mois'], 1)->format('F Y'); ?></h5>
                        <?php foreach ($periode['budgets'] as $budget): ?>
                            <div class="card">
                                <div class="card-header">
                                    Budget - Statut : <?php echo htmlspecialchars($budget['statut']); ?>
                                </div>
                                <div class="card-body">
                                    <p><strong>Solde de départ :</strong> <?php echo number_format($budget['solde_depart'], 2); ?> €</p>
                                    <p><strong>Gains prévus :</strong> <?php echo number_format($budget['total_gains'], 2); ?> €</p>
                                    <p><strong>Dépenses prévues :</strong> <?php echo number_format($budget['total_depenses'], 2); ?> €</p>
                                    <p><strong>Solde final prévu :</strong> <?php echo number_format($budget['solde_final_calculee'], 2); ?> €</p>
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
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="navigation-buttons">
                <a href="<?php echo BASE_URL; ?>/dashboard" class="btn btn-secondary">Retour au tableau de bord</a>
                <a href="<?php echo BASE_URL; ?>/user/finances" class="btn btn-primary">Gérer les transactions</a>
            </div>
        </div>
    </div>
</body>
</html>