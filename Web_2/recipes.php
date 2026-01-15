<?php
/**
 * MODUŁ ZARZĄDZANIA PRZEPISAMI
 * RECIPES MANAGEMENT MODULE
 * 
 * Rola: Zawiera wszystkie funkcje CRUD (Create, Read, Update, Delete) dla przepisów kulinarnych.
 * Role: Contains all CRUD (Create, Read, Update, Delete) functions for cooking recipes.
 * 
 * Funkcje obejmują: dodawanie przepisów, pobieranie przepisów z filtrowaniem i sortowaniem,
 * edycję przepisów, usuwanie przepisów oraz pobieranie informacji o moderatorach.
 * Functions include: adding recipes, retrieving recipes with filtering and sorting,
 * editing recipes, deleting recipes, and retrieving moderator information.
 */

require_once 'config.php';

/**
 * Dodaje nowy przepis do bazy danych
 * Adds a new recipe to the database
 * 
 * @param string $title - Tytuł przepisu / The recipe title
 * @param string $description - Krótki opis przepisu / Brief description of the recipe
 * @param string $ingredients - Lista składników (oddzielona nowymi liniami) / List of ingredients (newline separated)
 * @param string $instructions - Instrukcje gotowania (oddzielone nowymi liniami) / Cooking instructions (newline separated)
 * @param int $prep_time - Czas przygotowania w minutach / Preparation time in minutes
 * @param int $cook_time - Czas gotowania w minutach / Cooking time in minutes
 * @param int $servings - Liczba porcji / Number of servings the recipe makes
 * @param string $image_url - URL obrazu przepisu / URL of the recipe image
 * @return bool - Zwraca true jeśli przepis został pomyślnie dodany, false w przeciwnym razie / Returns true if recipe added successfully, false otherwise
 */
function addRecipe($title, $description, $ingredients, $instructions, $prep_time, $cook_time, $servings, $image_url)
{
    // Dostęp do globalnego połączenia z bazą danych
    // Access the global database connection
    global $conn;

    // Upewnienie się, że użytkownik jest zalogowany przed dodaniem przepisu
    // Ensure user is logged in before adding recipe
    if (!isLoggedIn()) {
        return false;
    }

    // Pobranie ID aktualnie zalogowanego moderatora
    // Get the current logged-in moderator's ID
    $moderator_id = $_SESSION['moderator_id'];

    // Escape wszystkich wejść tekstowych aby zapobiec wstrzyknięciu SQL
    // Escape all string inputs to prevent SQL injection
    $title = $conn->real_escape_string($title);
    $description = $conn->real_escape_string($description);
    $ingredients = $conn->real_escape_string($ingredients);
    $instructions = $conn->real_escape_string($instructions);

    // Zapytanie SQL do wstawienia nowego przepisu ze wszystkimi polami
    // SQL query to insert new recipe with all fields
    $sql = "INSERT INTO recipes (moderator_id, title, description, ingredients, instructions, prep_time, cook_time, servings, image_url) 
            VALUES ('$moderator_id', '$title', '$description', '$ingredients', '$instructions', '$prep_time', '$cook_time', '$servings', '$image_url')";

    // Wykonanie zapytania i zwrócenie statusu sukcesu
    // Execute query and return success status
    return $conn->query($sql) !== false;
}

/**
 * Pobiera przepisy z opcjonalnym filtrowaniem i sortowaniem
 * Retrieves recipes with optional filtering and sorting
 * 
 * @param int|null $author_id - Opcjonalnie: filtruj przepisy według konkretnego ID moderatora / Optional: filter recipes by specific moderator ID
 * @param string $sort_by - Pole sortowania: 'created_at', 'title', 'prep_time', 'cook_time', lub 'rating' / Sort field: 'created_at', 'title', 'prep_time', 'cook_time', or 'rating'
 * @param string $order - Kolejność sortowania: 'ASC' lub 'DESC' / Sort order: 'ASC' or 'DESC'
 * @return mysqli_result|false - Zwraca zestaw wyników przepisów z informacją o autorze, lub false w przypadku błędu / Returns result set of recipes with author info, or false on error
 */
