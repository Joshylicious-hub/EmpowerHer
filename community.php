
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// --- AI Content Moderation Function ---
function checkContentWithAI($title, $content) {
    // IMPORTANT: Replace the placeholder with your actual, secure API key.
    $api_key = "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"; 

    $ch = curl_init("https://api.openai.com/v1/chat/completions");

    // The System Prompt is crucial. It sets the rules for the AI.
    $system_prompt = "You are a content moderator for a community dedicated to empowering women through discussions on parenting, mental health, socializing, and general advice. Your task is to analyze the user's post content (Title and Body) and strictly determine if it is RELEVANT or IRRELEVANT to these core topics. The response must be a single, non-formatted JSON object with two keys: 'status' (RELEVANT or IRRELEVANT) and 'reason' (a brief explanation in a sentence or two). Do not add any text outside the JSON block.

RELEVANT topics include: parenting advice, childcare, mental health struggles, well-being, social issues, relationship advice, career/work-life balance, self-care, or asking genuine, supportive questions.
IRRELEVANT topics include: spam, commercial advertisements, hate speech, explicit content, topics completely unrelated to the community's mission (e.g., car repair, sports scores, generic news).";

    $user_content = "Title: \"".$title."\"\nContent: \"".$content."\"";

    $data = [
        "model" => "gpt-3.5-turbo", // A cost-effective and capable model for this task
        "messages" => [
            ["role" => "system", "content" => $system_prompt],
            ["role" => "user", "content" => $user_content]
        ],
        "temperature" => 0.0, // Set to 0.0 for deterministic, rule-based output
        "max_tokens" => 150,
        "response_format" => ["type" => "json_object"] // Ensures the output is JSON
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $api_key
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code != 200) {
        // Log or handle API error, but assume IRRELEVANT for safety
        error_log("OpenAI API Error (HTTP $http_code): " . $response);
        return ['status' => 'IRRERELEVANT', 'reason' => 'System check failed.'];
    }

    $json_response = json_decode($response, true);
    
    // Attempt to parse the content string from the response
    if (isset($json_response['choices'][0]['message']['content'])) {
        $content_string = $json_response['choices'][0]['message']['content'];
        $moderation_result = json_decode($content_string, true);
        if ($moderation_result && isset($moderation_result['status'])) {
             return $moderation_result;
        }
    }
    
    // Fallback if JSON parsing fails
    error_log("OpenAI Response Parsing Error: " . $response);
    return ['status' => 'IRRELEVANT', 'reason' => 'System check failed to process AI response.'];
}

// Global variable for the alert message
$content_alert = '';
// --- End AI Content Moderation Function ---

$userId = $_SESSION['user_id'] ?? 0; // default 0 if not logged in
$userName = $_SESSION['user_name'] ?? 'Guest';

// Protect page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userName = $_SESSION['user_name'];

$adminUsers = ['Joshua Andres', 'Issay', 'Mia Khalifa'];
$user_is_admin = in_array($userName, $adminUsers);



// Database connection
$conn = new mysqli("localhost", "u739446465_empowerher_db", "u739446465_Empowerher_db@", "u739446465_empowerher_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);


// Handle new post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_post'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $topic = trim($_POST['topic']);
    $mediaPath = NULL;
    
    // 1. Check if title or content is empty after trimming
    if (empty($title) || empty($content)) {
        $content_alert = "Error: Title and content fields cannot be empty.";
    } else {
        
        // 2. Perform AI content moderation check
        $moderation_result = checkContentWithAI($title, $content);
        
        $status = $moderation_result['status'] ?? 'IRRELEVANT';
        $reason = $moderation_result['reason'] ?? 'Content was flagged by the system.';

        if ($status === 'RELEVANT') {
            // 3. Post is approved! Proceed to save to DB.

            $dbTitle = $conn->real_escape_string($title);
            $dbContent = $conn->real_escape_string($content);
            $dbTopic = $conn->real_escape_string($topic);
            
            if (!empty($_FILES['media']['name'])) {
                $filename = time() . "_" . basename($_FILES['media']['name']);
                $targetDir = "uploads/posts/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                $targetFile = $targetDir . $filename;
                
                if (move_uploaded_file($_FILES['media']['tmp_name'], $targetFile)) {
                    $mediaPath = $targetFile;
                } else {
                    error_log("File upload failed for post.");
                }
            }

            $conn->query("INSERT INTO posts (user_id, user_name, title, content, topic, media, created_at) 
                          VALUES ($userId,'$userName','$dbTitle','$dbContent','$dbTopic','{$mediaPath}', NOW())");
            
            // Redirect to prevent form resubmission and clear post data
            header("Location: community.php");
            exit();

        } else {
            // 4. Post is rejected! Set an alert message.
            $content_alert = "Your post was rejected because it does not align with our community standards. The reason is: **" . htmlspecialchars($reason) . "** Please ensure your posts are related to parenting advice, mental health, or socializing with peers.";
        }
    }
}



// Handle new reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_reply'])) {
    $post_id = intval($_POST['post_id']);
    $reply = $conn->real_escape_string($_POST['reply']);
    $mediaPath = NULL;

    if (!empty($_FILES['media']['name'])) {
        $filename = time() . "_" . basename($_FILES['media']['name']);
        $targetDir = "uploads/replies/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $targetFile = $targetDir . $filename;
        move_uploaded_file($_FILES['media']['tmp_name'], $targetFile);
        $mediaPath = $targetFile;
    }

    $conn->query("INSERT INTO replies (post_id, user_id, user_name, reply, media, created_at) 
                  VALUES ($post_id,$userId,'$userName','$reply','$mediaPath', NOW())");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) { 
    $post_id = intval($_POST['post_id']);

    if ($user_is_admin) {
        // ✅ Admins can delete ANY post
        $postCheck = $conn->query("SELECT id FROM posts WHERE id = $post_id");
    } else {
        // ✅ Normal users can delete ONLY their own posts
        $postCheck = $conn->query("SELECT id FROM posts WHERE id = $post_id AND user_id = $userId");
    }

    if ($postCheck && $postCheck->num_rows > 0) {
        $conn->query("DELETE FROM posts WHERE id = $post_id");
        $conn->query("DELETE FROM replies WHERE post_id = $post_id");
        $conn->query("DELETE FROM post_reactions WHERE post_id = $post_id");
        
        
    }
}

// Handle delete reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reply'])) {
    $reply_id = intval($_POST['reply_id']);

    if ($user_is_admin) {
        // ✅ Admins can delete ANY reply
        $replyCheck = $conn->query("SELECT id FROM replies WHERE id = $reply_id");
    } else {
        // ✅ Users can delete ONLY their own replies
        $replyCheck = $conn->query("SELECT id FROM replies WHERE id = $reply_id AND user_id = $userId");
    }

    if ($replyCheck && $replyCheck->num_rows > 0) {
        $conn->query("DELETE FROM replies WHERE id = $reply_id");
        $conn->query("DELETE FROM post_reactions WHERE reply_id = $reply_id");
    }
}

