<?php
// app/models/UtilisateurModel.php
namespace app\models;

use PDO;
use Exception;

class UtilisateurModel
{
    private $db;

    public function __construct(PDO $database)
    {
        $this->db = $database;
    }

    public function getUtilisateurConnecte($nom_utilisateur, $mot_de_passe)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM utilisateurs WHERE nom_utilisateur = :nom_utilisateur");
            $stmt->execute(['nom_utilisateur' => $nom_utilisateur]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
                unset($utilisateur['mot_de_passe']);
                return $utilisateur;
            }
            return false;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la connexion: " . $e->getMessage());
        }
    }

    public function estAdmin($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT role FROM utilisateurs WHERE id_utilisateur = :id");
            $stmt->execute(['id' => $id]);
            $role = $stmt->fetchColumn();
            return $role === 'admin';
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la vérification du rôle: " . $e->getMessage());
        }
    }

    public function nomUtilisateurExists($nom_utilisateur)
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM utilisateurs WHERE nom_utilisateur = :nom_utilisateur");
            $stmt->execute(['nom_utilisateur' => $nom_utilisateur]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la vérification du nom d'utilisateur: " . $e->getMessage());
        }
    }

    public function getDepartements()
    {
        try {
            $stmt = $this->db->query("SELECT * FROM departements");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération des départements: " . $e->getMessage());
        }
    }

    public function create($nom_utilisateur, $mot_de_passe, $id_departement, $role = 'utilisateur_departement')
    {
        try {
            $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare(
                "INSERT INTO utilisateurs (nom_utilisateur, mot_de_passe, id_departement, role) 
                 VALUES (:nom_utilisateur, :mot_de_passe, :id_departement, :role)"
            );

            $stmt->execute([
                'nom_utilisateur' => $nom_utilisateur,
                'mot_de_passe' => $hashed_password,
                'id_departement' => $id_departement,
                'role' => $role
            ]);

            return $this->db->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la création de l'utilisateur: " . $e->getMessage());
        }
    }

    public function getAllUtilisateurs()
    {
        try {
            $stmt = $this->db->query("
            SELECT u.id_utilisateur, u.nom_utilisateur, u.id_departement, u.role, 
                   COALESCE(d.nom, 'Aucun département') AS nom_departement 
            FROM utilisateurs u 
            LEFT JOIN departements d ON u.id_departement = d.id_departement
        ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération des utilisateurs: " . $e->getMessage());
        }
    }

    public function getUtilisateurById($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération de l'utilisateur: " . $e->getMessage());
        }
    }

    public function updateUtilisateur($id, $nom_utilisateur, $id_departement, $role, $mot_de_passe = null)
    {
        try {
            if ($mot_de_passe) {
                $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                $stmt = $this->db->prepare(
                    "UPDATE utilisateurs 
                     SET nom_utilisateur = :nom_utilisateur, mot_de_passe = :mot_de_passe, id_departement = :id_departement, role = :role 
                     WHERE id_utilisateur = :id"
                );
                $stmt->execute([
                    'nom_utilisateur' => $nom_utilisateur,
                    'mot_de_passe' => $hashed_password,
                    'id_departement' => $id_departement,
                    'role' => $role,
                    'id' => $id
                ]);
            } else {
                $stmt = $this->db->prepare(
                    "UPDATE utilisateurs 
                     SET nom_utilisateur = :nom_utilisateur, id_departement = :id_departement, role = :role 
                     WHERE id_utilisateur = :id"
                );
                $stmt->execute([
                    'nom_utilisateur' => $nom_utilisateur,
                    'id_departement' => $id_departement,
                    'role' => $role,
                    'id' => $id
                ]);
            }
            return true;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la mise à jour de l'utilisateur: " . $e->getMessage());
        }
    }

    public function deleteUtilisateur($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = :id");
            $stmt->execute(['id' => $id]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la suppression de l'utilisateur: " . $e->getMessage());
        }
    }

    public function createDepartement($nom)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO departements (nom) VALUES (:nom)");
            $stmt->execute(['nom' => $nom]);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la création du département: " . $e->getMessage());
        }
    }

    public function getDepartementById($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM departements WHERE id_departement = :id");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération du département: " . $e->getMessage());
        }
    }

    public function updateDepartement($id, $nom)
    {
        try {
            $stmt = $this->db->prepare("UPDATE departements SET nom = :nom WHERE id_departement = :id");
            $stmt->execute(['nom' => $nom, 'id' => $id]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la mise à jour du département: " . $e->getMessage());
        }
    }

    public function deleteDepartement($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM departements WHERE id_departement = :id");
            $stmt->execute(['id' => $id]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la suppression du département: " . $e->getMessage());
        }
    }

    public function getCategories()
    {
        try {
            $stmt = $this->db->query("SELECT * FROM categories");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération des catégories: " . $e->getMessage());
        }
    }
    public function createCategorie($nom, $type)
    {
        $stmt = $this->db->prepare("INSERT INTO categories (nom, type) VALUES (:nom, :type)");
        return $stmt->execute([
            'nom' => $nom,
            'type' => $type
        ]);
    }

    public function getTransactionsByDepartement($id_departement, $mois = null, $annee = null)
    {
        try {
            $query = "SELECT t.*, c.nom AS nom_categorie, c.type AS type_categorie 
                      FROM transactions t 
                      JOIN categories c ON t.id_categorie = c.id_categorie 
                      WHERE t.id_departement = :id_departement";
            $params = ['id_departement' => $id_departement];

            if ($mois !== null) {
                $query .= " AND t.mois = :mois";
                $params['mois'] = $mois;
            }
            if ($annee !== null) {
                $query .= " AND t.annee = :annee";
                $params['annee'] = $annee;
            }

            $query .= " ORDER BY t.annee DESC, t.mois DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération des transactions: " . $e->getMessage());
        }
    }

    public function getTransactionById($id_transaction, $id_departement)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM transactions WHERE id_transaction = :id_transaction AND id_departement = :id_departement");
            $stmt->execute(['id_transaction' => $id_transaction, 'id_departement' => $id_departement]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération de la transaction: " . $e->getMessage());
        }
    }

    public function addTransaction($id_departement, $mois, $annee, $id_categorie, $montant, $description)
    {
        try {
            $this->db->beginTransaction();

            // Ajouter la transaction
            $stmt = $this->db->prepare(
                "INSERT INTO transactions (id_departement, mois, annee, id_categorie, montant, description) 
             VALUES (:id_departement, :mois, :annee, :id_categorie, :montant, :description)"
            );
            $stmt->execute([
                'id_departement' => $id_departement,
                'mois' => $mois,
                'annee' => $annee,
                'id_categorie' => $id_categorie,
                'montant' => $montant,
                'description' => $description
            ]);
            $id_transaction = $this->db->lastInsertId();

            // Récupérer le type de la catégorie (gain ou dépense)
            $stmtCat = $this->db->prepare("SELECT type FROM categories WHERE id_categorie = :id_categorie");
            $stmtCat->execute(['id_categorie' => $id_categorie]);
            $typeCategorie = $stmtCat->fetchColumn();

            // Vérifier si une entrée existe déjà dans situation_globale pour ce mois/année
            $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM situation_globale WHERE mois = :mois AND annee = :annee");
            $stmtCheck->execute(['mois' => $mois, 'annee' => $annee]);
            $exists = $stmtCheck->fetchColumn() > 0;

            if ($exists) {
                // Mettre à jour la situation globale existante
                $fieldToUpdate = ($typeCategorie === 'gain') ? 'gains_realises' : 'depenses_realisees';
                $stmtUpdate = $this->db->prepare(
                    "UPDATE situation_globale 
                 SET {$fieldToUpdate} = {$fieldToUpdate} + :montant 
                 WHERE mois = :mois AND annee = :annee"
                );
                $stmtUpdate->execute([
                    'montant' => $montant,
                    'mois' => $mois,
                    'annee' => $annee
                ]);

                // Recalculer le solde final
                $stmtRecalc = $this->db->prepare(
                    "UPDATE situation_globale 
                 SET solde_final_realise = solde_depart_realise + gains_realises - depenses_realisees,
                     solde_depart_mois_suivant = solde_depart_realise + gains_realises - depenses_realisees
                 WHERE mois = :mois AND annee = :annee"
                );
                $stmtRecalc->execute(['mois' => $mois, 'annee' => $annee]);
            } else {
                // Créer une nouvelle entrée dans situation_globale
                // Déterminer le solde de départ en fonction du mois précédent
                $moisPrecedent = ($mois == 1) ? 12 : $mois - 1;
                $anneePrecedente = ($mois == 1) ? $annee - 1 : $annee;

                $stmtPrev = $this->db->prepare("SELECT solde_depart_mois_suivant FROM situation_globale 
                                          WHERE mois = :mois AND annee = :annee");
                $stmtPrev->execute(['mois' => $moisPrecedent, 'annee' => $anneePrecedente]);
                $prevSituation = $stmtPrev->fetch(PDO::FETCH_ASSOC);
                $soldeDepart = $prevSituation ? $prevSituation['solde_depart_mois_suivant'] : 0;

                $gainsRealises = ($typeCategorie === 'gain') ? $montant : 0;
                $depensesRealisees = ($typeCategorie === 'depense') ? $montant : 0;
                $soldeFinalRealise = $soldeDepart + $gainsRealises - $depensesRealisees;

                $stmtInsert = $this->db->prepare(
                    "INSERT INTO situation_globale (mois, annee, solde_depart_previsionnel, gains_previsionnels, 
                depenses_previsionnelles, solde_final_previsionnel, solde_depart_realise, gains_realises, 
                depenses_realisees, solde_final_realise, solde_depart_mois_suivant) 
                VALUES (:mois, :annee, :sdp, :gp, :dp, :sfp, :sdr, :gr, :dr, :sfr, :sdms)"
                );
                $stmtInsert->execute([
                    'mois' => $mois,
                    'annee' => $annee,
                    'sdp' => 0, // Valeurs prévisionnelles par défaut
                    'gp' => 0,
                    'dp' => 0,
                    'sfp' => 0,
                    'sdr' => $soldeDepart,
                    'gr' => $gainsRealises,
                    'dr' => $depensesRealisees,
                    'sfr' => $soldeFinalRealise,
                    'sdms' => $soldeFinalRealise
                ]);
            }

            $this->db->commit();
            return $id_transaction;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Erreur lors de l'ajout de la transaction: " . $e->getMessage());
        }
    }

    public function updateTransaction($id_transaction, $id_departement, $mois, $annee, $id_categorie, $montant, $description)
    {
        try {
            $this->db->beginTransaction();

            // Récupérer l'ancienne transaction
            $stmtOld = $this->db->prepare(
                "SELECT t.*, c.type AS type_categorie 
             FROM transactions t 
             JOIN categories c ON t.id_categorie = c.id_categorie
             WHERE t.id_transaction = :id_transaction AND t.id_departement = :id_departement"
            );
            $stmtOld->execute(['id_transaction' => $id_transaction, 'id_departement' => $id_departement]);
            $oldTrans = $stmtOld->fetch(PDO::FETCH_ASSOC);

            if (!$oldTrans) {
                throw new Exception("Transaction introuvable");
            }

            // Récupérer le type de la nouvelle catégorie
            $stmtCat = $this->db->prepare("SELECT type FROM categories WHERE id_categorie = :id_categorie");
            $stmtCat->execute(['id_categorie' => $id_categorie]);
            $newTypeCategorie = $stmtCat->fetchColumn();

            // Mettre à jour la transaction
            $stmt = $this->db->prepare(
                "UPDATE transactions 
             SET mois = :mois, annee = :annee, id_categorie = :id_categorie, montant = :montant, description = :description 
             WHERE id_transaction = :id_transaction AND id_departement = :id_departement"
            );
            $stmt->execute([
                'mois' => $mois,
                'annee' => $annee,
                'id_categorie' => $id_categorie,
                'montant' => $montant,
                'description' => $description,
                'id_transaction' => $id_transaction,
                'id_departement' => $id_departement
            ]);

            // Traiter l'ancienne période - soustraire le montant de l'ancienne transaction
            $oldMois = $oldTrans['mois'];
            $oldAnnee = $oldTrans['annee'];
            $oldMontant = $oldTrans['montant'];
            $oldTypeCategorie = $oldTrans['type_categorie'];

            $stmtCheckOld = $this->db->prepare("SELECT COUNT(*) FROM situation_globale WHERE mois = :mois AND annee = :annee");
            $stmtCheckOld->execute(['mois' => $oldMois, 'annee' => $oldAnnee]);
            $oldExists = $stmtCheckOld->fetchColumn() > 0;

            if ($oldExists) {
                $fieldToUpdate = ($oldTypeCategorie === 'gain') ? 'gains_realises' : 'depenses_realisees';
                $stmtUpdateOld = $this->db->prepare(
                    "UPDATE situation_globale 
                 SET {$fieldToUpdate} = {$fieldToUpdate} - :montant 
                 WHERE mois = :mois AND annee = :annee"
                );
                $stmtUpdateOld->execute([
                    'montant' => $oldMontant,
                    'mois' => $oldMois,
                    'annee' => $oldAnnee
                ]);

                // Recalculer le solde final de l'ancienne période
                $stmtRecalcOld = $this->db->prepare(
                    "UPDATE situation_globale 
                 SET solde_final_realise = solde_depart_realise + gains_realises - depenses_realisees,
                     solde_depart_mois_suivant = solde_depart_realise + gains_realises - depenses_realisees
                 WHERE mois = :mois AND annee = :annee"
                );
                $stmtRecalcOld->execute(['mois' => $oldMois, 'annee' => $oldAnnee]);
            }

            // Traiter la nouvelle période - ajouter le nouveau montant
            $stmtCheckNew = $this->db->prepare("SELECT COUNT(*) FROM situation_globale WHERE mois = :mois AND annee = :annee");
            $stmtCheckNew->execute(['mois' => $mois, 'annee' => $annee]);
            $newExists = $stmtCheckNew->fetchColumn() > 0;

            if ($newExists) {
                $fieldToUpdate = ($newTypeCategorie === 'gain') ? 'gains_realises' : 'depenses_realisees';
                $stmtUpdateNew = $this->db->prepare(
                    "UPDATE situation_globale 
                 SET {$fieldToUpdate} = {$fieldToUpdate} + :montant 
                 WHERE mois = :mois AND annee = :annee"
                );
                $stmtUpdateNew->execute([
                    'montant' => $montant,
                    'mois' => $mois,
                    'annee' => $annee
                ]);

                // Recalculer le solde final de la nouvelle période
                $stmtRecalcNew = $this->db->prepare(
                    "UPDATE situation_globale 
                 SET solde_final_realise = solde_depart_realise + gains_realises - depenses_realisees,
                     solde_depart_mois_suivant = solde_depart_realise + gains_realises - depenses_realisees
                 WHERE mois = :mois AND annee = :annee"
                );
                $stmtRecalcNew->execute(['mois' => $mois, 'annee' => $annee]);
            } else {
                // Créer une nouvelle entrée dans situation_globale pour la nouvelle période
                // Déterminer le solde de départ en fonction du mois précédent
                $moisPrecedent = ($mois == 1) ? 12 : $mois - 1;
                $anneePrecedente = ($mois == 1) ? $annee - 1 : $annee;

                $stmtPrev = $this->db->prepare("SELECT solde_depart_mois_suivant FROM situation_globale 
                                          WHERE mois = :mois AND annee = :annee");
                $stmtPrev->execute(['mois' => $moisPrecedent, 'annee' => $anneePrecedente]);
                $prevSituation = $stmtPrev->fetch(PDO::FETCH_ASSOC);
                $soldeDepart = $prevSituation ? $prevSituation['solde_depart_mois_suivant'] : 0;

                $gainsRealises = ($newTypeCategorie === 'gain') ? $montant : 0;
                $depensesRealisees = ($newTypeCategorie === 'depense') ? $montant : 0;
                $soldeFinalRealise = $soldeDepart + $gainsRealises - $depensesRealisees;

                $stmtInsert = $this->db->prepare(
                    "INSERT INTO situation_globale (mois, annee, solde_depart_previsionnel, gains_previsionnels, 
                depenses_previsionnelles, solde_final_previsionnel, solde_depart_realise, gains_realises, 
                depenses_realisees, solde_final_realise, solde_depart_mois_suivant) 
                VALUES (:mois, :annee, :sdp, :gp, :dp, :sfp, :sdr, :gr, :dr, :sfr, :sdms)"
                );
                $stmtInsert->execute([
                    'mois' => $mois,
                    'annee' => $annee,
                    'sdp' => 0, // Valeurs prévisionnelles par défaut
                    'gp' => 0,
                    'dp' => 0,
                    'sfp' => 0,
                    'sdr' => $soldeDepart,
                    'gr' => $gainsRealises,
                    'dr' => $depensesRealisees,
                    'sfr' => $soldeFinalRealise,
                    'sdms' => $soldeFinalRealise
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Erreur lors de la mise à jour de la transaction: " . $e->getMessage());
        }
    }

    public function deleteTransaction($id_transaction, $id_departement)
    {
        try {
            $this->db->beginTransaction();

            // Récupérer les informations de la transaction avant de la supprimer
            $stmtTrans = $this->db->prepare(
                "SELECT t.*, c.type AS type_categorie 
             FROM transactions t 
             JOIN categories c ON t.id_categorie = c.id_categorie
             WHERE t.id_transaction = :id_transaction AND t.id_departement = :id_departement"
            );
            $stmtTrans->execute(['id_transaction' => $id_transaction, 'id_departement' => $id_departement]);
            $transaction = $stmtTrans->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                throw new Exception("Transaction introuvable");
            }

            // Supprimer la transaction
            $stmt = $this->db->prepare("DELETE FROM transactions WHERE id_transaction = :id_transaction AND id_departement = :id_departement");
            $stmt->execute(['id_transaction' => $id_transaction, 'id_departement' => $id_departement]);

            // Mettre à jour la situation globale - soustraire le montant
            $mois = $transaction['mois'];
            $annee = $transaction['annee'];
            $montant = $transaction['montant'];
            $typeCategorie = $transaction['type_categorie'];

            $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM situation_globale WHERE mois = :mois AND annee = :annee");
            $stmtCheck->execute(['mois' => $mois, 'annee' => $annee]);
            $exists = $stmtCheck->fetchColumn() > 0;

            if ($exists) {
                $fieldToUpdate = ($typeCategorie === 'gain') ? 'gains_realises' : 'depenses_realisees';
                $stmtUpdate = $this->db->prepare(
                    "UPDATE situation_globale 
                 SET {$fieldToUpdate} = {$fieldToUpdate} - :montant 
                 WHERE mois = :mois AND annee = :annee"
                );
                $stmtUpdate->execute([
                    'montant' => $montant,
                    'mois' => $mois,
                    'annee' => $annee
                ]);

                // Recalculer le solde final
                $stmtRecalc = $this->db->prepare(
                    "UPDATE situation_globale 
                 SET solde_final_realise = solde_depart_realise + gains_realises - depenses_realisees,
                     solde_depart_mois_suivant = solde_depart_realise + gains_realises - depenses_realisees
                 WHERE mois = :mois AND annee = :annee"
                );
                $stmtRecalc->execute(['mois' => $mois, 'annee' => $annee]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Erreur lors de la suppression de la transaction: " . $e->getMessage());
        }
    }

    public function getFinancialSummary($id_departement, $mois = null, $annee = null)
    {
        try {
            $query = "SELECT 
                        (SELECT IFNULL(SUM(t.montant), 0) 
                         FROM transactions t 
                         JOIN categories c ON t.id_categorie = c.id_categorie 
                         WHERE t.id_departement = :id_departement AND c.type = 'gain'";
            $params = ['id_departement' => $id_departement];

            if ($mois !== null) {
                $query .= " AND t.mois = :mois";
                $params['mois'] = $mois;
            }
            if ($annee !== null) {
                $query .= " AND t.annee = :annee";
                $params['annee'] = $annee;
            }

            $query .= ") AS total_gains, 
                      (SELECT IFNULL(SUM(t.montant), 0) 
                       FROM transactions t 
                       JOIN categories c ON t.id_categorie = c.id_categorie 
                       WHERE t.id_departement = :id_departement2 AND c.type = 'depense'";

            if ($mois !== null) {
                $query .= " AND t.mois = :mois2";
                $params['mois2'] = $mois;
            }
            if ($annee !== null) {
                $query .= " AND t.annee = :annee2";
                $params['annee2'] = $annee;
            }

            $query .= ") AS total_depenses";

            $params['id_departement2'] = $id_departement;

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Récupérer le solde de départ du budget
            $budget = $this->getBudgetByDepartementAndPeriod($id_departement, $mois, $annee);
            $result['solde_depart'] = $budget ? $budget['solde_depart'] : 0;
            $result['solde_final'] = $result['solde_depart'] + $result['total_gains'] - $result['total_depenses'];
            return $result;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération du résumé financier: " . $e->getMessage());
        }
    }

    public function isBudgetApproved($id_departement, $mois, $annee)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) 
                 FROM budgets 
                 WHERE id_departement = :id_departement 
                 AND mois = :mois 
                 AND annee = :annee 
                 AND statut = 'approuve'"
            );
            $stmt->execute([
                'id_departement' => $id_departement,
                'mois' => $mois,
                'annee' => $annee
            ]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la vérification du budget: " . $e->getMessage());
        }
    }

    public function getBudgetsByDepartement($id_departement, $mois = null, $annee = null)
    {
        try {
            $query = "SELECT b.*, d.nom AS nom_departement 
                      FROM budgets b 
                      JOIN departements d ON b.id_departement = d.id_departement 
                      WHERE b.id_departement = :id_departement";
            $params = ['id_departement' => $id_departement];

            if ($mois !== null) {
                $query .= " AND b.mois = :mois";
                $params['mois'] = $mois;
            }
            if ($annee !== null) {
                $query .= " AND b.annee = :annee";
                $params['annee'] = $annee;
            }

            $query .= " ORDER BY b.annee DESC, b.mois DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculer la solde finale pour chaque budget
            foreach ($budgets as &$budget) {
                $details = $this->getBudgetDetails($budget['id_budget']);
                $total_gains = 0;
                $total_depenses = 0;

                foreach ($details as $detail) {
                    if ($detail['type_categorie'] === 'gain') {
                        $total_gains += $detail['montant'];
                    } else {
                        $total_depenses += $detail['montant'];
                    }
                }

                $budget['total_gains'] = $total_gains;
                $budget['total_depenses'] = $total_depenses;
                $budget['solde_final_calculee'] = $budget['solde_depart'] + $total_gains - $total_depenses;
            }

            return $budgets;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération des budgets: " . $e->getMessage());
        }
    }

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
            throw new Exception("Erreur lors de la récupération des détails du budget: " . $e->getMessage());
        }
    }

    public function budgetExists($id_departement, $mois, $annee)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM budgets 
             WHERE id_departement = :id_departement 
             AND mois = :mois 
             AND annee = :annee"
            );
            $stmt->execute([
                'id_departement' => $id_departement,
                'mois' => $mois,
                'annee' => $annee
            ]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la vérification de l'existence du budget : " . $e->getMessage());
        }
    }
    // Récupérer un budget spécifique pour un département, mois et année
    public function getBudgetByDepartementAndPeriod($id_departement, $mois, $annee)
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM budgets 
             WHERE id_departement = :id_departement 
             AND mois = :mois 
             AND annee = :annee"
            );
            $stmt->execute([
                'id_departement' => $id_departement,
                'mois' => $mois,
                'annee' => $annee
            ]);
            $budget = $stmt->fetch(PDO::FETCH_ASSOC);

            // Date d'initialisation : janvier 2025
            $init_annee = 2025;
            $init_mois = 1;

            if ($budget) {
                // Récupérer les détails prévisionnels pour le budget actuel
                $details = $this->getBudgetDetails($budget['id_budget']);
                $total_gains = 0;
                $total_depenses = 0;
                foreach ($details as $detail) {
                    if ($detail['type_categorie'] === 'gain') {
                        $total_gains += $detail['montant'];
                    } else {
                        $total_depenses += $detail['montant'];
                    }
                }
                $budget['total_gains'] = $total_gains;
                $budget['total_depenses'] = $total_depenses;

                // Si c'est janvier 2025, conserver le solde de départ saisi
                if ($annee == $init_annee && $mois == $init_mois) {
                    // Ne rien faire : le solde_depart reste celui de la base
                }
                // Pour toute période avant janvier 2025, solde de départ = 0
                else if (($annee < $init_annee) || ($annee == $init_annee && $mois < $init_mois)) {
                    $budget['solde_depart'] = 0;
                    $this->updateBudgetSoldeDepart($budget['id_budget'], 0);
                }
                // Pour les périodes après janvier 2025, calculer à partir des réalisations précédentes
                else {
                    $previous_period = $this->getPreviousPeriod($mois, $annee);
                    $previous_summary = $this->getFinancialSummary(
                        $id_departement,
                        $previous_period['mois'],
                        $previous_period['annee']
                    );

                    if ($previous_summary) {
                        $previous_solde_final = $previous_summary['solde_final'];
                        $budget['solde_depart'] = $previous_solde_final;
                        $this->updateBudgetSoldeDepart($budget['id_budget'], $previous_solde_final);
                    }
                }

                // Calculer le solde final prévisionnel pour référence
                $budget['solde_final_calculee'] = $budget['solde_depart'] + $total_gains - $total_depenses;
            } else {
                // Si aucun budget n'existe pour cette période, vérifier la période précédente
                if (($annee > $init_annee) || ($annee == $init_annee && $mois >= $init_mois)) {
                    $previous_period = $this->getPreviousPeriod($mois, $annee);
                    $previous_summary = $this->getFinancialSummary(
                        $id_departement,
                        $previous_period['mois'],
                        $previous_period['annee']
                    );
                    if ($previous_summary) {
                        return [
                            'id_departement' => $id_departement,
                            'mois' => $mois,
                            'annee' => $annee,
                            'solde_depart' => $previous_summary['solde_final'],
                            'total_gains' => 0,
                            'total_depenses' => 0,
                            'solde_final_calculee' => $previous_summary['solde_final']
                        ];
                    }
                }
            }

            return $budget;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération du budget: " . $e->getMessage());
        }
    }

    // Créer un budget (proposition par l'utilisateur)
    public function createBudget($id_departement, $mois, $annee, $solde_depart)
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO budgets (id_departement, mois, annee, solde_depart, solde_final, statut) 
                 VALUES (:id_departement, :mois, :annee, :solde_depart, :solde_final, 'en_attente')"
            );
            $stmt->execute([
                'id_departement' => $id_departement,
                'mois' => $mois,
                'annee' => $annee,
                'solde_depart' => $solde_depart,
                'solde_final' => $solde_depart // Solde final sera recalculé après ajout des détails
            ]);
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la création du budget: " . $e->getMessage());
        }
    }

    // Ajouter un détail au budget
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

            // Mettre à jour la solde finale du budget
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
            $this->updateBudgetSoldeFinal($id_budget, $solde_final);

            return true;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de l'ajout du détail du budget: " . $e->getMessage());
        }
    }

    // Récupérer un budget par ID
    public function getBudgetById($id_budget)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM budgets WHERE id_budget = :id_budget");
            $stmt->execute(['id_budget' => $id_budget]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération du budget: " . $e->getMessage());
        }
    }

    // Mettre à jour la solde finale d'un budget
    public function updateBudgetSoldeFinal($id_budget, $solde_final)
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE budgets SET solde_final = :solde_final WHERE id_budget = :id_budget"
            );
            $stmt->execute([
                'solde_final' => $solde_final,
                'id_budget' => $id_budget
            ]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la mise à jour de la solde finale: " . $e->getMessage());
        }
    }

    private function getPreviousPeriod($mois, $annee)
    {
        $mois = (int) $mois;
        $annee = (int) $annee;
        if ($mois == 1) {
            return ['mois' => 12, 'annee' => $annee - 1];
        }
        return ['mois' => $mois - 1, 'annee' => $annee];
    }

    public function updateBudgetSoldeDepart($id_budget, $solde_depart)
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE budgets SET solde_depart = :solde_depart WHERE id_budget = :id_budget"
            );
            $stmt->execute([
                'solde_depart' => $solde_depart,
                'id_budget' => $id_budget
            ]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la mise à jour du solde de départ: " . $e->getMessage());
        }
    }

    public function getAllGains($mois = null, $annee = null)
    {
        $query = "
        SELECT 
            db.id_detail,
            b.id_budget,
            d.id_departement,
            d.nom AS nom_departement,
            b.mois,
            b.annee,
            c.id_categorie,
            c.nom AS categorie_gain,
            db.montant,
            db.description,
            b.statut AS statut_budget,
            DATE_FORMAT(CONCAT(b.annee, '-', b.mois, '-01'), '%Y-%m') AS periode
        FROM 
            details_budget db
        JOIN 
            budgets b ON db.id_budget = b.id_budget
        JOIN 
            categories c ON db.id_categorie = c.id_categorie
        JOIN 
            departements d ON b.id_departement = d.id_departement
        WHERE 
            c.type = 'gain'";

        if ($mois && $annee) {
            $query .= " AND b.mois = :mois AND b.annee = :annee";
        }

        $query .= " ORDER BY b.annee DESC, b.mois DESC, d.nom, c.nom";

        $stmt = $this->db->prepare($query);
        if ($mois && $annee) {
            $stmt->bindValue(':mois', $mois, \PDO::PARAM_INT);
            $stmt->bindValue(':annee', $annee, \PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllDepenses($mois = null, $annee = null)
    {
        $query = "
        SELECT 
            db.id_detail,
            b.id_budget,
            d.id_departement,
            d.nom AS nom_departement,
            b.mois,
            b.annee,
            c.id_categorie,
            c.nom AS categorie_depense,
            db.montant,
            db.description,
            b.statut AS statut_budget,
            DATE_FORMAT(CONCAT(b.annee, '-', b.mois, '-01'), '%Y-%m') AS periode
        FROM 
            details_budget db
        JOIN 
            budgets b ON db.id_budget = b.id_budget
        JOIN 
            categories c ON db.id_categorie = c.id_categorie
        JOIN 
            departements d ON b.id_departement = d.id_departement
        WHERE 
            c.type = 'depense'";

        if ($mois && $annee) {
            $query .= " AND b.mois = :mois AND b.annee = :annee";
        }

        $query .= " ORDER BY b.annee DESC, b.mois DESC, d.nom, c.nom";

        $stmt = $this->db->prepare($query);
        if ($mois && $annee) {
            $stmt->bindValue(':mois', $mois, \PDO::PARAM_INT);
            $stmt->bindValue(':annee', $annee, \PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllGainsRealises($mois = null, $annee = null)
    {
        $query = "
    SELECT 
        t.id_transaction,
        t.id_departement,
        d.nom AS nom_departement,
        t.mois,
        t.annee,
        c.id_categorie,
        c.nom AS categorie_gain,
        t.montant,
        t.description,
        DATE_FORMAT(CONCAT(t.annee, '-', t.mois, '-01'), '%Y-%m') AS periode
    FROM 
        transactions t
    JOIN 
        categories c ON t.id_categorie = c.id_categorie
    JOIN 
        departements d ON t.id_departement = d.id_departement
    WHERE 
        c.type = 'gain'";

        if ($mois && $annee) {
            $query .= " AND t.mois = :mois AND t.annee = :annee";
        }

        $query .= " ORDER BY t.annee DESC, t.mois DESC, d.nom, c.nom";

        $stmt = $this->db->prepare($query);
        if ($mois && $annee) {
            $stmt->bindValue(':mois', $mois, \PDO::PARAM_INT);
            $stmt->bindValue(':annee', $annee, \PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllDepensesRealisees($mois = null, $annee = null)
    {
        $query = "
    SELECT 
        t.id_transaction,
        t.id_departement,
        d.nom AS nom_departement,
        t.mois,
        t.annee,
        c.id_categorie,
        c.nom AS categorie_depense,
        t.montant,
        t.description,
        DATE_FORMAT(CONCAT(t.annee, '-', t.mois, '-01'), '%Y-%m') AS periode
    FROM 
        transactions t
    JOIN 
        categories c ON t.id_categorie = c.id_categorie
    JOIN 
        departements d ON t.id_departement = d.id_departement
    WHERE 
        c.type = 'depense'";

        if ($mois && $annee) {
            $query .= " AND t.mois = :mois AND t.annee = :annee";
        }

        $query .= " ORDER BY t.annee DESC, t.mois DESC, d.nom, c.nom";

        $stmt = $this->db->prepare($query);
        if ($mois && $annee) {
            $stmt->bindValue(':mois', $mois, \PDO::PARAM_INT);
            $stmt->bindValue(':annee', $annee, \PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
