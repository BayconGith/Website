<?php
/**
 * PLIK KONFIGURACYJNY BAZY DANYCH
 * DATABASE CONFIGURATION FILE
 * 
 * Rola: Ustanawia połączenie z bazą danych MySQL i inicjalizuje sesję PHP.
 * Role: Establishes MySQL database connection and initializes PHP session.
 * 
 * Ten plik jest wymagany przez wszystkie inne pliki w aplikacji do dostępu do bazy danych.
 * This file is required by all other files in the application to access the database.
 */

// Konfiguracja połączenia z bazą danych
// Database connection configuration
// Nazwa hosta, na którym działa serwer MySQL
// Hostname where MySQL server is running
$hostname = "localhost";

// Nazwa użytkownika bazy danych (domyślny użytkownik XAMPP)
// Database username (default XAMPP user)
$username = "root";

// Hasło do bazy danych (puste dla domyślnej konfiguracji XAMPP)
// Database password (empty for default XAMPP setup)
$password = "";

// Nazwa bazy danych do której się łączymy
// Name of the database to connect to
$dbname = "recipe_site";

// Ustanowienie połączenia z bazą danych MySQL
// Establish connection to MySQL database
$conn = mysqli_connect($hostname, $username, $password, $dbname);

// Sprawdzenie czy połączenie nie powiodło się i zakończenie z komunikatem błędu
// Check if connection failed and terminate with error message
if (mysqli_connect_errno()) {
    die("Connection failed: " . mysqli_connect_error());
}

// Rozpoczęcie sesji PHP do uwierzytelniania użytkownika i zarządzania stanem
// Start PHP session for user authentication and state management
session_start();