function getRecipes($author_id = null, $sort_by = 'created_at', $order = 'DESC')
{
    // Dostęp do globalnego połączenia z bazą danych
    // Access the global database connection
    global $conn;

    // Obsługa sortowania według ocen, które wymaga obliczenia średniej z tabeli komentarzy
    // Handle rating sort which requires calculating average from comments table
    if ($sort_by === 'rating') {
        // Połączenie z komentarzami aby obliczyć średnią ocenę dla każdego przepisu
        // Join with comments to calculate average rating per recipe
        $sql = "SELECT r.*, m.username, AVG(c.rating) as avg_rating FROM recipes r 
                JOIN moderators m ON r.moderator_id = m.id
                LEFT JOIN comments c ON r.id = c.recipe_id";

        // Filtruj według autora jeśli określono
        // Filter by author if specified
        if ($author_id) {
            $author_id = intval($author_id);
            $sql .= " WHERE r.moderator_id = $author_id";
        }

        // Grupuj według przepisu i sortuj według średniej oceny, następnie według daty
        // Group by recipe and sort by average rating, then by date
        $sql .= " GROUP BY r.id ORDER BY avg_rating $order, r.created_at DESC";
    } else {
        // Standardowe zapytanie dla sortowania innych niż oceny
        // Standard query for non-rating sorts
        $sql = "SELECT r.*, m.username FROM recipes r 
                JOIN moderators m ON r.moderator_id = m.id";

        // Filtruj według autora jeśli określono
        // Filter by author if specified
        if ($author_id) {
            $author_id = intval($author_id);
            $sql .= " WHERE r.moderator_id = $author_id";
        }

        // Walidacja pola sortowania względem dozwolonych wartości
        // Validate sort field against allowed values
        $allowed_sorts = ['created_at', 'title', 'prep_time', 'cook_time'];
        if (!in_array($sort_by, $allowed_sorts)) {
            $sort_by = 'created_at';
        }

        // Zastosowanie sortowania
        // Apply sorting
        $sql .= " ORDER BY r.$sort_by $order";
    }

    // Wykonanie zapytania i zwrócenie zestawu wyników
    // Execute query and return result set
    return $conn->query($sql);
}

/**
 * Pobiera pojedynczy przepis po jego ID wraz z informacją o autorze
 * Retrieves a single recipe by its ID with author information
 * 
 * @param int $id - ID przepisu do pobrania / The ID of the recipe to retrieve
 * @return array|null - Zwraca dane przepisu z nazwą użytkownika autora jako tablicę asocjacyjną, lub null jeśli nie znaleziono / Returns recipe data with author username as associative array, or null if not found
 */
function getRecipeById($id)
{
    // Dostęp do globalnego połączenia z bazą danych
    // Access the global database connection
    global $conn;

    // Konwersja ID na liczbę całkowitą aby zapobiec wstrzyknięciu SQL
    // Convert ID to integer to prevent SQL injection
    $id = intval($id);

    // Zapytanie pobierające przepis z nazwą użytkownika autora poprzez JOIN
    // Query to get recipe with author's username via JOIN
    $sql = "SELECT r.*, m.username FROM recipes r 
            JOIN moderators m ON r.moderator_id = m.id 
            WHERE r.id = $id";

    // Wykonanie zapytania i zwrócenie przepisu jako tablicy asocjacyjnej
    // Execute query and return the recipe as an associative array
    $result = $conn->query($sql);
    return $result->fetch_assoc();
}

