<?php
// app/config/routes.php
use flight\Engine;
use flight\net\Router;
use app\controllers\AuthController;
use app\controllers\AdminController;
use app\controllers\UserController;

Flight::route('GET /', function() {
    Flight::redirect('/login');
});

Flight::route('GET /login', function() {
    $controller = new AuthController();
    $controller->showLogin();
});

Flight::route('POST /login', function() {
    $controller = new AuthController();
    $controller->handleLogin();
});

Flight::route('GET /register', function() {
    $controller = new AuthController();
    $controller->showRegister();
});

Flight::route('POST /register', function() {
    $controller = new AuthController();
    $controller->handleRegister();
});

Flight::route('GET /logout', function() {
    $controller = new AuthController();
    $controller->logout();
});

// Dashboard admin
Flight::route('GET /admin/dashboard', function() {
    $controller = new AdminController();
    $controller->dashboard();
});

// Gestion des utilisateurs
Flight::route('GET /admin/users', function() {
    $controller = new AdminController();
    $controller->listUtilisateurs();
});

Flight::route('GET /admin/users/add', function() {
    $controller = new AdminController();
    $controller->showAddUtilisateur();
});

Flight::route('POST /admin/users/add', function() {
    $controller = new AdminController();
    $controller->handleAddUtilisateur();
});

Flight::route('GET /admin/users/edit/@id', function($id) {
    $controller = new AdminController();
    $controller->showEditUtilisateur($id);
});

Flight::route('POST /admin/users/edit/@id', function($id) {
    $controller = new AdminController();
    $controller->handleEditUtilisateur($id);
});

Flight::route('GET /admin/users/delete/@id', function($id) {
    $controller = new AdminController();
    $controller->deleteUtilisateur($id);
});

// Gestion des départements
Flight::route('GET /admin/departements', function() {
    $controller = new AdminController();
    $controller->listDepartements();
});

Flight::route('GET /admin/departements/add', function() {
    $controller = new AdminController();
    $controller->showAddDepartement();
});

Flight::route('POST /admin/departements/add', function() {
    $controller = new AdminController();
    $controller->handleAddDepartement();
});

Flight::route('GET /admin/departements/edit/@id', function($id) {
    $controller = new AdminController();
    $controller->showEditDepartement($id);
});

Flight::route('POST /admin/departements/edit/@id', function($id) {
    $controller = new AdminController();
    $controller->handleEditDepartement($id);
});

Flight::route('GET /admin/departements/delete/@id', function($id) {
    $controller = new AdminController();
    $controller->deleteDepartement($id);
});

// Dashboard utilisateur normal
Flight::route('GET /dashboard', function() {
    $controller = new UserController();
    $controller->dashboard();
});

// Profil utilisateur
Flight::route('GET /user/profile', function() {
    $controller = new UserController();
    $controller->profile();
});

Flight::route('POST /user/profile/update', function() {
    $controller = new UserController();
    $controller->updateProfile();
});

// Gestion des finances
Flight::route('GET /user/finances', function() {
    $controller = new UserController();
    $controller->finances();
});

Flight::route('POST /user/finances/add', function() {
    $controller = new UserController();
    $controller->addTransaction();
});

Flight::route('GET /user/finances/edit/@id', function($id) {
    $controller = new UserController();
    $controller->showEditTransaction($id);
});

Flight::route('POST /user/finances/edit/@id', function($id) {
    $controller = new UserController();
    $controller->updateTransaction($id);
});

Flight::route('GET /user/finances/delete/@id', function($id) {
    $controller = new UserController();
    $controller->deleteTransaction($id);
});

// Gestion des budgets (utilisateur)
Flight::route('GET /user/budgets', function() {
    $controller = new UserController();
    $controller->budgets();
});

Flight::route('POST /user/budgets/propose', function() {
    $controller = new UserController();
    $controller->proposeBudget();
});

// Gestion des budgets (admin)
Flight::route('GET /admin/budgets', function() {
    $controller = new AdminController();
    $controller->listBudgets();
});

Flight::route('GET /admin/budgets/approve/@id', function($id) {
    $controller = new AdminController();
    $controller->approveBudget($id);
});

Flight::route('GET /admin/budgets/reject/@id', function($id) {
    $controller = new AdminController();
    $controller->rejectBudget($id);
});

Flight::route('POST /admin/budgets/edit', function() {
    $controller = new AdminController();
    $controller->editBudget();
});

Flight::route('GET /user/export/dashboard/pdf', function() {
    $controller = new UserController();
    $controller->exportDashboardToPDF();
});

Flight::route('GET /user/export/budgets/pdf', function() {
    $controller = new UserController();
    $controller->exportBudgetsToPDF();
});

Flight::route('GET /user/export/finances/pdf', function() {
    $controller = new UserController();
    $controller->exportFinancesToPDF();
});

Flight::route('POST /user/budgets/import', function() {
    $controller = new UserController();
    $controller->importBudgets();
});

Flight::route('POST /user/finances/import', function() {
    $controller = new UserController();
    $controller->importTransactions();
});