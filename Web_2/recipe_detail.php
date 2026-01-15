<?php
/**
 * STRONA SZCZEGÓŁÓW PRZEPISU
 * RECIPE DETAIL PAGE
 * 
 * Rola: Wyświetla pełne szczegóły pojedynczego przepisu wraz z sekcją komentarzy.
 * Role: Displays full details of a single recipe along with comments section.
 * 
 * Pokazuje wszystkie informacje o przepisie (tytuł, opis, składniki, instrukcje, czasy),
 * umożliwia użytkownikom dodawanie komentarzy z ocenami (1-5 gwiazdek) oraz
 * pozwala autorom przepisu i komentarzy usuwać komentarze.
 * Shows all recipe information (title, description, ingredients, instructions, times),
 * allows users to add comments with ratings (1-5 stars), and
 * enables recipe and comment authors to delete comments.
 */

// Dołączenie wymaganych plików bazy danych, przepisów, komentarzy i uwierzytelniania
// Include required files for database, recipes, comments, and authentication
require_once 'config.php';
require_once 'recipes.php';
require_once 'comments.php';
require_once 'auth.php';

// Walidacja czy ID przepisu jest obecne w adresie URL
// Validate recipe ID is present in URL
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

// Walidacja i sanityzacja ID przepisu z parametru URL
// Validate and sanitize recipe ID from URL parameter
$recipe_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$recipe_id) {
    // Nieprawidłowe ID przepisu, przekierowanie na stronę główną
    // Invalid recipe ID, redirect to home
    header('Location: index.php');
    exit;
}

// Pobranie przepisu z bazy danych
// Fetch recipe from database
$recipe = getRecipeById($recipe_id);
if (!$recipe) {
    // Przepis nie znaleziony, przekierowanie na stronę główną
    // Recipe not found, redirect to home
    header('Location: index.php');
    exit;
}

// Ustalenie czy komentarze powinny być wyświetlone (domyślnie: tak, chyba że hide_comments=yes w URL)
// Determine if comments should be shown (default: yes, unless hide_comments=yes in URL)
$show_comments = !isset($_GET['hide_comments']) || $_GET['hide_comments'] !== 'yes';

// Sprawdzenie czy bieżący użytkownik jest autorem przepisu
// Check if current user is the recipe author
$is_recipe_author = isLoggedIn() && $recipe['moderator_id'] === $_SESSION['moderator_id'];

// Obsługa usuwania komentarza jeśli parametry usuwania są obecne
// Handle comment deletion if delete parameters are present
if (isset($_GET['delete_comment']) && isset($_GET['comment_id'])) {
    // Walidacja ID komentarza z adresu URL
    // Validate comment ID from URL
    $comment_id = filter_input(INPUT_GET, 'comment_id', FILTER_VALIDATE_INT);

    if ($comment_id) {
        // Pobranie komentarza w celu sprawdzenia właściela
        // Fetch the comment to check ownership
        $comment = getCommentById($comment_id);

        if ($comment) {
            // Sprawdzenie czy użytkownik jest właścicielem przepisu (może usuwać dowolny komentarz)
            // Check if user is recipe owner (can delete any comment)
            $is_recipe_owner = isLoggedIn() && $recipe['moderator_id'] === $_SESSION['moderator_id'];

            // Sprawdzenie czy użytkownik jest autorem komentarza (może usuwać własny komentarz)
            // Check if user is comment author (can delete own comment)
            $is_comment_author = isLoggedIn() && $comment['author_name'] === $_SESSION['username'];

            // Usunięcie komentarza jeśli użytkownik ma uprawnienia
            // Delete comment if user has permission
            if ($is_recipe_owner || $is_comment_author) {
                deleteComment($comment_id, $recipe['id']);
                // Przekierowanie aby odświeżyć stronę po usunięciu
                // Redirect to refresh page after deletion
                header("Location: recipe_detail.php?id=" . $recipe['id']);
                exit;
            }
        }
    }
}

// Obsługa przesłania nowego komentarza
// Handle new comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    // Użycie filter_input do bezpieczniejszej obsługi danych POST
    // Use filter_input for safer POST data handling
    $author_name = filter_input(INPUT_POST, 'author_name', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $content = filter_input(INPUT_POST, 'content', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT) ?? 5;

    // Walidacja wymaganych pól i dodanie komentarza
    // Validate required fields and add comment
    if (!empty($author_name) && !empty($content)) {
        addComment($recipe['id'], $author_name, $content, $rating);
        // Przekierowanie aby odświeżyć stronę i pokazać nowy komentarz
        // Redirect to refresh page and show new comment
        header("Location: recipe_detail.php?id=" . $recipe['id']);
        exit;
    }
}

