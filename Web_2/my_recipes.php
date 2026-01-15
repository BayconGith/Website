<?php
/**
 * STRONA MOICH PRZEPISÓW
 * MY RECIPES PAGE
 * 
 * Rola: Wyświetla listę przepisów utworzonych przez zalogowanego moderatora.
 * Role: Displays a list of recipes created by the logged-in moderator.
 * 
 * Umożliwia moderatorom przeglądanie, edycję i usuwanie swoich własnych przepisów.
 * Wyświetla przepisy w formacie listy z datą utworzenia i opcjami akcji.
 * Allows moderators to view, edit, and delete their own recipes.
 * Displays recipes in list format with creation date and action options.
 */

// Dołączenie wymaganych plików bazy danych, uwierzytelniania i przepisów
// Include required files for database, authentication, and recipes
require_once 'config.php';
require_once 'auth.php';
require_once 'recipes.php';

// Przekierowanie do logowania jeśli użytkownik nie jest uwierzytelniony
// Redirect to login if user is not authenticated
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Obsługa usuwania przepisu jeśli parametry usuwania są obecne
// Handle recipe deletion if delete parameters are present
if (isset($_GET['delete']) && isset($_GET['id'])) {
    // Walidacja ID przepisu z adresu URL
    // Validate recipe ID from URL
    $recipe_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    // Próba usunięcia przepisu jeśli podano prawidłowe ID
    // Attempt to delete recipe if valid ID provided
    if ($recipe_id && deleteRecipe($recipe_id)) {
        // Przekierowanie z komunikatem sukcesu
        // Redirect with success message
        header('Location: my_recipes.php?success=deleted');
        exit;
    }
}

// Pobranie wszystkich przepisów utworzonych przez bieżącego użytkownika
// Fetch all recipes created by the current user
$my_recipes = getMyRecipes();

// Pobranie komunikatu sukcesu z parametru URL jeśli jest obecny
// Get success message from URL parameter if present
$success = filter_input(INPUT_GET, 'success', FILTER_SANITIZE_SPECIAL_CHARS) ?: '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Recipes</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <div class="container">
            <h1>🍳 RECIPE CORNER</h1>
            <nav>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="index.php">View All Recipes</a>
                <a href="add_recipe.php">Add Recipe</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <main class="container">
        <h2>My Recipes</h2>

        <?php if ($success === 'deleted'): ?>
            <div class="success">Recipe deleted successfully!</div>
        <?php endif; ?>

        <?php if ($my_recipes && $my_recipes->num_rows > 0): ?>
            <div class="recipe-list">
                <div class="recipe-list-header">
                    <div>Recipe Title</div>
                    <div>Created Date</div>
                    <div>Actions</div>
                </div>
                <div class="recipe-list-body">
                    <?php while ($recipe = $my_recipes->fetch_assoc()): ?>
                        <div class="recipe-list-item">
                            <div class="recipe-list-info">
                                <h3><?php echo htmlspecialchars($recipe['title']); ?></h3>
                            </div>
                            <div class="recipe-status">
                                <?php echo date('F j, Y', strtotime($recipe['created_at'])); ?>
                            </div>
                            <div class="recipe-list-actions">
                                <a href="recipe_detail.php?id=<?php echo $recipe['id']; ?>" class="btn-detail">View</a>
                                <a href="edit_recipe.php?id=<?php echo $recipe['id']; ?>" class="btn-edit">Edit</a>
                                <a href="my_recipes.php?delete=1&id=<?php echo $recipe['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this recipe?');">Delete</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state-card">
                <div class="empty-emoji">🥄</div>
                <h3>No recipes yet</h3>
                <p>You haven’t published any recipes. Start your first one or browse for inspiration.</p>
                <div class="empty-actions">
                    <a href="add_recipe.php" class="empty-btn primary">Create your first recipe</a>
                    <a href="index.php" class="empty-btn secondary">Browse all recipes</a>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2026 RECIPE CORNER</p>
    </footer>
</body>

</html>