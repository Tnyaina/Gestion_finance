<?php
// app/controllers/AdminController.php
namespace app\controllers;

use app\models\UtilisateurModel;
use app\models\BudgetModel;
use Flight;
use Exception;

class AdminController
{
    private $utilisateurModel;
    private $budgetModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $db = Flight::db();
        $this->utilisateurModel = new UtilisateurModel($db);
        $this->budgetModel = new BudgetModel($db);

        // Vérifier si l'utilisateur est connecté et est un admin
        if (!isset($_SESSION['utilisateur']) || !$this->utilisateurModel->estAdmin($_SESSION['utilisateur']['id_utilisateur'])) {
            Flight::redirect('/login');
            return;
        }
    }

    public function dashboard()
    {
        $data = [
            'utilisateur' => $_SESSION['utilisateur']
        ];
        Flight::render('admin/dashboard.php', $data);
    }

    // Lister les utilisateurs
    public function listUtilisateurs()
    {
        $utilisateurs = $this->utilisateurModel->getAllUtilisateurs();
        $data = [
            'utilisateurs' => $utilisateurs,
            'success' => $_SESSION['user_success'] ?? null,
            'error' => $_SESSION['user_error'] ?? null
        ];
        unset($_SESSION['user_success'], $_SESSION['user_error']);
        Flight::render('admin/users.php', $data);
    }

    // Afficher le formulaire pour ajouter un utilisateur
    public function showAddUtilisateur()
    {
        $departements = $this->utilisateurModel->getDepartements();
        $data = [
            'departements' => $departements,
            'error' => $_SESSION['add_user_error'] ?? null
        ];
        unset($_SESSION['add_user_error']);
        Flight::render('admin/add_user.php', $data);
    }

    // Traiter l'ajout d'un utilisateur
    public function handleAddUtilisateur()
    {
        $nom_utilisateur = trim(Flight::request()->data->nom_utilisateur);
        $mot_de_passe = trim(Flight::request()->data->mot_de_passe);
        $confirm_mot_de_passe = trim(Flight::request()->data->confirm_mot_de_passe);
        $id_departement = Flight::request()->data->id_departement;
        $role = Flight::request()->data->role;

        // Validation
        $errors = [];
        if (empty($nom_utilisateur)) {
            $errors[] = "Le nom d'utilisateur est requis";
        }
        if ($this->utilisateurModel->nomUtilisateurExists($nom_utilisateur)) {
            $errors[] = "Ce nom d'utilisateur est déjà utilisé";
        }
        if (empty($mot_de_passe)) {
            $errors[] = "Le mot de passe est requis";
        }
        if (strlen($mot_de_passe) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
        }
        if ($mot_de_passe !== $confirm_mot_de_passe) {
            $errors[] = "Les mots de passe ne correspondent pas";
        }
        if (empty($id_departement)) {
            $errors[] = "Veuillez sélectionner un département";
        }
        if (empty($role) || !in_array($role, ['admin', 'utilisateur_departement'])) {
            $errors[] = "Veuillez sélectionner un rôle valide";
        }

        if (!empty($errors)) {
            $_SESSION['add_user_error'] = $errors;
            Flight::redirect('/admin/users/add');
            return;
        }

        try {
            $id = $this->utilisateurModel->create($nom_utilisateur, $mot_de_passe, $id_departement, $role);
            if ($id) {
                $_SESSION['user_success'] = "Utilisateur créé avec succès.";
                Flight::redirect('/admin/users');
            } else {
                $_SESSION['user_error'] = "Erreur lors de la création de l'utilisateur.";
                Flight::redirect('/admin/users/add');
            }
        } catch (Exception $e) {
            $_SESSION['user_error'] = $e->getMessage();
            Flight::redirect('/admin/users/add');
        }
    }

    // Afficher le formulaire pour modifier un utilisateur
    public function showEditUtilisateur($id)
    {
        $utilisateur = $this->utilisateurModel->getUtilisateurById($id);
        if (!$utilisateur) {
            Flight::redirect('/admin/users');
            return;
        }

        $departements = $this->utilisateurModel->getDepartements();
        $data = [
            'utilisateur' => $utilisateur,
            'departements' => $departements,
            'error' => $_SESSION['edit_user_error'] ?? null
        ];
        unset($_SESSION['edit_user_error']);
        Flight::render('admin/edit_user.php', $data);
    }

    // Traiter la modification d'un utilisateur
    public function handleEditUtilisateur($id)
    {
        $nom_utilisateur = trim(Flight::request()->data->nom_utilisateur);
        $mot_de_passe = trim(Flight::request()->data->mot_de_passe);
        $confirm_mot_de_passe = trim(Flight::request()->data->confirm_mot_de_passe);
        $id_departement = Flight::request()->data->id_departement;
        $role = Flight::request()->data->role;

        // Validation
        $errors = [];
        if (empty($nom_utilisateur)) {
            $errors[] = "Le nom d'utilisateur est requis";
        }
        $existingUtilisateur = $this->utilisateurModel->getUtilisateurById($id);
        if ($existingUtilisateur['nom_utilisateur'] !== $nom_utilisateur && $this->utilisateurModel->nomUtilisateurExists($nom_utilisateur)) {
            $errors[] = "Ce nom d'utilisateur est déjà utilisé";
        }
        if ($mot_de_passe && strlen($mot_de_passe) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
        }
        if ($mot_de_passe && $mot_de_passe !== $confirm_mot_de_passe) {
            $errors[] = "Les mots de passe ne correspondent pas";
        }
        if (empty($id_departement)) {
            $errors[] = "Veuillez sélectionner un département";
        }
        if (empty($role) || !in_array($role, ['admin', 'utilisateur_departement'])) {
            $errors[] = "Veuillez sélectionner un rôle valide";
        }

        if (!empty($errors)) {
            $_SESSION['edit_user_error'] = $errors;
            Flight::redirect('/admin/users/edit/' . $id);
            return;
        }

        try {
            $success = $this->utilisateurModel->updateUtilisateur($id, $nom_utilisateur, $id_departement, $role, $mot_de_passe ?: null);
            if ($success) {
                $_SESSION['user_success'] = "Utilisateur mis à jour avec succès.";
                Flight::redirect('/admin/users');
            } else {
                $_SESSION['user_error'] = "Erreur lors de la mise à jour de l'utilisateur.";
                Flight::redirect('/admin/users/edit/' . $id);
            }
        } catch (Exception $e) {
            $_SESSION['user_error'] = $e->getMessage();
            Flight::redirect('/admin/users/edit/' . $id);
        }
    }

    // Supprimer un utilisateur
    public function deleteUtilisateur($id)
    {
        try {
            $success = $this->utilisateurModel->deleteUtilisateur($id);
            if ($success) {
                $_SESSION['user_success'] = "Utilisateur supprimé avec succès.";
            } else {
                $_SESSION['user_error'] = "Erreur lors de la suppression de l'utilisateur.";
            }
        } catch (Exception $e) {
            $_SESSION['user_error'] = $e->getMessage();
        }
        Flight::redirect('/admin/users');
    }

    // Lister les départements
    public function listDepartements()
    {
        $departements = $this->utilisateurModel->getDepartements();
        $data = [
            'departements' => $departements,
            'success' => $_SESSION['dept_success'] ?? null,
            'error' => $_SESSION['dept_error'] ?? null
        ];
        unset($_SESSION['dept_success'], $_SESSION['dept_error']);
        Flight::render('admin/departements.php', $data);
    }

    // Afficher le formulaire pour ajouter un département
    public function showAddDepartement()
    {
        $data = [
            'error' => $_SESSION['add_dept_error'] ?? null
        ];
        unset($_SESSION['add_dept_error']);
        Flight::render('admin/add_departement.php', $data);
    }

    // Traiter l'ajout d'un département
    public function handleAddDepartement()
    {
        $nom = trim(Flight::request()->data->nom);

        // Validation
        $errors = [];
        if (empty($nom)) {
            $errors[] = "Le nom du département est requis";
        }

        if (!empty($errors)) {
            $_SESSION['add_dept_error'] = $errors;
            Flight::redirect('/admin/departements/add');
            return;
        }

        try {
            $id = $this->utilisateurModel->createDepartement($nom);
            if ($id) {
                $_SESSION['dept_success'] = "Département créé avec succès.";
                Flight::redirect('/admin/departements');
            } else {
                $_SESSION['dept_error'] = "Erreur lors de la création du département.";
                Flight::redirect('/admin/departements/add');
            }
        } catch (Exception $e) {
            $_SESSION['dept_error'] = $e->getMessage();
            Flight::redirect('/admin/departements/add');
        }
    }

    // Afficher le formulaire pour modifier un département
    public function showEditDepartement($id)
    {
        $departement = $this->utilisateurModel->getDepartementById($id);
        if (!$departement) {
            Flight::redirect('/admin/departements');
            return;
        }

        $data = [
            'departement' => $departement,
            'error' => $_SESSION['edit_dept_error'] ?? null
        ];
        unset($_SESSION['edit_dept_error']);
        Flight::render('admin/edit_departement.php', $data);
    }

    // Traiter la modification d'un département
    public function handleEditDepartement($id)
    {
        $nom = trim(Flight::request()->data->nom);

        // Validation
        $errors = [];
        if (empty($nom)) {
            $errors[] = "Le nom du département est requis";
        }

        if (!empty($errors)) {
            $_SESSION['edit_dept_error'] = $errors;
            Flight::redirect('/admin/departements/edit/' . $id);
            return;
        }

        try {
            $success = $this->utilisateurModel->updateDepartement($id, $nom);
            if ($success) {
                $_SESSION['dept_success'] = "Département mis à jour avec succès.";
                Flight::redirect('/admin/departements');
            } else {
                $_SESSION['dept_error'] = "Erreur lors de la mise à jour du département.";
                Flight::redirect('/admin/departements/edit/' . $id);
            }
        } catch (Exception $e) {
            $_SESSION['dept_error'] = $e->getMessage();
            Flight::redirect('/admin/departements/edit/' . $id);
        }
    }

    // Supprimer un département
    public function deleteDepartement($id)
    {
        try {
            $success = $this->utilisateurModel->deleteDepartement($id);
            if ($success) {
                $_SESSION['dept_success'] = "Département supprimé avec succès.";
            } else {
                $_SESSION['dept_error'] = "Erreur lors de la suppression du département.";
            }
        } catch (Exception $e) {
            $_SESSION['dept_error'] = $e->getMessage();
        }
        Flight::redirect('/admin/departements');
    }

    // Lister les budgets à valider
    public function listBudgets()
    {
        $budgets = $this->budgetModel->getAllBudgets('en_attente'); 
        $data = [
            'budgets' => $budgets,
            'success' => $_SESSION['budget_success'] ?? null,
            'error' => $_SESSION['budget_error'] ?? null
        ];
        unset($_SESSION['budget_success'], $_SESSION['budget_error']);
        Flight::render('admin/budgets.php', $data);
    }

    // Approuver un budget
    public function approveBudget($id)
    {
        try {
            $success = $this->budgetModel->approveBudget($id);
            if ($success) {
                $_SESSION['budget_success'] = "Budget approuvé avec succès.";
            } else {
                $_SESSION['budget_error'] = "Erreur lors de l'approbation du budget.";
            }
        } catch (Exception $e) {
            $_SESSION['budget_error'] = $e->getMessage();
        }
        Flight::redirect('/admin/budgets');
    }

    // Rejeter un budget
    public function rejectBudget($id)
    {
        try {
            $success = $this->budgetModel->rejectBudget($id);
            if ($success) {
                $_SESSION['budget_success'] = "Budget rejeté avec succès.";
            } else {
                $_SESSION['budget_error'] = "Erreur lors du rejet du budget.";
            }
        } catch (Exception $e) {
            $_SESSION['budget_error'] = $e->getMessage();
        }
        Flight::redirect('/admin/budgets');
    }

    public function editBudget()
    {
        $id_budget = Flight::request()->data->id_budget;
        $solde_depart = trim(Flight::request()->data->solde_depart);
        $detail_ids = Flight::request()->data->detail_ids ?? [];
        $categories = Flight::request()->data->categories ?? [];
        $montants = Flight::request()->data->montants ?? [];
        $descriptions = Flight::request()->data->descriptions ?? [];

        // Validation
        $errors = [];
        if (empty($solde_depart) || !is_numeric($solde_depart) || $solde_depart < 0) {
            $errors[] = "Le solde de départ est requis et doit être un nombre positif";
        }

        $details = [];
        for ($i = 0; $i < count($categories); $i++) {
            if (!empty($categories[$i]) && !empty($montants[$i])) {
                if (!is_numeric($montants[$i]) || $montants[$i] <= 0) {
                    $errors[] = "Le montant pour la catégorie #$i doit être un nombre positif";
                } else {
                    $details[] = [
                        'id_detail' => $detail_ids[$i] ?? null,
                        'id_categorie' => $categories[$i],
                        'montant' => $montants[$i],
                        'description' => $descriptions[$i] ?? ''
                    ];
                }
            }
        }

        if (empty($details)) {
            $errors[] = "Au moins un détail de budget est requis";
        }

        if (!empty($errors)) {
            $_SESSION['budget_error'] = $errors;
            Flight::redirect('/admin/budgets');
            return;
        }

        try {
            // Mettre à jour le solde de départ
            $stmt = Flight::db()->prepare(
                "UPDATE budgets SET solde_depart = :solde_depart, solde_final = :solde_depart WHERE id_budget = :id_budget"
            );
            $stmt->execute([
                'solde_depart' => $solde_depart,
                'id_budget' => $id_budget
            ]);

            // Supprimer les anciens détails
            $stmt = Flight::db()->prepare("DELETE FROM details_budget WHERE id_budget = :id_budget");
            $stmt->execute(['id_budget' => $id_budget]);

            // Ajouter les nouveaux détails
            foreach ($details as $detail) {
                $this->budgetModel->addBudgetDetail($id_budget, $detail['id_categorie'], $detail['montant'], $detail['description']);
            }

            $_SESSION['budget_success'] = "Budget modifié avec succès.";
            Flight::redirect('/admin/budgets');
        } catch (Exception $e) {
            $_SESSION['budget_error'] = $e->getMessage();
            Flight::redirect('/admin/budgets');
        }
    }
}