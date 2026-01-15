<?php
/**
 * STRONA REJESTRACJI
 * REGISTRATION PAGE
 * 
 * Rola: Wyświetla formularz rejestracji i przetwarza tworzenie nowych kont moderatorów.
 * Role: Displays registration form and processes creation of new moderator accounts.
 * 
 * Umożliwia nowym użytkownikom zarejestrowanie się jako moderatorzy z walidacją
 * nazwy użytkownika, emaila, hasła i zgodności haseł.
 * Allows new users to register as moderators with validation of
 * username, email, password, and password confirmation.
 */

// Dołączenie wymaganych plików bazy danych i uwierzytelniania
// Include required files for database and authentication
require_once 'config.php';
require_once 'auth.php';

// Inicjalizacja zmiennych komunikatów i wartości formularza
// Initialize message and form value variables
$error = '';
$success = '';
$username_value = '';
$email_value = '';
$password_value = '';
$confirm_value = '';

// Przetwarzanie przesłania formularza rejestracji gdy otrzymano żądanie POST
// Process registration form submission when POST request is received
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Użycie filter_input do bezpieczniejszej obsługi danych POST
    // Use filter_input for safer POST data handling
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '';

    // Nie filtrujemy haseł, ponieważ mogą zawierać znaki specjalne
    // Don't filter passwords as they may contain special characters
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    // Zachowanie wartości formularza gdy walidacja się nie powiedzie dla wygody użytkownika
    // Preserve form values when validation fails for user convenience
    $username_value = $username;
    $email_value = $email;
    $password_value = $password;
    $confirm_value = $confirm;

    // Walidacja czy wszystkie wymagane pola są wypełnione
    // Validate all required fields are filled
    if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Walidacja formatu adresu email
        // Validate email format
        $error = 'Invalid email address';
    } elseif ($password !== $confirm) {
        // Sprawdzenie czy hasła są zgodne
        // Check if passwords match
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 4) {
        // Wymuszenie minimalnej długości hasła
        // Enforce minimum password length
        $error = 'Password must be at least 4 characters';
    } elseif (register($username, $email, $password)) {
        // Rejestracja pomyślna
        // Registration successful
        $success = 'Registration successful! <a href="login.php">Login here</a>';
    } else {
        // Rejestracja nie powiodła się (prawdopodobnie zduplikowana nazwa użytkownika/email)
        // Registration failed (likely duplicate username/email)
        $error = 'Username or email already exists';
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Moderator</title>
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
            <h2>Register as Moderator</h2>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="auth-form-row">
                    <div class="form-group">
                        <label>Username:</label>
                        <input type="text" name="username" required value="<?php echo htmlspecialchars($username_value); ?>">
                    </div>

                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($email_value); ?>">
                    </div>
                </div>

                <div class="auth-form-row">
                    <div class="form-group">
                        <label>Password:</label>
                        <input type="password" id="password" name="password" required value="<?php echo htmlspecialchars($password_value); ?>">
                    </div>

                    <div class="form-group">
                        <label>Confirm Password:</label>
                        <input type="password" id="confirm_password" name="confirm" required value="<?php echo htmlspecialchars($confirm_value); ?>">
                    </div>
                </div>

                <div class="form-group" style="margin-top: -0.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 500;">
                        <input type="checkbox" id="togglePassword" style="width: auto;">
                        Show passwords
                    </label>
                </div>

                <button type="submit">Register</button>
            </form>

            <p>Already have an account? <a href="login.php">Login here</a></p>
            <p><a href="index.php">Continue without account</a></p>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 RECIPE CORNER</p>
    </footer>

    <script>
        // Toggle both password fields visibility
        document.getElementById('togglePassword')?.addEventListener('change', function() {
            var pass = document.getElementById('password');
            var confirm = document.getElementById('confirm_password');
            var type = this.checked ? 'text' : 'password';
            if (pass) pass.type = type;
            if (confirm) confirm.type = type;
        });
    </script>
</body>

</html>