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
                            <button class="btn btn-warning btn-sm" 
                                    data-id="<?php echo $budget['id_budget']; ?>"
                                    data-budget='<?php echo htmlspecialchars(json_encode($budget), ENT_QUOTES, 'UTF-8'); ?>'
                                    onclick="openEditModal(this)">Modifier</button>
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

    <!-- Modale pour modifier le budget - adaptée pour Bootstrap 3 -->
    <div class="modal fade" id="editBudgetModal" tabindex="-1" role="dialog" aria-labelledby="editBudgetModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="editBudgetModalLabel">Modifier le budget</h4>
                </div>
                <form id="editBudgetForm" method="POST" action="<?php echo BASE_URL; ?>/admin/budgets/edit">
                    <div class="modal-body">
                        <input type="hidden" name="id_budget" id="edit_id_budget">
                        <div class="form-group mb-3">
                            <label for="edit_solde_depart">Solde de départ (€)</label>
                            <input type="number" step="0.01" class="form-control" id="edit_solde_depart" name="solde_depart" required>
                        </div>
                        <h6>Détails du budget</h6>
                        <div id="edit-budget-details">
                            <!-- Les détails seront ajoutés dynamiquement via JS -->
                        </div>
                        <button type="button" class="btn btn-secondary mt-2" onclick="addEditDetailRow()">Ajouter un détail</button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Chargement des scripts dans le bon ordre pour Bootstrap 3 -->
    <script src="<?php echo BASE_URL; ?>/assets/js/jquery.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/bootstrap.min.js"></script>
    <script>
        function openEditModal(buttonElement) {
            try {
                var id_budget = $(buttonElement).data('id');
                var budgetJson = $(buttonElement).data('budget');
                
                console.log('ID Budget:', id_budget);
                console.log('Budget JSON:', budgetJson);
                
                document.getElementById('edit_id_budget').value = id_budget;
                document.getElementById('edit_solde_depart').value = budgetJson.solde_depart;

                var detailsContainer = document.getElementById('edit-budget-details');
                detailsContainer.innerHTML = ''; // Réinitialiser

                if (budgetJson.details && Array.isArray(budgetJson.details)) {
                    budgetJson.details.forEach(function(detail, index) {
                        addEditDetailRow(detail, index);
                    });
                }
                
                // Affichage du modal avec syntaxe jQuery pour Bootstrap 3
                $('#editBudgetModal').modal('show');
                
                console.log('Modal initialized');
            } catch (error) {
                console.error('Erreur lors de l\'ouverture du modal:', error);
                alert('Une erreur est survenue lors de l\'ouverture du modal. Voir la console pour plus de détails.');
            }
        }

        function addEditDetailRow(detail, index) {
            var container = document.getElementById('edit-budget-details');
            var row = document.createElement('div');
            row.className = 'detail-row form-group row';
            
            var detailId = detail ? detail.id_detail : '';
            var montant = detail ? detail.montant : '';
            var description = detail ? (detail.description || '') : '';
            
            row.innerHTML = `
                <input type="hidden" name="detail_ids[]" value="${detailId}">
                <div class="col-md-5">
                    <select class="form-control" name="categories[]" required>
                        <option value="">Sélectionner une catégorie</option>
                        <?php
                        // Récupérer les catégories depuis la base pour les options
                        $db = Flight::db();
                        $stmt = $db->query("SELECT * FROM categories");
                        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($categories as $categorie) {
                            echo '<option value="' . $categorie['id_categorie'] . '">' . htmlspecialchars($categorie['nom']) . ' (' . $categorie['type'] . ')</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" step="0.01" class="form-control" name="montants[]" placeholder="Montant (€)" value="${montant}" required>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" name="descriptions[]" placeholder="Description" value="${description}">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger" onclick="this.closest('.detail-row').remove()">X</button>
                </div>
            `;
            
            container.appendChild(row);
            
            // Si un détail existe, sélectionnez la bonne catégorie
            if (detail) {
                row.querySelector('select').value = detail.id_categorie;
            }
        }
    </script>
</body>
</html>