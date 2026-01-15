<?php
/**
 * STRONA WYLOGOWANIA
 * LOGOUT PAGE
 * 
 * Rola: Obsługuje proces wylogowania użytkownika.
 * Role: Handles the user logout process.
 * 
 * Niszczy sesję użytkownika, usuwa ciasteczka sesji i przekierowuje na stronę logowania.
 * Destroys user session, removes session cookies, and redirects to login page.
 */

// Dołączenie wymaganych plików konfiguracji i uwierzytelniania
// Include required files for configuration and authentication
require_once 'config.php';
require_once 'auth.php';

// Wywołanie funkcji wylogowania, która niszczy sesję i przekierowuje do logowania
// Call logout function which destroys session and redirects to login
logout();
