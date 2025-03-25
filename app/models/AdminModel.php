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
            $query = "SELECT * FROM situation_globale ORDER BY annee ASC, mois ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération de la situation globale: ' . $e->getMessage());
            return false;
        }
    }
    public function updateSituationGlobale()
    {
        try {
            // Récupérer toutes les entrées de situation_globale triées par ordre chronologique
            $situations = $this->getSituationGlobal();
            if (!$situations) {
                return false; // Rien à mettre à jour
            }

            // Parcourir chaque période
            foreach ($situations as $index => $situation) {
                $currentMois = (int) $situation['mois'];
                $currentAnnee = (int) $situation['annee'];
                $soldeFinalRealise = $situation['solde_final_realise'];

                // Calculer le mois et l'année suivants
                $nextMois = $currentMois + 1;
                $nextAnnee = $currentAnnee;
                if ($nextMois > 12) {
                    $nextMois = 1;
                    $nextAnnee++;
                }

                // Vérifier si une entrée existe pour le mois suivant
                $stmtCheck = $this->db->prepare(
                    "SELECT COUNT(*) FROM situation_globale WHERE mois = :mois AND annee = :annee"
                );
                $stmtCheck->execute(['mois' => $nextMois, 'annee' => $nextAnnee]);
                $exists = $stmtCheck->fetchColumn() > 0;

                if ($exists) {
                    // Mettre à jour les soldes de départ du mois suivant uniquement si l'entrée existe
                    $stmtUpdate = $this->db->prepare(
                        "UPDATE situation_globale 
                     SET solde_depart_previsionnel = :sdp, 
                         solde_depart_realise = :sdr,
                         solde_depart_mois_suivant = solde_final_realise
                     WHERE mois = :mois AND annee = :annee"
                    );
                    $stmtUpdate->execute([
                        'sdp' => $soldeFinalRealise,
                        'sdr' => $soldeFinalRealise,
                        'mois' => $nextMois,
                        'annee' => $nextAnnee
                    ]);
                }

                // Mettre à jour le solde_depart_mois_suivant de la période actuelle
                $stmtUpdateCurrent = $this->db->prepare(
                    "UPDATE situation_globale 
                 SET solde_depart_mois_suivant = :sdms 
                 WHERE mois = :mois AND annee = :annee"
                );
                $stmtUpdateCurrent->execute([
                    'sdms' => $soldeFinalRealise,
                    'mois' => $currentMois,
                    'annee' => $currentAnnee
                ]);
            }

            return true;
        } catch (Exception $e) {
            error_log('Erreur lors de la mise à jour de la situation globale : ' . $e->getMessage());
            return false;
        }
    }
}