// Pobranie wszystkich komentarzy dla tego przepisu
// Fetch all comments for this recipe
$comments = getCommentsByRecipe($recipe['id']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($recipe['title']); ?> - Recipe</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <div class="container">
            <h1>🍳 RECIPE CORNER</h1>
            <nav>
                <a href="index.php">Back to Recipes</a>
                <?php if (isLoggedIn()): ?>
                    <a href="my_recipes.php">My Recipes</a>
                    <a href="logout.php">Logout</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">
        <article class="recipe-detail">
            <?php if ($recipe['image_url']): ?>
                <img src="<?php echo htmlspecialchars($recipe['image_url']); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>" class="recipe-image">
            <?php endif; ?>

            <h1><?php echo htmlspecialchars($recipe['title']); ?></h1>

            <div class="recipe-meta-detail">
                <p><strong>Author:</strong> <?php echo htmlspecialchars($recipe['username']); ?></p>
                <p><strong>Posted:</strong> <?php echo date('M d, Y', strtotime($recipe['created_at'])); ?></p>
                <?php if ($recipe['prep_time']): ?>
                    <p><strong>Prep Time:</strong> <?php echo $recipe['prep_time']; ?> minutes</p>
                <?php endif; ?>
                <?php if ($recipe['cook_time']): ?>
                    <p><strong>Cook Time:</strong> <?php echo $recipe['cook_time']; ?> minutes</p>
                <?php endif; ?>
                <?php if ($recipe['servings']): ?>
                    <p><strong>Servings:</strong> <?php echo $recipe['servings']; ?></p>
                <?php endif; ?>
            </div>

            <section class="recipe-section">
                <h2>Description</h2>
                <p><?php echo nl2br(htmlspecialchars($recipe['description'])); ?></p>
            </section>

            <section class="recipe-section">
                <h2>Ingredients</h2>
                <ul>
                    <?php
                    $decoded_ingredients = html_entity_decode($recipe['ingredients']);
                    foreach (preg_split('/[\r\n]+/', $decoded_ingredients) as $ingredient):
                    ?>
                        <?php if (trim($ingredient)): ?>
                            <li><?php echo htmlspecialchars(trim($ingredient)); ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="recipe-section">
                <h2>Instructions</h2>
                <ol>
                    <?php
                    $decoded_instructions = html_entity_decode($recipe['instructions']);
                    foreach (preg_split('/[\r\n]+/', $decoded_instructions) as $instruction):
                    ?>
                        <?php if (trim($instruction)): ?>
                            <li><?php echo htmlspecialchars(trim($instruction)); ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </section>
        </article>

        <section class="comments-section">
            <div class="comments-header">
                <h2>Comments</h2>
                <button id="toggleCommentsBtn" class="btn-toggle-comments" type="button">
                    <?php echo $show_comments ? '🔽 Hide Comments' : '🔼 Show Comments'; ?>
                </button>
            </div>

            <div id="commentsContent" style="<?php echo !$show_comments ? 'display: none;' : ''; ?>">
                <?php if ($is_recipe_author): ?>
                    <div class="author-notice">
                        <p>✏️ You are the author of this recipe. You cannot comment on your own recipe, but you can view and delete comments below.</p>
                    </div>
                <?php else: ?>
                    <form method="POST" class="comment-form">
                        <input type="hidden" name="add_comment" value="1">

                        <?php if (isLoggedIn()): ?>
                            <input type="hidden" name="author_name" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">

                            <div class="logged-in-notice">
                                <p>🔒 Commenting as: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> (Moderator)</p>
                            </div>
                        <?php else: ?>
                            <div class="form-group">
                                <label>Your Name:</label>
                                <input type="text" name="author_name" placeholder="Enter your name" required>
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label>Rating:</label>
                            <select name="rating" required>
                                <option value="5">⭐⭐⭐⭐⭐ (5 Stars - Excellent)</option>
                                <option value="4">⭐⭐⭐⭐ (4 Stars - Very Good)</option>
                                <option value="3">⭐⭐⭐ (3 Stars - Good)</option>
                                <option value="2">⭐⭐ (2 Stars - Fair)</option>
                                <option value="1">⭐ (1 Star - Poor)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Your Comment:</label>
                            <textarea name="content" rows="5" placeholder="Share your thoughts about this recipe..." required></textarea>
                        </div>

                        <button type="submit" class="btn-submit-comment">Post Comment</button>
                    </form>
                <?php endif; ?>

                <div class="comments-list">
                    <?php if ($comments && $comments->num_rows > 0): ?>
                        <?php while ($comment = $comments->fetch_assoc()): ?>
                            <?php
                            $can_delete = false;
                            if (isLoggedIn()) {
                                $is_comment_author = $comment['author_name'] === $_SESSION['username'];
                                $is_recipe_owner = $recipe['moderator_id'] === $_SESSION['moderator_id'];
                                $can_delete = $is_comment_author || $is_recipe_owner;
                            }
                            ?>
                            <div class="comment">
                                <div class="comment-header">
                                    <div>
                                        <strong><?php echo htmlspecialchars($comment['author_name']); ?></strong>
                                        <span class="rating"><?php echo str_repeat('⭐', $comment['rating']); ?></span>
                                    </div>
                                    <?php if ($can_delete): ?>
                                        <a href="recipe_detail.php?id=<?php echo $recipe['id']; ?>&delete_comment=1&comment_id=<?php echo $comment['id']; ?>" class="btn-delete-comment" onclick="return confirm('Delete this comment');">✕</a>
                                    <?php endif; ?>
                                </div>
                                <small><?php echo date('M d, Y', strtotime($comment['created_at'])); ?></small>
                                <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="no-comments">No comments yet. Be the first to comment!</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 RECIPE CORNER</p>
    </footer>

    <script>
        // Toggle comments visibility without page reload
        document.getElementById('toggleCommentsBtn').addEventListener('click', function() {
            const commentsContent = document.getElementById('commentsContent');
            const btn = this;
            const isHidden = commentsContent.style.display === 'none';

            if (isHidden) {
                commentsContent.style.display = 'block';
                btn.textContent = '🔽 Hide Comments';
            } else {
                commentsContent.style.display = 'none';
                btn.textContent = '🔼 Show Comments';
            }
        });
    </script>
</body>

</html>