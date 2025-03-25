<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Admin - Gestion Financière</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin_dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Alerts -->
        <?php if (isset($_SESSION['import_success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['import_success']); ?></div>
            <?php unset($_SESSION['import_success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['import_error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['import_error']); ?></div>
            <?php unset($_SESSION['import_error']); ?>
        <?php endif; ?>

        <!-- Header -->
        <h2 class="text-center">Tableau de bord Admin</h2>
        <p class="text-center">Bienvenue, <?php echo htmlspecialchars($utilisateur['nom_utilisateur']); ?> !</p>
        <p class="text-center">Actions administratives :</p>
        <ul class="text-center list-unstyled">
            <li><a href="<?php echo BASE_URL; ?>/admin/users">Gérer les utilisateurs</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/departements">Gérer les départements</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/budgets">Valider les budgets</a></li>
        </ul>

        <!-- Filters -->
        <h3>Sélectionner une période</h3>
        <form method="GET" action="<?php echo BASE_URL; ?>/admin/dashboard" class="mb-4">
            <div class="form-row">
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
                        <?php for ($i = date('Y') + 1; $i >= 2020; $i--): ?>
                            <option value="<?php echo $i; ?>" <?php echo $annee == $i ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Afficher</button>
                </div>
            </div>
        </form>

        <!-- Import Section -->
        <div>
            <button id="importButton" class="btn" onclick="toggleImportForm()">Importer des données</button>
        </div>
        <div id="importForm" style="display: none;">
            <form method="POST" action="<?php echo BASE_URL; ?>/admin/import" enctype="multipart/form-data">
                <label for="import_type">Type de données :</label>
                <select name="import_type" id="import_type" required>
                    <option value="utilisateurs">Utilisateurs</option>
                    <option value="departements">Départements</option>
                    <option value="categories">Catégories</option>
                </select>
                <label for="file">Fichier CSV :</label>
                <input type="file" name="file" accept=".csv" required>
                <button type="submit" class="btn btn-primary">Importer</button>
            </form>
            <p>
                <a href="<?php echo BASE_URL; ?>/assets/user.csv">Modèle Utilisateurs</a> |
                <a href="<?php echo BASE_URL; ?>/assets/dept.cscv">Modèle Départements</a> |
                <a href="<?php echo BASE_URL; ?>/assets/cat.csv">Modèle Catégories</a>
            </p>
        </div>

        <!-- Global Situation -->
        <div class="global-section">
            <h3>Situation Globale</h3>
            <?php if ($situationGlobale === null): ?>
                <p class="text-center">Aucune donnée disponible. Sélectionnez une période.</p>
            <?php elseif (!empty($situationGlobale)): ?>
                <?php $moisNoms = [1 => "Janvier", 2 => "Février", 3 => "Mars", 4 => "Avril", 5 => "Mai", 6 => "Juin", 7 => "Juillet", 8 => "Août", 9 => "Septembre", 10 => "Octobre", 11 => "Novembre", 12 => "Décembre"]; ?>
                <?php foreach ($situationGlobale as $globale): ?>
                    <div class="periode-section">
                        <h4>Récapitulatif pour <?php echo $moisNoms[$globale['mois']] . ' ' . $globale['annee']; ?></h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Rubrique</th>
                                    <th>Prévisions</th>
                                    <th>Réalisations</th>
                                    <th>Écarts</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Solde de départ (€)</td>
                                    <td><?php echo number_format($globale['solde_depart_previsionnel'], 2); ?></td>
                                    <td><?php echo number_format($globale['solde_depart_realise'], 2); ?></td>
                                    <td>0.00</td>
                                </tr>
                                <tr>
                                    <td>Gains (€)</td>
                                    <td><?php echo number_format($globale['gains_previsionnels'], 2); ?></td>
                                    <td><?php echo number_format($globale['gains_realises'], 2); ?></td>
                                    <td style="<?php echo ($globale['gains_realises'] - $globale['gains_previsionnels']) < 0 ? 'color: red;' : ''; ?>">
                                        <?php echo number_format(abs($globale['gains_realises'] - $globale['gains_previsionnels']), 2); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Dépenses (€)</td>
                                    <td><?php echo number_format($globale['depenses_previsionnelles'], 2); ?></td>
                                    <td><?php echo number_format($globale['depenses_realisees'], 2); ?></td>
                                    <td style="<?php echo ($globale['depenses_realisees'] - $globale['depenses_previsionnelles']) < 0 ? 'color: red;' : ''; ?>">
                                        <?php echo number_format(abs($globale['depenses_realisees'] - $globale['depenses_previsionnelles']), 2); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Solde final (€)</td>
                                    <td><?php echo number_format($globale['solde_final_previsionnel'], 2); ?></td>
                                    <td><?php echo number_format($globale['solde_final_realise'], 2); ?></td>
                                    <td style="<?php echo ($globale['solde_final_realise'] - $globale['solde_final_previsionnel']) < 0 ? 'color: red;' : ''; ?>">
                                        <?php echo number_format(abs($globale['solde_final_realise'] - $globale['solde_final_previsionnel']), 2); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Department Details (Only shown when filters are applied) -->
        <?php if (!empty($periodes) && $mois && $annee): ?>
            <div class="details-section">
                <h3>Détails par Département</h3>
                <?php foreach ($periodes as $periode): ?>
                    <div class="periode-section">
                        <h4><?php echo (new \DateTime())->setDate($periode['annee'], $periode['mois'], 1)->format('F Y'); ?> - <?php echo htmlspecialchars($periode['nom_departement']); ?></h4>
                        <?php $isFuture = ($periode['annee'] > $annee) || ($periode['annee'] == $annee && $periode['mois'] > $mois); ?>
                        <?php if ($isFuture && !$periode['budget'] && $periode['realisations']['total_gains'] == 0 && $periode['realisations']['total_depenses'] == 0): ?>
                            <p class="future-note">Période future sans budget ni transactions.</p>
                        <?php endif; ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Rubrique</th>
                                    <th>Prévisions</th>
                                    <th>Réalisations</th>
                                    <th>Écarts</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Solde de départ (€)</td>
                                    <td><?php echo $periode['budget'] ? number_format($periode['budget']['solde_depart'], 2) : '0.00'; ?></td>
                                    <td><?php echo number_format($periode['realisations']['solde_depart'], 2); ?></td>
                                    <td>0.00</td>
                                </tr>
                                <tr>
                                    <td>Gains (€)</td>
                                    <td><?php echo $periode['budget'] ? number_format($periode['budget']['total_gains'], 2) : '0.00'; ?></td>
                                    <td><?php echo number_format($periode['realisations']['total_gains'], 2); ?></td>
                                    <td style="<?php echo $periode['ecarts']['gains'] < 0 ? 'color: red;' : ''; ?>">
                                        <?php echo number_format(abs($periode['ecarts']['gains']), 2); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Dépenses (€)</td>
                                    <td><?php echo $periode['budget'] ? number_format($periode['budget']['total_depenses'], 2) : '0.00'; ?></td>
                                    <td><?php echo number_format($periode['realisations']['total_depenses'], 2); ?></td>
                                    <td style="<?php echo $periode['ecarts']['depenses'] < 0 ? 'color: red;' : ''; ?>">
                                        <?php echo number_format(abs($periode['ecarts']['depenses']), 2); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Solde final (€)</td>
                                    <td><?php echo $periode['budget'] ? number_format($periode['budget']['solde_final_calculee'], 2) : '0.00'; ?></td>
                                    <td><?php echo number_format($periode['realisations']['solde_final'], 2); ?></td>
                                    <td style="<?php echo $periode['ecarts']['solde_final'] < 0 ? 'color: red;' : ''; ?>">
                                        <?php echo number_format(abs($periode['ecarts']['solde_final']), 2); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Gains and Expenses Sections -->
        <?php if (!empty($gains)): ?>
            <div class="details-section" id="gains-section">
                <h3>Liste des Gains Prévisionnels</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Département</th>
                            <th>Période</th>
                            <th>Catégorie</th>
                            <th>Montant (€)</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gains as $gain): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($gain['nom_departement']); ?></td>
                                <td><?php echo htmlspecialchars($gain['periode']); ?></td>
                                <td><?php echo htmlspecialchars($gain['categorie_gain']); ?></td>
                                <td><?php echo number_format($gain['montant'], 2); ?></td>
                                <td><?php echo htmlspecialchars($gain['description']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (!empty($gainsRealises)): ?>
            <div class="details-section">
                <h3>Liste des Gains Réalisés</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Département</th>
                            <th>Période</th>
                            <th>Catégorie</th>
                            <th>Montant (€)</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gainsRealises as $gain): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($gain['nom_departement']); ?></td>
                                <td><?php echo htmlspecialchars($gain['mois']); ?>/<?php echo htmlspecialchars($gain['annee']); ?></td>
                                <td><?php echo htmlspecialchars($gain['categorie_gain']); ?></td>
                                <td><?php echo number_format($gain['montant'], 2); ?></td>
                                <td><?php echo htmlspecialchars($gain['description']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (!empty($depenses)): ?>
            <div class="details-section" id="depenses-section">
                <h3>Liste des Dépenses Prévisionnelles</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Département</th>
                            <th>Période</th>
                            <th>Catégorie</th>
                            <th>Montant (€)</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($depenses as $depense): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($depense['nom_departement']); ?></td>
                                <td><?php echo htmlspecialchars($depense['periode']); ?></td>
                                <td><?php echo htmlspecialchars($depense['categorie_depense']); ?></td>
                                <td><?php echo number_format($depense['montant'], 2); ?></td>
                                <td><?php echo htmlspecialchars($depense['description']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (!empty($depensesRealises)): ?>
            <div class="details-section">
                <h3>Liste des Dépenses Réalisées</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Département</th>
                            <th>Période</th>
                            <th>Catégorie</th>
                            <th>Montant (€)</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($depensesRealises as $depense): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($depense['nom_departement']); ?></td>
                                <td><?php echo htmlspecialchars($depense['mois']); ?>/<?php echo htmlspecialchars($depense['annee']); ?></td>
                                <td><?php echo htmlspecialchars($depense['categorie_depense']); ?></td>
                                <td><?php echo number_format($depense['montant'], 2); ?></td>
                                <td><?php echo htmlspecialchars($depense['description']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Export Section -->
        <div>
            <button id="exportButton" class="btn" onclick="toggleExportForm()">Exporter une période</button>
            <button id="exportMonthButton" class="btn" onclick="toggleExportMonthForm()">Exporter un mois</button>
            <a href="<?php echo BASE_URL; ?>/logout" class="btn btn-danger">Se déconnecter</a>
        </div>
        <?php if (!$mois && !$annee): ?>
            <div id="exportForm" style="display: none;">
                <form method="POST" action="<?php echo BASE_URL; ?>/admin/export">
                    <label for="start_date">Date de début :</label>
                    <input type="month" id="start_date" name="start_date" required>
                    <label for="end_date">Date de fin :</label>
                    <input type="month" id="end_date" name="end_date" required>
                    <button type="submit" class="btn btn-primary">Exporter en PDF</button>
                </form>
            </div>
        <?php endif; ?>
        <div id="exportMonthForm" style="display: none;">
            <form method="POST" action="<?php echo BASE_URL; ?>/admin/export_month">
                <label for="month_date">Mois :</label>
                <input type="month" id="month_date" name="month_date" required>
                <button type="submit" class="btn btn-primary">Exporter en PDF</button>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        function toggleImportForm() {
            document.getElementById('importForm').style.display = 
                document.getElementById('importForm').style.display === 'none' ? 'block' : 'none';
        }

        function toggleExportForm() {
            document.getElementById('exportForm').style.display = 
                document.getElementById('exportForm').style.display === 'none' ? 'block' : 'none';
            document.getElementById('exportMonthForm').style.display = 'none';
        }

        function toggleExportMonthForm() {
            document.getElementById('exportMonthForm').style.display = 
                document.getElementById('exportMonthForm').style.display === 'none' ? 'block' : 'none';
            document.getElementById('exportForm').style.display = 'none';
        }
    </script>
</body>
</html>