// Handle feature post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feature_post']) && $user_is_admin) {
    $post_id = intval($_POST['post_id']);
    $conn->query("UPDATE posts SET featured=1 WHERE id=$post_id");
}

// Handle reactions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['react'])) {
    $post_id = !empty($_POST['post_id']) ? intval($_POST['post_id']) : NULL;
    $reply_id = !empty($_POST['reply_id']) ? intval($_POST['reply_id']) : NULL;
    $reaction_type = $conn->real_escape_string($_POST['reaction_type']);

    $conn->query("DELETE FROM post_reactions 
                  WHERE user_id=$userId 
                  AND post_id ".($post_id ? "=$post_id" : "IS NULL")." 
                  AND reply_id ".($reply_id ? "=$reply_id" : "IS NULL"));

    $conn->query("INSERT INTO post_reactions (post_id, reply_id, user_id, reaction_type) 
                  VALUES ".($post_id ? "($post_id, NULL, $userId, '$reaction_type')" : "(NULL, $reply_id, $userId, '$reaction_type')"));
}

// Handle search
$searchQuery = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $searchQuery = "WHERE title LIKE '%$search%' OR content LIKE '%$search%' OR user_name LIKE '%$search%' OR topic LIKE '%$search%'";
}

$posts = $conn->query("
    SELECT p.*, u.profile_pic 
    FROM posts p 
    LEFT JOIN users u ON p.user_id = u.id 
    $searchQuery 
    ORDER BY p.created_at DESC
");

// Trending topics
// Trending topics based on selected categories
$allTopics = $conn->query("SELECT topic FROM posts WHERE topic IS NOT NULL AND topic <> ''");
$topicCounts = [];
while ($row = $allTopics->fetch_assoc()) {
    $topic = $row['topic'];
    $topicCounts[$topic] = ($topicCounts[$topic] ?? 0) + 1;
}
arsort($topicCounts);
$topTopics = array_slice($topicCounts, 0, 10, true);


// Predefined topics
$predefinedTopics = ["Parenting","Childcare","Education & Learning","Health & Nutrition","Mental Health & Wellbeing","Career & Work-Life Balance","Financial Management","Relationships & Support","Legal Advice & Rights","Community Resources","Self-Care & Lifestyle","Housing & Living","Others"];

// Reaction types
$reactionTypes = ['like','love','laugh','sad','angry'];
$reactionMap = [
    'like' => ['emoji'=>'👍','color'=>'bg-blue-100 text-blue-600'],
    'love' => ['emoji'=>'❤️','color'=>'bg-red-100 text-red-600'],
    'laugh'=> ['emoji'=>'😂','color'=>'bg-yellow-100 text-yellow-600'],
    'sad'  => ['emoji'=>'😢','color'=>'bg-purple-100 text-purple-600'],
    'angry'=> ['emoji'=>'😡','color'=>'bg-orange-100 text-orange-600'],
];




// Fetch current user profile picture
$sqlUser = "SELECT profile_pic FROM users WHERE id = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("i", $userId);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$userData = $resultUser->fetch_assoc();
$stmtUser->close();

// ✅ SAFE PROFILE IMAGE FIX
if (!empty($userData['profile_pic']) && file_exists($userData['profile_pic'])) {
    $profilePic = $userData['profile_pic'];
} else {
    $profilePic = "images/profile.jpg"; // fallback
}




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

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Community Forum - EmpowerHer</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Raleway:wght@400;600&display=swap" rel="stylesheet">
<link rel="icon" type="image/png" sizes="34x34" href="images/logo7.png">
<link rel="icon" type="image/png" sizes="64x64" href="images/logo7.png">
<link rel="icon" type="image/png" sizes="192x192" href="images/logo7.png">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
  body {
  background-color: var(--bg-body);
  color: var(--text-color);
  transition: background 0.3s, color 0.3s;
}
  html, body {
    height: 100%;
    margin: 0;
    padding: 0;
}

.dashboard {
    display: flex;
    min-height: 100vh;
}

:root {
  --bg-main: linear-gradient(135deg, #fff5f7, #ffe6f0);
  --bg-card: linear-gradient(135deg, #fff, #ffe9f2);
  --bg-sidebar: linear-gradient(180deg, #ff6b6b, #ff8ea3);
  --text-main: #333;
  --text-heading: #ff4e6d;
  --text-light: #555;
  --card-shadow: rgba(0,0,0,0.08);
}
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
    
    /* Make sidebar sticky */
    position: sticky;
    top: 0;
    height: 100vh; /* full viewport height */
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
.sidebar.collapsed ~ .main-content {
  margin-left: 0;
}
 .profile {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #fff;
  padding: 8px 15px;
  border-radius: 50px;
  box-shadow: 0 6px 15px rgba(0,0,0,0.08);
  transition: background 0.4s;
}

.profile img {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  object-fit: cover;
}
/* Logout Button */
.logout-btn {
  background: #ff4e6d;
  color: #fff;
  padding: 8px 15px;
  border-radius: 25px;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: background 0.3s, transform 0.2s;
}

.logout-btn:hover {
  background: #e63950;
  transform: scale(1.05);
}
.profile {
  display: flex;
  align-items: center;
  gap: 10px;
}

.profile-img {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #ddd;
}

.upload-label {
  cursor: pointer;
  font-size: 16px;
  color: #555;
  margin-left: 5px;
}

.upload-label:hover {
  color: #1DA1F2;
}
/* Theme Toggle Button */
.theme-toggle {
  background: none;
  border: none;
  font-size: 1.6rem;
  cursor: pointer;
  color: var(--text-heading);
  transition: transform 0.3s;
}

.theme-toggle:hover {
  transform: rotate(20deg);
}

/* Dark mode background */
body.dark-mode .profile {
  background: #2a2a2a; /* dark mode background */
}
body.dark-mode {
  --bg-body: #121212;
  --bg-sidebar: #1e1e1e;
  --bg-card: #1f1f1f;
  --bg-main: #181818;
  --text-color: #eee;
  --text-light: #fff;
  --card-shadow: rgba(0, 0, 0, 0.5);
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

/* Search & Trending Box Dark Mode */
body.dark-mode .bg-gradient-to-r.from-pink-50.to-purple-50 {
  background: #1f1f1f !important; /* dark background */
  border-color: #333 !important;
}

body.dark-mode .text-gray-800,
body.dark-mode .text-gray-700,
body.dark-mode .text-purple-600,
body.dark-mode .text-pink-700 {
  color: #fff !important; /* make text white */
}

body.dark-mode input[type="text"],
body.dark-mode textarea,
body.dark-mode select {
  background: #2a2a2a !important;
  color: #fff !important;
  border-color: #444 !important;
}

body.dark-mode input::placeholder,
body.dark-mode textarea::placeholder {
  color: #aaa !important;
}

body.dark-mode button {
  color: #fff !important;
}
/* 🔥 Posts & Replies - Dark Mode */
body.dark-mode .space-y-6 .bg-white {
  background: #1f1f1f !important;   /* dark background */
  border-color: #333 !important;
}

body.dark-mode .space-y-6 .text-gray-800,
body.dark-mode .space-y-6 .text-gray-700,
body.dark-mode .space-y-6 .text-purple-600,
body.dark-mode .space-y-6 .text-pink-600,
body.dark-mode .space-y-6 .text-xs.text-gray-400 {
  color: #fff !important;   /* make all text white */
}

/* Replies inside posts */
body.dark-mode .space-y-6 .bg-gray-50 {
  background: #2a2a2a !important;
  color: #fff !important;
  border-color: #444 !important;
}

/* Reaction buttons stay colorful but with better contrast */
body.dark-mode .space-y-6 button {
  color: #fff !important;
}

/* 🔥 Reaction Buttons - Dark Mode */
body.dark-mode .space-y-6 .flex.gap-2 button,
body.dark-mode .space-y-6 .flex.gap-1 button {
  background: #2a2a2a !important;  /* dark background */
  color: #fff !important;          /* white text */
  border: 1px solid #444;          /* subtle border */
}

/* Keep emoji bright */
body.dark-mode .space-y-6 button span:first-child {
  filter: none !important;
}
/* 🔥 Trending Topics - Dark Mode */
body.dark-mode .bg-gradient-to-r.from-pink-50.to-purple-50 {
  background: #1f1f1f !important;   /* dark background */
  border-color: #333 !important;
}

body.dark-mode .bg-gradient-to-r.from-pink-50.to-purple-50 h3,
body.dark-mode .bg-gradient-to-r.from-pink-50.to-purple-50 i,
body.dark-mode .bg-gradient-to-r.from-pink-50.to-purple-50 .text-gray-800 {
  color: #fff !important; /* make heading & icons white */
}

body.dark-mode .bg-gradient-to-r.from-pink-50.to-purple-50 a {
  background: #2a2a2a !important;   /* dark chip background */
  color: #fff !important;           /* white text */
  border: 1px solid #444;
}

body.dark-mode .bg-gradient-to-r.from-pink-50.to-purple-50 a:hover {
  background: #444 !important;      /* hover effect */
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
/* ===== Mobile Optimization Fix ===== */
@media (max-width: 768px) {
  html, body {
    margin: 0;
    padding: 0;
    width: 100%;
    height: 100%;
    overflow-x: hidden; /* prevent horizontal scroll */
  }

  .dashboard {
    display: flex;
    min-height: 100vh;
    width: 100%;
  }

  main.main-content {
    flex: 1;
    overflow-y: auto; /* scroll posts & sections */
    padding: 8px;
    transition: margin-left 0.3s; /* optional smooth push effect */
  }

  .sidebar {
    width: 250px;
    flex-shrink: 0; 
    position: fixed;
    top: 0;
    left: -250px; /* hidden by default */
    height: 100%;
    z-index: 2;
    transition: left 0.3s;
  }

  .sidebar.show {
    left: 0; /* show when toggled */
  }

  /* Optional: push main content when sidebar is open */
  main.main-content.shifted {
    margin-left: 250px;
  }

  /* Posts, replies, forms stretch full width */
  .space-y-6,
  .bg-white,
  .bg-gray-50,
  .bg-gradient-to-r {
    width: 100% !important;
    margin: 0 0 12px 0;
    border-radius: 12px;
  }

  input, textarea, select, button {
    width: 100% !important;
  }

  /* Remove side margins for content */
  section.px-4.lg\:px-0 {
    padding: 0 !important;
  }
}

.main-content {
    flex: 1;                    /* take remaining space */
    padding: 40px 50px;         /* content padding */
         /* space for sidebar on desktop */
    transition: margin-left 0.3s, padding 0.3s;
    position: relative;          /* aligns with fixed sidebar */
    top: 0;                      /* starts at top of viewport */
    min-height: 100vh;           /* full height */
    background: var(--bg-main);
    box-sizing: border-box;
}



.sidebar.collapsed + .main-content {
    margin-left: 0;
    padding-left: 20px;    
}


/* Topbar */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 50px;
}

.topbar h1 {
   font-family: 'Playfair Display', serif;
    font-size: 2.8rem;
    color: var(--text-heading);
    position: relative;
    
}

.topbar h1::after {
    content: "";
    width: 60px;
    height: 4px;
    background: var(--text-heading);
    position: absolute;
    left: 0;
    bottom: -8px;
    border-radius: 2px;
}




/* Mobile version */
@media (max-width: 768px) {
  .topbar {
    display: flex;
    flex-direction: column;
    height: 0vh; /* full viewport height */
    padding: 0px;
    position: relative;
    margin-bottom: 200px;
   
  }

  
  .top-row {
    display: flex;
    align-items: center;
    width: 100%;
    gap: 10px;
    justify-content: space-between; 
  }
  
   /* Sidebar logo */
  .sidebar .logo {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    text-align: center;
    color: #fff;
    margin-top: 40px;
    letter-spacing: 1px;
  }

 #sidebarToggle {
  position: fixed !important;     /* stays in viewport */
  top: 15px;                      
  left: 15px;                    
  z-index: 9999 !important;       
  width: 40px !important;         
  height: 40px !important;
  font-size: 18px;
  background: linear-gradient(135deg, #ff6b81, #ff9a9e);
  border: none;
  border-radius: 10px;
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
  transition: all 0.25s ease;
}

#sidebarToggle:hover {
  transform: scale(1.05);
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
}


  .top-row h1 {
    flex: none;              /* don't stretch it */
  font-size: 25px;
  margin: 0;               /* remove big right margin */
  white-space: nowrap;     /* keep text in one line */
     margin-left: 20px;
     margin-top: 50px;
  }

  /* Profile section at bottom */
  .profile {

    display: flex;               /* use flexbox */
  justify-content: center;     /* center horizontally */
  align-items: center;         /* center vertically */
    padding: 5px 15px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.08);
    margin-top: 20px;
    
  }

  .profile img {
    width: 40px;
    height: 40px;
    
    
  }

  .username-text,
  .logout-btn,
  #theme-toggle,
  .verified-badge {
    font-size: 14px;
  }
  .logout-btn {
    
    margin-right: 0px;
    height: 35px;
    width: 100px;
  }
  #theme-toggle {
    font-size: 30px;     /* bigger on mobile */
    width: 20px;
    height: 20px;
    margin-bottom: 25px;
   margin-left: 0px;
  }
  .username-text {
  white-space: nowrap;    /* prevent wrapping */
  text-overflow: ellipsis; /* optional: show "..." if text is too long */
}
}
@media (max-width: 768px) {
  form button[type="submit"] {
    width: 90px !important;   /* override Tailwind width */
    padding: 0.25rem 0.5rem !important; /* smaller padding */
  }

  form button[type="submit"] i {
    font-size: 0.75rem !important; /* smaller icon */
  }
}

/* 📱 Mobile Notification Styles */
@media (max-width: 768px) {
  .notif-dropdown {
    left: 50%;                          /* center horizontally */
    top: 60px;                          /* slightly below bell */
    transform: translateX(48%);        /* center alignment */
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

</style>
</head>
<body>

<div class="dashboard">
<aside class="sidebar">
  <h2 class="logo">EmpowerHer</h2>
  <nav>
    <ul>
      <li><a href="dashboard.php" class="flex items-center gap-2"><i class="fas fa-home"></i> Dashboard</a></li>
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



      
      <li><a href="chatbot.php" class="flex items-center gap-2"><i class="fas fa-comments"></i> AI Chatbot</a></li>
      <li><a href="modules.php" class="flex items-center gap-2"><i class="fas fa-book"></i> Learning Modules</a></li>
      <li class="active.php"><a href="community.php" class="flex items-center gap-2"><i class="fas fa-users"></i> Community</a></li>
      <li><a href="feedback.php" class="flex items-center gap-2"><i class="fas fa-hand-holding-heart"></i> Feedback</a></li>
    
    </ul>
  </nav>
</aside>

<main class="main-content">
<header class="topbar">
   <button id="sidebarToggle" class="sidebar-toggle"><i class="fas fa-bars"></i></button>
  <div class="top-row">
   
    <h1>Connect with the community</h1>
  </div>

  <div class="profile">
    <button id="theme-toggle" class="theme-toggle"><i class="fas fa-moon"></i></button>
    <img src="<?php echo htmlspecialchars($profilePic); ?>?v=<?php echo time(); ?>" 
     alt="Profile" class="profile-img" id="profilePicDisplay">
    <span class="username-text"><?php echo htmlspecialchars($userName); ?></span>
    <?php if ($userName === 'Joshua Andres' || $userName === 'Issay' || $userName === 'Mia Khalifa'): ?>
      <span class="verified-badge" title="Verified Owner"><i class="fas fa-check-circle"></i></span>
    <?php endif; ?>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</header>

<section class="px-4 lg:px-0">

<!-- Search -->
<div class="mb-6 flex justify-center">
  <div class="w-full lg:w-11/12 bg-gradient-to-r from-pink-50 to-purple-50 rounded-2xl p-6 shadow-md border border-pink-100">
    <form method="GET" class="relative flex items-center">
      <input 
        type="text" 
        name="search" 
        placeholder="Search the topic or post..." 
        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
        class="w-full border border-pink-300 rounded-full px-6 py-3 shadow-md focus:outline-none focus:ring-2 focus:ring-pink-400 text-gray-800 placeholder-gray-400 text-sm transition"
      >
      <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-pink-500 hover:bg-pink-600 text-white px-5 py-2 rounded-full shadow-md transition flex items-center gap-2">
        <i class="fas fa-search"></i> Search
      </button>
    </form>
  </div>
</div>

<!-- Trending -->
<div class="mb-6 flex justify-center">
  <div class="w-full lg:w-11/12 bg-gradient-to-r from-pink-50 to-purple-50 rounded-2xl p-5 shadow-md border border-pink-100">
    <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2"><i class="fas fa-fire text-pink-500"></i> Trending Topics</h3>
    <div class="flex flex-wrap gap-2">
      <?php foreach ($topTopics as $topic => $count): ?>
  <a href="community.php?search=<?= urlencode($topic) ?>" class="bg-pink-100 hover:bg-pink-200 text-pink-700 font-medium px-3 py-1 rounded-full shadow-sm text-sm"><?= htmlspecialchars($topic) ?> (<?= $count ?>)</a>
<?php endforeach; ?>
    </div>
  </div>
</div>

<!-- New Post -->
<?php if (!empty($content_alert)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-3" role="alert">
        <strong class="font-bold">Content Alert!</strong>
        <span class="block sm:inline"><?= $content_alert ?></span>
    </div>
<?php endif; ?>
<div class="bg-gradient-to-r from-pink-50 to-purple-50 p-5 rounded-2xl shadow mb-6 border border-pink-100 w-full lg:w-11/12 mx-auto">
  <h2 class="text-xl font-bold text-pink-600 mb-3 flex items-center gap-2">
    <i class="fas fa-pen-nib"></i> Start a New Discussion
  </h2>
  <form method="POST" class="space-y-3" enctype="multipart/form-data">
    <input type="hidden" name="new_post" value="1">

    <!-- ✅ Topic must be selected -->
    <select name="topic" required 
      class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-pink-400 outline-none text-sm">
      <option value="" disabled selected>Select or type topic</option>
      <?php foreach($predefinedTopics as $topicOption): ?>
        <option value="<?= htmlspecialchars($topicOption) ?>"><?= htmlspecialchars($topicOption) ?></option>
      <?php endforeach; ?>
    </select>

    <input type="text" name="title" placeholder="Discussion Title" 
      class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-pink-400 outline-none text-sm">

    <textarea name="content" rows="3" placeholder="Write your story or question..." 
      class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-pink-400 outline-none text-sm"></textarea>

    <!-- Media upload -->
    <label for="post-media" class="flex items-center gap-2 cursor-pointer text-pink-500 hover:text-pink-600 text-sm">
      <i class="fas fa-image"></i> Add Image/Video
    </label>
    <input type="file" name="media" id="post-media" accept="image/*,video/*" class="hidden">

    <!-- 👇 Preview container -->
    <div id="mediaPreview" class="mt-2"></div>

    <button type="submit" class="bg-pink-500 hover:bg-pink-600 transition text-white px-4 py-1 rounded-full shadow text-sm">
      <i class="fas fa-paper-plane"></i> Post
    </button>
  </form>
</div>



<!-- Posts -->
<div class="space-y-6 w-full lg:w-11/12 mx-auto">
<?php while ($post = $posts->fetch_assoc()): ?>
<?php $postProfile = !empty($post['profile_pic']) ? $post['profile_pic'] : "images/profile.jpg"; ?>
<div class="bg-white p-5 rounded-2xl shadow-md border border-gray-100 hover:shadow-xl transition" data-post-id="<?= $post['id'] ?>">

  <div class="flex items-start justify-between mb-2">
    <div class="flex items-center gap-2">
      <img src="<?php echo htmlspecialchars($postProfile); ?>?v=<?php echo time(); ?>"  alt="Profile" class="w-8 h-8 rounded-full object-cover border border-pink-300">
      <span class="font-medium text-gray-800 text-sm">
        <?= htmlspecialchars($post['user_name']) ?>
        <?php if ($post['user_name'] === 'Joshua Andres' || $post['user_name'] === 'Issay'  || $post['user_name'] === 'Mia Khalifa'): ?>
          <span title="Verified Owner" class="ml-1 inline-block text-blue-500"><i class="fas fa-check-circle"></i></span>
        <?php endif; ?>
      </span>
    </div>
    
    <span class="text-xs text-gray-400"><?= date("M d, Y", strtotime($post['created_at'])) ?>
</span>
  </div>
  
  
  <h3 class="text-lg font-semibold text-pink-600"><?= htmlspecialchars($post['title']) ?></h3>
  <p class="text-xs text-purple-600 font-medium mb-1">Topic: <?= htmlspecialchars($post['topic'] ?: 'Uncategorized') ?></p>
  <p class="text-gray-700 text-sm leading-relaxed"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
  <?php if(!empty($post['media'])): ?>
    <?php if(preg_match('/\.(mp4|webm|ogg)$/i', $post['media'])): ?>
      <video src="<?= $post['media'] ?>" controls class="w-full mt-2 rounded-lg"></video>
    <?php else: ?>
      <img src="<?= $post['media'] ?>" alt="Post Media" class="w-full mt-2 rounded-lg">
    <?php endif; ?>
  <?php endif; ?>
  
  



<!-- Post Reactions -->
<?php 
  $post_id = $post['id'];
  $reactions = $conn->query("SELECT reaction_type, COUNT(*) as cnt FROM post_reactions WHERE post_id=$post_id GROUP BY reaction_type");
  $reactionCounts = [];
  while($r = $reactions->fetch_assoc()) $reactionCounts[$r['reaction_type']] = $r['cnt'];
?>
<div class="mt-2 flex gap-2">
  <?php foreach($reactionMap as $type => $data): ?>
    <button type="button"
            class="flex items-center gap-1 px-2 py-1 rounded-full hover:scale-110 transition <?= $data['color'] ?>"
            onclick="react(<?= $post_id ?>, null, '<?= $type ?>')">
      <span><?= $data['emoji'] ?></span>
      <span id="reaction-count-<?= $type ?>-post-<?= $post_id ?>" class="text-xs">
        <?= $reactionCounts[$type] ?? 0 ?>
      </span>
    </button>
  <?php endforeach; ?>
</div>


<!-- edit post button -->

<?php if ($post['user_id'] == $userId || $user_is_admin): ?>

  <div class="mt-2 flex gap-2">

    <!-- ✅ EDIT (owner OR admin) -->
    <button class="flex-0 text-sm bg-blue-500 hover:bg-blue-600 text-white px-5 py-1 rounded-full shadow edit-btn"
            data-post-id="<?= $post['id'] ?>"
            data-title="<?= htmlspecialchars($post['title'], ENT_QUOTES) ?>"
            data-content="<?= htmlspecialchars($post['content'], ENT_QUOTES) ?>"
            data-topic="<?= htmlspecialchars($post['topic'], ENT_QUOTES) ?>">
      Edit
    </button>

    <!-- ✅ DELETE (owner OR admin) -->
    <button class="flex-0 text-sm bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-full shadow"
            onclick="deletePost(event, <?= $post['id'] ?>)">
      <?= $user_is_admin && $post['user_id'] != $userId ? 'Admin Delete' : 'Delete' ?>
    </button>

  </div>

<?php endif; ?>






<!-- Replies -->
<div class="mt-3 pl-3 border-l-2 border-pink-200 space-y-2">
  <?php
    $replies = $conn->query("
        SELECT r.*, u.profile_pic 
        FROM replies r 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE r.post_id=$post_id 
        ORDER BY r.created_at ASC
    ");
    while ($reply = $replies->fetch_assoc()):
      $reply_id = $reply['id'];
      $replyProfile = !empty($reply['profile_pic']) ? $reply['profile_pic'] : "images/profile.jpg";
      $reactions = $conn->query("SELECT reaction_type, COUNT(*) as cnt FROM post_reactions WHERE reply_id=$reply_id GROUP BY reaction_type");
      $reactionCounts = [];
      while($r = $reactions->fetch_assoc()) $reactionCounts[$r['reaction_type']] = $r['cnt'];
  ?>
  <div class="bg-gray-50 p-2 rounded-lg shadow-sm text-sm relative"
     data-reply-id="<?= $reply_id ?>">
    <div class="flex items-center gap-2 mb-1">
      <img src="<?php echo htmlspecialchars($replyProfile); ?>?v=<?php echo time(); ?>"  alt="Profile" class="w-6 h-6 rounded-full object-cover border border-purple-300">
      <span class="text-sm font-medium text-gray-700">
        <?= htmlspecialchars($reply['user_name']) ?>
        <?php if ($reply['user_name'] === 'Joshua Andres' || $reply['user_name'] === 'Issay' || $reply['user_name'] === 'Mia Khalifa'): ?>
          <span title="Verified Owner" class="ml-1 inline-block text-blue-500"><i class="fas fa-check-circle"></i></span>
        <?php endif; ?>
      </span>
      <span class="text-xs text-gray-400">· <?= date("M d, Y", strtotime($post['created_at'])) ?></span>
    </div>
    <p><?= nl2br(htmlspecialchars($reply['reply'])) ?></p>
    <?php if(!empty($reply['media'])): ?>
      <?php if(preg_match('/\.(mp4|webm|ogg)$/i', $reply['media'])): ?>
        <video src="<?= $reply['media'] ?>" controls class="w-full mt-1 rounded-lg"></video>
      <?php else: ?>
        <img src="<?= $reply['media'] ?>" alt="Reply Media" class="w-full mt-1 rounded-lg">
      <?php endif; ?>
    <?php endif; ?>
    

<!-- Reply Reactions (AJAX) -->
<div class="mt-1 flex gap-1">
  <?php foreach($reactionMap as $type => $data): ?>
    <button type="button"
            class="flex items-center gap-1 px-2 py-1 rounded-full hover:scale-110 transition <?= $data['color'] ?>"
            onclick="react(null, <?= $reply_id ?>, '<?= $type ?>')">
      <span><?= $data['emoji'] ?></span>
      <span id="reaction-count-<?= $type ?>-reply-<?= $reply_id ?>" class="text-xs">
        <?= $reactionCounts[$type] ?? 0 ?>
      </span>
    </button>
  <?php endforeach; ?>
</div>



    <!-- Edit & Delete Reply -->
    <?php if($reply['user_id'] == $userId): ?>
      <div class="mt-2 flex gap-2">
        <!-- Edit Button -->
        <button class="flex-0 text-sm bg-blue-500 hover:bg-blue-600 text-white px-5 py-1 rounded-full shadow text-center edit-reply-btn pr-5.5 pl-5.5"
                data-reply-id="<?= $reply_id ?>"
                data-reply="<?= htmlspecialchars($reply['reply'], ENT_QUOTES) ?>"
                data-media="<?= htmlspecialchars($reply['media'], ENT_QUOTES) ?>">
          Edit
        </button>

        <!-- Delete Button -->
        <form onsubmit="deleteReply(event, <?= $reply_id ?>)">
          <button type="submit" class=" flex-0 text-sm bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-full shadow text-center delete-btn mr-64 ">
            Delete
          </button>
        </form>
      </div>
      
    <?php endif; ?>

  </div>
  <?php endwhile; ?>
  
  
  
  
  
  
  

  <!-- Reply Form -->
  <form method="POST" class="reply-form mt-2 space-y-1" enctype="multipart/form-data">
    <input type="hidden" name="new_reply" value="1">
    <input type="hidden" name="post_id" value="<?= $post_id ?>">
    <textarea name="reply" rows="2" placeholder="Write a reply..." class="w-full border rounded-lg px-3 py-1 focus:ring-2 focus:ring-purple-400 outline-none text-sm"></textarea>
    <label for="reply-media-<?= $post_id ?>" class="flex items-center gap-2 cursor-pointer text-purple-500 hover:text-purple-600 text-sm"><i class="fas fa-image"></i> Add Image/Video</label>
    <input type="file" name="media" id="reply-media-<?= $post_id ?>" accept="image/*,video/*" class="hidden">
    <button type="submit" class="bg-purple-500 hover:bg-purple-600 transition text-white px-3 py-1 rounded-full shadow text-sm">Reply</button>
  </form>
</div>
</div>
<?php endwhile; ?>
</div>
</section>
</main>
</div>

<!-- Edit Post Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 px-4">
  <div class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-md md:max-w-lg shadow-2xl transform transition-all p-6">
    
    <!-- Header -->
    <div class="flex justify-between items-center border-b pb-3 mb-5">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
        Edit Post
      </h2>
      <button type="button" id="closeModal" 
              class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <!-- Form -->
    <form method="POST" action="edit_post.php" class="space-y-5">
      <input type="hidden" name="post_id" id="editPostId">
      
      <!-- Title -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
        <input type="text" name="title" id="editTitle" 
               class="w-full border rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" 
               required>
      </div>

      <!-- Topic Dropdown -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Topic</label>
        <select name="topic" id="editTopic" required
                class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-pink-400 focus:border-pink-400 outline-none">
          <option value="" disabled selected>Select a topic</option>
          <?php foreach($predefinedTopics as $topicOption): ?>
            <option value="<?= htmlspecialchars($topicOption) ?>"><?= htmlspecialchars($topicOption) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Content -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Content</label>
        <textarea name="content" id="editContent" rows="4" 
                  class="w-full border rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none" 
                  required></textarea>
      </div>

    
        <button type="submit" 
                class="px-5 py-2 text-sm font-medium rounded-lg bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow hover:opacity-90 transition">
          Save Changes
        </button>
      </div>
    </form>
  </div>
</div>



<!-- Edit Reply Modal -->
<div id="editReplyModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
  <div class="bg-white rounded-lg shadow-lg  w-11/12 max-w-md p-4 relative mx-auto my-8">
    <h2 class="text-lg font-semibold mb-2">Edit Reply</h2>
    <form id="editReplyForm" enctype="multipart/form-data">
      <input type="hidden" name="reply_id" id="edit-reply-id">
      <textarea name="reply" id="edit-reply-text" rows="3" class="w-full border rounded-lg px-2 py-1 focus:ring-2 focus:ring-purple-400 outline-none text-sm"></textarea>
      <label for="edit-reply-media" class="flex items-center gap-2 cursor-pointer text-purple-500 hover:text-purple-600 text-sm mt-2">
        <i class="fas fa-image"></i> Change Image/Video
      </label>
      <input type="file" name="media" id="edit-reply-media" accept="image/*,video/*" class="hidden">
      <div class="mt-3 flex justify-end gap-2">
        <button type="button" class="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400" onclick="closeEditModal()">Cancel</button>
        <button type="submit" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">Save</button>
      </div>
    </form>
  </div>
</div>
<!-- edit reply tanggal alert -->
<div class="reply-item" data-reply-id="<?= $reply_id ?>">
  <p><?= htmlspecialchars($reply['reply']) ?></p>
  <?php if(!empty($reply['media'])): ?>
    <?php if(preg_match('/\.(mp4|webm|ogg)$/i', $reply['media'])): ?>
      <video src="<?= $reply['media'] ?>" controls class="w-full mt-2 rounded-lg"></video>
    <?php else: ?>
      <img src="<?= $reply['media'] ?>" class="w-full mt-2 rounded-lg">
    <?php endif; ?>
  <?php endif; ?>
</div>



<script>
    // Edit reply button
document.querySelectorAll('.edit-reply-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const replyId = btn.dataset.replyId;
    const replyText = btn.dataset.reply;

    document.getElementById('edit-reply-id').value = replyId;
    document.getElementById('edit-reply-text').value = replyText;

    document.getElementById('editReplyModal').classList.remove('hidden');
  });
});

// Close modal
function closeEditModal() {
  document.getElementById('editReplyModal').classList.add('hidden');
}

// Handle form submit via AJAX
document.getElementById('editReplyForm').addEventListener('submit', function(e) {
  e.preventDefault();

  const formData = new FormData(this);

  fetch('edit_reply.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if(data.status === 'success') {
      // Update the reply text on the page
      const replyDiv = document.querySelector(`[data-reply-id='${formData.get('reply_id')}'] p`);
      if(replyDiv) {
        replyDiv.innerText = formData.get('reply');

        // Optional: update the media if a new one was uploaded
        const mediaInput = document.getElementById('edit-reply-media');
        if(mediaInput.files.length > 0) {
          const replyContainer = document.querySelector(`[data-reply-id='${formData.get('reply_id')}']`);
          
          // Remove old media if exists
          const oldMedia = replyContainer.querySelector('img, video');
          if(oldMedia) oldMedia.remove();

          const file = mediaInput.files[0];
          const fileURL = URL.createObjectURL(file);

          let newMedia;
          if(file.type.startsWith('image/')) {
            newMedia = document.createElement('img');
            newMedia.src = fileURL;
            newMedia.className = "w-full mt-2 rounded-lg";
          } else if(file.type.startsWith('video/')) {
            newMedia = document.createElement('video');
            newMedia.src = fileURL;
            newMedia.controls = true;
            newMedia.className = "w-full mt-2 rounded-lg";
          }
          if(newMedia) replyContainer.appendChild(newMedia);
        }
      }

      closeEditModal();
    }
  })
  .catch(err => console.error(err));
});

    
    
    
    //edit post to
   document.querySelectorAll(".edit-btn").forEach(btn => {
  btn.addEventListener("click", () => {
    document.getElementById("editPostId").value = btn.dataset.postId;
    document.getElementById("editTitle").value = btn.dataset.title;
    document.getElementById("editContent").value = btn.dataset.content;
    document.getElementById("editTopic").value = btn.dataset.topic;
    document.getElementById("editModal").classList.remove("hidden");
    document.getElementById("editModal").classList.add("flex");
  });
});

document.getElementById("closeModal").addEventListener("click", () => {
  document.getElementById("editModal").classList.add("hidden");
});
    
    
    
    
    
    
    
    
const toggleBtn = document.getElementById("theme-toggle");
const body = document.body;
if (localStorage.getItem("theme") === "dark") {
  body.classList.add("dark-mode");
  toggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
}
toggleBtn.addEventListener("click", () => {
  body.classList.toggle("dark-mode");
  if (body.classList.contains("dark-mode")) {
    localStorage.setItem("theme", "dark");
    toggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
  } else {
    localStorage.setItem("theme", "light");
    toggleBtn.innerHTML = '<i class="fas fa-moon"></i>';
  }
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















async function react(postId, replyId, reactionType) {
  try {
    const fd = new FormData();
    if (postId) fd.append('post_id', postId);
    if (replyId) fd.append('reply_id', replyId);
    fd.append('reaction_type', reactionType);

    // POST to react (created above)
    const res = await fetch('react.php', {
      method: 'POST',
      body: fd,
      credentials: 'same-origin' // important so PHP session cookie is sent
    });

    if (!res.ok) {
      console.error('Network response not ok', res.status);
      return;
    }

    const data = await res.json();

    if (!data.success) {
      console.error('Server error:', data.error || data);
      return;
    }

    // update the correct count element
    const id = postId ? `reaction-count-${reactionType}-post-${postId}` : `reaction-count-${reactionType}-reply-${replyId}`;
    const el = document.getElementById(id);
    if (el) el.textContent = data.count;

    // (optional) add/remove an "active" class to button to show user's reaction
    // you can implement class toggling here if desired

  } catch (err) {
    console.error('React request failed', err);
  }
}















async function deleteReply(e, replyId) {
  e.preventDefault();

  if (!confirm("Are you sure you want to delete this reply?")) return;

  const fd = new FormData();
  fd.append("delete_reply", 1);
  fd.append("reply_id", replyId);

  const res = await fetch("community.php", {
    method: "POST",
    body: fd,
    credentials: "same-origin"
  });

  if (res.ok) {
    // Remove the reply from DOM
    document.querySelector(`[data-reply-id="${replyId}"]`)?.remove();
  }
}












document.addEventListener("DOMContentLoaded", () => {

  // 1️⃣ Handle Enter key to submit reply
  document.querySelectorAll('.reply-form textarea').forEach(textarea => {
    textarea.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault(); // prevent new line
        this.closest('form').dispatchEvent(new Event('submit')); // trigger form submit
      }
    });
  });

  // 2️⃣ Handle AJAX form submission
  document.querySelectorAll(".reply-form").forEach(form => {
    form.addEventListener("submit", async function (e) {
      e.preventDefault(); // 🚫 stop page reload

      const formData = new FormData(this);

      try {
        let response = await fetch("add_reply.php", {
          method: "POST",
          body: formData
        });

        let data = await response.json();

        if (data.success) {
          // Build the new reply HTML
          const replyHtml = `
            <div class="bg-gray-50 p-2 rounded-lg shadow-sm text-sm relative" data-reply-id="${data.reply_id}">
              <div class="flex items-center gap-2 mb-1">
                <img src="${data.profile_pic}" class="w-6 h-6 rounded-full object-cover border border-purple-300">
                <span class="text-sm font-medium text-gray-700">
                  ${data.user_name}
                  ${data.is_verified ? '<span class="ml-1 text-blue-500"><i class="fas fa-check-circle"></i></span>' : ''}
                </span>
                <span class="text-xs text-gray-500 ml-auto">${data.created_at}</span>
              </div>
              <p>${data.reply}</p>
              ${data.media ? `<div class="mt-1"><img src="${data.media}" class="max-h-40 rounded-lg"></div>` : ""}
            </div>
          `;

          // Insert reply above the form
          this.insertAdjacentHTML("beforebegin", replyHtml);

          // Reset the form (clear textarea & file input)
          this.reset();
        } else {
          alert("Failed to add reply: " + (data.error ?? "Unknown error"));
        }
      } catch (err) {
        console.error("Reply error:", err);
        alert("Something went wrong submitting your reply.");
      }
    });
  });

});