/**
 * Aktualizuje istniejący przepis nowymi informacjami
 * Pozwala tylko autorowi przepisu edytować własny przepis
 * Updates an existing recipe with new information
 * Only allows the recipe author to edit their own recipe
 * 
 * @param int $recipe_id - ID przepisu do edycji / The ID of the recipe to edit
 * @param string $title - Zaktualizowany tytuł przepisu / Updated recipe title
 * @param string $description - Zaktualizowany opis / Updated description
 * @param string $ingredients - Zaktualizowana lista składników / Updated ingredients list
 * @param string $instructions - Zaktualizowane instrukcje gotowania / Updated cooking instructions
 * @param int $prep_time - Zaktualizowany czas przygotowania w minutach / Updated prep time in minutes
 * @param int $cook_time - Zaktualizowany czas gotowania w minutach / Updated cook time in minutes
 * @param int $servings - Zaktualizowana liczba porcji / Updated number of servings
 * @param string $image_url - Zaktualizowany URL obrazu / Updated image URL
 * @return bool - Zwraca true jeśli aktualizacja się powiedzie, false jeśli brak autoryzacji lub błąd / Returns true if update succeeds, false if unauthorized or error
 */
function editRecipe($recipe_id, $title, $description, $ingredients, $instructions, $prep_time, $cook_time, $servings, $image_url)
{
    // Dostęp do globalnego połączenia z bazą danych
    // Access the global database connection
    global $conn;

    // Upewnienie się, że użytkownik jest zalogowany
    // Ensure user is logged in
    if (!isLoggedIn()) {
        return false;
    }

    // Konwersja recipe_id na liczbę całkowitą dla bezpieczeństwa
    // Convert recipe_id to integer for security
    $recipe_id = intval($recipe_id);
    $moderator_id = $_SESSION['moderator_id'];

    // Escape wszystkich wejść tekstowych aby zapobiec wstrzyknięciu SQL
    // Escape all string inputs to prevent SQL injection
    $title = $conn->real_escape_string($title);
    $description = $conn->real_escape_string($description);
    $ingredients = $conn->real_escape_string($ingredients);
    $instructions = $conn->real_escape_string($instructions);

    // Sprawdzenie czy przepis istnieje
    // Check if recipe exists
    $check_sql = "SELECT moderator_id FROM recipes WHERE id = $recipe_id";
    $check_result = $conn->query($check_sql);

    // Przepis nie znaleziony
    // Recipe not found
    if ($check_result->num_rows !== 1) {
        return false;
    }

    // Weryfikacja czy bieżący użytkownik jest autorem przepisu
    // Verify the current user is the recipe author
    $check_row = $check_result->fetch_assoc();
    if ($check_row['moderator_id'] !== $moderator_id) {
        return false;
    }

    // Zapytanie SQL do aktualizacji przepisu wszystkimi nowymi wartościami
    // SQL query to update recipe with all new values
    $sql = "UPDATE recipes SET title = '$title', description = '$description', ingredients = '$ingredients', instructions = '$instructions', prep_time = '$prep_time', cook_time = '$cook_time', servings = '$servings', image_url = '$image_url' WHERE id = $recipe_id";

    // Wykonanie aktualizacji i zwrócenie statusu sukcesu
    // Execute update and return success status
    return $conn->query($sql) !== false;
}

/**
 * Usuwa przepis z bazy danych
 * Pozwala tylko autorowi przepisu usunąć własny przepis
 * Deletes a recipe from the database
 * Only allows the recipe author to delete their own recipe
 * 
 * @param int $recipe_id - ID przepisu do usunięcia / The ID of the recipe to delete
 * @return bool - Zwraca true jeśli usunięcie się powiedzie, false jeśli brak autoryzacji lub błąd / Returns true if deletion succeeds, false if unauthorized or error
 */
function deleteRecipe($recipe_id)
{
    // Dostęp do globalnego połączenia z bazą danych
    // Access the global database connection
    global $conn;

    // Upewnienie się, że użytkownik jest zalogowany
    // Ensure user is logged in
    if (!isLoggedIn()) {
        return false;
    }

    // Konwersja recipe_id na liczbę całkowitą dla bezpieczeństwa
    // Convert recipe_id to integer for security
    $recipe_id = intval($recipe_id);
    $moderator_id = $_SESSION['moderator_id'];

    // Sprawdzenie czy przepis istnieje i pobranie jego właściciela
    // Check if recipe exists and get its owner
    $check_sql = "SELECT moderator_id FROM recipes WHERE id = $recipe_id";
    $check_result = $conn->query($check_sql);

    // Przepis nie znaleziony
    // Recipe not found
    if ($check_result->num_rows !== 1) {
        return false;
    }

    // Weryfikacja czy bieżący użytkownik jest autorem przepisu
    // Verify the current user is the recipe author
    $check_row = $check_result->fetch_assoc();
    if ($check_row['moderator_id'] !== $moderator_id) {
        return false;
    }

    // Zapytanie SQL do usunięcia przepisu
    // SQL query to delete the recipe
    $sql = "DELETE FROM recipes WHERE id = $recipe_id";

    // Wykonanie usunięcia i zwrócenie statusu sukcesu
    // Execute deletion and return success status
    return $conn->query($sql) !== false;
}

