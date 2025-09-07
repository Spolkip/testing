<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}
include("./src/database.php");
include("./src/controller.php");
$controller = new Controller($conn);
$search_results = $controller->searchPlaylists(); // Changed to use the search function
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Κεντρική Σελίδα</title>
    <link rel="stylesheet" href="sidenav.css" />
    <link rel="stylesheet" href="theme.css" />
    <link rel="stylesheet" href="buttons.css" />
    <link rel="stylesheet" href="modal.css" />
    <link rel="stylesheet" href="feed.css" />
    <link rel="stylesheet" href="profile.css" />
    <script src="theme.js"></script>
    <script src="modal.js"></script>
    <script src="render.js"></script>
  </head>
  <body>
    
    <div class="mode-tog"></div>
    <div class="dark-mode-container"><div class="dark-mode"></div></div>
    
    <nav class="sidenav">
      <a href="main.php" class="logo-link"><img class="logo" src="Images/logo.png" alt="logo" /></a>
      <a href="playlists.php" class="logout-button"><span>Οι Λίστες μου</span></a>
      <a href="#" id="logout-btn" class="logout-button"><span>ΑΠΟΣΥΝΔΕΣΗ</span></a>
    </nav>

    <div class="sign-container">
      <a href="#" class="profile-icon"><img src="Images/user-icon.png" alt="User Icon"></a>
    </div>

    <main id="content" class="profile-container">
        <header class="profile-header">
            <h1>Λίστες Περιεχομένου</h1>
        </header>

        <section class="search-section">
            <form action="main.php" method="GET" class="search-form">
                <div class="form-row">
                    <input type="text" name="search_text" placeholder="Αναζήτηση σε τίτλους..." value="<?php echo htmlspecialchars($search_results['search_params']['search_text']); ?>">
                    <input type="text" name="user_query" placeholder="Αναζήτηση χρήστη..." value="<?php echo htmlspecialchars($search_results['search_params']['user_query']); ?>">
                </div>
                <div class="form-row">
                    <label>Ημερομηνία από:</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($search_results['search_params']['date_from']); ?>">
                    <label>έως:</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($search_results['search_params']['date_to']); ?>">
                </div>
                 <div class="form-row">
                    <label>Αποτελέσματα ανά σελίδα:</label>
                    <select name="results_per_page">
                        <option value="10" <?php if ($search_results['results_per_page'] == 10) echo 'selected'; ?>>10</option>
                        <option value="25" <?php if ($search_results['results_per_page'] == 25) echo 'selected'; ?>>25</option>
                    </select>
                    <button type="submit" class="buttons">Αναζήτηση</button>
                </div>
            </form>
        </section>

        <section class="results-section">
            <h2>Αποτελέσματα (<?php echo $search_results['total_results']; ?>)</h2>
            <div class="playlists-grid">
                <?php if (empty($search_results['playlists'])): ?>
                    <p>Δεν βρέθηκαν λίστες. Δοκιμάστε μια διαφορετική αναζήτηση ή δημιουργήστε μια νέα!</p>
                <?php else: ?>
                    <?php foreach ($search_results['playlists'] as $playlist): ?>
                        <a href="view_playlist.php?id=<?php echo $playlist['id']; ?>" class="playlist-card">
                            <h3><?php echo htmlspecialchars($playlist['name']); ?></h3>
                            <p>από <?php echo htmlspecialchars($playlist['username']); ?></p>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($search_results['total_pages'] > 1): ?>
                <div class="pagination-controls">
                    <?php
                        $query_params = $search_results['search_params'];
                        $query_params['results_per_page'] = $search_results['results_per_page'];
                    ?>
                    <?php if ($search_results['page'] > 1): ?>
                        <a href="?page=<?php echo $search_results['page'] - 1; ?>&<?php echo http_build_query($query_params); ?>" class="buttons">Προηγούμενη</a>
                    <?php endif; ?>

                    <span>Σελίδα <?php echo $search_results['page']; ?> από <?php echo $search_results['total_pages']; ?></span>

                    <?php if ($search_results['page'] < $search_results['total_pages']): ?>
                        <a href="?page=<?php echo $search_results['page'] + 1; ?>&<?php echo http_build_query($query_params); ?>" class="buttons">Επόμενη</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
    
  </body>
</html>

