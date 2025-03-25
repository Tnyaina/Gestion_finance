<?php
// app/models/BudgetModel.php
namespace app\models;

require('D:\xampp\htdocs\PDF\fpdf186\fpdf.php');
use FPDF;
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

    public function exportSituationGlobaleToPDF($startMonth, $startYear, $endMonth, $endYear)
    {
        try {
            // Récupérer toutes les situations globales disponibles
            $query = "SELECT * FROM situation_globale ORDER BY annee ASC, mois ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $situations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Générer la liste complète des périodes entre start et end
            $periods = [];
            $currentMonth = $startMonth;
            $currentYear = $startYear;
            while (($currentYear < $endYear) || ($currentYear == $endYear && $currentMonth <= $endMonth)) {
                $periods[] = ['mois' => $currentMonth, 'annee' => $currentYear];
                $currentMonth++;
                if ($currentMonth > 12) {
                    $currentMonth = 1;
                    $currentYear++;
                }
            }

            // Trouver le dernier solde final réalisé avant la période demandée
            $lastSoldeFinalRealise = 0; // Valeur par défaut
            foreach ($situations as $situation) {
                $situationYear = (int) $situation['annee'];
                $situationMonth = (int) $situation['mois'];
                if (($situationYear < $startYear) || ($situationYear == $startYear && $situationMonth < $startMonth)) {
                    $lastSoldeFinalRealise = $situation['solde_final_realise'];
                } else {
                    break; // Arrêter dès qu'on dépasse la période de départ
                }
            }

            // Compléter les données pour la période demandée
            $completeSituations = [];
            foreach ($periods as $period) {
                $mois = $period['mois'];
                $annee = $period['annee'];

                // Chercher si la période existe dans les données récupérées
                $situation = array_filter($situations, function ($s) use ($mois, $annee) {
                    return $s['mois'] == $mois && $s['annee'] == $annee;
                });
                $situation = array_values($situation)[0] ?? null;

                if ($situation) {
                    $completeSituations[] = $situation;
                    $lastSoldeFinalRealise = $situation['solde_final_realise'];
                } else {
                    // Si la période n'existe pas, créer une entrée avec le dernier solde final réalisé
                    $completeSituations[] = [
                        'mois' => $mois,
                        'annee' => $annee,
                        'solde_depart_previsionnel' => $lastSoldeFinalRealise,
                        'gains_previsionnels' => 0,
                        'depenses_previsionnelles' => 0,
                        'solde_final_previsionnel' => $lastSoldeFinalRealise,
                        'solde_depart_realise' => $lastSoldeFinalRealise,
                        'gains_realises' => 0,
                        'depenses_realisees' => 0,
                        'solde_final_realise' => $lastSoldeFinalRealise,
                        'solde_depart_mois_suivant' => $lastSoldeFinalRealise
                    ];
                }
            }

            if (empty($completeSituations)) {
                throw new Exception("Aucune donnée disponible pour la période sélectionnée.");
            }

            // Créer le PDF
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, 'Situation Globale - Budgets', 0, 1, 'C');
            $pdf->Ln(5);

            // Largeurs des colonnes pour A4
            $colWidthLabel = 40;
            $colWidthValue = 50;

            foreach ($completeSituations as $situation) {
                $periode = sprintf("%02d/%d", $situation['mois'], $situation['annee']);
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 10, "Periode : $periode", 0, 1);
                $pdf->Ln(2);

                // En-têtes du tableau
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->Cell($colWidthLabel, 8, '', 1);
                $pdf->Cell($colWidthValue, 8, 'Previsions', 1, 0, 'C');
                $pdf->Cell($colWidthValue, 8, 'Realisations', 1, 0, 'C');
                $pdf->Cell($colWidthValue, 8, 'Ecarts', 1, 0, 'C');
                $pdf->Ln();

                // Lignes du tableau
                $pdf->SetFont('Arial', '', 10);

                // Solde de départ
                $pdf->Cell($colWidthLabel, 8, 'Solde de depart', 1);
                $pdf->Cell($colWidthValue, 8, number_format($situation['solde_depart_previsionnel'], 2), 1, 0, 'R');
                $pdf->Cell($colWidthValue, 8, number_format($situation['solde_depart_realise'], 2), 1, 0, 'R');
                $ecartDepart = $situation['solde_depart_realise'] - $situation['solde_depart_previsionnel'];
                $pdf->Cell($colWidthValue, 8, number_format($ecartDepart, 2), 1, 0, 'R');
                $pdf->Ln();

                // Gains
                $pdf->Cell($colWidthLabel, 8, 'Gains', 1);
                $pdf->Cell($colWidthValue, 8, number_format($situation['gains_previsionnels'], 2), 1, 0, 'R');
                $pdf->Cell($colWidthValue, 8, number_format($situation['gains_realises'], 2), 1, 0, 'R');
                $ecartGains = $situation['gains_realises'] - $situation['gains_previsionnels'];
                $pdf->Cell($colWidthValue, 8, number_format($ecartGains, 2), 1, 0, 'R');
                $pdf->Ln();

                // Dépenses
                $pdf->Cell($colWidthLabel, 8, 'Depenses', 1);
                $pdf->Cell($colWidthValue, 8, number_format($situation['depenses_previsionnelles'], 2), 1, 0, 'R');
                $pdf->Cell($colWidthValue, 8, number_format($situation['depenses_realisees'], 2), 1, 0, 'R');
                $ecartDepenses = $situation['depenses_realisees'] - $situation['depenses_previsionnelles'];
                $pdf->Cell($colWidthValue, 8, number_format($ecartDepenses, 2), 1, 0, 'R');
                $pdf->Ln();

                // Solde final
                $pdf->Cell($colWidthLabel, 8, 'Solde final', 1);
                $pdf->Cell($colWidthValue, 8, number_format($situation['solde_final_previsionnel'], 2), 1, 0, 'R');
                $pdf->Cell($colWidthValue, 8, number_format($situation['solde_final_realise'], 2), 1, 0, 'R');
                $ecartFinal = $situation['solde_final_realise'] - $situation['solde_final_previsionnel'];
                $pdf->Cell($colWidthValue, 8, number_format($ecartFinal, 2), 1, 0, 'R');
                $pdf->Ln(10); // Espace entre périodes
            }

            // Nom du fichier
            $fileName = "Situation_Globale_{$startYear}-{$startMonth}_to_{$endYear}-{$endMonth}.pdf";
            $pdf->Output('D', $fileName);
            exit;

        } catch (Exception $e) {
            error_log('Erreur lors de l\'exportation PDF : ' . $e->getMessage());
            Flight::redirect('/admin/dashboard?error=export_failed');
        }
    }
    public function exportMonthDetailsToPDF($month, $year)
    {
        try {
            // Récupérer toutes les situations globales disponibles
            $query = "SELECT * FROM situation_globale ORDER BY annee ASC, mois ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $situations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Trouver le dernier solde final réalisé avant le mois demandé
            $lastSoldeFinalRealise = 0;
            foreach ($situations as $situation) {
                $situationYear = (int) $situation['annee'];
                $situationMonth = (int) $situation['mois'];
                if (($situationYear < $year) || ($situationYear == $year && $situationMonth < $month)) {
                    $lastSoldeFinalRealise = $situation['solde_final_realise'];
                } else {
                    break;
                }
            }

            // Vérifier si la situation globale existe pour le mois demandé
            $situation = array_filter($situations, function ($s) use ($month, $year) {
                return $s['mois'] == $month && $s['annee'] == $year;
            });
            $situation = array_values($situation)[0] ?? null;

            if (!$situation) {
                $situation = [
                    'mois' => $month,
                    'annee' => $year,
                    'solde_depart_previsionnel' => $lastSoldeFinalRealise,
                    'gains_previsionnels' => 0,
                    'depenses_previsionnelles' => 0,
                    'solde_final_previsionnel' => $lastSoldeFinalRealise,
                    'solde_depart_realise' => $lastSoldeFinalRealise,
                    'gains_realises' => 0,
                    'depenses_realisees' => 0,
                    'solde_final_realise' => $lastSoldeFinalRealise,
                    'solde_depart_mois_suivant' => $lastSoldeFinalRealise
                ];
            }

            // Récupérer les détails financiers par département
            $departements = $this->db->query("SELECT * FROM departements")->fetchAll(PDO::FETCH_ASSOC);
            $departementDetails = [];
            foreach ($departements as $departement) {
                $id_departement = $departement['id_departement'];

                // Budget (prévisions)
                $budgetStmt = $this->db->prepare(
                    "SELECT b.id_budget, b.solde_depart, b.solde_final AS solde_final_calculee
                 FROM budgets b
                 WHERE b.id_departement = :id AND b.mois = :mois AND b.annee = :annee"
                );
                $budgetStmt->execute(['id' => $id_departement, 'mois' => $month, 'annee' => $year]);
                $budget = $budgetStmt->fetch(PDO::FETCH_ASSOC);

                if ($budget) {
                    $detailsStmt = $this->db->prepare(
                        "SELECT 
                        SUM(CASE WHEN c.type = 'gain' THEN d.montant ELSE 0 END) AS total_gains,
                        SUM(CASE WHEN c.type = 'depense' THEN d.montant ELSE 0 END) AS total_depenses
                     FROM details_budget d
                     JOIN categories c ON d.id_categorie = c.id_categorie
                     WHERE d.id_budget = :id_budget"
                    );
                    $detailsStmt->execute(['id_budget' => $budget['id_budget']]);
                    $details = $detailsStmt->fetch(PDO::FETCH_ASSOC);
                    $budget['total_gains'] = $details['total_gains'] ?? 0;
                    $budget['total_depenses'] = $details['total_depenses'] ?? 0;
                } else {
                    $budget = [
                        'solde_depart' => $lastSoldeFinalRealise,
                        'total_gains' => 0,
                        'total_depenses' => 0,
                        'solde_final_calculee' => $lastSoldeFinalRealise
                    ];
                }

                // Réalisations (transactions)
                $realStmt = $this->db->prepare(
                    "SELECT 
                    COALESCE(SUM(CASE WHEN c.type = 'gain' THEN t.montant ELSE 0 END), 0) AS total_gains,
                    COALESCE(SUM(CASE WHEN c.type = 'depense' THEN t.montant ELSE 0 END), 0) AS total_depenses
                 FROM transactions t
                 JOIN categories c ON t.id_categorie = c.id_categorie
                 WHERE t.id_departement = :id AND t.mois = :mois AND t.annee = :annee"
                );
                $realStmt->execute(['id' => $id_departement, 'mois' => $month, 'annee' => $year]);
                $realisations = $realStmt->fetch(PDO::FETCH_ASSOC);
                $realisations = [
                    'solde_depart' => $budget['solde_depart'],
                    'total_gains' => $realisations['total_gains'],
                    'total_depenses' => $realisations['total_depenses'],
                    'solde_final' => $budget['solde_depart'] + $realisations['total_gains'] - $realisations['total_depenses']
                ];

                // Récupérer les détails des gains et dépenses réalisés pour ce département
                $transactionsStmt = $this->db->prepare(
                    "SELECT c.nom AS categorie, t.montant, t.description, c.type
                 FROM transactions t
                 JOIN categories c ON t.id_categorie = c.id_categorie
                 WHERE t.id_departement = :id AND t.mois = :mois AND t.annee = :annee"
                );
                $transactionsStmt->execute(['id' => $id_departement, 'mois' => $month, 'annee' => $year]);
                $transactions = $transactionsStmt->fetchAll(PDO::FETCH_ASSOC);

                $departementDetails[] = [
                    'nom' => $departement['nom'],
                    'budget' => $budget,
                    'realisations' => $realisations,
                    'transactions' => $transactions
                ];

                // Agréger pour la situation globale si elle était vide
                if ($situation['gains_previsionnels'] == 0 && $situation['depenses_previsionnelles'] == 0) {
                    $situation['gains_previsionnels'] += $budget['total_gains'];
                    $situation['depenses_previsionnelles'] += $budget['total_depenses'];
                    $situation['solde_final_previsionnel'] = $situation['solde_depart_previsionnel'] + $situation['gains_previsionnels'] - $situation['depenses_previsionnelles'];
                    $situation['gains_realises'] += $realisations['total_gains'];
                    $situation['depenses_realisees'] += $realisations['total_depenses'];
                    $situation['solde_final_realise'] = $situation['solde_depart_realise'] + $situation['gains_realises'] - $situation['depenses_realisees'];
                }
            }

            // Créer le PDF
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, "Details Financiers - " . sprintf("%02d/%d", $month, $year), 0, 1, 'C');
            $pdf->Ln(5);

            $colWidthLabel = 40;
            $colWidthValue = 50;

            // Situation Globale
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, 'Situation Globale', 0, 1);
            $pdf->Ln(2);

            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell($colWidthLabel, 8, '', 1);
            $pdf->Cell($colWidthValue, 8, 'Previsions', 1, 0, 'C');
            $pdf->Cell($colWidthValue, 8, 'Realisations', 1, 0, 'C');
            $pdf->Cell($colWidthValue, 8, 'Ecarts', 1, 0, 'C');
            $pdf->Ln();

            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell($colWidthLabel, 8, 'Solde de depart', 1);
            $pdf->Cell($colWidthValue, 8, number_format($situation['solde_depart_previsionnel'], 2), 1, 0, 'R');
            $pdf->Cell($colWidthValue, 8, number_format($situation['solde_depart_realise'], 2), 1, 0, 'R');
            $ecartDepart = $situation['solde_depart_realise'] - $situation['solde_depart_previsionnel'];
            $pdf->Cell($colWidthValue, 8, number_format($ecartDepart, 2), 1, 0, 'R');
            $pdf->Ln();

            $pdf->Cell($colWidthLabel, 8, 'Gains', 1);
            $pdf->Cell($colWidthValue, 8, number_format($situation['gains_previsionnels'], 2), 1, 0, 'R');
            $pdf->Cell($colWidthValue, 8, number_format($situation['gains_realises'], 2), 1, 0, 'R');
            $ecartGains = $situation['gains_realises'] - $situation['gains_previsionnels'];
            $pdf->Cell($colWidthValue, 8, number_format($ecartGains, 2), 1, 0, 'R');
            $pdf->Ln();

            $pdf->Cell($colWidthLabel, 8, 'Depenses', 1);
            $pdf->Cell($colWidthValue, 8, number_format($situation['depenses_previsionnelles'], 2), 1, 0, 'R');
            $pdf->Cell($colWidthValue, 8, number_format($situation['depenses_realisees'], 2), 1, 0, 'R');
            $ecartDepenses = $situation['depenses_realisees'] - $situation['depenses_previsionnelles'];
            $pdf->Cell($colWidthValue, 8, number_format($ecartDepenses, 2), 1, 0, 'R');
            $pdf->Ln();

            $pdf->Cell($colWidthLabel, 8, 'Solde final', 1);
            $pdf->Cell($colWidthValue, 8, number_format($situation['solde_final_previsionnel'], 2), 1, 0, 'R');
            $pdf->Cell($colWidthValue, 8, number_format($situation['solde_final_realise'], 2), 1, 0, 'R');
            $ecartFinal = $situation['solde_final_realise'] - $situation['solde_final_previsionnel'];
            $pdf->Cell($colWidthValue, 8, number_format($ecartFinal, 2), 1, 0, 'R');
            $pdf->Ln(10);

            // Détails par département
            foreach ($departementDetails as $dept) {
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 10, "Departement : " . $dept['nom'], 0, 1);
                $pdf->Ln(2);

                // Tableau récapitulatif
                $pdf->SetFont('Arial', 'B', 10);
                $pdf->Cell($colWidthLabel, 8, '', 1);
                $pdf->Cell($colWidthValue, 8, 'Previsions', 1, 0, 'C');
                $pdf->Cell($colWidthValue, 8, 'Realisations', 1, 0, 'C');
                $pdf->Cell($colWidthValue, 8, 'Ecarts', 1, 0, 'C');
                $pdf->Ln();

                $pdf->SetFont('Arial', '', 10);
                $pdf->Cell($colWidthLabel, 8, 'Solde de depart', 1);
                $pdf->Cell($colWidthValue, 8, number_format($dept['budget']['solde_depart'], 2), 1, 0, 'R');
                $pdf->Cell($colWidthValue, 8, number_format($dept['realisations']['solde_depart'], 2), 1, 0, 'R');
                $ecartDeptDepart = $dept['realisations']['solde_depart'] - $dept['budget']['solde_depart'];
                $pdf->Cell($colWidthValue, 8, number_format($ecartDeptDepart, 2), 1, 0, 'R');
                $pdf->Ln();

                $pdf->Cell($colWidthLabel, 8, 'Gains', 1);
                $pdf->Cell($colWidthValue, 8, number_format($dept['budget']['total_gains'], 2), 1, 0, 'R');
                $pdf->Cell($colWidthValue, 8, number_format($dept['realisations']['total_gains'], 2), 1, 0, 'R');
                $ecartDeptGains = $dept['realisations']['total_gains'] - $dept['budget']['total_gains'];
                $pdf->Cell($colWidthValue, 8, number_format($ecartDeptGains, 2), 1, 0, 'R');
                $pdf->Ln();

                $pdf->Cell($colWidthLabel, 8, 'Depenses', 1);
                $pdf->Cell($colWidthValue, 8, number_format($dept['budget']['total_depenses'], 2), 1, 0, 'R');
                $pdf->Cell($colWidthValue, 8, number_format($dept['realisations']['total_depenses'], 2), 1, 0, 'R');
                $ecartDeptDepenses = $dept['realisations']['total_depenses'] - $dept['budget']['total_depenses'];
                $pdf->Cell($colWidthValue, 8, number_format($ecartDeptDepenses, 2), 1, 0, 'R');
                $pdf->Ln();

                $pdf->Cell($colWidthLabel, 8, 'Solde final', 1);
                $pdf->Cell($colWidthValue, 8, number_format($dept['budget']['solde_final_calculee'], 2), 1, 0, 'R');
                $pdf->Cell($colWidthValue, 8, number_format($dept['realisations']['solde_final'], 2), 1, 0, 'R');
                $ecartDeptFinal = $dept['realisations']['solde_final'] - $dept['budget']['solde_final_calculee'];
                $pdf->Cell($colWidthValue, 8, number_format($ecartDeptFinal, 2), 1, 0, 'R');
                $pdf->Ln(5);

                // Détails des gains et dépenses réalisés
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 10, 'Details des Transactions (Gains et Depenses)', 0, 1);
                $pdf->Ln(2);

                $pdf->SetFont('Arial', 'B', 10);
                $pdf->Cell(40, 8, 'Departement', 1);
                $pdf->Cell(30, 8, 'Periode', 1);
                $pdf->Cell(40, 8, 'Categorie', 1);
                $pdf->Cell(30, 8, 'Montant (€)', 1, 0, 'R');
                $pdf->Cell(50, 8, 'Description', 1);
                $pdf->Ln();

                $pdf->SetFont('Arial', '', 10);
                if (empty($dept['transactions'])) {
                    $pdf->Cell(190, 8, 'Aucune transaction enregistree pour ce departement.', 1, 1, 'C');
                } else {
                    foreach ($dept['transactions'] as $transaction) {
                        $periode = sprintf("%02d/%d", $month, $year);
                        $pdf->Cell(40, 8, $dept['nom'], 1);
                        $pdf->Cell(30, 8, $periode, 1);
                        $pdf->Cell(40, 8, $transaction['categorie'], 1);
                        $montant = $transaction['type'] == 'depense' ? -1 * $transaction['montant'] : $transaction['montant'];
                        $pdf->Cell(30, 8, number_format($montant, 2), 1, 0, 'R');
                        $pdf->Cell(50, 8, $transaction['description'] ?? 'N/A', 1);
                        $pdf->Ln();
                    }
                }
                $pdf->Ln(10);
            }

            $fileName = "Details_Financiers_{$year}-{$month}.pdf";
            $pdf->Output('D', $fileName);
            exit;

        } catch (Exception $e) {
            error_log('Erreur lors de l\'exportation PDF : ' . $e->getMessage());
            Flight::redirect('/admin/dashboard?error=export_failed');
        }
    }

}