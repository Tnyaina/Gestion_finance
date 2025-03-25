<?php
// app/controllers/UserController.php
namespace app\controllers;

use app\models\UtilisateurModel;
use Flight;
use Exception;

require_once dirname(__DIR__) . '/../public/fpdf186/fpdf.php';
class UserController
{
    private $utilisateurModel;
    private $db;

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

    private function cleanForPdf($string)
    {
        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8');
        }
        // Remplacer certains caractères problématiques
        $replacements = [
            '€' => 'EUR', // Remplace le symbole € par "EUR"
            '’' => "'",   // Apostrophe courbée
            '–' => '-',   // Tiret long
            '…' => '...'  // Points de suspension
        ];
        $string = str_replace(array_keys($replacements), array_values($replacements), $string);
        // Convertir en ISO-8859-1, ignorer les caractères non convertibles
        return mb_convert_encoding($string, 'ISO-8859-1', 'UTF-8');
    }

    public function exportDashboardToPDF()
    {
        $utilisateur = $_SESSION['utilisateur'];
        $all = Flight::request()->query['all'] === 'true';
        $mois = $all ? null : (Flight::request()->query['mois'] ?? null);
        $annee = $all ? null : (Flight::request()->query['annee'] ?? null);
        $nom_departement = $this->utilisateurModel->getDepartementById($utilisateur['id_departement'])['nom'];
        $periodes = $this->getDashboardData($utilisateur['id_departement'], $mois, $annee);

        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, $this->cleanForPdf("Tableau de bord - $nom_departement"), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $filtre = 'Filtre : ' . ($all ? 'Toutes les périodes' : ($mois ? sprintf("%02d", $mois) : 'Tous') . '/' . ($annee ?: 'Toutes'));
        $pdf->Cell(0, 10, $this->cleanForPdf($filtre), 0, 1);

        foreach ($periodes as $periode) {
            $periode_label = (new \DateTime())->setDate($periode['annee'], $periode['mois'], 1)->format('F Y');
            $pdf->Ln(5);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, $this->cleanForPdf($periode_label), 0, 1);
            $pdf->SetFont('Arial', '', 10);

            $pdf->Cell(50, 10, $this->cleanForPdf('Rubrique'), 1);
            $pdf->Cell(40, 10, $this->cleanForPdf('Prévisions (EUR)'), 1); // Remplace € par EUR
            $pdf->Cell(40, 10, $this->cleanForPdf('Réalisations (EUR)'), 1);
            $pdf->Cell(40, 10, $this->cleanForPdf('Écarts (EUR)'), 1);
            $pdf->Ln();

            $data = [
                [$this->cleanForPdf('Solde de départ'), $periode['budget'] ? number_format($periode['budget']['solde_depart'], 2) : '0.00', number_format($periode['realisations']['solde_depart'], 2), '0.00'],
                [$this->cleanForPdf('Gains'), $periode['budget'] ? number_format($periode['budget']['total_gains'], 2) : '0.00', number_format($periode['realisations']['total_gains'], 2), number_format(abs($periode['ecarts']['gains']), 2)],
                [$this->cleanForPdf('Dépenses'), $periode['budget'] ? number_format($periode['budget']['total_depenses'], 2) : '0.00', number_format($periode['realisations']['total_depenses'], 2), number_format(abs($periode['ecarts']['depenses']), 2)],
                [$this->cleanForPdf('Solde final'), $periode['budget'] ? number_format($periode['budget']['solde_final_calculee'], 2) : '0.00', number_format($periode['realisations']['solde_final'], 2), number_format(abs($periode['ecarts']['solde_final']), 2)]
            ];

            foreach ($data as $row) {
                $pdf->Cell(50, 10, $row[0], 1);
                $pdf->Cell(40, 10, $row[1], 1);
                $pdf->Cell(40, 10, $row[2], 1);
                $pdf->Cell(40, 10, $row[3], 1);
                $pdf->Ln();
            }
        }

        $filename = 'dashboard_' . $utilisateur['id_utilisateur'] . '_' . date('YmdHis') . '.pdf';
        $filePath = __DIR__ . '/../../public/exports/' . $filename;
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }
        $pdf->Output('F', $filePath);
        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        readfile($filePath);
        unlink($filePath);
        exit;
    }

    public function exportBudgetsToPDF()
    {
        $utilisateur = $_SESSION['utilisateur'];
        $all = Flight::request()->query['all'] === 'true';
        $mois = $all ? null : (Flight::request()->query['mois'] ?? null);
        $annee = $all ? null : (Flight::request()->query['annee'] ?? null);
        $nom_departement = $this->utilisateurModel->getDepartementById($utilisateur['id_departement'])['nom'];
        $budgets = $this->utilisateurModel->getBudgetsByDepartement($utilisateur['id_departement'], $mois, $annee);

        foreach ($budgets as &$budget) {
            $budget['details'] = $this->utilisateurModel->getBudgetDetails($budget['id_budget']);
        }
        unset($budget);

        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, $this->cleanForPdf("Budgets - $nom_departement"), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $filtre = 'Filtre : ' . ($all ? 'Tous les budgets' : ($mois ? sprintf("%02d", $mois) : 'Tous') . '/' . ($annee ?: 'Toutes'));
        $pdf->Cell(0, 10, $this->cleanForPdf($filtre), 0, 1);

        foreach ($budgets as $budget) {
            $periode_label = (new \DateTime())->setDate($budget['annee'], $budget['mois'], 1)->format('F Y');
            $pdf->Ln(5);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, $this->cleanForPdf($periode_label), 0, 1);

            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(0, 10, $this->cleanForPdf("Budget - Statut : {$budget['statut']}"), 1, 1);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 8, $this->cleanForPdf('Solde de départ : ') . number_format($budget['solde_depart'], 2) . ' EUR', 0, 1);
            $pdf->Cell(0, 8, $this->cleanForPdf('Gains prévus : ') . number_format($budget['total_gains'], 2) . ' EUR', 0, 1);
            $pdf->Cell(0, 8, $this->cleanForPdf('Dépenses prévues : ') . number_format($budget['total_depenses'], 2) . ' EUR', 0, 1);
            $pdf->Cell(0, 8, $this->cleanForPdf('Solde final prévu : ') . number_format($budget['solde_final_calculee'], 2) . ' EUR', 0, 1);

            $pdf->Ln(5);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(0, 10, $this->cleanForPdf('Détails :'), 0, 1);
            if (empty($budget['details'])) {
                $pdf->Cell(0, 10, $this->cleanForPdf('Aucun détail disponible.'), 0, 1);
            } else {
                $pdf->Cell(50, 10, $this->cleanForPdf('Catégorie'), 1);
                $pdf->Cell(30, 10, $this->cleanForPdf('Type'), 1);
                $pdf->Cell(30, 10, $this->cleanForPdf('Montant (EUR)'), 1);
                $pdf->Cell(70, 10, $this->cleanForPdf('Description'), 1);
                $pdf->Ln();
                $pdf->SetFont('Arial', '', 10);
                foreach ($budget['details'] as $detail) {
                    $pdf->Cell(50, 10, $this->cleanForPdf($detail['nom_categorie']), 1);
                    $pdf->Cell(30, 10, $this->cleanForPdf($detail['type_categorie']), 1);
                    $pdf->Cell(30, 10, number_format($detail['montant'], 2), 1);
                    $pdf->Cell(70, 10, $this->cleanForPdf($detail['description'] ?: 'N/A'), 1);
                    $pdf->Ln();
                }
            }
        }

        $exportDir = __DIR__ . '/../../public/exports/';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0777, true);
        }
        $filename = 'budgets_' . $utilisateur['id_utilisateur'] . '_' . date('YmdHis') . '.pdf';
        $filePath = $exportDir . $filename;
        $pdf->Output('F', $filePath);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        readfile($filePath);
        unlink($filePath);
        exit;
    }

    public function exportFinancesToPDF()
    {
        $utilisateur = $_SESSION['utilisateur'];
        $all = Flight::request()->query['all'] === 'true';
        $mois = $all ? null : (Flight::request()->query['mois'] ?? null);
        $annee = $all ? null : (Flight::request()->query['annee'] ?? null);
        $nom_departement = $this->utilisateurModel->getDepartementById($utilisateur['id_departement'])['nom'];
        $summary = $this->utilisateurModel->getFinancialSummary($utilisateur['id_departement'], $mois, $annee);
        $transactions = $this->utilisateurModel->getTransactionsByDepartement($utilisateur['id_departement'], $mois, $annee);

        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, $this->cleanForPdf("Finances - $nom_departement"), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $filtre = 'Filtre : ' . ($all ? 'Toutes les transactions' : ($mois ? sprintf("%02d", $mois) : 'Tous') . '/' . ($annee ?: 'Toutes'));
        $pdf->Cell(0, 10, $this->cleanForPdf($filtre), 0, 1);

        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, $this->cleanForPdf('Résumé financier'), 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(50, 10, $this->cleanForPdf('Total des Gains :'), 0);
        $pdf->Cell(50, 10, number_format($summary['total_gains'], 2) . ' EUR', 0, 1);
        $pdf->Cell(50, 10, $this->cleanForPdf('Total des Dépenses :'), 0);
        $pdf->Cell(50, 10, number_format($summary['total_depenses'], 2) . ' EUR', 0, 1);
        $pdf->Cell(50, 10, $this->cleanForPdf('Solde Final :'), 0);
        $pdf->Cell(50, 10, number_format($summary['solde_final'], 2) . ' EUR', 0, 1);

        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, $this->cleanForPdf('Transactions'), 0, 1);
        if (empty($transactions)) {
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 10, $this->cleanForPdf('Aucune transaction trouvée.'), 0, 1);
        } else {
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(20, 10, $this->cleanForPdf('ID'), 1);
            $pdf->Cell(40, 10, $this->cleanForPdf('Catégorie'), 1);
            $pdf->Cell(30, 10, $this->cleanForPdf('Type'), 1);
            $pdf->Cell(30, 10, $this->cleanForPdf('Montant (EUR)'), 1);
            $pdf->Cell(40, 10, $this->cleanForPdf('Description'), 1);
            $pdf->Cell(20, 10, $this->cleanForPdf('Mois'), 1);
            $pdf->Cell(20, 10, $this->cleanForPdf('Année'), 1);
            $pdf->Ln();
            $pdf->SetFont('Arial', '', 10);
            foreach ($transactions as $transaction) {
                $pdf->Cell(20, 10, $transaction['id_transaction'], 1);
                $pdf->Cell(40, 10, $this->cleanForPdf($transaction['nom_categorie']), 1);
                $pdf->Cell(30, 10, $this->cleanForPdf($transaction['type_categorie']), 1);
                $pdf->Cell(30, 10, number_format($transaction['montant'], 2), 1);
                $pdf->Cell(40, 10, $this->cleanForPdf($transaction['description'] ?: 'N/A'), 1);
                $pdf->Cell(20, 10, sprintf("%02d", $transaction['mois']), 1);
                $pdf->Cell(20, 10, $transaction['annee'], 1);
                $pdf->Ln();
            }
        }

        $exportDir = __DIR__ . '/../../public/exports/';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0777, true);
        }
        $filename = 'finances_' . $utilisateur['id_utilisateur'] . '_' . date('YmdHis') . '.pdf';
        $filePath = $exportDir . $filename;
        $pdf->Output('F', $filePath);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        readfile($filePath);
        unlink($filePath);
        exit;
    }
    // Méthode utilitaire pour dashboard
    private function getDashboardData($id_departement, $mois, $annee)
    {
        $budgets = $this->utilisateurModel->getBudgetsByDepartement($id_departement, $mois, $annee);
        $transactions = $this->utilisateurModel->getTransactionsByDepartement($id_departement, $mois, $annee);

        $periodes = [];
        foreach ($budgets as $budget) {
            $key = $budget['annee'] . '-' . $budget['mois'];
            // Initialisation complète pour chaque période avec budget
            $periodes[$key] = [
                'annee' => $budget['annee'],
                'mois' => $budget['mois'],
                'budget' => $budget,
                'realisations' => ['total_gains' => 0, 'total_depenses' => 0, 'solde_depart' => $budget['solde_depart'] ?? 0, 'solde_final' => 0],
                'ecarts' => ['gains' => 0, 'depenses' => 0, 'solde_final' => 0]
            ];
        }

        foreach ($transactions as $transaction) {
            $key = $transaction['annee'] . '-' . $transaction['mois'];
            // Si la période n'existe pas encore (pas de budget), l'initialiser
            if (!isset($periodes[$key])) {
                $periodes[$key] = [
                    'annee' => $transaction['annee'],
                    'mois' => $transaction['mois'],
                    'budget' => null,
                    'realisations' => ['total_gains' => 0, 'total_depenses' => 0, 'solde_depart' => 0, 'solde_final' => 0],
                    'ecarts' => ['gains' => 0, 'depenses' => 0, 'solde_final' => 0]
                ];
            }

            // Ajouter les transactions aux réalisations
            $type = $this->utilisateurModel->getCategories()[$transaction['id_categorie']]['type'] ?? 'depense';
            if ($type === 'gain') {
                $periodes[$key]['realisations']['total_gains'] += $transaction['montant'];
            } else {
                $periodes[$key]['realisations']['total_depenses'] += $transaction['montant'];
            }

            // Mettre à jour le solde final et les écarts
            $budget = $periodes[$key]['budget'];
            $periodes[$key]['realisations']['solde_final'] = $periodes[$key]['realisations']['solde_depart'] +
                $periodes[$key]['realisations']['total_gains'] -
                $periodes[$key]['realisations']['total_depenses'];
            $periodes[$key]['ecarts']['gains'] = $periodes[$key]['realisations']['total_gains'] - ($budget['total_gains'] ?? 0);
            $periodes[$key]['ecarts']['depenses'] = $periodes[$key]['realisations']['total_depenses'] - ($budget['total_depenses'] ?? 0);
            $periodes[$key]['ecarts']['solde_final'] = $periodes[$key]['realisations']['solde_final'] - ($budget['solde_final_calculee'] ?? 0);
        }

        return array_values($periodes);
    }
}
