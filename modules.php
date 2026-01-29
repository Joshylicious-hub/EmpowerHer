<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "u739446465_empowerher_db", "u739446465_Empowerher_db@", "u739446465_empowerher_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $fileName = basename($_FILES["profile_pic"]["name"]);
    $targetFile = $uploadDir . time() . "_" . $fileName;

    if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetFile)) {
        $stmt = $conn->prepare("UPDATE users SET profile_pic=? WHERE id=?");
        $stmt->bind_param("si", $targetFile, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
        $_SESSION['profile_pic'] = $targetFile; // update session
    }
    header("Location: modules.php");
    exit();
}

// Fetch user profile picture
$sqlUser = "SELECT profile_pic FROM users WHERE id = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("i", $_SESSION['user_id']);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$userData = $resultUser->fetch_assoc();
$profilePic = !empty($userData['profile_pic']) ? $userData['profile_pic'] : "images/profile.jpg";
$stmtUser->close();

$userName = $_SESSION['user_name'] ?? "Mama";


// Helper function for lesson links
function renderLessonLink($lessonId, $text, $icon, $completedLessons, &$firstLockedDone) {
    $locked = true;

    if (!$firstLockedDone) {
        if (!in_array($lessonId, $completedLessons)) {
            $locked = false; // first unread unlocked
            $firstLockedDone = true;
        } else {
            $locked = false; // already completed unlocked
        }
    }

    $class = $locked ? "lesson-locked" : "";
    $href = $locked ? "#" : "lessons/lesson$lessonId.php";
    $lockIcon = $locked ? " <i class='fas fa-lock'></i>" : "";
    return "<a href='$href' class='$class'><i class='$icon'></i> $text$lockIcon</a>";
}

