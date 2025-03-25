<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budgets de votre Département - Gestion Financière</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/budgets.css">
</head>

<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h1 class="sidebar-title">Budgets</h1>
            <p class="departement-name"><?php echo htmlspecialchars($nom_departement); ?></p>

            <!-- Formulaire Proposition Budget -->
            <section class="budget-form">
                <h2>Proposer un budget</h2>
                <form method="POST" action="<?php echo BASE_URL; ?>/user/budgets/propose">
                    <div class="form-group">
                        <label for="mois">Mois</label>
                        <select id="mois" name="mois" required>
                            <option value="">Sélectionner un mois</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo sprintf("%02d", $i); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="annee">Année</label>
                        <select id="annee" name="annee" required>
                            <option value="">Sélectionner une année</option>
                            <?php for ($i = date('Y'); $i <= date('Y') + 5; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="solde_depart">Solde de départ (€)</label>
                        <input type="number" step="0.01" id="solde_depart" name="solde_depart" placeholder="Ex: 1000.00" required>
                    </div>
                    <h3>Détails du budget</h3>
                    <div id="budget-details">
                        <!-- Ligne de détail initiale -->
                        <div class="detail-row">
                            <select name="categories[]" required>
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach ($categories as $categorie): ?>
                                    <option value="<?php echo $categorie['id_categorie']; ?>">
                                        <?php echo htmlspecialchars($categorie['nom']) . ' (' . $categorie['type'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" step="0.01" name="montants[]" placeholder="Montant (€)" required>
                            <input type="text" name="descriptions[]" placeholder="Description">
                            <button type="button" class="btn-remove" onclick="removeDetailRow(this)">×</button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-add" onclick="addDetailRow()">+ Ajouter un détail</button>
                    <button type="submit" class="btn btn-submit">Proposer</button>
                </form>
            </section>

            <!-- Section Importation Budget -->
            <section class="import-budget">
                <h2>Importer un budget</h2>
                <form method="POST" action="<?php echo BASE_URL; ?>/user/budgets/import" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="budget_file">Fichier CSV</label>
                        <input type="file" id="budget_file" name="budget_file" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-import">Importer</button>
                </form>
                <p class="model-link"><a href="<?php echo BASE_URL; ?>/assets/sample_budget.csv" download>Télécharger un modèle CSV</a></p>
            </section>

            <!-- Filtres -->
            <section class="filters">
                <h2>Filtrer les budgets</h2>
                <form method="GET" action="<?php echo BASE_URL; ?>/user/budgets">
                    <div class="form-group">
                        <label for="mois_filter">Mois</label>
                        <select id="mois_filter" name="mois">
                            <option value="">Tous les mois</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $mois == $i ? 'selected' : ''; ?>>
                                    <?php echo sprintf("%02d", $i); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="annee_filter">Année</label>
                        <select id="annee_filter" name="annee">
                            <option value="">Toutes les années</option>
                            <?php for ($i = date('Y'); $i >= 2020; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php echo $annee == $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-filter">Filtrer</button>
                </form>
            </section>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <button class="btn btn-export" data-toggle="modal" data-target="#exportBudgetsModal">Exporter en PDF</button>
            </header>

            <!-- Messages -->
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

            <!-- Liste des budgets -->
            <section class="budgets-list">
                <h2>Budgets</h2>
                <?php if (empty($periodes)): ?>
                    <p>Aucun budget trouvé pour votre département.</p>
                <?php else: ?>
                    <?php foreach ($periodes as $periode): ?>
                        <article class="budget-period">
                            <h3><?php echo (new \DateTime())->setDate($periode['annee'], $periode['mois'], 1)->format('F Y'); ?></h3>
                            <?php foreach ($periode['budgets'] as $budget): ?>
                                <div class="budget-card">
                                    <h4>Budget - Statut : <?php echo htmlspecialchars($budget['statut']); ?></h4>
                                    <ul class="budget-summary">
                                        <li><strong>Solde de départ :</strong> <?php echo number_format($budget['solde_depart'], 2); ?> €</li>
                                        <li><strong>Gains prévus :</strong> <?php echo number_format($budget['total_gains'], 2); ?> €</li>
                                        <li><strong>Dépenses prévues :</strong> <?php echo number_format($budget['total_depenses'], 2); ?> €</li>
                                        <li><strong>Solde final prévu :</strong> <?php echo number_format($budget['solde_final_calculee'], 2); ?> €</li>
                                    </ul>
                                    <h5>Détails :</h5>
                                    <?php if (empty($budget['details'])): ?>
                                        <p>Aucun détail disponible.</p>
                                    <?php else: ?>
                                        <table class="budget-details-table">
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
                            <?php endforeach; ?>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- Navigation -->
            <footer class="navigation">
                <a href="<?php echo BASE_URL; ?>/dashboard" class="btn btn-back">Retour au tableau de bord</a>
                <a href="<?php echo BASE_URL; ?>/user/finances" class="btn btn-next">Gérer les transactions</a>
            </footer>
        </main>

        <!-- Modal Export -->
        <div class="modal" id="exportBudgetsModal">
            <div class="modal-content">
                <header>
                    <h2>Exporter les budgets</h2>
                    <button class="close" data-dismiss="modal">×</button>
                </header>
                <main>
                    <p>Choisissez une option d'exportation :</p>
                    <a href="<?php echo BASE_URL; ?>/user/export/budgets/pdf?all=true" class="btn btn-export-all">Exporter tous les budgets</a>
                    <form action="<?php echo BASE_URL; ?>/user/export/budgets/pdf" method="GET">
                        <div class="form-group">
                            <label for="mois_export">Mois</label>
                            <select id="mois_export" name="mois">
                                <option value="">Sélectionner un mois</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo sprintf("%02d", $i); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="annee_export">Année</label>
                            <select id="annee_export" name="annee">
                                <option value="">Sélectionner une année</option>
                                <?php for ($i = date('Y'); $i >= 2020; $i--): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-export-selected">Exporter la période sélectionnée</button>
                    </form>
                </main>
                <footer>
                    <button class="btn btn-cancel" data-dismiss="modal">Annuler</button>
                </footer>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        function addDetailRow() {
            const container = document.getElementById('budget-details');
            const row = document.createElement('div');
            row.className = 'detail-row';
            row.innerHTML = `
                <select name="categories[]" required>
                    <option value="">Sélectionner une catégorie</option>
                    <?php foreach ($categories as $categorie): ?>
                        <option value="<?php echo $categorie['id_categorie']; ?>">
                            <?php echo htmlspecialchars($categorie['nom']) . ' (' . $categorie['type'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" step="0.01" name="montants[]" placeholder="Montant (€)" required>
                <input type="text" name="descriptions[]" placeholder="Description">
                <button type="button" class="btn-remove" onclick="removeDetailRow(this)">×</button>
            `;
            container.appendChild(row);
        }

        function removeDetailRow(button) {
            const container = document.getElementById('budget-details');
            if (container.children.length > 1) { // Empêche de supprimer la dernière ligne
                button.parentElement.remove();
            }
        }
    </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/jquery.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/bootstrap.min.js"></script>
</body>

</html>