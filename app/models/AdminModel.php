<?php
// app/models/BudgetModel.php
namespace app\models;

use PDO;
use Exception;

class AdminModel
{
    private $db;

    public function __construct(PDO $database)
    {
        $this->db = $database;
    }

    public function getSituationGlobal()
    {
        try {
            $query = "SELECT * FROM situation_globale";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération de la situation globale: ' . $e->getMessage());
            return false;
        }
    }
}