// === ALL MODULES (32 Lessons) ===
$modules = [
    ["name"=>"👶 Early Beginnings Foundational Skills", "lessons"=>[
        ["id"=>1,"text"=>"Lesson 1: Bonding & Skin-to-Skin Contact","icon"=>"fa-solid fa-heart"],
        ["id"=>2,"text"=>"Lesson 2: Understanding Newborn Cues","icon"=>"fa-solid fa-baby"],
        ["id"=>3,"text"=>"Lesson 3: Safe Sleep & Positioning","icon"=>"fa-solid fa-bed"],
        ["id"=>4,"text"=>"Lesson 4: Feeding & Burping Basics","icon"=>"fa-solid fa-bottle-water"],
        ["id"=>5,"text"=>"Lesson 5: Bathing & Umbilical Cord Care","icon"=>"fa-solid fa-hand-sparkles"]
    ]],
    ["name"=>"🧒 Infant & Early Growth (1 year old)", "lessons"=>[
        ["id"=>6,"text"=>"Lesson 6: Tummy Time & Motor Skills","icon"=>"fa-solid fa-dumbbell"],
        ["id"=>7,"text"=>"Lesson 7: Encouraging Babbling & Early Language","icon"=>"fa-solid fa-comment"],
        ["id"=>8,"text"=>"Lesson 8: Building Secure Attachment","icon"=>"fa-solid fa-hand-holding-heart"],
        ["id"=>9,"text"=>"Lesson 9: Starting Solid Foods Safely","icon"=>"fa-solid fa-utensils"],
        ["id"=>10,"text"=>"Lesson 10: Playtime & Social Development","icon"=>"fa-solid fa-people-group"]
    ]],
    ["name"=>"👧 Toddler (1 - 3 years)", "lessons"=>[
        ["id"=>11,"text"=>"Lesson 11: Toilet Training Made Easy","icon"=>"fa-solid fa-toilet"],
        ["id"=>12,"text"=>"Lesson 12: Managing Tantrums & Big Emotions","icon"=>"fa-solid fa-face-smile"],
        ["id"=>13,"text"=>"Lesson 13: Encouraging Creativity & Play","icon"=>"fa-solid fa-paint-brush"],
        ["id"=>14,"text"=>"Lesson 14: Toddler Safety at Home & Outdoors","icon"=>"fa-solid fa-shield-halved"],
        ["id"=>15,"text"=>"Lesson 15: Gentle & Positive Discipline","icon"=>"fa-solid fa-handshake-angle"]
    ]],
    ["name"=>"👦 Early Childhood (3 - 5 years)", "lessons"=>[
        ["id"=>16,"text"=>"Lesson 16: Getting Ready for Kindergarten","icon"=>"fa-solid fa-book-open"],
        ["id"=>17,"text"=>"Lesson 17: Making Friends & Sharing","icon"=>"fa-solid fa-users"],
        ["id"=>18,"text"=>"Lesson 18: Managing Conflicts & Emotions","icon"=>"fa-solid fa-handshake"],
        ["id"=>19,"text"=>"Lesson 19: Learning Letters, Numbers & Stories","icon"=>"fa-solid fa-book"],
        ["id"=>20,"text"=>"Lesson 20: Fun Physical Activities & Play","icon"=>"fa-solid fa-basketball"]
    ]],
    ["name"=>"🧑 Middle Childhood (6 - 12 years)", "lessons"=>[
        ["id"=>21,"text"=>"Lesson 21: Building Responsibility with Chores","icon"=>"fa-solid fa-list-check"],
        ["id"=>22,"text"=>"Lesson 22: Balancing Schoolwork & Screen Time","icon"=>"fa-solid fa-tv"],
        ["id"=>23,"text"=>"Lesson 23: Teamwork & Friendship Skills","icon"=>"fa-solid fa-people-group"],
        ["id"=>24,"text"=>"Lesson 24: Problem-Solving & Critical Thinking","icon"=>"fa-solid fa-brain"],
        ["id"=>25,"text"=>"Lesson 25: Healthy Eating & Active Lifestyle","icon"=>"fa-solid fa-heart-pulse"]
    ]],
    ["name"=>"🧑‍🦱 Adolescence (13 - 18 years)", "lessons"=>[
        ["id"=>26,"text"=>"Lesson 26: Helping Your Teen Manage Time & Set Goals","icon"=>"fa-solid fa-clock"],
        ["id"=>27,"text"=>"Lesson 27: Guiding Healthy Technology & Social Media Habits","icon"=>"fa-solid fa-laptop"],
        ["id"=>28,"text"=>"Lesson 28: Supporting Healthy Friendships & Communication","icon"=>"fa-solid fa-users"],
        ["id"=>29,"text"=>"Lesson 29: Encouraging Critical Thinking & Problem Solving","icon"=>"fa-solid fa-brain"],
        ["id"=>30,"text"=>"Lesson 30: Teaching Nutrition, Exercise & Self-Care","icon"=>"fa-solid fa-heart-pulse"],
        ["id"=>31,"text"=>"Lesson 31: Helping Your Teen Cope with Stress & Emotions","icon"=>"fa-solid fa-face-smile"],
        ["id"=>32,"text"=>"Lesson 32: Guiding Safe Decisions & Handling Peer Pressure","icon"=>"fa-solid fa-handshake"]
    ]]
    
];

// Fetch completed lessons
$completedLessons = [];
$stmt = $conn->prepare("SELECT lesson_id FROM user_progress WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $completedLessons[] = $row['lesson_id'];
}
$stmt->close();

// Count completed lessons and check if all are done
$totalLessons = 32; // total lessons in main modules
$completedCount = count($completedLessons);
$allLessonsCompleted = ($completedCount >= $totalLessons);

$userId = $_SESSION['user_id'];

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

// ✅ Now close the connection at the end
$conn->close();


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EmpowerHer – Learning Modules</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Raleway:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="modules.css">
  <link rel="icon" type="image/png" sizes="34x34" href="images/logo7.png">
