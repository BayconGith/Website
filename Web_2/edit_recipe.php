<?php
/**
 * STRONA EDYCJI PRZEPISU
 * EDIT RECIPE PAGE
 * 
 * Rola: Umożliwia moderatorom edycję ich własnych przepisów.
 * Role: Allows moderators to edit their own recipes.
 * 
 * Tylko autor przepisu może edytować swój przepis. Formularz jest wstępnie wypełniony
 * aktualnymi danymi przepisu i pozwala na aktualizację wszystkich pól.
 * Only the recipe author can edit their recipe. The form is pre-filled with
 * current recipe data and allows updating all fields.
 */

// Dołączenie wymaganych plików bazy danych, uwierzytelniania i przepisów
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

// Walidacja i pobranie ID przepisu z parametru URL
// Validate and get recipe ID from URL parameter
$recipe_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$recipe_id) {
    // Nieprawidłowe lub brakujące ID przepisu, przekierowanie na stronę główną
    // Invalid or missing recipe ID, redirect to home
    header('Location: index.php');
    exit;
}

// Pobranie przepisu z bazy danych
// Fetch the recipe from database
$recipe = getRecipeById($recipe_id);

// Sprawdzenie czy przepis istnieje i należy do bieżącego użytkownika
// Check if recipe exists and belongs to current user
if (!$recipe || $recipe['moderator_id'] !== $_SESSION['moderator_id']) {
    // Przepis nie znaleziony lub użytkownik nie jest jego właścicielem, przekierowanie na stronę główną
    // Recipe not found or user doesn't own it, redirect to home
    header('Location: index.php');
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
    } elseif (editRecipe($recipe_id, $title, $description, $ingredients, $instructions, $prep_time, $cook_time, $servings, $image_url)) {
        // Przepis zaktualizowany pomyślnie
        // Recipe updated successfully
        $success = 'Recipe updated successfully!';
        // Przeładowanie danych przepisu aby pokazać zaktualizowane wartości
        // Reload recipe data to show updated values
        $recipe = getRecipeById($recipe_id);
    } else {
        // Wystąpił błąd podczas aktualizacji przepisu
        // Error occurred during recipe update
        $error = 'Error updating recipe';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Recipe</title>
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
            <h2>Edit Recipe</h2>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" class="recipe-form">
                <div>
                    <label>Recipe Title *</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($recipe['title']); ?>" required>
                </div>

                <div>
                    <label>Image URL</label>
                    <input type="url" name="image_url" value="<?php echo htmlspecialchars($recipe['image_url']); ?>">
                </div>

                <div>
                    <label>Description *</label>
                    <textarea name="description" rows="4" required><?php echo htmlspecialchars($recipe['description']); ?></textarea>
                </div>

                <div>
                    <label>Ingredients (one per line) *</label>
                    <textarea name="ingredients" rows="8" required><?php echo htmlspecialchars($recipe['ingredients']); ?></textarea>
                </div>

                <div>
                    <label>Instructions (one per line) *</label>
                    <textarea name="instructions" rows="8" required><?php echo htmlspecialchars($recipe['instructions']); ?></textarea>
                </div>

                <div class="form-row">
                    <div>
                        <label>Prep Time (minutes)</label>
                        <input type="number" name="prep_time" value="<?php echo htmlspecialchars($recipe['prep_time']); ?>">
                    </div>

                    <div>
                        <label>Cook Time (minutes)</label>
                        <input type="number" name="cook_time" value="<?php echo htmlspecialchars($recipe['cook_time']); ?>">
                    </div>

                    <div>
                        <label>Servings</label>
                        <input type="number" name="servings" value="<?php echo htmlspecialchars($recipe['servings']); ?>">
                    </div>
                </div>

                <button type="submit" class="btn-submit">Update Recipe</button>
            </form>

            <div style="margin-top: 1.5rem;">
                <a href="my_recipes.php" class="btn-secondary">← Back to My Recipes</a>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 RECIPE CORNER</p>
    </footer>
</body>

</html>