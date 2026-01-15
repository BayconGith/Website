<?php
/**
 * STRONA GŁÓWNA - LISTA PRZEPISÓW
 * MAIN PAGE - RECIPES LIST
 * 
 * Rola: Główna strona aplikacji wyświetlająca wszystkie przepisy z opcjami filtrowania i sortowania.
 * Role: Main application page displaying all recipes with filtering and sorting options.
 * 
 * Użytkownicy mogą przeglądać przepisy, filtrować według autora, sortować według różnych kryteriów
 * (data, tytuł, czas przygotowania, ocena) i wyświetlać oceny gwiazdkowe dla każdego przepisu.
 * Users can browse recipes, filter by author, sort by various criteria
 * (date, title, prep time, rating), and view star ratings for each recipe.
 */

// Dołączenie wymaganych plików bazy danych, uwierzytelniania, przepisów i komentarzy
// Include required files for database, authentication, recipes, and comments
require_once 'config.php';
require_once 'auth.php';
require_once 'recipes.php';
require_once 'comments.php';

// Użycie filter_input do bezpieczniejszej obsługi danych GET z parametrów URL
// Use filter_input for safer GET data handling from URL parameters
// Pobranie filtra autora (null jeśli nie ustawiony lub nieprawidłowy)
// Get author filter (null if not set or invalid)
$author_filter = filter_input(INPUT_GET, 'author', FILTER_VALIDATE_INT) ?: null;

// Pobranie pola sortowania (domyślnie 'created_at' jeśli nie ustawione)
// Get sort field (default to 'created_at' if not set)
$sort_by = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'created_at';

// Pobranie kolejności sortowania (domyślnie 'DESC' jeśli nie ustawione)
// Get sort order (default to 'DESC' if not set)
$order = filter_input(INPUT_GET, 'order', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'DESC';

// Walidacja wartości sort_by i order względem dozwolonych wartości
// Validate sort_by and order values against allowed values
$allowed_sort = ['created_at', 'title', 'prep_time', 'cook_time', 'rating'];
$allowed_order = ['ASC', 'DESC'];

// Reset do domyślnej wartości jeśli podano nieprawidłowe pole sortowania
// Reset to default if invalid sort field provided
if (!in_array($sort_by, $allowed_sort)) {
    $sort_by = 'created_at';
}

// Reset do domyślnej wartości jeśli podano nieprawidłową kolejność
// Reset to default if invalid order provided
if (!in_array($order, $allowed_order)) {
    $order = 'DESC';
}

// Pobranie przepisów z zastosowanymi filtrami i sortowaniem
// Fetch recipes with applied filters and sorting
$recipes = getRecipes($author_filter, $sort_by, $order);

// Pobranie wszystkich moderatorów do rozwijanej listy filtra autorów
// Get all moderators for the author filter dropdown
$moderators = getModerators();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipe Website</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <div class="container">
            <h1>🍳 RECIPE CORNER</h1>
            <nav>
                <?php if (isLoggedIn()): ?>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="my_recipes.php">My Recipes</a>
                    <a href="add_recipe.php">Add Recipe</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">
        <section class="filters">
            <h2>Filters & Sorting</h2>
            <form method="GET" action="index.php" class="filter-form">
                <div>
                    <label>Filter by Author:</label>
                    <select name="author">
                        <option value="">All Authors</option>
                        <?php while ($mod = $moderators->fetch_assoc()): ?>
                            <option value="<?php echo $mod['id']; ?>" <?php if ($author_filter === $mod['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($mod['username']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label>Sort by:</label>
                    <select name="sort">
                        <option value="created_at" <?php if ($sort_by === 'created_at') echo 'selected'; ?>>Date</option>
                        <option value="title" <?php if ($sort_by === 'title') echo 'selected'; ?>>Title</option>
                        <option value="prep_time" <?php if ($sort_by === 'prep_time') echo 'selected'; ?>>Prep Time</option>
                        <option value="rating" <?php if ($sort_by === 'rating') echo 'selected'; ?>>Rating</option>
                    </select>
                </div>

                <div>
                    <label>Order:</label>
                    <select name="order">
                        <option value="DESC" <?php if ($order === 'DESC') echo 'selected'; ?>>Descending</option>
                        <option value="ASC" <?php if ($order === 'ASC') echo 'selected'; ?>>Ascending</option>
                    </select>
                </div>

                <button type="submit">Apply Filters</button>
            </form>
        </section>

        <section class="recipes-grid">
            <?php while ($recipe = $recipes->fetch_assoc()): ?>
                <div class="recipe-card">
                    <?php if ($recipe['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($recipe['image_url']); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
                    <?php endif; ?>

                    <h3><?php echo htmlspecialchars($recipe['title']); ?></h3>

                    <p class="author">By: <?php echo htmlspecialchars($recipe['username']); ?></p>

                    <div class="recipe-meta">
                        <?php if ($recipe['prep_time']): ?>
                            <span>⏱️ Prep: <?php echo $recipe['prep_time']; ?> min</span>
                        <?php endif; ?>
                        <?php if ($recipe['cook_time']): ?>
                            <span>🍳 Cook: <?php echo $recipe['cook_time']; ?> min</span>
                        <?php endif; ?>
                        <?php if ($recipe['servings']): ?>
                            <span>👥 Servings: <?php echo $recipe['servings']; ?></span>
                        <?php endif; ?>
                    </div>

                    <?php
                    $rating_display = getRecipeRatingDisplay($recipe['id']);
                    ?>
                    <div class="recipe-rating">
                        <?php echo htmlspecialchars($rating_display['display_string']); ?>
                    </div>

                    <a href="recipe_detail.php?id=<?php echo $recipe['id']; ?>" class="btn-detail">View Recipe</a>
                </div>
            <?php endwhile; ?>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 RECIPE CORNER</p>
    </footer>
</body>

</html>