<?php
// app/controllers/UserController.php
namespace app\controllers;

use app\models\UtilisateurModel;
use Flight;
use Exception;

class UserController
{
    private $utilisateurModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $db = Flight::db();
        $this->utilisateurModel = new UtilisateurModel($db);

        if (!isset($_SESSION['utilisateur'])) {
            Flight::redirect('/login');
            return;
        }

        if ($this->utilisateurModel->estAdmin($_SESSION['utilisateur']['id_utilisateur'])) {
            Flight::redirect('/admin/dashboard');
            return;
        }
    }

    public function dashboard()
    {
        $mois = Flight::request()->query->mois ?? null;
        $annee = Flight::request()->query->annee ?? null;

        $id_departement = $_SESSION['utilisateur']['id_departement'];

        $data = [
            'utilisateur' => $_SESSION['utilisateur'],
            'mois' => $mois,
            'annee' => $annee
        ];

        // Récupérer le nom du département
        $departement = $this->utilisateurModel->getDepartementById($id_departement);
        $data['nom_departement'] = $departement ? $departement['nom'] : 'Inconnu';

        if ($mois && $annee) {
            // Si un filtre est appliqué, afficher uniquement cette période
            $budget = $this->utilisateurModel->getBudgetByDepartementAndPeriod($id_departement, $mois, $annee);
            $realisations = $this->utilisateurModel->getFinancialSummary($id_departement, $mois, $annee);
            $ecarts = [
                'solde_depart' => 0,
                'gains' => $budget ? ($realisations['total_gains'] - $budget['total_gains']) : $realisations['total_gains'],
                'depenses' => $budget ? ($realisations['total_depenses'] - $budget['total_depenses']) : $realisations['total_depenses'],
                'solde_final' => $budget ? ($realisations['solde_final'] - $budget['solde_final_calculee']) : $realisations['solde_final']
            ];
            $data['periodes'] = [
                [
                    'mois' => $mois,
                    'annee' => $annee,
                    'budget' => $budget,
                    'realisations' => $realisations,
                    'ecarts' => $ecarts
                ]
            ];
        } else {
            // Sans filtre, récupérer toutes les périodes avec budgets ou transactions
            $budgets = $this->utilisateurModel->getBudgetsByDepartement($id_departement);
            $transactions = $this->utilisateurModel->getTransactionsByDepartement($id_departement);

            // Créer une liste unique de périodes (mois/année) à partir des budgets et transactions
            $periodes = [];
            foreach ($budgets as $budget) {
                $periode_key = $budget['annee'] . '-' . sprintf("%02d", $budget['mois']);
                $periodes[$periode_key] = ['mois' => $budget['mois'], 'annee' => $budget['annee']];
            }
            foreach ($transactions as $transaction) {
                $periode_key = $transaction['annee'] . '-' . sprintf("%02d", $transaction['mois']);
                if (!isset($periodes[$periode_key])) {
                    $periodes[$periode_key] = ['mois' => $transaction['mois'], 'annee' => $transaction['annee']];
                }
            }

            // Pour chaque période, récupérer budget, réalisations et écarts
            $data['periodes'] = [];
            foreach ($periodes as $periode) {
                $mois = $periode['mois'];
                $annee = $periode['annee'];
                $budget = $this->utilisateurModel->getBudgetByDepartementAndPeriod($id_departement, $mois, $annee);
                $realisations = $this->utilisateurModel->getFinancialSummary($id_departement, $mois, $annee);
                $ecarts = [
                    'solde_depart' => 0,
                    'gains' => $budget ? ($realisations['total_gains'] - $budget['total_gains']) : $realisations['total_gains'],
                    'depenses' => $budget ? ($realisations['total_depenses'] - $budget['total_depenses']) : $realisations['total_depenses'],
                    'solde_final' => $budget ? ($realisations['solde_final'] - $budget['solde_final_calculee']) : $realisations['solde_final']
                ];
                $data['periodes'][] = [
                    'mois' => $mois,
                    'annee' => $annee,
                    'budget' => $budget,
                    'realisations' => $realisations,
                    'ecarts' => $ecarts
                ];
            }
        }

        Flight::render('user/dashboard.php', $data);
    }

    public function profile()
    {
        $utilisateur = $this->utilisateurModel->getUtilisateurById($_SESSION['utilisateur']['id_utilisateur']);
        $departements = $this->utilisateurModel->getDepartements();
        $data = [
            'utilisateur' => $utilisateur,
            'departements' => $departements,
            'success' => $_SESSION['profile_success'] ?? null,
            'error' => $_SESSION['profile_error'] ?? null
        ];
        unset($_SESSION['profile_success'], $_SESSION['profile_error']);
        Flight::render('user/profile.php', $data);
    }

    public function updateProfile()
    {
        $nom_utilisateur = trim(Flight::request()->data->nom_utilisateur);
        $mot_de_passe = trim(Flight::request()->data->mot_de_passe);
        $confirm_mot_de_passe = trim(Flight::request()->data->confirm_mot_de_passe);
        $id_departement = Flight::request()->data->id_departement;

        $errors = [];
        if (empty($nom_utilisateur)) {
            $errors[] = "Le nom d'utilisateur est requis";
        }
        $existingUtilisateur = $this->utilisateurModel->getUtilisateurById($_SESSION['utilisateur']['id_utilisateur']);
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

        if (!empty($errors)) {
            $_SESSION['profile_error'] = $errors;
            Flight::redirect('/user/profile');
            return;
        }

        try {
            $success = $this->utilisateurModel->updateUtilisateur(
                $_SESSION['utilisateur']['id_utilisateur'],
                $nom_utilisateur,
                $id_departement,
                $existingUtilisateur['role'],
                $mot_de_passe ?: null
            );
            if ($success) {
                $utilisateur = $this->utilisateurModel->getUtilisateurById($_SESSION['utilisateur']['id_utilisateur']);
                $_SESSION['utilisateur'] = $utilisateur;
                $_SESSION['profile_success'] = "Profil mis à jour avec succès.";
                Flight::redirect('/user/profile');
            } else {
                $_SESSION['profile_error'] = "Erreur lors de la mise à jour du profil.";
                Flight::redirect('/user/profile');
            }
        } catch (Exception $e) {
            $_SESSION['profile_error'] = $e->getMessage();
            Flight::redirect('/user/profile');
        }
    }

    public function finances()
    {
        $mois = Flight::request()->query->mois ?? null;
        $annee = Flight::request()->query->annee ?? null;

        $id_departement = $_SESSION['utilisateur']['id_departement'];
        $transactions = $this->utilisateurModel->getTransactionsByDepartement($id_departement, $mois, $annee);
        $summary = $this->utilisateurModel->getFinancialSummary($id_departement, $mois, $annee);
        $categories = $this->utilisateurModel->getCategories();

        $data = [
            'transactions' => $transactions,
            'summary' => $summary,
            'categories' => $categories,
            'mois' => $mois,
            'annee' => $annee,
            'success' => $_SESSION['finance_success'] ?? null,
            'error' => $_SESSION['finance_error'] ?? null
        ];
        unset($_SESSION['finance_success'], $_SESSION['finance_error']);
        Flight::render('user/finances.php', $data);
    }

    public function addTransaction()
    {
        $id_departement = $_SESSION['utilisateur']['id_departement'];
        $id_categorie = Flight::request()->data->id_categorie;
        $montant = trim(Flight::request()->data->montant);
        $description = trim(Flight::request()->data->description);
        $date_transaction = Flight::request()->data->date_transaction;

        // Extraire mois et année de la date
        $date = new \DateTime($date_transaction);
        $mois = (int)$date->format('m');
        $annee = (int)$date->format('Y');

        // Vérifier si le budget est approuvé
        if (!$this->utilisateurModel->isBudgetApproved($id_departement, $mois, $annee)) {
            $_SESSION['finance_error'] = ["Aucun budget approuvé pour le mois $mois/$annee. Vous ne pouvez pas ajouter de transaction."];
            Flight::redirect('/user/finances');
            return;
        }

        // Validation
        $errors = [];
        if (empty($id_categorie)) {
            $errors[] = "Veuillez sélectionner une catégorie";
        }
        if (empty($montant) || !is_numeric($montant) || $montant <= 0) {
            $errors[] = "Le montant est requis et doit être un nombre positif";
        }
        if (empty($date_transaction)) {
            $errors[] = "La date de la transaction est requise";
        }

        if (!empty($errors)) {
            $_SESSION['finance_error'] = $errors;
            Flight::redirect('/user/finances');
            return;
        }

        try {
            $id = $this->utilisateurModel->addTransaction(
                $id_departement,
                $mois,
                $annee,
                $id_categorie,
                $montant,
                $description
            );
            if ($id) {
                $_SESSION['finance_success'] = "Transaction ajoutée avec succès.";
                Flight::redirect('/user/finances');
            } else {
                $_SESSION['finance_error'] = "Erreur lors de l'ajout de la transaction.";
                Flight::redirect('/user/finances');
            }
        } catch (Exception $e) {
            $_SESSION['finance_error'] = $e->getMessage();
            Flight::redirect('/user/finances');
        }
    }

    public function showEditTransaction($id)
    {
        $id_departement = $_SESSION['utilisateur']['id_departement'];
        $transaction = $this->utilisateurModel->getTransactionById($id, $id_departement);
        if (!$transaction) {
            $_SESSION['finance_error'] = "Transaction non trouvée.";
            Flight::redirect('/user/finances');
            return;
        }

        $categories = $this->utilisateurModel->getCategories();
        $data = [
            'transaction' => $transaction,
            'categories' => $categories,
            'error' => $_SESSION['finance_error'] ?? null
        ];
        unset($_SESSION['finance_error']);
        Flight::render('user/edit_transaction.php', $data);
    }

    public function updateTransaction($id)
    {
        $id_departement = $_SESSION['utilisateur']['id_departement'];
        $id_categorie = Flight::request()->data->id_categorie;
        $montant = trim(Flight::request()->data->montant);
        $description = trim(Flight::request()->data->description);
        $date_transaction = Flight::request()->data->date_transaction;

        // Extraire mois et année de la date
        $date = new \DateTime($date_transaction);
        $mois = (int)$date->format('m');
        $annee = (int)$date->format('Y');

        // Vérifier si le budget est approuvé
        if (!$this->utilisateurModel->isBudgetApproved($id_departement, $mois, $annee)) {
            $_SESSION['finance_error'] = ["Aucun budget approuvé pour le mois $mois/$annee. Vous ne pouvez pas modifier la transaction pour cette période."];
            Flight::redirect('/user/finances/edit/' . $id);
            return;
        }

        // Validation
        $errors = [];
        if (empty($id_categorie)) {
            $errors[] = "Veuillez sélectionner une catégorie";
        }
        if (empty($montant) || !is_numeric($montant) || $montant <= 0) {
            $errors[] = "Le montant est requis et doit être un nombre positif";
        }
        if (empty($date_transaction)) {
            $errors[] = "La date de la transaction est requise";
        }

        if (!empty($errors)) {
            $_SESSION['finance_error'] = $errors;
            Flight::redirect('/user/finances/edit/' . $id);
            return;
        }

        try {
            $success = $this->utilisateurModel->updateTransaction(
                $id,
                $id_departement,
                $mois,
                $annee,
                $id_categorie,
                $montant,
                $description
            );
            if ($success) {
                $_SESSION['finance_success'] = "Transaction mise à jour avec succès.";
                Flight::redirect('/user/finances');
            } else {
                $_SESSION['finance_error'] = "Erreur lors de la mise à jour de la transaction.";
                Flight::redirect('/user/finances/edit/' . $id);
            }
        } catch (Exception $e) {
            $_SESSION['finance_error'] = $e->getMessage();
            Flight::redirect('/user/finances/edit/' . $id);
        }
    }

    public function deleteTransaction($id)
    {
        $id_departement = $_SESSION['utilisateur']['id_departement'];
        try {
            $success = $this->utilisateurModel->deleteTransaction($id, $id_departement);
            if ($success) {
                $_SESSION['finance_success'] = "Transaction supprimée avec succès.";
            } else {
                $_SESSION['finance_error'] = "Erreur lors de la suppression de la transaction.";
            }
        } catch (Exception $e) {
            $_SESSION['finance_error'] = $e->getMessage();
        }
        Flight::redirect('/user/finances');
    }

    public function budgets()
    {
        $mois = Flight::request()->query->mois ?? null;
        $annee = Flight::request()->query->annee ?? null;

        $id_departement = $_SESSION['utilisateur']['id_departement'];

        $data = [
            'mois' => $mois,
            'annee' => $annee,
            'success' => $_SESSION['budget_success'] ?? null,
            'error' => $_SESSION['budget_error'] ?? null
        ];
        unset($_SESSION['budget_success'], $_SESSION['budget_error']);

        // Récupérer le nom du département
        $departement = $this->utilisateurModel->getDepartementById($id_departement);
        $data['nom_departement'] = $departement ? $departement['nom'] : 'Inconnu';

        // Récupérer les catégories pour le formulaire
        $data['categories'] = $this->utilisateurModel->getCategories();

        if ($mois && $annee) {
            // Si un filtre est appliqué, afficher uniquement cette période
            $budgets = $this->utilisateurModel->getBudgetsByDepartement($id_departement, $mois, $annee);
            foreach ($budgets as &$budget) {
                $budget['details'] = $this->utilisateurModel->getBudgetDetails($budget['id_budget']);
            }
            $data['periodes'] = [
                [
                    'mois' => $mois,
                    'annee' => $annee,
                    'budgets' => $budgets
                ]
            ];
        } else {
            // Sans filtre, récupérer tous les budgets
            $budgets = $this->utilisateurModel->getBudgetsByDepartement($id_departement);

            // Organiser les budgets par période (mois/année)
            $periodes = [];
            foreach ($budgets as $budget) {
                $budget['details'] = $this->utilisateurModel->getBudgetDetails($budget['id_budget']);
                $periode_key = $budget['annee'] . '-' . sprintf("%02d", $budget['mois']);
                if (!isset($periodes[$periode_key])) {
                    $periodes[$periode_key] = [
                        'mois' => $budget['mois'],
                        'annee' => $budget['annee'],
                        'budgets' => []
                    ];
                }
                $periodes[$periode_key]['budgets'][] = $budget;
            }

            $data['periodes'] = array_values($periodes);
        }

        Flight::render('user/budgets.php', $data);
    }

    public function proposeBudget()
    {
        $id_departement = $_SESSION['utilisateur']['id_departement'];
        $mois = Flight::request()->data->mois;
        $annee = Flight::request()->data->annee;
        $solde_depart = trim(Flight::request()->data->solde_depart);
        $categories = Flight::request()->data->categories ?? [];
        $montants = Flight::request()->data->montants ?? [];
        $descriptions = Flight::request()->data->descriptions ?? [];

        // Validation
        $errors = [];
        if (empty($mois) || $mois < 1 || $mois > 12) {
            $errors[] = "Veuillez sélectionner un mois valide (1-12)";
        }
        if (empty($annee) || $annee < 2020 || $annee > date('Y') + 5) {
            $errors[] = "Veuillez sélectionner une année valide";
        }
        if (empty($solde_depart) || !is_numeric($solde_depart) || $solde_depart < 0) {
            $errors[] = "Le solde de départ est requis et doit être un nombre positif";
        }

        // Vérifier si un budget existe déjà
        if ($this->utilisateurModel->budgetExists($id_departement, $mois, $annee)) {
            $errors[] = "Un budget existe déjà pour cette période.";
        }

        // Valider les détails du budget
        $details = [];
        for ($i = 0; $i < count($categories); $i++) {
            if (!empty($categories[$i]) && !empty($montants[$i])) {
                if (!is_numeric($montants[$i]) || $montants[$i] <= 0) {
                    $errors[] = "Le montant pour la catégorie " . htmlspecialchars($categories[$i]) . " doit être un nombre positif";
                } else {
                    $details[] = [
                        'id_categorie' => $categories[$i],
                        'montant' => $montants[$i],
                        'description' => $descriptions[$i] ?? ''
                    ];
                }
            }
        }

        if (empty($details)) {
            $errors[] = "Veuillez ajouter au moins un détail de budget (catégorie et montant)";
        }

        if (!empty($errors)) {
            $_SESSION['budget_error'] = $errors;
            Flight::redirect('/user/budgets');
            return;
        }

        try {
            $id_budget = $this->utilisateurModel->createBudget($id_departement, $mois, $annee, $solde_depart);
            foreach ($details as $detail) {
                $this->utilisateurModel->addBudgetDetail(
                    $id_budget,
                    $detail['id_categorie'],
                    $detail['montant'],
                    $detail['description']
                );
            }
            $_SESSION['budget_success'] = "Proposition de budget soumise avec succès. En attente de validation par l'admin.";
            Flight::redirect('/user/budgets');
        } catch (Exception $e) {
            $_SESSION['budget_error'] = $e->getMessage();
            Flight::redirect('/user/budgets');
        }
    }
}
