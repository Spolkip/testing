<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}
include("./src/database.php");
include("./src/controller.php");

$controller = new Controller($conn);
$message = '';
$message_type = 'success';

// Χειρισμός υποβολής φόρμας για δημιουργία λίστας ή προσθήκη βίντεο
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        // Καλούμε τη σωστή μέθοδο του controller ανάλογα με την ενέργεια
        $action = $_POST['action'];
        if (method_exists($controller, $action)) {
            $response = $controller->$action();
            $message = $response['message'];
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }
}

// Ανάκτηση δεδομένων για εμφάνιση
$my_playlists = $controller->getMyPlaylists();
$feed_playlists = $controller->getFeedPlaylists();
?>
<!DOCTYPE html>
<html lang="el">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Διαχείριση Λιστών</title>
    <link rel="stylesheet" href="sidenav.css" />
    <link rel="stylesheet" href="theme.css" />
    <link rel="stylesheet" href="buttons.css" />
    <link rel="stylesheet" href="modal.css" />
    <link rel="stylesheet" href="profile.css" />
    <script src="theme.js"></script>
  </head>
  <body>
    
    <div class="mode-tog"></div>
    <div class="dark-mode-container"><div class="dark-mode"></div></div>
    
    <nav class="sidenav">
      <a href="main.php" class="logo-link">
        <img class="logo" src="Images/logo.png" alt="logo" />
      </a>
      <a href="playlists.php" class="logout-button"><span>Οι Λίστες μου</span></a>
      <a href="#" id="logout-btn" class="logout-button"><span>ΑΠΟΣΥΝΔΕΣΗ</span></a>
    </nav>

    <main class="profile-container">
        <header class="profile-header">
            <h1>Διαχείριση Λιστών</h1>
        </header>

        <?php if ($message): ?>
            <div class="message-box <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <section class="user-details">
            <h2>Δημιουργία Νέας Λίστας</h2>
            <form action="playlists.php" method="POST" class="playlist-form">
                <input type="hidden" name="action" value="createPlaylist">
                <div class="label-input">
                    <label for="playlist_name">Όνομα Λίστας:</label>
                    <input type="text" id="playlist_name" name="playlist_name" required>
                </div>
                <button type="submit" class="buttons">Δημιουργία</button>
            </form>
        </section>

        <section class="user-details">
            <h2>Προσθήκη Βίντεο σε Λίστα</h2>
            <form action="playlists.php" method="POST" class="playlist-form">
                <input type="hidden" name="action" value="addVideoToPlaylist">
                 <div class="label-input">
                    <label for="playlist_id">Επιλογή Λίστας:</label>
                    <select id="playlist_id" name="playlist_id" required>
                        <option value="">-- Οι λίστες μου --</option>
                        <?php foreach ($my_playlists as $playlist): ?>
                            <option value="<?php echo $playlist['id']; ?>"><?php echo htmlspecialchars($playlist['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="label-input">
                    <label for="video_title">Τίτλος Βίντεο:</label>
                    <input type="text" id="video_title" name="video_title" required>
                </div>
                <div class="label-input">
                    <label for="video_url">YouTube URL:</label>
                    <input type="url" id="video_url" name="video_url" required placeholder="https://www.youtube.com/watch?v=...">
                </div>
                <button type="submit" class="buttons">Προσθήκη</button>
            </form>
        </section>

        <section class="user-feeds">
            <h2>Οι Λίστες στο Feed μου</h2>
            <div class="playlists-grid">
                <?php if (empty($feed_playlists)): ?>
                    <p>Δεν υπάρχουν λίστες για προβολή.</p>
                <?php else: ?>
                    <?php foreach ($feed_playlists as $playlist): ?>
                        <a href="view_playlist.php?id=<?php echo $playlist['id']; ?>" class="playlist-card">
                            <h3><?php echo htmlspecialchars($playlist['name']); ?></h3>
                            <p>από <?php echo htmlspecialchars($playlist['username']); ?></p>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>

  </body>
</html>
