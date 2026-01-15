<?php
/**
 * MODUŁ ZARZĄDZANIA KOMENTARZAMI
 * COMMENTS MANAGEMENT MODULE
 * 
 * Rola: Zawiera funkcje do zarządzania komentarzami i ocenami przepisów.
 * Role: Contains functions for managing comments and ratings for recipes.
 * 
 * Funkcje obejmują: dodawanie komentarzy, pobieranie komentarzy, usuwanie komentarzy,
 * obliczanie średnich ocen i generowanie wyświetlania ocen z gwiazdkami.
 * Functions include: adding comments, retrieving comments, deleting comments,
 * calculating average ratings, and generating star rating displays.
 */

require_once 'config.php';

/**
 * Dodaje nowy komentarz do przepisu
 * Adds a new comment to a recipe
 * 
 * @param int $recipe_id - ID przepisu, do którego dodawany jest komentarz / The ID of the recipe being commented on
 * @param string $author_name - Nazwa osoby piszącej komentarz / Name of the person writing the comment
 * @param string $content - Treść komentarza / The comment text content
 * @param int $rating - Wartość oceny (1-5 gwiazdek) / Rating value (1-5 stars)
 * @return bool - Zwraca true jeśli komentarz został pomyślnie dodany, false w przeciwnym razie / Returns true if comment was successfully added, false otherwise
 */
function addComment($recipe_id, $author_name, $content, $rating)
{
    // Dostęp do globalnego połączenia z bazą danych
    // Access the global database connection
    global $conn;

    // Konwersja recipe_id na liczbę całkowitą aby zapobiec wstrzyknięciu SQL
    // Convert recipe_id to integer to prevent SQL injection
    $recipe_id = intval($recipe_id);

    // Escape znaków specjalnych w nazwie autora aby zapobiec wstrzyknięciu SQL
    // Escape special characters in author name to prevent SQL injection
    $author_name = $conn->real_escape_string($author_name);

    // Escape znaków specjalnych w treści komentarza aby zapobiec wstrzyknięciu SQL
    // Escape special characters in comment content to prevent SQL injection
    $content = $conn->real_escape_string($content);

    // Konwersja oceny na liczbę całkowitą dla bezpieczeństwa
    // Convert rating to integer for safety
    $rating = intval($rating);

    // Walidacja czy ocena mieści się w akceptowalnym zakresie (1-5 gwiazdek)
    // Jeśli nieprawidłowa, ustaw domyślnie maksymalną ocenę 5
    // Validate rating is within acceptable range (1-5 stars)
    // If invalid, default to maximum rating of 5
    if ($rating < 1 || $rating > 5) {
        $rating = 5;
    }

    // Przygotowanie zapytania SQL INSERT do dodania komentarza do bazy danych
    // Prepare SQL INSERT query to add comment to database
    $sql = "INSERT INTO comments (recipe_id, author_name, content, rating) 
            VALUES ('$recipe_id', '$author_name', '$content', '$rating')";

    // Wykonanie zapytania i zwrócenie statusu sukcesu
    // Zwraca true jeśli wstawianie się powiedzie, false jeśli się nie uda
    // Execute the query and return success status
    // Returns true if insertion succeeds, false if it fails
    return $conn->query($sql) !== false;
}

/**
 * Pobiera wszystkie komentarze dla konkretnego przepisu
 * Retrieves all comments for a specific recipe
 * 
 * @param int $recipe_id - ID przepisu, dla którego pobieramy komentarze / The ID of the recipe to get comments for
 * @return mysqli_result|false - Zwraca zestaw wyników komentarzy uporządkowanych od najnowszego, lub false w przypadku błędu / Returns result set of comments ordered by newest first, or false on error
 */
function getCommentsByRecipe($recipe_id)
{
    // Dostęp do globalnego połączenia z bazą danych
    // Access the global database connection
    global $conn;

    // Konwersja recipe_id na liczbę całkowitą aby zapobiec wstrzyknięciu SQL
    // Convert recipe_id to integer to prevent SQL injection
    $recipe_id = intval($recipe_id);

    // Zapytanie pobierające wszystkie komentarze dla tego przepisu, sortowane od najnowszego
    // Query to get all comments for this recipe, sorted by newest first
    $sql = "SELECT * FROM comments WHERE recipe_id = $recipe_id ORDER BY created_at DESC";

    // Wykonanie zapytania i zwrócenie zestawu wyników
    // Execute query and return the result set
    return $conn->query($sql);
}

/**
 * Pobiera konkretny komentarz po jego ID
 * Retrieves a specific comment by its ID
 * 
 * @param int $comment_id - ID komentarza do pobrania / The ID of the comment to retrieve
 * @return array|null - Zwraca dane komentarza jako tablicę asocjacyjną jeśli znaleziono, null w przeciwnym razie / Returns comment data as associative array if found, null otherwise
 */
function getCommentById($comment_id)
{
    // Dostęp do globalnego połączenia z bazą danych
    // Access the global database connection
    global $conn;

    // Konwersja comment_id na liczbę całkowitą dla bezpieczeństwa
    // Convert comment_id to integer for security
    $comment_id = intval($comment_id);

    // Zapytanie pobierające konkretny komentarz po ID
    // Query to get the specific comment by ID
    $sql = "SELECT * FROM comments WHERE id = $comment_id";
    $result = $conn->query($sql);

    // Sprawdzenie czy znaleziono dokładnie jeden komentarz
    // Check if exactly one comment was found
    if ($result && $result->num_rows === 1) {
        // Zwrócenie komentarza jako tablicy asocjacyjnej
        // Return the comment as an associative array
        return $result->fetch_assoc();
    }
    // Zwrócenie null jeśli komentarz nie został znaleziony
    // Return null if comment not found
    return null;
}

