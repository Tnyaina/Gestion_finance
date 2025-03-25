<?php
// app/models/ImportModel.php
namespace app\models;

use PDO;
use Exception;

class ImportModel
{
    private $db;
    private $utilisateurModel;

    public function __construct(PDO $database, UtilisateurModel $utilisateurModel)
    {
        $this->db = $database;
        $this->utilisateurModel = $utilisateurModel;
    }

    public function importBudgets($id_departement, $filePath)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("Impossible d'ouvrir le fichier CSV.");
        }

        $report = ['success' => 0, 'errors' => []];
        $firstRow = true;
        $budgetCreated = [];

        $this->db->beginTransaction();
        try {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if ($firstRow) {
                    $firstRow = false; // Ignorer l'en-tête
                    continue;
                }

                if (count($data) < 6) {
                    $report['errors'][] = "Ligne invalide (moins de 6 colonnes) : " . implode(',', $data);
                    continue;
                }

                list($mois, $annee, $solde_depart, $categorie, $montant, $description) = $data;

                // Validation
                if (!is_numeric($mois) || $mois < 1 || $mois > 12) {
                    $report['errors'][] = "Mois invalide ($mois) à la ligne : " . implode(',', $data);
                    continue;
                }
                if (!is_numeric($annee) || $annee < 2020 || $annee > date('Y') + 5) {
                    $report['errors'][] = "Année invalide ($annee) à la ligne : " . implode(',', $data);
                    continue;
                }
                if (!is_numeric($solde_depart) || $solde_depart < 0) {
                    $report['errors'][] = "Solde de départ invalide ($solde_depart) à la ligne : " . implode(',', $data);
                    continue;
                }
                if (!is_numeric($montant) || $montant <= 0) {
                    $report['errors'][] = "Montant invalide ($montant) à la ligne : " . implode(',', $data);
                    continue;
                }

                $categoryId = $this->getCategoryId($categorie);
                if (!$categoryId) {
                    $report['errors'][] = "Catégorie '$categorie' non trouvée à la ligne : " . implode(',', $data);
                    continue;
                }

                $periodeKey = "$mois-$annee";
                if (!isset($budgetCreated[$periodeKey])) {
                    if ($this->utilisateurModel->budgetExists($id_departement, $mois, $annee)) {
                        $budget = $this->utilisateurModel->getBudgetByDepartementAndPeriod($id_departement, $mois, $annee);
                        $budgetCreated[$periodeKey] = $budget['id_budget'];
                    } else {
                        $budgetCreated[$periodeKey] = $this->utilisateurModel->createBudget($id_departement, $mois, $annee, $solde_depart);
                    }
                }

                $this->utilisateurModel->addBudgetDetail($budgetCreated[$periodeKey], $categoryId, $montant, $description);
                $report['success']++;
            }

            if (empty($report['errors'])) {
                $this->db->commit();
            } else {
                $this->db->rollBack();
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Erreur lors de l'importation des budgets : " . $e->getMessage());
        }

        fclose($handle);
        return $report;
    }

    public function importTransactions($id_departement, $filePath)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("Impossible d'ouvrir le fichier CSV.");
        }

        $report = ['success' => 0, 'errors' => []];
        $firstRow = true;

        $this->db->beginTransaction();
        try {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if ($firstRow) {
                    $firstRow = false; // Ignorer l'en-tête
                    continue;
                }

                if (count($data) < 4) {
                    $report['errors'][] = "Ligne invalide (moins de 4 colonnes) : " . implode(',', $data);
                    continue;
                }

                list($date_transaction, $categorie, $montant, $description) = $data;

                // Validation de la date
                try {
                    $date = new \DateTime($date_transaction);
                    $mois = (int)$date->format('m');
                    $annee = (int)$date->format('Y');
                } catch (Exception $e) {
                    $report['errors'][] = "Date invalide ($date_transaction) à la ligne : " . implode(',', $data);
                    continue;
                }

                // Vérification budget approuvé
                if (!$this->utilisateurModel->isBudgetApproved($id_departement, $mois, $annee)) {
                    $report['errors'][] = "Budget non approuvé pour $mois/$annee à la ligne : " . implode(',', $data);
                    continue;
                }

                $categoryId = $this->getCategoryId($categorie);
                if (!$categoryId) {
                    $report['errors'][] = "Catégorie '$categorie' non trouvée à la ligne : " . implode(',', $data);
                    continue;
                }

                if (!is_numeric($montant) || $montant <= 0) {
                    $report['errors'][] = "Montant invalide ($montant) à la ligne : " . implode(',', $data);
                    continue;
                }

                $this->utilisateurModel->addTransaction($id_departement, $mois, $annee, $categoryId, $montant, $description);
                $report['success']++;
            }

            if (empty($report['errors'])) {
                $this->db->commit();
            } else {
                $this->db->rollBack();
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Erreur lors de l'importation des transactions : " . $e->getMessage());
        }

        fclose($handle);
        return $report;
    }

    private function getCategoryId($categoryName)
    {
        $categories = $this->utilisateurModel->getCategories();
        foreach ($categories as $cat) {
            if (strtolower($cat['nom']) === strtolower($categoryName)) {
                return $cat['id_categorie'];
            }
        }
        return null;
    }
}