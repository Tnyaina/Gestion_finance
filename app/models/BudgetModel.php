<?php
// app/models/BudgetModel.php
namespace app\models;

use PDO;
use Exception;

class BudgetModel
{
    private $db;

    public function __construct(PDO $database)
    {
        $this->db = $database;
    }

    // Récupérer tous les budgets (avec filtres optionnels)
    public function getAllBudgets($statut = null, $id_departement = null)
    {
        try {
            $query = "SELECT b.*, d.nom AS nom_departement 
                      FROM budgets b 
                      JOIN departements d ON b.id_departement = d.id_departement";
            $params = [];

            if ($statut !== null) {
                $query .= " WHERE b.statut = :statut";
                $params['statut'] = $statut;
            }
            if ($id_departement !== null) {
                $query .= ($statut === null ? " WHERE" : " AND") . " b.id_departement = :id_departement";
                $params['id_departement'] = $id_departement;
            }

            $query .= " ORDER BY b.annee DESC, b.mois DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($budgets as &$budget) {
                $budget['details'] = $this->getBudgetDetails($budget['id_budget']);
            }
            return $budgets;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération des budgets : " . $e->getMessage());
        }
    }

    // Récupérer les détails d'un budget
    public function getBudgetDetails($id_budget)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT db.*, c.nom AS nom_categorie, c.type AS type_categorie 
                 FROM details_budget db 
                 JOIN categories c ON db.id_categorie = c.id_categorie 
                 WHERE db.id_budget = :id_budget"
            );
            $stmt->execute(['id_budget' => $id_budget]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération des détails du budget : " . $e->getMessage());
        }
    }

    // Approuver un budget
    public function approveBudget($id_budget)
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE budgets SET statut = 'approuve' WHERE id_budget = :id_budget"
            );
            $stmt->execute(['id_budget' => $id_budget]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de l'approbation du budget : " . $e->getMessage());
        }
    }

    // Rejeter un budget
    public function rejectBudget($id_budget)
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE budgets SET statut = 'rejete' WHERE id_budget = :id_budget"
            );
            $stmt->execute(['id_budget' => $id_budget]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Erreur lors du rejet du budget : " . $e->getMessage());
        }
    }

    // Récupérer un budget par ID
    public function getBudgetById($id_budget)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT b.*, d.nom AS nom_departement 
                 FROM budgets b 
                 JOIN departements d ON b.id_departement = d.id_departement 
                 WHERE b.id_budget = :id_budget"
            );
            $stmt->execute(['id_budget' => $id_budget]);
            $budget = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($budget) {
                $budget['details'] = $this->getBudgetDetails($id_budget);
            }
            return $budget;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération du budget : " . $e->getMessage());
        }
    }
    // Ajouter cette méthode au BudgetModel si elle n'existe pas encore
    public function addBudgetDetail($id_budget, $id_categorie, $montant, $description)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO details_budget (id_budget, id_categorie, montant, description) 
                 VALUES (:id_budget, :id_categorie, :montant, :description)"
            );
            $stmt->execute([
                'id_budget' => $id_budget,
                'id_categorie' => $id_categorie,
                'montant' => $montant,
                'description' => $description
            ]);

            // Mettre à jour la solde finale
            $budget = $this->getBudgetById($id_budget);
            $details = $this->getBudgetDetails($id_budget);
            $total_gains = 0;
            $total_depenses = 0;

            foreach ($details as $detail) {
                if ($detail['type_categorie'] === 'gain') {
                    $total_gains += $detail['montant'];
                } else {
                    $total_depenses += $detail['montant'];
                }
            }

            $solde_final = $budget['solde_depart'] + $total_gains - $total_depenses;
            $stmt = $this->db->prepare(
                "UPDATE budgets SET solde_final = :solde_final WHERE id_budget = :id_budget"
            );
            $stmt->execute(['solde_final' => $solde_final, 'id_budget' => $id_budget]);

            return true;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de l'ajout du détail du budget : " . $e->getMessage());
        }
    }
}