<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}
include("./src/database.php");
include("./src/controller.php");
$controller = new Controller($conn);

try {
    $playlist_data = $controller->getPlaylistDetails();
    $info = $playlist_data['info'];
    $videos = $playlist_data['videos'];
    $video_ids = array_map(function($v) { return $v['video_id']; }, $videos);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="el">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($info['name']); ?></title>
    <link rel="stylesheet" href="sidenav.css" />
    <link rel="stylesheet" href="theme.css" />
    <link rel="stylesheet" href="buttons.css" />
    <link rel="stylesheet" href="profile.css" />
    <script src="theme.js"></script>
  </head>
  <body>
    
    <div class="mode-tog"></div>
    <div class="dark-mode-container"><div class="dark-mode"></div></div>

    <nav class="sidenav">
      <a href="index.html" class="logo-link">
        <img class="logo" src="Images/logo.png" alt="logo" />
      </a>
      <a href="playlists.php" class="logout-button"><span>Οι Λίστες μου</span></a>
      <a href="#" id="logout-btn" class="logout-button"><span>ΑΠΟΣΥΝΔΕΣΗ</span></a>
    </nav>

    <main class="profile-container">
        <header class="profile-header">
            <div>
                <h1><?php echo htmlspecialchars($info['name']); ?></h1>
                <p style="margin-top: 5px;">Λίστα από: <?php echo htmlspecialchars($info['username']); ?></p>
            </div>
        </header>

        <?php if (!empty($video_ids)): ?>
            <section class="playlist-player-section">
                <div id="player"></div>
            </section>
        <?php endif; ?>

        <section class="user-feeds">
            <h2>Βίντεο στη Λίστα</h2>
            <?php if (empty($videos)): ?>
                <p>Αυτή η λίστα δεν έχει βίντεο ακόμα.</p>
            <?php else: ?>
                <ul class="video-list">
                    <?php foreach ($videos as $index => $video): ?>
                        <li onclick="playVideo(<?php echo $index; ?>)">
                            <strong><?php echo htmlspecialchars($video['video_title']); ?></strong>
                            <small>(Προστέθηκε από <?php echo htmlspecialchars($video['username']); ?> στις <?php echo date('d/m/Y H:i', strtotime($video['added_at'])); ?>)</small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </main>
    
    <script>
        var tag = document.createElement('script');
        tag.src = "https://www.youtube.com/iframe_api";
        var firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

        var player;
        var videoIds = <?php echo json_encode($video_ids); ?>;
        
        function onYouTubeIframeAPIReady() {
            if (videoIds.length > 0) {
                player = new YT.Player('player', {
                    height: '480',
                    width: '100%',
                    playerVars: {
                        'playsinline': 1
                    },
                    events: {
                        'onReady': onPlayerReady
                    }
                });
            }
        }

        function onPlayerReady(event) {
            // Φορτώνουμε την playlist όταν ο player είναι έτοιμος
            event.target.cuePlaylist(videoIds);
        }

        function playVideo(index) {
            if (player && typeof player.playVideoAt === 'function') {
                player.playVideoAt(index);
                // Κάνουμε scroll στον player για καλύτερη εμπειρία χρήστη
                document.getElementById('player').scrollIntoView({ behavior: 'smooth' });
            }
        }
    </script>
  </body>
</html>
