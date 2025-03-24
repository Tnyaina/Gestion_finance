<?php
// app/controllers/AuthController.php
namespace app\controllers;

use app\models\UtilisateurModel;
use Flight;
use Exception;

class AuthController
{
    private $utilisateurModel;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $db = Flight::db(); 
        if ($db === null) {
            Flight::halt(500, 'Erreur : la connexion à la base de données n\'est pas initialisée.');
        }

        $this->utilisateurModel = new UtilisateurModel($db);
    }

    public function showLogin()
    {
        if (isset($_SESSION['utilisateur'])) {
            // Vérifier si l'utilisateur est un admin et rediriger en conséquence
            if ($this->utilisateurModel->estAdmin($_SESSION['utilisateur']['id_utilisateur'])) {
                Flight::redirect('/admin/dashboard');
            } else {
                Flight::redirect('/dashboard');
            }
            return;
        }

        $data = [
            'error' => $_SESSION['login_error'] ?? null
        ];

        unset($_SESSION['login_error']);
        Flight::render('login.php', $data);
    }

    public function handleLogin()
    {
        $nom_utilisateur = trim(Flight::request()->data->nom_utilisateur);
        $mot_de_passe = trim(Flight::request()->data->mot_de_passe);

        try {
            $utilisateur = $this->utilisateurModel->getUtilisateurConnecte($nom_utilisateur, $mot_de_passe);

            if ($utilisateur) {
                $_SESSION['utilisateur'] = $utilisateur;
                // Rediriger selon le rôle
                if ($this->utilisateurModel->estAdmin($utilisateur['id_utilisateur'])) {
                    Flight::redirect('/admin/dashboard');
                } else {
                    Flight::redirect('/dashboard');
                }
            } else {
                $_SESSION['login_error'] = 'Nom d\'utilisateur ou mot de passe incorrect';
                Flight::redirect('/login');
            }
        } catch (Exception $e) {
            $_SESSION['login_error'] = $e->getMessage();
            Flight::redirect('/login');
        }
    }

    public function showRegister()
    {
        if (isset($_SESSION['utilisateur'])) {
            if ($this->utilisateurModel->estAdmin($_SESSION['utilisateur']['id_utilisateur'])) {
                Flight::redirect('/admin/dashboard');
            } else {
                Flight::redirect('/dashboard');
            }
            return;
        }

        $departements = $this->utilisateurModel->getDepartements();
        $data = [
            'departements' => $departements,
            'error' => $_SESSION['register_error'] ?? null,
            'success' => $_SESSION['register_success'] ?? null
        ];

        unset($_SESSION['register_error'], $_SESSION['register_success']);
        Flight::render('register.php', $data);
    }

    public function handleRegister()
    {
        $nom_utilisateur = trim(Flight::request()->data->nom_utilisateur);
        $mot_de_passe = trim(Flight::request()->data->mot_de_passe);
        $confirm_mot_de_passe = trim(Flight::request()->data->confirm_mot_de_passe);
        $id_departement = Flight::request()->data->id_departement;

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

        if (!empty($errors)) {
            $_SESSION['register_error'] = $errors;
            Flight::redirect('/register');
            return;
        }

        try {
            $id = $this->utilisateurModel->create($nom_utilisateur, $mot_de_passe, $id_departement);
            if ($id) {
                $_SESSION['register_success'] = "Compte créé avec succès. Veuillez vous connecter.";
                Flight::redirect('/login');
            } else {
                $_SESSION['register_error'] = ['Erreur lors de la création du compte'];
                Flight::redirect('/register');
            }
        } catch (Exception $e) {
            $_SESSION['register_error'] = [$e->getMessage()];
            Flight::redirect('/register');
        }
    }

    public function logout()
    {
        session_destroy();
        Flight::redirect('/login');
    }
}