<link rel="icon" type="image/png" sizes="64x64" href="images/logo7.png">
<link rel="icon" type="image/png" sizes="192x192" href="images/logo7.png">
  <style>
    body.dark-mode {
      --bg-main: url("images/dark-bg.jpg"), linear-gradient(135deg, #1a1a1a, #262626);
      --bg-card: rgba(40, 40, 40, 0.9);
      --bg-sidebar: linear-gradient(180deg, #111, #222);
      --text-main: #eee;
      --text-heading: #ff9a9e;
      --text-light: #bbb;
      --card-shadow: rgba(0,0,0,0.6);
    }
    .lesson-locked {
      pointer-events: none;
      opacity: 0.5;
      cursor: not-allowed;
    }
    .topbar .profile {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .topbar .profile img {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      cursor: pointer;
    }
    .theme-toggle {
      background: var(--text-heading);
      border: none;
      color: #fff;
      padding: 8px 12px;
      border-radius: 20px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.9rem;
      transition: background 0.3s, transform 0.2s;
    }
    .theme-toggle:hover {
      background: #e63950;
      transform: scale(1.05);
    }
    .hidden-input {
      display: none;
    }




    .notification-bell { position: relative; cursor: pointer; }
.notif-badge { position: absolute; top: -6px; right: -10px; background: red; color: #fff; border-radius: 50%; font-size: 12px; padding: 2px 6px; }
.notif-dropdown { display: none; position: absolute; left: 220px; top: 80px; background: #fff; width: 350px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); z-index: 1000; padding: 10px; }

/* =========================
   NOTIFICATIONS DESIGN
   ========================= */

/* Bell icon container */
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




  .custom-modules-section {
   background: var(--bg-card); /* same as module-card */
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    margin-top: 20px;
    margin-bottom: 80px;
  }

  .custom-modules-section h2 {
  color: var(--text-heading); /* same as module-card headings */
    font-weight: 700;
    margin-bottom: 15px;
  }
   .custom-modules-section h3 {
  color: var(--text-heading); /* same as module-card headings */
    font-weight: 700;
    margin-bottom: 15px;
  }

  .custom-form {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 25px;
  }

  .custom-form input, .custom-form textarea {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 10px;
    font-size: 14px;
  }

  .custom-form button {
     background: linear-gradient(135deg, #ff6b81, #ff9a9e);
     color: white;
    border: none;
    padding: 10px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
  }

  .custom-form button:hover {
     background: linear-gradient(135deg, #ff4d6d, #ff7b8d);
  }

  .module-card {
    background: #f9f9f9;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
  }

  .module-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .delete-btn {
    background: none;
    border: none;
    color: #dc3545;
    font-size: 18px;
    cursor: pointer;
  }

  .delete-btn:hover {
    color: #b02a37;
  }

  .lessons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
  }

  .custom-lesson {
    display: inline-block;
    background: #e0d4f7;
    color: #4b0082;
    padding: 8px 12px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
  }

  .custom-lesson:hover {
    background: #d1b8f7;
  }

/* Dark Mode for Module Cards */
body.dark-mode .module-card {
    background: var(--bg-card); /* already set in your existing dark-mode vars */
    color: #eee;
    box-shadow: 0 10px 25px rgba(0,0,0,0.5);
}

body.dark-mode .module-card h3 {
    color: var(--text-heading); /* pinkish heading in dark mode */
}

/* Dark Mode for Lessons inside module cards */
body.dark-mode .module-card .lessons a {
    background: #333; /* darker background for lesson links */
    color: #eee;
}

body.dark-mode .module-card .lessons a i {
    color: var(--text-heading);
}

body.dark-mode .module-card .lessons a:hover {
    background: #ff4e6d; /* hover pink for dark mode */
    color: #fff;
}

body.dark-mode .module-card .lessons a:hover i {
    color: #fff;
}

/* Optional: Custom module card delete button in dark mode */
body.dark-mode .module-card.custom .delete-btn {
    background: #b33; 
    color: #fff;
}

body.dark-mode .module-card.custom .delete-btn:hover {
    background: #ff4e6d;
}




/* Dark Mode for Custom Modules Section */
body.dark-mode .custom-modules-section {
    background: var(--bg-card); /* already set in your existing dark-mode vars */
    color: #eee; /* text color */
    padding: 20px;
    border-radius: 18px;
}

/* Headings */
body.dark-mode .custom-modules-section h2,
body.dark-mode .custom-modules-section h3 {
    color: var(--text-heading); /* use your dark mode heading color */
}

/* Form inputs & textarea */
body.dark-mode .custom-modules-section input,
body.dark-mode .custom-modules-section textarea {
    background: #2a2a2a;
    color: #eee;
    border: 1px solid #444;
}

/* Form button */
body.dark-mode .custom-modules-section button {
    background: linear-gradient(135deg, #ff6b81, #ff9a9e);
    color: #fff;
}

body.dark-mode .custom-modules-section button:hover {
    background: linear-gradient(135deg, #ff4d6d, #ff7b8d);
}





/* Custom module cards */
body.dark-mode .module-card.custom {
    background: #2a2a2a; /* dark card background */
    color: #eee;
    box-shadow: 0 10px 25px rgba(0,0,0,0.5);
}

/* Lessons inside custom module cards */
body.dark-mode .module-card.custom .lessons a {
    background: #333;
    color: #eee;
}

body.dark-mode .module-card.custom .lessons a i {
    color: var(--text-heading);
}

body.dark-mode .module-card.custom .lessons a:hover {
    background: #ff4e6d;
    color: #fff;
}

body.dark-mode .module-card.custom .lessons a:hover i {
    color: #fff;
}

/* Delete button */
body.dark-mode .module-card.custom .delete-btn {
    background: #b33;
    color: #fff;
}

body.dark-mode .module-card.custom .delete-btn:hover {
    background: #ff4e6d;
}



.locked-overlay {
  background: rgba(255, 182, 193, 0.15);
  border: 2px dashed #ff6b81;
  padding: 25px;
  text-align: center;
  border-radius: 15px;
  font-size: 1rem;
  color: #555;
}

.locked-overlay .progress-info {
  margin-top: 8px;
  font-size: 0.9rem;
  color: #888;
}

/* Dark mode version */
body.dark-mode .locked-overlay {
  background: rgba(255, 78, 109, 0.15);
  color: #eee;
  border-color: #ff6b81;
}

/* 🌸 EmpowerHer Loading Overlay */
#pageLoadingOverlay {
  display: none;
  position: fixed;
  inset: 0;
  width: 100%;
  height: 100%;
  background: rgba(255, 182, 193, 0.35); /* soft pink transparent */
  backdrop-filter: blur(6px) saturate(140%);
  z-index: 9999;
  overflow: hidden;
  justify-content: center;
  align-items: center;
  flex-direction: column;
  font-family: 'Poppins', sans-serif;
  text-align: center;
  transition: opacity 0.4s ease;
}

/* 🌸 Floating Bubbles */
.floating-shapes .bubble {
  position: absolute;
  border-radius: 50%;
  background: radial-gradient(circle at 30% 30%,
    rgba(255, 110, 140, 0.9) 0%,
    rgba(255, 65, 100, 0.8) 40%,
    rgba(255, 180, 190, 0.5) 80%);
  box-shadow: 0 0 25px rgba(255, 90, 120, 0.7);
  animation: floatUp 10s infinite ease-in-out, shimmer 3s infinite ease-in-out;
  opacity: 0.9;
  filter: brightness(1.1);
  backdrop-filter: blur(2px);
}

/* 🌸 Bubble sizes and positions */
.floating-shapes .bubble:nth-child(1) {
  width: 180px; height: 180px; left: 10%; bottom: -20%; animation-delay: 0s;
}
.floating-shapes .bubble:nth-child(2) {
  width: 140px; height: 140px; left: 60%; bottom: -25%; animation-delay: 2s;
}
.floating-shapes .bubble:nth-child(3) {
  width: 200px; height: 200px; left: 80%; bottom: -15%; animation-delay: 4s;
}
.floating-shapes .bubble:nth-child(4) {
  width: 120px; height: 120px; left: 30%; bottom: -10%; animation-delay: 1s;
}
.floating-shapes .bubble:nth-child(5) {
  width: 100px; height: 100px; left: 75%; bottom: -5%; animation-delay: 3s;
}

/* 🌸 Bubble float animation */
@keyframes floatUp {
  0% {
    transform: translateY(0) scale(1);
    opacity: 0.9;
  }
  50% {
    transform: translateY(-40vh) scale(1.05);
    opacity: 1;
  }
  100% {
    transform: translateY(-90vh) scale(1);
    opacity: 0.4;
  }
}

/* 🌸 Bubble shimmer effect */
@keyframes shimmer {
  0%, 100% {
    box-shadow: 0 0 25px rgba(255, 120, 150, 0.6), inset -8px -8px 20px rgba(255, 200, 210, 0.2);
    filter: brightness(1.1);
  }
  50% {
    box-shadow: 0 0 40px rgba(255, 130, 160, 0.9), inset 5px 5px 25px rgba(255, 255, 255, 0.4);
    filter: brightness(1.3);
  }
}

/* 🌸 Center Content */
.loading-content {
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
  animation: fadeInUp 0.8s ease;
}

/* 🌸 Spinner */
.spinner {
  border: 6px solid rgba(255, 192, 203, 0.3);
  border-top: 6px solid #ff4e6d;
  border-radius: 50%;
  width: 80px;
  height: 80px;
  animation: spin 1.2s linear infinite, glowPulse 2s ease-in-out infinite;
  box-shadow: 0 0 25px rgba(255, 107, 129, 0.4);
}

/* 🌸 Typing Text */
.typing-text {
  margin-top: 25px;
  color: #ff4e6d;
  font-size: 1.3rem;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  border-right: 3px solid #ff4e6d;
  animation: blinkCursor 0.8s infinite;
  text-shadow: 0 0 8px rgba(255, 107, 129, 0.4);
  background: rgba(255, 255, 255, 0.25);
  padding: 12px 25px;
  border-radius: 25px;
  backdrop-filter: blur(8px);
}

/* 🌸 Animations */
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
@keyframes glowPulse {
  0%, 100% { box-shadow: 0 0 20px rgba(255, 107, 129, 0.3); }
  50% { box-shadow: 0 0 40px rgba(255, 107, 129, 0.6); }
}
@keyframes fadeInUp {
  0% { opacity: 0; transform: translateY(30px); }
  100% { opacity: 1; transform: translateY(0); }
}
@keyframes blinkCursor {
  0%, 100% { border-color: transparent; }
  50% { border-color: #ff4e6d; }
}

/* 🌙 Dark Mode */
body.dark-mode #pageLoadingOverlay {
  background: rgba(25, 25, 25, 0.65);
  backdrop-filter: blur(8px);
}
body.dark-mode .typing-text {
  color: #ff9aad;
  border-right-color: #ff9aad;
}
body.dark-mode .spinner {
  border: 6px solid #333;
  border-top: 6px solid #ff9aad;
}


  </style>
</head>
<body>
  <div class="dashboard">
    <aside class="sidebar">
      <h2 class="logo">EmpowerHer</h2>
      <nav>
        <ul>
          <li><i class="fas fa-home"></i> <a href="dashboard.php" style="text-decoration: none; color: white; ">Dashboard</a></li>
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

          <li><i class="fas fa-comments"></i> <a href="chatbot.php" style="text-decoration: none; color: white; ">AI Chatbot</a></li>
          <li class="active"><i class="fas fa-book"></i> <a href="modules.php" style="text-decoration: none; color: white; ">Learning Modules</a></li>
          <li><i class="fas fa-users"></i> <a href="community.php" style="text-decoration: none; color: white; ">Community</a></li>
           <li><i class="fas fa-hand-holding-heart"></i><a href="feedback.php" style = "text-decoration: none; color: white;">Feedback</a></li>
          
        </ul>
      </nav>
    </aside>

    <main class="main-content">
      <header class="topbar">
        <button id="sidebarToggle" class="sidebar-toggle">
    <i class="fas fa-bars"></i>
</button>
        <h1>Learning Modules🌸</h1>
        
        <div class="profile">
          <button id="themeToggle" class="theme-toggle">
            <i class="fas fa-moon"></i> 
          </button>
          
          <!-- Profile Upload Form -->
          <form id="uploadForm" method="POST" enctype="multipart/form-data">
            <input type="file" name="profile_pic" id="profileInput" class="hidden-input" accept="image/*" onchange="document.getElementById('uploadForm').submit();">
            <img src="<?php echo htmlspecialchars($profilePic); ?>?v=<?php echo time(); ?>" 
     alt="Profile" class="profile-img" id="profilePicDisplay">

          </form>

          <span>
            <?= htmlspecialchars($userName); ?>
            <?php if ($userName === 'Joshua Andres' || $userName === 'Issay' || $userName === 'Mia Khalifa'): ?>
              <span title="Verified Owner" style="color:#1DA1F2; margin-left:4px;">
                <i class="fas fa-check-circle"></i>
              </span>
            <?php endif; ?>
          </span>
          <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
          </a>
        </div>
      </header>

      <div class="modules-container">
        <?php
          $firstLockedDone = false;
          foreach($modules as $module): ?>
            <div class="module-card">
              <h3><?= $module['name']; ?></h3>
              <div class="lessons">
                <?php
                  foreach($module['lessons'] as $lesson) {
                    echo renderLessonLink($lesson['id'], $lesson['text'], $lesson['icon'], $completedLessons, $firstLockedDone);
                  }
                ?>
              </div>
            </div>
        <?php endforeach; ?>
      </div>
      
      
   
<div class="custom-modules-section">
  <h2>✨ Create Learning Modules Instantly with EmpowerHer AI Nova</h2>

  <?php if (!$allLessonsCompleted): ?>
    <div class="locked-overlay">
      <p>🔒 Unlock this feature by completing all 32 learning modules!</p>
      <div class="progress-info">
        You’ve completed <strong><?= $completedCount ?></strong> / <?= $totalLessons ?> lessons.
      </div>
    </div>
  <?php else: ?>
    <form id="customModuleForm" method="POST" action="save_custom_module.php" class="custom-form">
      <input type="text" name="module_name" placeholder="Enter your module title (e.g. Parenting Tips)" required>
      <textarea name="topic" placeholder="Describe what you want to learn or focus on (e.g. managing stress as a parent)" required></textarea>
      <button type="submit"><i class="fa-solid fa-magic-wand-sparkles"></i> Generate Module</button>
    </form>

    <div class="user-modules">
      <h3>📚 Your Custom Modules</h3>
      <?php
        session_start();
        $conn = new mysqli("localhost", "u739446465_empowerher_db", "u739446465_Empowerher_db@", "u739446465_empowerher_db");
        $stmt = $conn->prepare("SELECT * FROM custom_modules WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0):
          while ($mod = $result->fetch_assoc()):
            $lessons = json_decode($mod['lessons'], true);
      ?>
        <div class="module-card custom">
          <h3><?= htmlspecialchars($mod['module_name']); ?></h3>
          <div class="lessons">
            <?php foreach ($lessons as $lesson): ?>
              <a href="custom_lesson.php?id=<?= $mod['id']; ?>" class="custom-lesson"><i class="fa-solid fa-star"></i> <?= htmlspecialchars($lesson); ?></a>
            <?php endforeach; ?>
          </div>
          <form method="POST" action="delete_custom_module.php" class="delete-form" onsubmit="return confirm('Delete this module?');">
            <input type="hidden" name="id" value="<?= $mod['id']; ?>">
            <button type="submit" class="delete-btn">🗑 Delete</button>
          </form>
        </div>
      <?php
          endwhile;
        else:
          echo "<p style='color:gray;'>You haven’t created any custom modules yet.</p>";
        endif;
        $stmt->close();
        $conn->close();
      ?>
    </div>
  <?php endif; ?>
</div>



    <!-- 🌸 EmpowerHer AI Loading Overlay -->
    <div id="pageLoadingOverlay">
      <div class="floating-shapes">
        <span class="bubble"></span>
        <span class="bubble"></span>
        <span class="bubble"></span>
        <span class="bubble"></span>
        <span class="bubble"></span>
      </div>
    
      <div class="loading-content">
        <div class="spinner"></div>
        <div id="typingText" class="typing-text"></div>
      </div>
    </div>



      
    </main>
  </div>

  <script>
    const toggleBtn = document.getElementById("themeToggle");
    const body = document.body;

    if (localStorage.getItem("theme") === "dark") {
      body.classList.add("dark-mode");
      toggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
    }

    toggleBtn.addEventListener("click", () => {
      body.classList.toggle("dark-mode");
      const isDark = body.classList.contains("dark-mode");
      toggleBtn.innerHTML = isDark
        ? '<i class="fas fa-sun"></i> '
        : '<i class="fas fa-moon"></i> ';
      localStorage.setItem("theme", isDark ? "dark" : "light");
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





 const sidebar = document.querySelector('.sidebar'); // ✅ ADD THIS LINE
  const sidebarToggle = document.getElementById('sidebarToggle');

  sidebarToggle.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    // Change icon dynamically
    if (sidebar.classList.contains('collapsed')) {
      sidebarToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
    } else {
      sidebarToggle.innerHTML = '<i class="fa-solid fa-xmark"></i>';
    }
  });




//loading

document.addEventListener("DOMContentLoaded", function() {
  const overlay = document.getElementById("pageLoadingOverlay");
  const typingText = document.getElementById("typingText");

  const messages = [
    "🌸 EmpowerHer AI is preparing your personalized module...",
    "💖 Understanding your learning style...",
    "✨ Designing something meaningful for you...",
    "🌷 Almost done — your EmpowerHer journey begins soon!"
  ];

  let msgIndex = 0;
  let charIndex = 0;
  let isDeleting = false;

  function typeEffect() {
    const currentMsg = messages[msgIndex];
    if (!isDeleting) {
      typingText.textContent = currentMsg.substring(0, charIndex++);
      if (charIndex <= currentMsg.length) {
        setTimeout(typeEffect, 50);
      } else {
        setTimeout(() => (isDeleting = true, typeEffect()), 1200);
      }
    } else {
      typingText.textContent = currentMsg.substring(0, charIndex--);
      if (charIndex >= 0) {
        setTimeout(typeEffect, 25);
      } else {
        isDeleting = false;
        msgIndex = (msgIndex + 1) % messages.length;
        setTimeout(typeEffect, 400);
      }
    }
  }

  // Show overlay on navigation to certain pages
  document.querySelectorAll("a").forEach(link => {
    link.addEventListener("click", function() {
      const href = this.getAttribute("href");
      if (href && (href.includes("custom_lesson.php") || href.includes("modules.php"))) {
        overlay.style.display = "flex";
        typeEffect();
      }
    });
  });

  window.addEventListener("beforeunload", () => {
    overlay.style.display = "flex";
    typeEffect();
  });
});
  </script>
</body>
</html>
