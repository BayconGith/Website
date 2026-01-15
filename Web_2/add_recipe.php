<?php
/**
 * STRONA DODAWANIA PRZEPISU
 * ADD RECIPE PAGE
 * 
 * Rola: Umożliwia zalogowanym moderatorom dodawanie nowych przepisów kulinarnych do bazy danych.
 * Role: Allows logged-in moderators to add new cooking recipes to the database.
 * 
 * Wyświetla formularz z polami: tytuł, opis, składniki, instrukcje, czas przygotowania,
 * czas gotowania, liczba porcji i URL obrazu. Wymaga uwierzytelnienia.
 * Displays a form with fields: title, description, ingredients, instructions, prep time,
 * cook time, servings, and image URL. Requires authentication.
 */

// Włączenie raportowania błędów do debugowania podczas rozwoju
// Enable error reporting for debugging during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Dołączenie wymaganych plików bazy danych, uwierzytelniania i funkcji przepisów
// Include required files for database, authentication, and recipe functions
require_once 'config.php';
require_once 'auth.php';
require_once 'recipes.php';

// Przekierowanie do logowania jeśli użytkownik nie jest uwierzytelniony
// Redirect to login if user is not authenticated
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Inicjalizacja zmiennych komunikatów
// Initialize message variables
$success = '';
$error = '';

// Przetwarzanie przesłania formularza gdy otrzymano żądanie POST
// Process form submission when POST request is received
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Użycie filter_input do bezpieczniejszej obsługi i sanityzacji danych POST
    // Use filter_input for safer POST data handling and sanitization
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $ingredients = filter_input(INPUT_POST, 'ingredients', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $instructions = filter_input(INPUT_POST, 'instructions', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $prep_time = filter_input(INPUT_POST, 'prep_time', FILTER_VALIDATE_INT) ?? '';
    $cook_time = filter_input(INPUT_POST, 'cook_time', FILTER_VALIDATE_INT) ?? '';
    $servings = filter_input(INPUT_POST, 'servings', FILTER_VALIDATE_INT) ?? '';
    $image_url = filter_input(INPUT_POST, 'image_url', FILTER_SANITIZE_URL) ?? '';

    // Walidacja czy wymagane pola nie są puste
    // Validate required fields are not empty
    if (empty($title) || empty($description) || empty($ingredients) || empty($instructions)) {
        $error = 'Please fill in all required fields';
    } elseif (addRecipe($title, $description, $ingredients, $instructions, $prep_time, $cook_time, $servings, $image_url)) {
        // Przepis dodany pomyślnie, przekierowanie na stronę moich przepisów
        // Recipe added successfully, redirect to my recipes page
        $success = 'Recipe added successfully!';
        // Wyczyszczenie danych formularza po pomyślnym przesłaniu
        // Clear form data after successful submission
        header('Location: my_recipes.php?added=1');
        exit;
    } else {
        // Wystąpił błąd podczas wstawiania przepisu
        // Error occurred during recipe insertion
        $error = 'Error adding recipe';
    }
}



?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Recipe</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <div class="container">
            <h1>🍳 RECIPE CORNER</h1>
            <nav>
                <a href="index.php">View Recipes</a>
                <a href="my_recipes.php">My Recipes</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="recipe-form-container">
            <h2>Add New Recipe</h2>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" class="recipe-form">
                <div>
                    <label>Recipe Title *</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                </div>

                <div>
                    <label>Image URL</label>
                    <input type="url" name="image_url" value="<?php echo htmlspecialchars($_POST['image_url'] ?? ''); ?>">
                </div>

                <div>
                    <label>Description *</label>
                    <textarea name="description" rows="4" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label>Ingredients (one per line) *</label>
                    <textarea name="ingredients" rows="8" required><?php echo htmlspecialchars($_POST['ingredients'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label>Instructions (one per line) *</label>
                    <textarea name="instructions" rows="8" required><?php echo htmlspecialchars($_POST['instructions'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div>
                        <label>Prep Time (minutes)</label>
                        <input type="number" name="prep_time" value="<?php echo htmlspecialchars($_POST['prep_time'] ?? ''); ?>">
                    </div>

                    <div>
                        <label>Cook Time (minutes)</label>
                        <input type="number" name="cook_time" value="<?php echo htmlspecialchars($_POST['cook_time'] ?? ''); ?>">
                    </div>

                    <div>
                        <label>Servings</label>
                        <input type="number" name="servings" value="<?php echo htmlspecialchars($_POST['servings'] ?? ''); ?>">
                    </div>
                </div>

                <button type="submit" class="btn-submit">Add Recipe</button>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 RECIPE CORNER</p>
    </footer>
</body>

</html>