/**
 * Usuwa komentarz z przepisu
 * Deletes a comment from a recipe
 * 
 * @param int $comment_id - ID komentarza do usunięcia / The ID of the comment to delete
 * @param int $recipe_id - ID przepisu (dla dodatkowej walidacji) / The ID of the recipe (for additional validation)
 * @return bool - Zwraca true jeśli usunięcie się powiedzie, false w przeciwnym razie / Returns true if deletion succeeds, false otherwise
 */
function deleteComment($comment_id, $recipe_id)
{
    // Dostęp do globalnego połączenia z bazą danych
    // Access the global database connection
    global $conn;

    // Konwersja obu ID na liczby całkowite aby zapobiec wstrzyknięciu SQL
    // Convert both IDs to integers to prevent SQL injection
    $comment_id = intval($comment_id);
    $recipe_id = intval($recipe_id);

    // Zapytanie usuwające z oboma ID komentarza i przepisu dla bezpieczeństwa
    // Delete query with both comment ID and recipe ID for security
    $sql = "DELETE FROM comments WHERE id = $comment_id AND recipe_id = $recipe_id";

    // Wykonanie usunięcia i zwrócenie statusu sukcesu
    // Execute deletion and return success status
    return $conn->query($sql) !== false;
}

/**
 * Oblicza średnią ocenę i całkowitą liczbę komentarzy dla przepisu
 * Calculates average rating and total comment count for a recipe
 * 
 * @param int $recipe_id - ID przepisu, dla którego obliczamy oceny / The ID of the recipe to calculate ratings for
 * @return array - Zwraca tablicę asocjacyjną z kluczami 'average_rating' i 'total_comments' / Returns associative array with 'average_rating' and 'total_comments' keys
 */
function getAverageRating($recipe_id)
{
    // Dostęp do globalnego połączenia z bazą danych
    // Access the global database connection
    global $conn;

    // Konwersja recipe_id na liczbę całkowitą dla bezpieczeństwa
    // Convert recipe_id to integer for security
    $recipe_id = intval($recipe_id);

    // Zapytanie obliczające średnią ocenę i liczące całkowitą liczbę komentarzy
    // Query to calculate average rating and count total comments
    $sql = "SELECT AVG(rating) as average_rating, COUNT(*) as total_comments FROM comments WHERE recipe_id = $recipe_id";

    // Wykonanie zapytania
    // Execute the query
    $result = $conn->query($sql);

    // Jeśli znaleziono wynik, zwróć dane
    // If result found, return the data
    if ($result && $result->num_rows === 1) {
        return $result->fetch_assoc();
    }

    // Zwrócenie domyślnych wartości jeśli nie ma komentarzy
    // Return default values if no comments exist
    return array('average_rating' => 0, 'total_comments' => 0);
}

/**
 * Generuje sformatowane wyświetlanie oceny dla przepisu z gwiazdkami i tekstem
 * Generates a formatted rating display for a recipe with stars and text
 * 
 * @param int $recipe_id - ID przepisu, dla którego wyświetlić ocenę / The ID of the recipe to display rating for
 * @return array - Zwraca tablicę z komponentami wyświetlania oceny (stars, average, total, display_string) / Returns array with rating display components (stars, average, total, display_string)
 */
function getRecipeRatingDisplay($recipe_id)
{
    // Pobranie średniej oceny i całkowitej liczby komentarzy
    // Get the average rating and total comments count
    $rating_info = getAverageRating($recipe_id);

    // Zaokrąglenie średniej oceny do 1 miejsca po przecinku dla wyświetlania
    // Round average rating to 1 decimal place for display
    $avg_rating = round($rating_info['average_rating'], 1);
    $total_comments = $rating_info['total_comments'];

    // Budowanie ciągu wyświetlania gwiazdek (wypełnione i puste gwiazdki)
    // Build star display string (filled and empty stars)
    $star_display = '';
    for ($i = 0; $i < 5; $i++) {
        // Dodanie wypełnionej gwiazdki jeśli w zakresie oceny floor, w przeciwnym razie pusta gwiazdka
        // Add filled star if within the rating floor, otherwise empty star
        if ($i < floor($rating_info['average_rating'])) {
            $star_display .= '⭐';
        } else {
            $star_display .= '☆';
        }
    }

    // Użycie liczby pojedynczej 'person' lub mnogiej 'people' w zależności od liczby komentarzy
    // Use singular 'person' or plural 'people' based on comment count
    $people_text = $total_comments == 1 ? 'person' : 'people';

    // Zwrócenie wszystkich komponentów wyświetlania oceny
    // Return all rating display components
    return array(
        'stars' => $star_display,
        'average' => $avg_rating,
        'total' => $total_comments,
        'people_text' => $people_text,
        'display_string' => $total_comments > 0 ? "$star_display $avg_rating ({$total_comments} {$people_text})" : "No ratings yet"
    );
}
