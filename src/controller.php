<?php
class Controller
{
    private $requestMethod;

    private $connection;

    function __construct($conn) {
        $this->connection = $conn;
    }

    public function login()
    {
        $username = $this->validate("username", true);
        $password = $this->validate("password", true);
        $remember_me = $this->validate("remember-me");

        $stmt = $this->connection->prepare("SELECT * FROM user_data WHERE username = ? AND BINARY(password) = ?");
        if (!$stmt) {
            throw new Exception($this->connection->error);
        }
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];

            if ($remember_me) {
                setcookie("username", $row['username'], time() + (86400 * 30), "/");
            }
            
            return ['redirect' => 'main.php'];
            
        } else {
            throw new Exception("User not found.");
        }
    }

    function register() {
        $first_name = $this->validate("firstname", true);
        $last_name = $this->validate("lastname", true);
        $username= $this->validate("username", true);
        $email = $this->validate("email", true);
        $password = $this->validate("password", true);

        $sql = "INSERT INTO user_data (first_name, last_name, username, email, password)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = mysqli_stmt_init($this->connection);

        if (!mysqli_stmt_prepare($stmt, $sql)) {
            throw new Exception(mysqli_error($this->connection));
        }

        mysqli_stmt_bind_param($stmt, "sssss",
                            $first_name,
                            $last_name,
                            $username,
                            $email,
                            $password);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception($this->connection->error);
        }
 
        return ['closeModal' => 'true'];
    }

    public function getUser() {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Not logged in.");
        }

        $id = $_SESSION['user_id'];
        $stmt = $this->connection->prepare("SELECT first_name, last_name, username, email FROM user_data WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return $row;
        } else {
            throw new Exception("User not found.");
        }
    }

    public function edit() {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Not logged in.");
        }
    
        $id = $_SESSION['user_id'];
        $first_name = $this->validate("firstname", true);
        $last_name  = $this->validate("lastname", true);
        $username   = $this->validate("username", true);
        $email      = $this->validate("email", true);
        $password   = $this->validate("password"); // Password is now optional
    
        // Check if username is already taken by ANOTHER user
        $stmt = $this->connection->prepare("SELECT id FROM user_data WHERE username = ? AND id != ?");
        if (!$stmt) { throw new Exception($this->connection->error); }
        $stmt->bind_param("si", $username, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Το όνομα χρήστη υπάρχει ήδη.");
        }
    
        // Check if email is already taken by ANOTHER user
        $stmt = $this->connection->prepare("SELECT id FROM user_data WHERE email = ? AND id != ?");
        if (!$stmt) { throw new Exception($this->connection->error); }
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Το email χρησιμοποιείται ήδη.");
        }
    
        if (!empty($password)) {
            // If password is provided, update it along with other fields
            $sql = "UPDATE user_data 
                    SET first_name = ?, last_name = ?, username = ?, email = ?, password = ?
                    WHERE id = ?";
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) { throw new Exception($this->connection->error); }
            $stmt->bind_param("sssssi", $first_name, $last_name, $username, $email, $password, $id);
        } else {
            // If password is empty, don't update it
            $sql = "UPDATE user_data 
                    SET first_name = ?, last_name = ?, username = ?, email = ?
                    WHERE id = ?";
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) { throw new Exception($this->connection->error); }
            $stmt->bind_param("ssssi", $first_name, $last_name, $username, $email, $id);
        }
    
        if (!$stmt->execute()) {
            throw new Exception($this->connection->error);
        }
        
        // Return a success response with the updated data
        return [
            "success" => true,
            "userData" => [
                "first_name" => $first_name,
                "last_name" => $last_name,
                "username" => $username,
                "email" => $email
            ]
        ];
    }
    
    // Creates a new playlist for the logged-in user.
    public function createPlaylist() {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Δεν είστε συνδεδεμένος.");
        }
        $user_id = $_SESSION['user_id'];
        $playlist_name = $this->validate("playlist_name", true);

        $sql = "INSERT INTO playlists (user_id, name) VALUES (?, ?)";
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) { throw new Exception($this->connection->error); }
        $stmt->bind_param("is", $user_id, $playlist_name);
        if (!$stmt->execute()) {
            throw new Exception($this->connection->error);
        }
        return ['success' => true, 'message' => 'Η λίστα δημιουργήθηκε με επιτυχία.'];
    }

    // Adds a YouTube video to an existing playlist.
    public function addVideoToPlaylist() {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Δεν είστε συνδεδεμένος.");
        }
        $user_id = $_SESSION['user_id'];
        $playlist_id = $this->validate("playlist_id", true);
        $video_title = $this->validate("video_title", true);
        $video_url = $this->validate("video_url", true);

        // Extracts the video ID from the YouTube URL.
        preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/", $video_url, $matches);
        $video_id = $matches[1] ?? null;

        if (!$video_id) {
            throw new Exception("Μη έγκυρο YouTube URL. Παρακαλώ χρησιμοποιήστε τη μορφή https://www.youtube.com/watch?v=...");
        }

        $sql = "INSERT INTO playlist_videos (playlist_id, user_id, video_title, video_id) VALUES (?, ?, ?, ?)";
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) { throw new Exception($this->connection->error); }
        $stmt->bind_param("iiss", $playlist_id, $user_id, $video_title, $video_id);

        if (!$stmt->execute()) {
            throw new Exception($this->connection->error);
        }
        return ['success' => true, 'message' => 'Το βίντεο προστέθηκε με επιτυχία.'];
    }

    // Retrieves all playlists created by the current user.
    public function getMyPlaylists() {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Δεν είστε συνδεδεμένος.");
        }
        $user_id = $_SESSION['user_id'];
        $stmt = $this->connection->prepare("SELECT id, name FROM playlists WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $playlists = [];
        while ($row = $result->fetch_assoc()) {
            $playlists[] = $row;
        }
        return $playlists;
    }
    
    // Edits the name of a specific playlist.
    public function editPlaylist() {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Not logged in.");
        }
        $user_id = $_SESSION['user_id'];
        $playlist_id = $this->validate("playlist_id", true);
        $new_name = $this->validate("new_playlist_name", true);

        $sql = "UPDATE playlists SET name = ? WHERE id = ? AND user_id = ?";
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) { throw new Exception($this->connection->error); }
        $stmt->bind_param("sii", $new_name, $playlist_id, $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Database update failed: " . $this->connection->error);
        }
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Playlist not found or you don't have permission to edit it.");
        }

        return ['success' => true, 'message' => 'Playlist updated successfully.'];
    }

    // Deletes a playlist and all its associated videos.
    public function deletePlaylist() {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Not logged in.");
        }
        $user_id = $_SESSION['user_id'];
        $playlist_id = $this->validate("playlist_id", true);

        // First, delete associated videos from playlist_videos
        $sql_videos = "DELETE FROM playlist_videos WHERE playlist_id = ? AND playlist_id IN (SELECT id FROM playlists WHERE user_id = ?)";
        $stmt_videos = $this->connection->prepare($sql_videos);
        if (!$stmt_videos) { throw new Exception($this->connection->error); }
        $stmt_videos->bind_param("ii", $playlist_id, $user_id);
        if (!$stmt_videos->execute()) {
            throw new Exception("Failed to delete videos from playlist: " . $this->connection->error);
        }

        // Then, delete the playlist itself
        $sql_playlist = "DELETE FROM playlists WHERE id = ? AND user_id = ?";
        $stmt_playlist = $this->connection->prepare($sql_playlist);
        if (!$stmt_playlist) { throw new Exception($this->connection->error); }
        $stmt_playlist->bind_param("ii", $playlist_id, $user_id);

        if (!$stmt_playlist->execute()) {
            throw new Exception("Failed to delete playlist: " . $this->connection->error);
        }
        
        if ($stmt_playlist->affected_rows === 0) {
            throw new Exception("Playlist not found or you don't have permission to delete it.");
        }

        return ['success' => true, 'message' => 'Playlist deleted successfully.'];
    }


    // Retrieves playlists for the user's feed (their own and from users they follow).
    public function getFeedPlaylists() {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Δεν είστε συνδεδεμένος.");
        }
        $user_id = $_SESSION['user_id'];
        
        $sql = "
            SELECT p.id, p.name, u.username 
            FROM playlists p
            JOIN user_data u ON p.user_id = u.id
            WHERE p.user_id = ? 
            OR p.user_id IN (SELECT following_id FROM user_follows WHERE follower_id = ?)
            ORDER BY p.created_at DESC
        ";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $playlists = [];
        while ($row = $result->fetch_assoc()) {
            $playlists[] = $row;
        }
        return $playlists;
    }

    // Retrieves the details and videos for a specific playlist.
    public function getPlaylistDetails() {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Δεν είστε συνδεδεμένος.");
        }
        
        if (!isset($_GET['id'])) {
            throw new Exception("Δεν δόθηκε ID λίστας.");
        }
        $playlist_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if (!$playlist_id) {
            throw new Exception("Μη έγκυρο ID λίστας.");
        }

        $stmt = $this->connection->prepare("SELECT p.name, u.username FROM playlists p JOIN user_data u ON p.user_id = u.id WHERE p.id = ?");
        $stmt->bind_param("i", $playlist_id);
        $stmt->execute();
        $playlist_info = $stmt->get_result()->fetch_assoc();
        if (!$playlist_info) {
            throw new Exception("Η λίστα δεν βρέθηκε.");
        }

        $stmt = $this->connection->prepare("
            SELECT pv.video_title, pv.video_id, pv.added_at, u.username 
            FROM playlist_videos pv
            JOIN user_data u ON pv.user_id = u.id
            WHERE pv.playlist_id = ? 
            ORDER BY pv.added_at ASC
        ");
        $stmt->bind_param("i", $playlist_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $videos = [];
        while ($row = $result->fetch_assoc()) {
            $videos[] = $row;
        }

        return ['info' => $playlist_info, 'videos' => $videos];
    }
    
    // Retrieves all playlists from all users in the database.
    public function getAllPlaylists() {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Δεν είστε συνδεδεμένος.");
        }
        
        $sql = "
            SELECT p.id, p.name, u.username 
            FROM playlists p
            JOIN user_data u ON p.user_id = u.id
            ORDER BY p.created_at DESC
        ";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $playlists = [];
        while ($row = $result->fetch_assoc()) {
            $playlists[] = $row;
        }
        return $playlists;
    }
    
    // Handles searching for playlists based on various criteria and pagination.
    public function searchPlaylists() {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Δεν είστε συνδεδεμένος.");
        }

        $search_text = filter_input(INPUT_GET, 'search_text', FILTER_SANITIZE_STRING) ?: '';
        $date_from = filter_input(INPUT_GET, 'date_from', FILTER_SANITIZE_STRING) ?: '';
        $date_to = filter_input(INPUT_GET, 'date_to', FILTER_SANITIZE_STRING) ?: '';
        $user_query = filter_input(INPUT_GET, 'user_query', FILTER_SANITIZE_STRING) ?: '';
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
        $results_per_page = filter_input(INPUT_GET, 'results_per_page', FILTER_VALIDATE_INT) ?: 10;
        if (!in_array($results_per_page, [10, 25])) {
            $results_per_page = 10;
        }
        $offset = ($page - 1) * $results_per_page;

        $params = [];
        $param_types = '';
        $base_sql = "
            FROM playlists p
            JOIN user_data u ON p.user_id = u.id
            LEFT JOIN playlist_videos pv ON p.id = pv.playlist_id
        ";
        $where_clauses = [];

        if (!empty($search_text)) {
            $where_clauses[] = "(p.name LIKE ? OR pv.video_title LIKE ?)";
            $search_text_like = '%' . $search_text . '%';
            array_push($params, $search_text_like, $search_text_like);
            $param_types .= 'ss';
        }
        if (!empty($date_from)) {
            $where_clauses[] = "p.created_at >= ?";
            $params[] = $date_from;
            $param_types .= 's';
        }
        if (!empty($date_to)) {
            $where_clauses[] = "p.created_at <= ?";
            $params[] = $date_to . ' 23:59:59';
            $param_types .= 's';
        }
        if (!empty($user_query)) {
            $where_clauses[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
            $user_query_like = '%' . $user_query . '%';
            array_push($params, $user_query_like, $user_query_like, $user_query_like, $user_query_like);
            $param_types .= 'ssss';
        }

        $where_sql = count($where_clauses) > 0 ? " WHERE " . implode(' AND ', $where_clauses) : '';

        $count_sql = "SELECT COUNT(DISTINCT p.id) as total " . $base_sql . $where_sql;
        $stmt = $this->connection->prepare($count_sql);
        if (!empty($param_types)) {
            $stmt->bind_param($param_types, ...$params);
        }
        $stmt->execute();
        $total_results = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $total_pages = ceil($total_results / $results_per_page);

        $results_sql = "
            SELECT DISTINCT p.id, p.name, u.username
            " . $base_sql . $where_sql . "
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ";
        array_push($params, $results_per_page, $offset);
        $param_types .= 'ii';

        $stmt = $this->connection->prepare($results_sql);
         if (!empty($param_types)) {
            $stmt->bind_param($param_types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $playlists = [];
        while ($row = $result->fetch_assoc()) {
            $playlists[] = $row;
        }

        return [
            'playlists' => $playlists,
            'page' => $page,
            'total_pages' => (int)$total_pages,
            'total_results' => (int)$total_results,
            'results_per_page' => $results_per_page,
            'search_params' => [
                'search_text' => $search_text,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'user_query' => $user_query,
            ]
        ];
    }

    public function logout() {
        session_unset();
        session_destroy();
        return ['redirect' => 'index.html'];
    }

    function validate($input, $mandatory=false) {
        if ($mandatory == true && empty($_POST[$input])) {
            throw new Exception('Όλα τα πεδία είναι υποχρεωτικά!');
        }

        return htmlspecialchars(stripcslashes($_POST[$input] ?? ''));
    }

    public function getRequestMethod() {
        return $this->requestMethod;
    }

    public function setRequestMethod(string $methodName) {
        if ($methodName === '') {
            throw new Exception('RequestMethod cannot be empty.');
        }

        $this->requestMethod = $methodName;
    }

    public function render($file){
        if (file_exists($file)) {
            ob_start();
            echo include($file);
            ob_flush();
            ob_end_clean();
        }
    }
}

