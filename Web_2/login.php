<?php
/**
 * STRONA LOGOWANIA
 * LOGIN PAGE
 * 
 * Rola: Wyświetla formularz logowania i przetwarza uwierzytelnianie moderatorów.
 * Role: Displays login form and processes moderator authentication.
 * 
 * Umożliwia moderatorom zalogowanie się do systemu przy użyciu nazwy użytkownika i hasła.
 * Po pomyślnym zalogowaniu przekierowuje na stronę główną z przepisami.
 * Allows moderators to log into the system using username and password.
 * Upon successful login, redirects to the main recipes page.
 */

// Dołączenie wymaganych plików bazy danych i uwierzytelniania
// Include required files for database and authentication
require_once 'config.php';
require_once 'auth.php';

// Inicjalizacja zmiennej komunikatu błędu
// Initialize error message variable
$error = '';

// Przetwarzanie przesłania formularza logowania gdy otrzymano żądanie POST
// Process login form submission when POST request is received
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Użycie filter_input do bezpieczniejszej obsługi danych POST
    // Use filter_input for safer POST data handling
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

    // Nie filtrujemy haseł, ponieważ mogą zawierać znaki specjalne
    // Don't filter passwords as they may contain special characters
    $password = $_POST['password'] ?? '';

    // Walidacja czy oba pola są wypełnione
    // Validate both fields are filled
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } elseif (login($username, $password)) {
        // Logowanie pomyślne, przekierowanie na stronę główną
        // Login successful, redirect to home page
        header('Location: index.php');
        exit;
    } else {
        // Logowanie nie powiodło się, wyświetlenie komunikatu błędu
        // Login failed, show error message
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderator Login</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <div class="container">
            <h1>🍳 RECIPE CORNER</h1>
        </div>
    </header>

    <main class="container">
        <div class="auth-form">
            <h2>Login</h2>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="auth-form-row">
                    <div class="form-group">
                        <label>Username:</label>
                        <input type="text" name="username" required>
                    </div>

                    <div class="form-group">
                        <label>Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                </div>

                <div class="form-group" style="margin-top: -0.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 500;">
                        <input type="checkbox" id="togglePassword" style="width: auto;">
                        Show password
                    </label>
                </div>

                <button type="submit">Login</button>
            </form>

            <p>Don't have an account? <a href="register.php">Register here</a></p>
            <p><a href="index.php">Continue without account</a></p>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 RECIPE CORNER</p>
    </footer>

    <script>
        // Toggle password visibility on login page
        document.getElementById('togglePassword')?.addEventListener('change', function() {
            var pass = document.getElementById('password');
            if (pass) {
                pass.type = this.checked ? 'text' : 'password';
            }
        });
    </script>
</body>

</html>