/**
 * Pobiera wszystkich moderatorów posortowanych alfabetycznie według nazwy użytkownika
 * Używane do wypełniania rozwijanej listy filtra autorów
 * Retrieves all moderators sorted alphabetically by username
 * Used for populating author filter dropdown
 * 
 * @return mysqli_result|false - Zwraca zestaw wyników moderatorów z id i nazwą użytkownika, lub false w przypadku błędu / Returns result set of moderators with id and username, or false on error
 */
function getModerators()
{
    // Dostęp do globalnego połączenia z bazą danych
    // Access the global database connection
    global $conn;

    // Zapytanie pobierające wszystkich moderatorów posortowanych alfabetycznie
    // Query to get all moderators sorted alphabetically
    $sql = "SELECT id, username FROM moderators ORDER BY username";

    // Wykonanie zapytania i zwrócenie zestawu wyników
    // Execute query and return result set
    return $conn->query($sql);
}

/**
 * Pobiera wszystkie przepisy utworzone przez aktualnie zalogowanego moderatora
 * Retrieves all recipes created by the currently logged-in moderator
 * 
 * @return mysqli_result|null - Zwraca zestaw wyników przepisów użytkownika posortowanych od najnowszego, lub null jeśli nie zalogowany / Returns result set of user's recipes sorted by newest first, or null if not logged in
 */
function getMyRecipes()
{
    // Dostęp do globalnego połączenia z bazą danych
    // Access the global database connection
    global $conn;

    // Upewnienie się, że użytkownik jest zalogowany
    // Ensure user is logged in
    if (!isLoggedIn()) {
        return null;
    }

    // Pobranie ID moderatora bieżącego użytkownika
    // Get the current user's moderator ID
    $moderator_id = $_SESSION['moderator_id'];

    // Zapytanie pobierające wszystkie przepisy tego moderatora, od najnowszego
    // Query to get all recipes by this moderator, newest first
    $sql = "SELECT * FROM recipes WHERE moderator_id = $moderator_id ORDER BY created_at DESC";

    // Wykonanie zapytania i zwrócenie zestawu wyników
    // Execute query and return result set
    return $conn->query($sql);
}

/**
 * Pobiera adres email konkretnego moderatora
 * Retrieves the email address of a specific moderator
 * 
 * @param int $moderator_id - ID moderatora, dla którego pobrać email / The ID of the moderator to get email for
 * @return string|null - Zwraca ciąg znaków adresu email jeśli znaleziono, null jeśli moderator nie istnieje / Returns email address string if found, null if moderator doesn't exist
 */
function getModeratorEmail($moderator_id)
{
    // Dostęp do globalnego połączenia z bazą danych
    // Access the global database connection
    global $conn;

    // Konwersja moderator_id na liczbę całkowitą dla bezpieczeństwa
    // Convert moderator_id to integer for security
    $moderator_id = intval($moderator_id);

    // Zapytanie pobierające adres email moderatora
    // Query to get moderator's email address
    $sql = "SELECT email FROM moderators WHERE id = $moderator_id";
    $result = $conn->query($sql);

    // Jeśli znaleziono moderatora, zwróć adres email
    // If moderator found, return email address
    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        return $row['email'];
    }

    // Zwrócenie null jeśli moderator nie został znaleziony
    // Return null if moderator not found
    return null;
}
