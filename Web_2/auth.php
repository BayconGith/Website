<?php
/**
 * MODUŁ UWIERZYTELNIANIA I AUTORYZACJI
 * AUTHENTICATION AND AUTHORIZATION MODULE
 * 
 * Rola: Obsługuje wszystkie operacje związane z uwierzytelnianiem użytkowników.
 * Role: Handles all user authentication-related operations.
 * 
 * Funkcje obejmują: rejestrację moderatorów, logowanie, wylogowanie,
 * sprawdzanie statusu zalogowania i zarządzanie sesjami z limitami czasu.
 * Functions include: moderator registration, login, logout,
 * checking login status, and managing sessions with timeouts.
 */

require_once 'config.php';

// Upewnienie się, że sesja jest rozpoczęta dla wszystkich operacji uwierzytelniania
// Ensure session is started for all authentication operations
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Rejestruje nowe konto moderatora w bazie danych
 * Registers a new moderator account in the database
 * 
 * @param string $username - Pożądana nazwa użytkownika dla moderatora / The desired username for the moderator
 * @param string $email - Adres email moderatora / The email address of the moderator
 * @param string $password - Hasło w postaci zwykłego tekstu (zostanie zahashowane) / The plain text password (will be hashed)
 * @return bool - Zwraca true jeśli rejestracja się powiedzie, false jeśli nazwa użytkownika/email istnieje / Returns true if registration succeeds, false if username/email exists
 */
function register($username, $email, $password)
{
    // Dostęp do globalnego połączenia z bazą danych
    // Access the global database connection
    global $conn;

    // Escape znaków specjalnych w nazwie użytkownika aby zapobiec wstrzyknięciu SQL
    // Escape special characters in username to prevent SQL injection
    $username = $conn->real_escape_string($username);

    // Escape znaków specjalnych w emailu aby zapobiec wstrzyknięciu SQL
    // Escape special characters in email to prevent SQL injection
    $email = $conn->real_escape_string($email);

    // Hashowanie hasła przy użyciu algorytmu bcrypt dla bezpiecznego przechowywania
    // Hash the password using bcrypt algorithm for secure storage
    $password = password_hash($password, PASSWORD_BCRYPT);

    // Zapytanie SQL do wstawienia nowego moderatora do bazy danych
    // SQL query to insert new moderator into database
    $sql = "INSERT INTO moderators (username, email, password) VALUES ('$username', '$email', '$password')";

    // Wykonanie zapytania i zwrócenie statusu sukcesu
    // Execute the query and return success status
    if ($conn->query($sql)) {
        return true;
    }
    // Zwrócenie false jeśli wstawianie się nie powiedzie (np. zduplikowana nazwa użytkownika/email)
    // Return false if insertion fails (e.g., duplicate username/email)
    return false;
}

/**
 * Uwierzytelnia moderatora i rozpoczyna jego sesję
 * Authenticates a moderator and starts their session
 * 
 * @param string $username - Nazwa użytkownika do uwierzytelnienia / The username to authenticate
 * @param string $password - Hasło w postaci zwykłego tekstu do weryfikacji / The plain text password to verify
 * @return bool - Zwraca true jeśli logowanie się powiedzie, false jeśli dane logowania są nieprawidłowe / Returns true if login succeeds, false if credentials are invalid
 */
function login($username, $password)
{
    // Dostęp do globalnego połączenia z bazą danych
    // Access the global database connection
    global $conn;

    // Escape nazwy użytkownika aby zapobiec wstrzyknięciu SQL
    // Escape username to prevent SQL injection
    $username = $conn->real_escape_string($username);

    // Zapytanie pobierające ID moderatora i zahashowane hasło
    // Query to get moderator ID and hashed password
    $sql = "SELECT id, password FROM moderators WHERE username = '$username'";
    $result = $conn->query($sql);

    // Sprawdzenie czy znaleziono dokładnie jednego użytkownika
    // Check if exactly one user was found
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Weryfikacja podanego hasła względem przechowywanego hasha
        // Verify the provided password against the stored hash
        if (password_verify($password, $row['password'])) {
            // Regeneracja ID sesji aby zapobiec atakom fiksacji sesji
            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            // Przechowanie informacji o użytkowniku w zmiennych sesji
            // Store user information in session variables
            $_SESSION['moderator_id'] = $row['id'];
            $_SESSION['username'] = $username;
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();
            return true;
        }
    }
    // Zwrócenie false jeśli użytkownik nie został znaleziony lub hasło nie pasuje
    // Return false if user not found or password doesn't match
    return false;
}

/**
 * Wylogowuje bieżącego użytkownika przez zniszczenie jego sesji i przekierowanie do logowania
 * Czyści wszystkie dane sesji, usuwa ciasteczko sesji i niszczy sesję
 * Logs out the current user by destroying their session and redirecting to login
 * Clears all session data, removes session cookie, and destroys the session
 * 
 * @return void - Przekierowuje na stronę logowania i kończy wykonywanie skryptu / Redirects to login page and terminates script execution
 */
function logout()
{
    // Wyczyszczenie wszystkich zmiennych sesji aby usunąć przechowywane dane użytkownika
    // Clear all session variables to remove stored user data
    $_SESSION = array();

    // Zniszczenie ciasteczka sesji przez ustawienie czasu wygaśnięcia na czas przeszły
    // Destroy the session cookie by setting expiration to past time
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Zniszczenie sesji po stronie serwera
    // Destroy the session on the server side
    session_destroy();

    // Przekierowanie użytkownika na stronę logowania
    // Redirect user to login page
    header('Location: login.php');
    exit;
}

/**
 * Sprawdza czy użytkownik jest aktualnie zalogowany i sesja jest ważna
 * Implementuje również limit czasu sesji (5 minut nieaktywności)
 * Checks if a user is currently logged in and session is valid
 * Also implements session timeout (5 minutes of inactivity)
 * 
 * @return bool - Zwraca true jeśli użytkownik jest zalogowany z ważną sesją, false w przeciwnym razie / Returns true if user is logged in with valid session, false otherwise
 */
function isLoggedIn()
{
    // Sprawdzenie czy wymagane zmienne sesji istnieją
    // Check if required session variables exist
    if (!isset($_SESSION['moderator_id']) || !isset($_SESSION['logged_in'])) {
        return false;
    }

    // Sprawdzenie limitu czasu sesji (300 sekund = 5 minut)
    // Check for session timeout (300 seconds = 5 minutes)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 300)) {
        // Auto-wylogowanie jeśli sesja wygasła
        // Auto-logout if session has expired
        logout();
        return false;
    }

    // Aktualizacja znacznika czasu ostatniej aktywności aby przedłużyć sesję
    // Update last activity timestamp to extend session
    $_SESSION['last_activity'] = time();

    // Użytkownik jest zalogowany i sesja jest ważna
    // User is logged in and session is valid
    return true;
}
