<?php
/**
 * Connexion à la base de données PharmaGest
 * Utilise PDO avec gestion d'erreurs
 */

$host = 'localhost';
$dbname = 'pharmagest';
$username = 'root';
$password = ''; 
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // les erreurs SQL lèvent des exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // les résultats sont des tableaux associatifs
    PDO::ATTR_EMULATE_PREPARES   => false,                   // vraies requêtes préparées (sécurité)
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // En développement, on affiche l'erreur pour diagnostiquer
    // (à masquer en production, voir étape sécurité plus tard)
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}