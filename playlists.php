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
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action']) && $_POST['action'] === 'create_playlist') {
            $response = $controller->createPlaylist();
        } elseif (isset($_POST['action']) && $_POST['action'] === 'add_video') {
            $response = $controller->addVideoToPlaylist();
        }
        if (isset($response) && $response['success']) {
            $message = $response['message'];
            $message_type = 'success';
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }
}
$my_playlists = $controller->getMyPlaylists();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Οι Λίστες μου</title>
    <link rel="stylesheet" href="sidenav.css" />
    <link rel="stylesheet" href="theme.css" />
    <link rel="stylesheet" href="buttons.css" />
    <link rel="stylesheet" href="profile.css" />
    <script src="theme.js"></script>
    <script src="api-call.js"></script>
    <script src="render.js"></script>
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

     <div class="sign-container">
      <a href="#" class="profile-icon">
        <img src="Images/user-icon.png" alt="User Icon">
      </a>
    </div>

    <main class="profile-container">
        <header class="profile-header">
            <h1>Οι Λίστες μου</h1>
        </header>

        <?php if ($message): ?>
            <div class="message-box <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <section class="user-details">
            <h2>Δημιουργία Νέας Λίστας</h2>
            <form action="playlists.php" method="POST" class="playlist-form">
                <input type="hidden" name="action" value="create_playlist">
                <div class="label-input">
                    <label for="playlist_name">Όνομα Λίστας</label>
                    <input type="text" name="playlist_name" required>
                </div>
                <button type="submit" class="buttons">Δημιουργία</button>
            </form>
        </section>

        <section class="user-feeds">
            <h2>Προσθήκη Video σε Λίστα</h2>
            <?php if (empty($my_playlists)): ?>
                <p>Πρέπει πρώτα να δημιουργήσετε μια λίστα για να προσθέσετε βίντεο.</p>
            <?php else: ?>
            <form action="playlists.php" method="POST" class="playlist-form">
                <input type="hidden" name="action" value="add_video">
                <div class="label-input">
                    <label for="playlist_id">Επιλογή Λίστας</label>
                    <select name="playlist_id" required>
                        <?php foreach ($my_playlists as $playlist): ?>
                            <option value="<?php echo $playlist['id']; ?>"><?php echo htmlspecialchars($playlist['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="label-input">
                    <label for="video_title">Τίτλος Video</label>
                    <input type="text" name="video_title" required>
                </div>
                <div class="label-input">
                    <label for="video_url">YouTube URL</label>
                    <input type="text" name="video_url" required>
                </div>
                <button type="submit" class="buttons">Προσθήκη</button>
            </form>
            <?php endif; ?>
        </section>
    </main>

</body>
</html>

