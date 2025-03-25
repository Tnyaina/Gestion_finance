<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Finances - Gestion Financière</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/finances.css">
</head>
<body>
    <div class="app-container">
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>Gestion des Finances de votre Département</h1>
                <button class="btn btn-export" data-toggle="modal" data-target="#exportFinancesModal">Exporter en PDF</button>
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

            <!-- Résumé financier -->
            <section class="summary-section">
                <div class="summary-card">
                    <h3>Total des Gains</h3>
                    <p><?php echo number_format($summary['total_gains'], 2); ?> €</p>
                </div>
                <div class="summary-card">
                    <h3>Total des Dépenses</h3>
                    <p><?php echo number_format($summary['total_depenses'], 2); ?> €</p>
                </div>
                <div class="summary-card">
                    <h3>Solde Final</h3>
                    <p class="<?php echo $summary['solde_final'] >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo number_format($summary['solde_final'], 2); ?> €
                    </p>
                </div>
            </section>

            <!-- Filtres -->
            <section class="filters">
                <h2>Filtrer les transactions</h2>
                <form method="GET" action="<?php echo BASE_URL; ?>/user/finances">
                    <div class="form-group">
                        <label for="mois">Mois</label>
                        <select id="mois" name="mois">
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
                        <select id="annee" name="annee">
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

            <!-- Formulaire pour ajouter une transaction -->
            <section class="add-transaction">
                <h2>Ajouter une transaction</h2>
                <p class="info-note">Note : Vous ne pouvez ajouter une transaction que si un budget approuvé existe pour le mois et l'année correspondants. <a href="<?php echo BASE_URL; ?>/user/budgets">Voir les budgets</a></p>
                <form method="POST" action="<?php echo BASE_URL; ?>/user/finances/add">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="id_categorie">Catégorie</label>
                            <select id="id_categorie" name="id_categorie" required>
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach ($categories as $categorie): ?>
                                    <option value="<?php echo $categorie['id_categorie']; ?>">
                                        <?php echo htmlspecialchars($categorie['nom']) . ' (' . $categorie['type'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="montant">Montant (€)</label>
                            <input type="number" step="0.01" id="montant" name="montant" required>
                        </div>
                        <div class="form-group">
                            <label for="date_transaction">Date</label>
                            <input type="date" id="date_transaction" name="date_transaction" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <input type="text" id="description" name="description">
                        </div>
                        <button type="submit" class="btn btn-submit">Ajouter</button>
                    </div>
                </form>
            </section>

            <!-- Liste des transactions -->
            <section class="transactions-list">
                <h2>Transactions du département</h2>
                <table class="transactions-table">
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
                                <td colspan="8">Aucune transaction trouvée.</td>
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
                                        <a href="<?php echo BASE_URL; ?>/user/finances/edit/<?php echo $transaction['id_transaction']; ?>" class="btn btn-edit">Modifier</a>
                                        <a href="<?php echo BASE_URL; ?>/user/finances/delete/<?php echo $transaction['id_transaction']; ?>" class="btn btn-delete" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette transaction ?');">Supprimer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <!-- Navigation -->
            <footer class="navigation">
                <a href="<?php echo BASE_URL; ?>/dashboard" class="btn btn-back">Retour au tableau de bord</a>
            </footer>
        </main>

        <!-- Modal Export -->
        <div class="modal" id="exportFinancesModal">
            <div class="modal-content">
                <header>
                    <h2>Exporter les finances</h2>
                    <button class="close" data-dismiss="modal">×</button>
                </header>
                <main>
                    <p>Choisissez une option d'exportation :</p>
                    <a href="<?php echo BASE_URL; ?>/user/export/finances/pdf?all=true" class="btn btn-export-all">Exporter toutes les transactions</a>
                    <form action="<?php echo BASE_URL; ?>/user/export/finances/pdf" method="GET">
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

    <script src="<?php echo BASE_URL; ?>/assets/js/jquery.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/bootstrap.min.js"></script>
</body>
</html>