async function deletePost(e, postId) {
  e.preventDefault(); // 🚫 prevent full page reload

  if (!confirm("Are you sure you want to delete this post?")) return;

  const fd = new FormData();
  fd.append("delete_post", 1);
  fd.append("post_id", postId);

  const res = await fetch("community.php", {
    method: "POST",
    body: fd,
    credentials: "same-origin"
  });

  if (res.ok) {
    // Remove the post from DOM without reload
    document.querySelector(`[data-post-id="${postId}"]`)?.remove();
  }
}








const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.querySelector('.sidebar');
const mainContent = document.querySelector('.main-content');

sidebarToggle.addEventListener('click', () => {
    if (window.innerWidth <= 768) {
        // Mobile toggle
        sidebar.classList.toggle('show');
        mainContent.classList.toggle('shifted');
    } else {
        // Desktop toggle
        sidebar.classList.toggle('collapsed');
    }
});






document.getElementById('post-media').addEventListener('change', function () {
    const preview = document.getElementById('mediaPreview');
    preview.innerHTML = ''; // Clear previous preview
    const file = this.files[0];

    if (file) {
        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.className = "w-40 h-40 object-cover rounded-lg shadow mt-2";
            preview.appendChild(img);
        } else if (file.type.startsWith('video/')) {
            const video = document.createElement('video');
            video.src = URL.createObjectURL(file);
            video.controls = true;
            video.className = "w-64 rounded-lg shadow mt-2";
            preview.appendChild(video);
        }
    }
});

</script>
</body>
</html>
