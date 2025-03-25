<!-- app/views/user/dashboard.php -->
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Utilisateur - Gestion Financière</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/dashboard.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
        }

        .periode-section {
            margin-bottom: 40px;
        }

        .departement-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="dashboard-container">
            <h2 class="text-center">Tableau de bord Utilisateur</h2>
            <p class="text-center">Bienvenue, <?php echo htmlspecialchars($utilisateur['nom_utilisateur']); ?> !</p>
            <div class="departement-title">Département : <?php echo htmlspecialchars($nom_departement); ?></div>

            <!-- Filtres -->
            <h4>Sélectionner une période</h4>
            <form method="GET" action="<?php echo BASE_URL; ?>/dashboard" class="mb-4">
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
                        <button type="submit" class="btn btn-primary">Afficher</button>
                    </div>
                </div>
            </form>

            <!-- Bouton pour ouvrir la modale -->
            <div class="form-group col-md-3 align-self-end">
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#exportDashboardModal">Exporter en PDF</button>
            </div>

            <!-- Modale pour Bootstrap 3.5 -->
            <div class="modal fade" id="exportDashboardModal" tabindex="-1" role="dialog" aria-labelledby="exportDashboardModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="exportDashboardModalLabel">Exporter le tableau de bord</h4>
                        </div>
                        <div class="modal-body">
                            <p>Choisissez une option d'exportation :</p>
                            <a href="<?php echo BASE_URL; ?>/user/export/dashboard/pdf?all=true" class="btn btn-primary btn-block mb-3">Exporter toutes les périodes</a>
                            <form action="<?php echo BASE_URL; ?>/user/export/dashboard/pdf" method="GET">
                                <div class="form-group">
                                    <label for="mois_export">Mois</label>
                                    <select class="form-control" id="mois_export" name="mois">
                                        <option value="">Sélectionner un mois</option>
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo sprintf("%02d", $i); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="annee_export">Année</label>
                                    <select class="form-control" id="annee_export" name="annee">
                                        <option value="">Sélectionner une année</option>
                                        <?php for ($i = date('Y'); $i >= 2020; $i--): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Exporter la période sélectionnée</button>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Affichage des périodes -->
            <?php if (empty($periodes)): ?>
                <p class="text-center">Aucune donnée financière disponible pour votre département.</p>
            <?php else: ?>
                <?php foreach ($periodes as $periode): ?>
                    <div class="periode-section">
                        <h4>Récapitulatif financier pour <?php echo (new \DateTime())->setDate($periode['annee'], $periode['mois'], 1)->format('F Y'); ?></h4>
                        <table class="table table-bordered">
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
                                    <td <?php echo $periode['ecarts']['gains'] < 0 ? 'style="color: red;"' : ''; ?>><?php echo number_format(abs($periode['ecarts']['gains']), 2); ?></td>
                                </tr>
                                <tr>
                                    <td>Dépenses (€)</td>
                                    <td><?php echo $periode['budget'] ? number_format($periode['budget']['total_depenses'], 2) : '0.00'; ?></td>
                                    <td><?php echo number_format($periode['realisations']['total_depenses'], 2); ?></td>
                                    <td <?php echo $periode['ecarts']['depenses'] < 0 ? 'style="color: red;"' : ''; ?>><?php echo number_format(abs($periode['ecarts']['depenses']), 2); ?></td>
                                </tr>
                                <tr>
                                    <td>Solde final (€)</td>
                                    <td><?php echo $periode['budget'] ? number_format($periode['budget']['solde_final_calculee'], 2) : '0.00'; ?></td>
                                    <td><?php echo number_format($periode['realisations']['solde_final'], 2); ?></td>
                                    <td <?php echo $periode['ecarts']['solde_final'] < 0 ? 'style="color: red;"' : ''; ?>><?php echo number_format(abs($periode['ecarts']['solde_final']), 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Options -->
            <h4>Vos options :</h4>
            <ul>
                <li><a href="<?php echo BASE_URL; ?>/user/profile">Voir et modifier mon profil</a></li>
                <li><a href="<?php echo BASE_URL; ?>/user/budgets">Voir les budgets de mon département</a></li>
                <li><a href="<?php echo BASE_URL; ?>/user/finances">Gérer mes transactions</a></li>
            </ul>
            <a href="<?php echo BASE_URL; ?>/logout" class="btn btn-danger">Se déconnecter</a>
        </div>
    </div>
    <script src="<?php echo BASE_URL; ?>/assets/js/jquery.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/bootstrap.min.js"></script>
</body>

</html>