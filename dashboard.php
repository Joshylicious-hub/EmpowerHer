<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId   = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

// Database connection
$conn = new mysqli("localhost", "u739446465_empowerher_db", "u739446465_Empowerher_db@", "u739446465_empowerher_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// ✅ Fetch completed lessons count
$sql = "SELECT COUNT(*) as completed_count FROM user_lessons WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$completedLessons = $row['completed_count'] ?? 0;
$stmt->close();

// ✅ Fetch community posts count
$sqlPosts = "SELECT COUNT(*) as post_count FROM posts";
$resultPosts = $conn->query($sqlPosts);
$rowPosts = $resultPosts->fetch_assoc();
$communityPosts = $rowPosts['post_count'] ?? 0;

// ✅ Fetch reactions count
$sqlReactions = "
    SELECT COUNT(*) as reaction_count 
    FROM post_reactions pr
    INNER JOIN posts p ON pr.post_id = p.id
    WHERE p.user_id = ?
";
$stmtReactions = $conn->prepare($sqlReactions);
$stmtReactions->bind_param("i", $userId);
$stmtReactions->execute();
$resultReactions = $stmtReactions->get_result();
$rowReactions = $resultReactions->fetch_assoc();
$totalReactions = $rowReactions['reaction_count'] ?? 0;
$stmtReactions->close();

// ✅ Fetch user profile picture
$sqlUser = "SELECT profile_pic FROM users WHERE id = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("i", $userId);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$userData   = $resultUser->fetch_assoc();
$profilePic = $userData['profile_pic'] ?? 'images/profile.jpg';
$stmtUser->close();

$sqlNotif = "
    SELECT r.id, r.post_id, r.user_id, r.user_name, r.reply, r.created_at, r.media, u.profile_pic
    FROM replies r
    INNER JOIN posts p ON r.post_id = p.id
    INNER JOIN users u ON r.user_id = u.id
    WHERE p.user_id = ? AND r.user_id != ? AND r.is_read = 0
    ORDER BY r.created_at DESC
    LIMIT 7
";

$stmtNotif = $conn->prepare($sqlNotif);
$stmtNotif->bind_param("ii", $userId, $userId);
$stmtNotif->execute();
$resultNotif = $stmtNotif->get_result();
$notifications = $resultNotif->fetch_all(MYSQLI_ASSOC);
$stmtNotif->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="icon" type="image/png" sizes="34x34" href="images/logo7.png">
<link rel="icon" type="image/png" sizes="64x64" href="images/logo7.png">
<link rel="icon" type="image/png" sizes="192x192" href="images/logo7.png">

  <title>EmpowerHer Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Raleway:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css"/>
  <style>
    /* Sidebar Notification Styles */
    .notification-bell { position: relative; cursor: pointer; }
    .notif-badge { position: absolute; top: -6px; right: -10px; background: red; color: #fff; border-radius: 50%; font-size: 12px; padding: 2px 6px; }
    .notif-dropdown { display: none; position: absolute; left: 220px; top: 80px; background: #fff; width: 350px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); z-index: 1000; padding: 10px; }
    .notif-dropdown h4 { margin: 5px 0 10px 5px; font-size: 1rem; color: #444; }
    .notif-list { max-height: 300px; overflow-y: auto; }
    .notif-item { display: flex; align-items: flex-start; gap: 10px; padding: 10px; border-radius: 8px; transition: background 0.2s; text-decoration: none; }
    .notif-item:hover { background: #f7f7f7; }
    .notif-item img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
    .notif-text { font-size: 0.9rem; color: #333; flex: 1; }
    .notif-text strong { color: #111; }
    .notif-text small { display: block; color: #777; margin-top: 2px; }
    .notif-footer { display: flex; justify-content: space-between; padding: 8px 10px; border-top: 1px solid #eee; font-size: 0.85rem; }
    .notif-footer a { text-decoration: none; color: #1DA1F2; cursor: pointer; }

    /* Dark mode styles */
    body.dark-mode { background: #121212; color: #eee; }
    body.dark-mode .main-content { background: #1e1e1e; }
    body.dark-mode .notif-dropdown { background: #2a2a2a; color: #eee; }
    body.dark-mode .notif-item:hover { background: #333; }

    /* Profile section default (light mode) */
.profile {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  border-radius: 30px;
  background: #f5f5f5; /* light mode background */
  transition: background 0.3s ease, color 0.3s ease;
}

/* Dark mode background */
body.dark-mode .profile {
  background: #2a2a2a; /* dark mode background */
}

/* Username text */
.username-text {
  color: #000;
  transition: color 0.3s ease;
}

body.dark-mode .username-text {
  color: #fff;
}

/* Verified badge stays blue */
.verified-badge {
  color: #1DA1F2;
}

/* 📱 Mobile Notification Styles */
@media (max-width: 768px) {
  .notif-dropdown {
    left: 50%;                          /* center horizontally */
    top: 50px;                          /* slightly below bell */
    transform: translateX(50%);        /* center alignment */
    width: 90vw;                        /* responsive width */
    max-width: 170px;                  /* cap width */
    border-radius: 16px;
    padding: 12px;
    z-index: 5000;                      /* always on top */
  }

  .notif-dropdown h4 {
    font-size: 0.95rem;
  }

  .notif-list {
    max-height: 60vh;                   /* more flexible height */
    overflow-y: auto;
  }

  .notif-item {
    gap: 8px;
    padding: 8px;
  }

  .notif-item img {
    width: 32px;
    height: 32px;
  }

  .notif-text {
    font-size: 0.85rem;
  }

  .notif-text small {
    font-size: 0.75rem;
  }

  .notif-footer {
    flex-direction: column;
    gap: 8px;
    font-size: 0.8rem;
    text-align: center;
  }

  .notif-footer a {
    font-size: 0.8rem;
  }
}

.content-card ul {
  list-style-type: none; /* removes the bullet */
  padding-left: 0;       /* removes the indent */
}

  </style>
</head>
<body>
  <div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">

      <h2 class="logo">EmpowerHer</h2>
      <nav>
        <ul>
          <li class="active" ><i class="fas fa-home"></i><a href="dashboard.php" style="text-decoration: none; color: white;">Dashboard</a></li>
          <!-- Notifications -->
          <li class="notification-bell" id="notifBell">
            <i class="fas fa-bell"></i> Notifications
            <?php if (!empty($notifications)): ?>
              <span class="notif-badge" id="notifCount"><?php echo count($notifications); ?></span>
            <?php endif; ?>
            <div class="notif-dropdown" id="notifDropdown">
              <h4>Notifications 🔔</h4>
              <div class="notif-list" id="notifList">
                <?php if (!empty($notifications)): ?>
                  <?php foreach ($notifications as $notif): ?>
                    <a href="mark_read.php?reply_id=<?php echo $notif['id']; ?>&post_id=<?php echo $notif['post_id']; ?>" class="notif-item">
                      <img src="<?php echo htmlspecialchars($notif['profile_pic'] ?? 'images/profile.jpg'); ?>" alt="User">
                      <div class="notif-text">
                        <strong><?php echo htmlspecialchars($notif['user_name']); ?></strong>
                        replied: "<?php echo htmlspecialchars(mb_strimwidth($notif['reply'], 0, 50, '...')); ?>"
                        <small><?php echo date("M d, Y h:i A", strtotime($notif['created_at'])); ?></small>
                      </div>
                    </a>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p style="padding:10px; color: black;">No new notifications.</p>
                <?php endif; ?>
              </div>
              <div class="notif-footer">
                <a id="markAllRead">Mark all as read</a>
                <a href="community.php">View all</a>
              </div>
            </div>
          </li>
          <li><i class="fas fa-comments"></i> <a href="chatbot.php" style = "text-decoration: none; color: white;">AI Chatbot</a></li>
          <li><i class="fas fa-book"></i> <a href="modules.php" style = "text-decoration: none; color: white;">Learning Modules</a></li>
          <li><i class="fas fa-users"></i><a href="community.php" style = "text-decoration: none; color: white;">Community</a></li>
         <li><i class="fas fa-hand-holding-heart"></i><a href="feedback.php" style = "text-decoration: none; color: white;">Feedback</a></li>
         


        </ul>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <!-- Topbar -->
      <header class="topbar">
        <button id="sidebarToggle" class="sidebar-toggle">
    <i class="fas fa-bars"></i>
  </button>
        <h1>Welcome, <?php echo htmlspecialchars($userName); ?> 🌸</h1>

        <div class="profile">
          <!-- Dark/Light toggle -->
          <button id="theme-toggle" class="theme-toggle"><i class="fas fa-moon"></i></button>
          
          <!-- Profile Picture -->
        <img src="<?php echo htmlspecialchars($profilePic); ?>?v=<?php echo time(); ?>" 
     alt="Profile" class="profile-img" id="profilePicDisplay">
          <input type="file" id="profilePicInput" accept="image/*" style="display:none;">

        <span class="username-text">
  <?php echo htmlspecialchars($userName); ?>
</span>
<?php if ($userName === 'Joshua Andres' || $userName === 'Issay' || $userName === 'Mia Khalifa'): ?>
  <span class="verified-badge" title="Verified Owner">
    <i class="fas fa-check-circle"></i>
  </span>
<?php endif; ?>


          <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
        
      </header>

      <!-- Stats Section -->
      <section class="stats">
        <div class="stat-card"><i class="fas fa-book-reader"></i><h3>Modules Completed</h3><p><?php echo $completedLessons; ?></p></div>
        <div class="stat-card"><i class="fas fa-users"></i><h3>Community Posts</h3><p><?php echo $communityPosts; ?></p></div>
        <div class="stat-card"><i class="fas fa-hand-holding-heart"></i><h3>Likes/Reactions</h3><p><?php echo $totalReactions; ?></p></div>
      </section>

      <!-- Content Grid -->
      <section class="content-grid">
        <div class="content-card chatbot"><h2>AI Chatbot 💬</h2><p>Need advice? Ask EmpowerHer’s AI chatbot for support.</p><button onclick="window.location.href='chatbot.php'">Open Chat</button></div>
        <div class="content-card modules"><h2>Learning Modules 📚</h2><ul><li>🌸 Self-Care & Wellness</li><li>👩‍👧 Parenting Guidance</li><li>🍼 Maternal & Child Health</li><li>❤️ Healthy Relationships</li></ul><button onclick="window.location.href='modules.php'">Continue Learning</button></div>
        <div class="content-card community"><h2>Community 🤝</h2><p>Connect with fellow mothers. Share your story and inspire others.</p><button onclick="window.location.href='community.php'">Join Discussion</button></div>
       
      </section>
    </main>
  </div>

  <!-- Cropper Modal -->
  <div id="cropModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); justify-content:center; align-items:center; z-index:2000;">
    <div style="background:#fff; padding:20px; border-radius:10px; max-width:500px; width:90%;">
      <h3>Adjust Profile Picture</h3>
      <img id="cropImage" style="max-width:100%; display:block; margin:auto;">
      <div style="margin-top:10px; text-align:center;">
        <button id="cropCancel">Cancel</button>
        <button id="cropSave">Save</button>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
  <script>
    // Profile Picture Cropping
    let cropper;
    const profileDisplay = document.getElementById("profilePicDisplay");
    const profileInput = document.getElementById("profilePicInput");
    const cropModal = document.getElementById("cropModal");
    const cropImage = document.getElementById("cropImage");
    const cropSave = document.getElementById("cropSave");
    const cropCancel = document.getElementById("cropCancel");

    profileDisplay.addEventListener("click", () => profileInput.click());

    profileInput.addEventListener("change", (e) => {
      if (profileInput.files && profileInput.files.length > 0) {
        const reader = new FileReader();
        reader.onload = function(event) {
          cropImage.src = event.target.result;
          cropModal.style.display = "flex";
          cropper = new Cropper(cropImage, { aspectRatio: 1, viewMode: 1 });
        };
        reader.readAsDataURL(profileInput.files[0]);
      }
    });

    cropCancel.addEventListener("click", () => {
      cropModal.style.display = "none";
      cropper.destroy();
    });

    cropSave.addEventListener("click", () => {
      cropper.getCroppedCanvas({ width: 200, height: 200 }).toBlob((blob) => {
        const formData = new FormData();
        formData.append("profile_pic", blob, "profile.jpg");
        fetch("upload_profile.php", { method: "POST", body: formData })
          .then(() => location.reload());
        cropModal.style.display = "none";
        cropper.destroy();
      }, "image/jpeg");
    });

    // Notifications toggle
    const bell = document.getElementById("notifBell");
    const dropdown = document.getElementById("notifDropdown");
    bell.addEventListener("click", () => {
      dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    });
    document.addEventListener("click", (e) => {
      if (!bell.contains(e.target)) dropdown.style.display = "none";
    });

    // Dark Mode Toggle
    const toggleBtn = document.getElementById("theme-toggle");
    const body = document.body;
    if (localStorage.getItem("theme") === "dark") {
      body.classList.add("dark-mode");
      toggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
    }
    toggleBtn.addEventListener("click", () => {
      body.classList.toggle("dark-mode");
      localStorage.setItem("theme", body.classList.contains("dark-mode") ? "dark" : "light");
      toggleBtn.innerHTML = body.classList.contains("dark-mode") ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
    });

    // Mark all notifications as read
    document.getElementById("markAllRead").addEventListener("click", function(e){
      e.preventDefault();
      fetch("mark_all_read.php").then(() => {
        const count = document.getElementById("notifCount");
        if(count) count.style.display = "none";
        const list = document.getElementById("notifList");
        list.innerHTML = "<p style='padding:10px;'>No new notifications.</p>";
      });
    });



    
   const sidebar = document.querySelector('.sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');

sidebarToggle.addEventListener('click', () => {

  if (window.innerWidth <= 768) {
    // Mobile: toggle active class for slide-in/out
    sidebar.classList.toggle('active');
  } else {
    // Desktop: toggle collapsed class for width shrink
    sidebar.classList.toggle('collapsed');
  }

  // Change icon dynamically
  if ((window.innerWidth <= 768 && sidebar.classList.contains('active')) ||
      (window.innerWidth > 768 && !sidebar.classList.contains('collapsed'))) {
    sidebarToggle.innerHTML = '<i class="fa-solid fa-xmark"></i>';
  } else {
    sidebarToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
  }
});





  </script>
</body>
</html>
