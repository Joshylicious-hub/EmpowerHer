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
  <title>EmpowerHer Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Raleway:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css"/>
  <style>
/* Reset */
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Raleway', sans-serif; }

/* Theme Variables */
:root {
  --bg-main: linear-gradient(135deg, #fff5f7, #ffe6f0);
  --bg-card: linear-gradient(135deg, #fff, #ffe9f2);
  --bg-sidebar: linear-gradient(180deg, #ff6b6b, #ff8ea3);
  --text-main: #333;
  --text-heading: #ff4e6d;
  --text-light: #555;
  --card-shadow: rgba(0,0,0,0.08);
}
body.dark-mode {
  --bg-main: linear-gradient(135deg, #1a1a1a, #262626);
  --bg-card: linear-gradient(135deg, #2a2a2a, #333);
  --bg-sidebar: linear-gradient(180deg, #111, #222);
  --text-main: #eee;
  --text-heading: #ff9a9e;
  --text-light: #bbb;
  --card-shadow: rgba(0,0,0,0.5);
}

body {
  background: var(--bg-main);
  color: var(--text-main);
  display: flex;
  height: 100vh;
  overflow: hidden;
  transition: background 0.4s, color 0.4s;
}

/* Layout */
.dashboard { display: flex; width: 100%; }

/* Sidebar */
.sidebar {
  background: var(--bg-sidebar);
  width: 250px;
  padding: 30px 20px;
  color: #fff;
  display: flex;
  flex-direction: column;
  border-top-right-radius: 25px;
  border-bottom-right-radius: 25px;
  box-shadow: 6px 0 25px rgba(0,0,0,0.15);
  position: relative;
  z-index: 2;
  transition: background 0.4s;
}

.sidebar .logo {
  font-family: 'Playfair Display', serif;
  font-size: 2rem;
  margin-bottom: 50px;
  text-align: center;
  letter-spacing: 1px;
}

.sidebar nav ul {
  list-style: none;
}

.sidebar nav ul li {
  margin: 20px 0;
  font-size: 1.1rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 12px;
  transition: background 0.3s, transform 0.2s;
}

.sidebar nav ul li.active,
.sidebar nav ul li:hover {
  background: rgba(255,255,255,0.25);
  transform: translateX(6px);
}

/* Sidebar toggle button */
.sidebar-toggle {
    background: linear-gradient(135deg, #ff6b81, #ff9a9e); /* Pink gradient */
    border: none;
    color: white;
    font-size: 1.2rem;
    padding: 10px 14px;
    border-radius: 8px; /* circular button */
    cursor: pointer;
    margin-right: 15px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* Hover effect - makes it pop */
.sidebar-toggle:hover {
    background: linear-gradient(135deg, #ff4d6d, #ff7b8d);
    transform: scale(1.1);
    box-shadow: 0 6px 12px rgba(0,0,0,0.25);
}

/* Collapsed sidebar style */
.sidebar.collapsed {
    width: 0;
    padding: 0;
    overflow: hidden;
    border-radius: 0;
}

/* Make main container take full width when sidebar is hidden */
.sidebar.collapsed ~ .chat-container,
.sidebar.collapsed ~ .main-content {
    flex: 1;
    width: 100%;
}


/* Main Content */
.main-content {
  flex: 1;
  padding: 40px 50px;
  overflow-y: auto;
  background: var(--bg-main);
  border-top-left-radius: 40px;
  border-bottom-left-radius: 40px;
  box-shadow: inset 0 0 50px rgba(0,0,0,0.05);
}

/* Topbar */
.topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 50px; }
.topbar h1 { font-family: 'Playfair Display', serif; font-size: 2.4rem; color: var(--text-heading); position: relative; }
.topbar h1::after { content: ""; width: 60px; height: 4px; background: var(--text-heading); position: absolute; left: 0; bottom: -8px; border-radius: 2px; }

/* Profile */
.profile { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 50px; background: #fff; transition: background 0.3s; }
body.dark-mode .profile { background: #2a2a2a; }
.username-text { color: #000; }
body.dark-mode .username-text { color: #fff; }
.profile img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #ddd; }
.verified-badge { color: #1DA1F2; }

/* Notifications */
.notification-bell { position: relative; cursor: pointer; }
.notif-badge { position: absolute; top: -6px; right: -10px; background: red; color: #fff; border-radius: 50%; font-size: 12px; padding: 2px 6px; }
.notif-dropdown { display: none; position: absolute; left: 220px; top: 80px; background: #fff; width: 350px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); z-index: 1000; padding: 10px; }
.notif-list { max-height: 300px; overflow-y: auto; }
.notif-item { display: flex; align-items: flex-start; gap: 10px; padding: 10px; border-radius: 8px; transition: background 0.2s; text-decoration: none; color: #333; }
.notif-item:hover { background: #f7f7f7; }
.notif-item img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
body.dark-mode .notif-dropdown { background: #2a2a2a; color: #eee; }
body.dark-mode .notif-item:hover { background: #333; }

/* Buttons */
.theme-toggle { background: none; border: none; font-size: 1.6rem; cursor: pointer; color: var(--text-heading);  margin-left: 5px; }
.logout-btn { background: #ff4e6d; color: #fff; padding: 8px 11px; border-radius: 25px; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; margin-left: 0px;  }
.logout-btn:hover { background: #e63950; }


/* Feedback Section */
.feedback-section {
  margin-top: 50px;
  padding: 20px 0 60px;
  border-top: 2px solid rgba(0,0,0,0.05);
}
.feedback-section .form-title {
  font-size: 2rem;
  font-family: 'Playfair Display', serif;
  color: var(--text-heading);
  margin-bottom: 10px;
}
.feedback-section .form-subtitle {
  font-size: 1.05rem;
  color: var(--text-light);
  margin-bottom: 40px;
}

.feedback-form {
  display: flex;
  flex-direction: column;
  gap: 35px; /* 🔹 more spacing between questions */
  max-width: 950px;
}

/* Labels */
.form-label {
  font-weight: 600;
  margin-bottom: 12px; /* 🔹 more breathing space */
  color: var(--text-main);
  font-size: 1rem;
}

/* Rating */
.rating {
  display: flex;
  gap: 18px;
}
.rating input {
  display: none;
}
.rating label {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  background: var(--bg-card);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.3s, color 0.3s, transform 0.2s;
  box-shadow: 0 2px 5px var(--card-shadow);
}
.rating label:hover {
  transform: scale(1.05);
}
.rating input:checked + label {
  background: var(--text-heading);
  color: #fff;
}

/* Options */
.options-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit,minmax(250px,1fr));
  gap: 14px;
}
.options-grid label,
.options-inline label {
  cursor: pointer;
  font-size: 0.95rem;
  color: var(--text-light);
}
.options-inline {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
}

/* Textareas */
textarea {
  width: 100%;
  border: 1px solid #ccc;
  border-radius: 12px;
  padding: 14px;
  font-size: 1rem;
  resize: none;
  transition: border 0.3s, box-shadow 0.3s;
}
textarea:focus {
  border-color: var(--text-heading);
  box-shadow: 0 0 6px rgba(255,78,109,0.3);
  outline: none;
}

/* Submit Button */
.submit-wrap {
  text-align: right;
}
.submit-btn {
  background: linear-gradient(90deg, #ff6b6b, #ff8ea3);
  border: none;
  padding: 14px 35px;
  border-radius: 35px;
  font-size: 1rem;
  color: #fff;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s, background 0.3s;
}
.submit-btn:hover {
  transform: translateY(-2px);
  background: linear-gradient(90deg, #ff4e6d, #ff6b8b);
}

.notification-bell { position: relative; cursor: pointer; }
.notif-badge { position: absolute; top: -6px; right: -10px; background: red; color: #fff; border-radius: 50%; font-size: 12px; padding: 2px 6px; }
.notif-dropdown { display: none; position: absolute; left: 220px; top: 80px; background: #fff; width: 350px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); z-index: 1000; padding: 10px; }

/* Notifications */
.notifications {
  position: relative;
  margin-right: 20px;
}

.notif-icon {
  position: relative;
  font-size: 1.6rem;
  cursor: pointer;
  color: var(--text-heading);
}

.notif-icon #notif-count {
  position: absolute;
  top: -6px;
  right: -8px;
  background: #ff4e6d;
  color: #fff;
  font-size: 0.75rem;
  font-weight: bold;
  border-radius: 50%;
  padding: 2px 6px;
}

.notif-dropdown {
  display: none;
  position: absolute;
  right: 0;
  top: 35px;
  background: var(--bg-card);
  color: var(--text-main);
  width: 280px;
  max-height: 350px;
  overflow-y: auto;
  box-shadow: 0 8px 20px var(--card-shadow);
  border-radius: 12px;
  z-index: 1000;
}

.notif-dropdown ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.notif-dropdown li {
  padding: 12px 16px;
  border-bottom: 1px solid rgba(0,0,0,0.08);
  font-size: 0.95rem;
  transition: background 0.2s;
}

.notif-dropdown li:hover {
  background: rgba(0,0,0,0.05);
}

#mark-all {
  display: block;
  text-align: center;
  padding: 12px;
  font-weight: bold;
  color: #ff4e6d;
  cursor: pointer;
  border-top: 1px solid rgba(0,0,0,0.08);
  transition: background 0.2s;
}

#mark-all:hover {
  background: rgba(255, 78, 109, 0.1);
}

/* Dark mode for notifications */
body.dark-mode .notif-dropdown {
  background: var(--bg-card);
  color: var(--text-main);
}

body.dark-mode .notif-dropdown li {
  border-bottom: 1px solid rgba(255,255,255,0.08);
}

.notification-bell {
  position: relative;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 1.1rem;
}

/* Red badge counter */
.notif-badge {
  position: absolute;
  top: -6px;
  right: -10px;
  background: red;
  color: #fff;
  border-radius: 50%;
  font-size: 12px;
  padding: 2px 6px;
  font-weight: bold;
}

/* Dropdown box */
.notif-dropdown {
  display: none;
  position: absolute;
  left: 220px; /* adjust depending on sidebar */
  top: 80px;
  background: #fff;
  width: 350px;
  border-radius: 10px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.15);
  z-index: 1000;
  padding: 10px;
}

/* Dropdown header */
.notif-dropdown h4 {
  margin: 5px 0 10px 5px;
  font-size: 1rem;
  color: #444;
}

/* Scrollable list */
.notif-list {
  max-height: 300px;
  overflow-y: auto;
}

/* Individual notification item */
.notif-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 10px;
  border-radius: 8px;
  transition: background 0.2s;
  text-decoration: none;
}

.notif-item:hover {
  background: #f7f7f7;
}

/* Avatar inside notifications */
.notif-item img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

/* Notification text */
.notif-text {
  font-size: 0.9rem;
  color: #333;
  flex: 1;
}

.notif-text strong {
  color: #111;
}

.notif-text small {
  display: block;
  color: #777;
  margin-top: 2px;
  font-size: 0.8rem;
}

/* Unread style */
.notif-unread {
  background: rgba(255, 78, 109, 0.08);
  font-weight: bold;
}

/* Footer links */
.notif-footer {
  display: flex;
  justify-content: space-between;
  padding: 8px 10px;
  border-top: 1px solid #eee;
  font-size: 0.85rem;
}

.notif-footer a {
  text-decoration: none;
  color: #1DA1F2;
  cursor: pointer;
}

/* Dark mode adaptation */
body.dark-mode .notif-dropdown {
  background: #2a2a2a;
  color: #eee;
}

body.dark-mode .notif-item:hover {
  background: #333;
}

body.dark-mode .notif-text {
  color: #ddd;
}

body.dark-mode .notif-text small {
  color: #aaa;
}

body.dark-mode .notif-footer {
  border-top: 1px solid #444;
}


/* ================== Mobile Responsive Fix ================== */
@media (max-width: 768px) {
  /* Shift the heading to the right */
  .topbar h1 {
   flex: none;              /* don't stretch it */
  font-size: 25px;
  margin: 0;               /* remove big right margin */
  white-space: nowrap;     /* keep text in one line */
     margin-left: 20px;
     margin-top: 50px;
     margin-bottom: 20px;
  }

  


  /* Sidebar */
  .sidebar {
    position: fixed;
    top: 0;
    left: -250px; /* hidden by default */
    height: 100%;
    z-index: 1000;
    transition: left 0.3s ease;
  }

  .sidebar.active {
    left: 0; /* slide in */
  }

  .sidebar-toggle {
    position: absolute;
    top: 15px;
    left: 15px;
    z-index: 1100;
  }

  /* Main Content */
  .main-content {
    width: 100%;
    height: 100%;
    padding: 20px;
    border-radius: 0; /* remove rounded corners for full width */
    box-shadow: none;
  }

  /* Topbar */
  .topbar {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }

  .topbar h1 {
    font-size: 1.6rem;
  }

  /* Profile */
  .profile {
    width: 100%;    
    display: flex;               /* use flexbox */
  justify-content: center;     /* center horizontally */
  align-items: center;         /* center vertically */
    padding: 10px 10px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.08);
    
  }

  .profile img {
    width: 40px;
    height: 40px;
    
    
  }

  /* Stats */
  .stats {
    flex-direction: column;
    gap: 15px;
  }

  .stat-card {
    width: 100%;
    min-width: unset;
    padding: 20px;
  }

  /* Content Grid */
  .content-grid {
    grid-template-columns: 1fr; /* single column on mobile */
    gap: 20px;
  }

  .content-card {
    padding: 20px;
  }

  .content-card h2 {
    font-size: 1.2rem;
  }
}


@media (max-width: 768px) {
  /* ...your other mobile styles */

  .content-card.community {
    margin-bottom: 120px; /* adds bottom gap */
  }
}
@media (max-width: 768px) {
  .feedback-section {
    margin-bottom: 80px; /* Adds space at the bottom */
  }
}
@media (max-width: 768px) {
  .sidebar .logo {
    font-size: 2rem;          
    text-align: center;         
    margin-top: 40px;         
    
  }
}
@media (max-width: 768px) {
  .sidebar-toggle {
    position: absolute;
    top: 15px;
    left: 15px;
    z-index: 9999 !important;
    width: 40px !important;
    height: 40px !important;

    display: flex;               /* enable flexbox */
    align-items: center;         /* vertically center text */
    justify-content: center;     /* horizontally center text */

    text-align: center;          /* ensures alignment for text */
    font-size: 1rem;             /* optional: adjust text size */
    border-radius: 8px;          /* optional: rounded edges */
  }
}

  </style>
</head>
<body>
  <div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar">
      <h2 class="logo">EmpowerHer</h2>
      <nav>
        <ul>
         <li><a href="dashboard.php" style = "text-decoration: none;color: white;" ><i class="fas fa-home"></i> Dashboard</a></li>
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
                  <p style="padding:10px;">No new notifications.</p>
                <?php endif; ?>
              </div>
              <div class="notif-footer">
                <a id="markAllRead">Mark all as read</a>
                <a href="community.php">View all</a>
              </div>
            </div>
          </li>
         <li ><i class="fas fa-comments"></i> <a href="chatbot.php" style = "text-decoration: none; color: white;">AI Chatbot</a></li>
          <li><i class="fas fa-book"></i> <a href="modules.php" style = "text-decoration: none; color: white;">Learning Modules</a></li>
          <li><i class="fas fa-users"></i><a href="community.php" style = "text-decoration: none; color: white;">Community</a></li>
           <li class ="active"><i class="fas fa-hand-holding-heart"></i><a href="feedback.php" style = "text-decoration: none; color: white;">Feedback</a></li>
          
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
        <h1>Give us feedback 🌸</h1>
        <div class="profile">
          <button id="theme-toggle" class="theme-toggle"><i class="fas fa-moon"></i></button>
        <img src="<?php echo htmlspecialchars($profilePic); ?>?v=<?php echo time(); ?>" 
     alt="Profile" class="profile-img" id="profilePicDisplay">

          <input type="file" id="profilePicInput" accept="image/*" style="display:none;">
          <span class="username-text"><?php echo htmlspecialchars($userName); ?></span>
          <?php if ($userName === 'Joshua Andres'): ?>
            <span class="verified-badge" title="Verified Owner"><i class="fas fa-check-circle"></i></span>
          <?php endif; ?>
          <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
      </header>

      
      <!-- Feedback Section -->
<section class="feedback-section">
  <h2 class="form-title">We Value Your Feedback 🌸</h2>
  <p class="form-subtitle">Your feedback helps us improve EmpowerHer and serve you better.</p>

  <!-- ✅ Show success/error message here -->
  <?php if (isset($_SESSION['feedback_success'])): ?>
    <p class="status success"><?= htmlspecialchars($_SESSION['feedback_success']) ?></p>
    <?php unset($_SESSION['feedback_success']); ?>
  <?php elseif (isset($_SESSION['feedback_error'])): ?>
    <p class="status error"><?= htmlspecialchars($_SESSION['feedback_error']) ?></p>
    <?php unset($_SESSION['feedback_error']); ?>
  <?php endif; ?>

  <form action="submit_feedback.php" method="POST" class="feedback-form">

    <!-- Rating -->
    <div class="form-group">
      <label class="form-label">1. Overall, how would you rate the system?</label>
      <div class="rating">
        <input type="radio" id="rate1" name="rating" value="1" required><label for="rate1">1</label>
        <input type="radio" id="rate2" name="rating" value="2"><label for="rate2">2</label>
        <input type="radio" id="rate3" name="rating" value="3"><label for="rate3">3</label>
        <input type="radio" id="rate4" name="rating" value="4"><label for="rate4">4</label>
        <input type="radio" id="rate5" name="rating" value="5"><label for="rate5">5</label>
      </div>
    </div>

    <!-- Improvements -->
    <div class="form-group">
      <label class="form-label">2. Which areas should we improve or expand?</label>
      <div class="options-grid">
        <label><input type="checkbox" name="improvements[]" value="UI/Design"> User Interface & Design</label>
        <label><input type="checkbox" name="improvements[]" value="Modules"> Learning Modules</label>
        <label><input type="checkbox" name="improvements[]" value="Chatbot"> AI Chatbot</label>
        <label><input type="checkbox" name="improvements[]" value="Community"> Community Features</label>
        <label><input type="checkbox" name="improvements[]" value="Notifications"> Notifications System</label>
        <label><input type="checkbox" name="improvements[]" value="Performance"> Speed & Performance</label>
        <label><input type="checkbox" name="improvements[]" value="Accessibility"> Accessibility & Mobile Support</label>
        <label><input type="checkbox" name="improvements[]" value="Security"> Privacy & Security</label>
      </div>
    </div>

    <!-- Ease of Use -->
    <div class="form-group">
      <label class="form-label">3. How easy is it to navigate and use the system?</label>
      <div class="options-inline">
        <label><input type="radio" name="usability" value="Very Easy" required> Very Easy</label>
        <label><input type="radio" name="usability" value="Somewhat Easy"> Somewhat Easy</label>
        <label><input type="radio" name="usability" value="Neutral"> Neutral</label>
        <label><input type="radio" name="usability" value="Difficult"> Difficult</label>
      </div>
    </div>

    <!-- Content Relevance -->
    <div class="form-group">
      <label class="form-label">4. Do you find the content and resources helpful?</label>
      <div class="options-inline">
        <label><input type="radio" name="content_helpful" value="Yes" required> Yes</label>
        <label><input type="radio" name="content_helpful" value="Somewhat"> Somewhat</label>
        <label><input type="radio" name="content_helpful" value="No"> No</label>
      </div>
    </div>

    <!-- Bug Report -->
    <div class="form-group">
      <label class="form-label">5. Did you encounter any bugs or errors?</label>
      <div class="options-inline">
        <label><input type="radio" name="bug_report" value="1"> Yes</label>
        <label><input type="radio" name="bug_report" value="0" checked> No</label>
      </div>
    </div>

    <!-- Feature Suggestions -->
    <div class="form-group">
      <label class="form-label">6. What new features would you like to see?</label>
      <textarea name="features" rows="3" placeholder="E.g., Daily tips, progress tracking, gamification..."></textarea>
    </div>

    <!-- Additional Feedback -->
    <div class="form-group">
      <label class="form-label">7. Please share any additional feedback or suggestions</label>
      <textarea name="message" rows="5" placeholder="Write your detailed feedback here..." required></textarea>
    </div>

    <!-- Submit -->
    <div class="form-group submit-wrap">
      <button type="submit" class="submit-btn">Submit Feedback</button>
    </div>
  </form>
</section>

    </main>
  </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
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

// Notifications
const bell = document.getElementById("notifBell");
const dropdown = document.getElementById("notifDropdown");
bell.addEventListener("click", () => {
  dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
});
document.addEventListener("click", (e) => {
  if (!bell.contains(e.target)) dropdown.style.display = "none";
});
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
