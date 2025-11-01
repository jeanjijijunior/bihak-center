<?php
/**
 * User Detail Page
 */

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Bihak Center</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../assets/images/favimg.png">
    <link rel="stylesheet" type="text/css" href="../assets/css/header_new.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;700&family=Poppins:wght@300;600&display=swap" rel="stylesheet">
</head>

<body>
    <?php include '../includes/header_new.php'; ?>


    <?php
$conn = new mysqli('localhost', 'root', '', 'bihak');
$id = intval($_GET['id']);
$result = $conn->query("SELECT * FROM usagers WHERE id=$id");
$user = $result->fetch_assoc();

function getMediaEmbed($url) {
    if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
        if (preg_match('/(youtu\.be\/|v=)([\w-]+)/', $url, $matches)) {
            return '<iframe width="300" height="200" src="https://www.youtube.com/embed/' . $matches[2] . '" frameborder="0" allowfullscreen></iframe>';
        }
    } elseif (strpos($url, 'facebook.com') !== false) {
        return '<iframe src="https://www.facebook.com/plugins/video.php?href=' . urlencode($url) . '&show_text=0&width=300" width="300" height="200" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowfullscreen></iframe>';
    } elseif (strpos($url, 'instagram.com') !== false) {
        return '<blockquote class="instagram-media" data-instgrm-permalink="' . $url . '" data-instgrm-version="12"></blockquote>
        <script async src="//www.instagram.com/embed.js"></script>';
    } else {
        return '<a href="' . htmlspecialchars($url) . '" target="_blank">View media</a>';
    }
    return '';
}

echo '<h2>' . htmlspecialchars($user['name']) . '</h2>';
echo '<p><strong>Age:</strong> ' . $user['age'] . '</p>';
echo '<p><strong>Description:</strong> ' . htmlspecialchars($user['description']) . '</p>';
echo '<p><strong>Category:</strong> ' . htmlspecialchars($user['category']) . '</p>';
echo '<p><strong>Request:</strong> ' . htmlspecialchars($user['request']) . '</p>';
echo '<p><strong>Contacts:</strong> ' . htmlspecialchars($user['contacts']) . '</p>';
if (!empty($user['video_link'])) {
    echo '<p><strong>Media Preview:</strong><br>' . getMediaEmbed($user['video_link']) . '</p>';
}
?>

   
  </div>
</section>

<footer>
  <div class="footer-container">
      <div class="footer-section">
          <h3>Discover Our Programs</h3>
          <ul>
              <li><a href="#">Academic & Professional Orientation</a></li>
              <li><a href="#">Project Development Coaching</a></li>
              <li><a href="#">Financial Support</a></li>
              <li><a href="#">Technology for Development</a></li>
          </ul>
      </div>

      <div class="about-us">
        <h3>About us</h3>
        <ul>
            <li><a href="#">Our Vision</a></li>
            <li><a href="#">Our Mission</a></li>
            <li><a href="#">Our Motivation</a></li>
        </ul>
    </div>

      <div class="social-links">
          <h3>Follow Us</h3>
          <ul>
              <li><a href="#"><img src="images/facebk.png" alt="Facebook"><span>Bihak Center</span></a></li>
              <li><a href="#"><img src="images/image.png" alt="Instagram"><span>Bihak Center</span></a></li>
              <li><a href="#"><img src="images/xx.webp" alt="Twitter"><span>@bihak_center</span></a></li>
          </ul>
      </div>
  </div>

  <div class="footer-bottom">
      <p>© 2025 Bihak Center | All Rights Reserved</p>
  </div>
</footer>

<button onclick="topFunction()" id="myBtn">↑</button>
<script>
  let mybutton = document.getElementById("myBtn");
  window.onscroll = function() {scrollFunction()};
  function scrollFunction() {
    mybutton.style.display = (document.documentElement.scrollTop > 30) ? "block" : "none";
  }
  function topFunction() {
    document.documentElement.scrollTop = 0;
  }
</script>

</body>
</html>