<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? "Mama";

// Database connection
$conn = new mysqli("localhost", "u739446465_empowerher_db", "u739446465_Empowerher_db@", "u739446465_empowerher_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch user profile picture
$sqlUser = "SELECT profile_pic FROM users WHERE id = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("i", $userId);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$userData = $resultUser->fetch_assoc();
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
<title>EmpowerHer – AI Chatbot</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Raleway:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="icon" type="image/png" sizes="34x34" href="images/logo7.png">

<link rel="icon" type="image/png" sizes="64x64" href="images/logo7.png">
<link rel="icon" type="image/png" sizes="192x192" href="images/logo7.png">

<style>
:root {
    --color-primary: #ff6b6b;
    --color-secondary: #ff8ea3;
    --color-bg: #f5f5f5;
    --color-dark: #1e1e1e;
    --color-card: linear-gradient(135deg, #fff5f7, #ffe6f0);
    --color-text: #333;
    --color-dark-text: #eee;
    --transition-speed: 0.4s;
    
}

/* Reset */
* { margin:0; padding:0; box-sizing:border-box; font-family:'Raleway',sans-serif; }
body { display:flex; height:100vh; background:var(--color-bg); color:var(--color-text); transition: background var(--transition-speed), color var(--transition-speed); }

/* Sidebar default (desktop) */
.sidebar {
    background: linear-gradient(180deg, var(--color-primary), var(--color-secondary));
    width: 250px;
    padding: 30px 20px;
    color:#fff;
    display:flex;
    flex-direction:column;
    border-top-right-radius:25px;
    border-bottom-right-radius:25px;
    box-shadow: 6px 0 25px rgba(0,0,0,0.15);
    position: relative;
  z-index: 2;
  transition: background 0.4s;
}
body.dark-mode .msg.bot .bubble {
    background: #3a3a3a;
    color: #fff;
  }
 body.dark-mode .avatar {
  background: #444;  /* solid dark grey */
  color: #eee;       /* light robot icon */
}
body.dark-mode .msg.user .bubble {
  background: #3a3a3a;  /* darker blue */
  color: #fff;
}
body.dark-mode #send {
  background: #444;   /* solid dark grey */
  color: #eee;        /* lighter icon */
}
body.dark-mode .question-btn {
  background: #ff9a9e;
  
  color: #eee;
  border: 1px solid #555;
}
body.dark-mode .chat-header h1 {
  color: #ff9a9e;  /* example: soft pink */
}

/* Mobile: sidebar hidden by default */
@media (max-width: 768px) {
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
  
   .chat-header {
    padding: 0px 12px;       /* reduce inner spacing */
    height: 50px;            /* smaller header height */
    border-radius: 0 !important;
  }

  .chat-header h1 {
    font-size: 1.2rem;       /* smaller title */
  }

  .chat-header button {
    font-size: 1rem;         /* smaller toggle/mode buttons */
    padding: 6px 8px;
  }

   .chat-body {
       margin-top: 105px;
      
  }

  .chat-body .msg.bot:first-child .bubble {
    margin-top: 0;  /* reset */
    margin-left: 8px;
    margin-right: 8px;
  }
  body.dark-mode .msg.bot .bubble {
    background: #3a3a3a;
    color: #fff;
  }
  body.dark-mode .msg.user .bubble {
  background: #3a3a3a;  /* darker blue */
  color: #fff;
}

  .avatar {
    width: 35px;
    height: 35px;
    font-size: 0.8rem;
    margin-top: 0;  /* reset */
  }
  body.dark-mode .avatar {
  background: #444;  /* solid dark grey */
  color: #eee;       /* light robot icon */
}
body.dark-mode #send {
  background: #444;   /* solid dark grey */
  color: #eee;        /* lighter icon */
}
body.dark-mode .question-btn {
  background: #ff9a9e;
  color: #eee;
  border: 1px solid #555;
}
/* Dark mode */
body.dark-mode .chat-header h1 {
  color: #ff9a9e;
}


 
    .notif-dropdown {
    left: 50%;                /* center horizontally */
    top: 60px;                /* push down below bell */
    transform: translateX(-50%); /* center alignment */
    width: 90vw;              /* responsive width */
    max-width: 170px;         /* cap width on larger phones */
    border-radius: 26px;
    padding: 15px;
    z-index: 5000;
     box-shadow: 0 8px 20px rgba(0,0,0,0.2);
  }

  .notif-badge {
    top: -4px;
    right: -4px;
    font-size: 11px;
    padding: 2px 5px;
  }

    
    /* Chat container takes full width */
    .chat-container {
        width: 100%;
        margin-left: 0;
        border-radius: 0;
    }
}
.sidebar.dark-mode { background:#111; color:#eee; }
.sidebar .logo { font-family: 'Playfair Display', serif; font-size:2rem; margin-bottom:50px; text-align:center; letter-spacing:1px; }
.sidebar nav ul { list-style:none; padding:0; margin:0; }
.sidebar nav ul li {
    margin: 20px 0;
    font-size:1.1rem;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:15px;
    padding:12px 14px;
    border-radius:14px;
    transition: all 0.3s ease;
}
.sidebar nav ul li:hover,
.sidebar nav ul li.active { background: rgba(255,255,255,0.15); transform: translateX(5px); }
.sidebar nav ul li a {
    color: #fff;
    font-size: 1.1rem;
    text-decoration: none;
    transition: color 0.3s ease;
}
.sidebar nav ul li.active a,
.sidebar nav ul li:hover a {
    color: #fff;
}



/* Notifications */
.notification-bell { position: relative; display:flex; align-items:center; gap:6px; }
.notif-badge { position:absolute; top:-6px; right:-6px; background:red; color:#fff; border-radius:50%; font-size:12px; padding:2px 6px; font-weight:600; }
.notif-dropdown {
    display:none;
    position:absolute;
    left:270px;
    top:50px;
    background:var(--color-card);
    width:360px;
    border-radius:12px;
    box-shadow:0 8px 20px rgba(0,0,0,0.15);
    z-index:1000;
    padding:12px;
    color:var(--color-text);
}
.notif-dropdown.dark-mode { background:#222; color:#eee; }
.notif-dropdown h4 { margin:5px 0 12px; font-size:1rem; font-weight:600; }
.notif-list { max-height:320px; overflow-y:auto; }
.notif-item { display:flex; align-items:flex-start; gap:12px; padding:12px; border-radius:10px; transition: all 0.2s ease; text-decoration:none; color:inherit; }
.notif-item:hover { background: rgba(0,0,0,0.05); }
.notif-item img { width:45px; height:45px; border-radius:50%; object-fit:cover; }
.notif-text { font-size:0.95rem; flex:1; }
.notif-text strong { color:var(--color-text); }
.notif-dropdown.dark-mode .notif-text strong { color:#fff; }
.notif-text small { display:block; color:#777; margin-top:2px; }
.notif-dropdown.dark-mode .notif-text small { color:#ccc; }

/* Chat container */
.chat-container { flex:1; display:flex; flex-direction:column; background: var(--color-card); transition: background var(--transition-speed), color var(--transition-speed); border-top-left-radius:15px; border-top-right-radius:15px; }
.chat-container.dark-mode { background:#111; color:#eee; }

/* Chat Header */
.chat-header { flex-shrink:0; background: linear-gradient(135deg,var(--color-secondary),var(--color-primary)); color:#fff; padding:20px 30px; display:flex; justify-content:space-between; align-items:center; border-top-left-radius:15px; border-top-right-radius:15px; transition: all var(--transition-speed); box-shadow:0 4px 12px rgba(0,0,0,0.1); }
.chat-header.dark-mode { background:#222; color:#fff; }

/* Dark mode toggle button */
.mode-toggle {
    background:rgba(255,255,255,0.2);
    border:none;
    cursor:pointer;
    font-size:1.3rem;
    padding:10px 14px;
    border-radius:50%;
    color:#fff;
    transition: all var(--transition-speed);
}
.mode-toggle.dark-mode { color:#ffcc00; background:rgba(255,255,255,0.1); }

/* Chat Body */
.chat-body { flex:1; padding:25px; overflow-y:auto; display:flex; flex-direction:column; gap:18px; }

/* Pre-made questions */
.quick-questions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    padding: 10px 25px;
    background: var(--color-card);
    border-top: 1px solid #ddd;
}
.quick-questions.dark-mode {
    background: #222;
    border-color: #444;
}
.question-btn {
    background: linear-gradient(135deg, var(--color-secondary), var(--color-primary));
    color: #fff;
    border: none;
    border-radius: 20px;
    padding: 8px 14px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: transform 0.2s ease;
}
.question-btn:hover { transform: translateY(-2px); }

.chat-input { flex-shrink:0; display:flex; padding:18px 25px; background:var(--color-card); border-top:1px solid #ddd; transition: all var(--transition-speed); border-radius:0 0 15px 15px; }
.chat-input.dark-mode { background:#222; border-color:#444; }
.chat-input input { flex:1; padding:14px 18px; border:1px solid #ddd; border-radius:30px; outline:none; font-size:1rem; background:#fff; color:#333; transition: all var(--transition-speed); box-shadow: inset 0 2px 4px rgba(0,0,0,0.08); }
.chat-input.dark-mode input { background:#333; color:#eee; border-color:#555; }
.chat-input button { margin-left:14px; background:linear-gradient(135deg,var(--color-secondary),var(--color-primary)); border:none; color:#fff; padding:14px 22px; border-radius:30px; cursor:pointer; box-shadow:0 4px 12px rgba(0,0,0,0.12); transition: transform 0.2s ease; }
.chat-input button:hover { transform: translateY(-2px); }

/* Messages */
.msg { display:flex; align-items:flex-start; gap:12px; max-width:75%; animation:fadeIn 0.35s ease-in; }
.msg.user { align-self:flex-end; flex-direction:row-reverse; }
.msg.bot { align-self:flex-start; }
.avatar { width:45px; height:45px; border-radius:50%; background:linear-gradient(135deg,var(--color-secondary),var(--color-primary)); display:flex; justify-content:center; align-items:center; color:#fff; font-size:1rem; flex-shrink:0; box-shadow:0 2px 8px rgba(0,0,0,0.15); }
.bubble { padding:16px 20px; border-radius:22px; line-height:1.5; font-size:1rem; word-wrap:break-word; box-shadow:0 6px 15px rgba(0,0,0,0.08); transition: background var(--transition-speed), color var(--transition-speed); }
.msg.user .bubble { background:linear-gradient(135deg,var(--color-secondary),var(--color-primary)); color:#fff; border-bottom-right-radius:6px; }
.msg.bot .bubble { background:#f0f0f0; border:1px solid #eee; border-bottom-left-radius:6px; color:#333; }
.msg.bot.dark-mode .bubble { background:#222; border-color:#444; color:#eee; }

/* Animations */
@keyframes fadeIn { from {opacity:0; transform:translateY(15px);} to {opacity:1; transform:translateY(0);} }

/* Dark mode body */
body.dark-mode { background:#121212; color:#eee; }





.typing-temp .bubble {
    font-style: italic;
    opacity: 0.7;
}



.sidebar-toggle {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 1.2rem;
    padding: 8px 12px;
    border-radius: 8px;
    cursor: pointer;
    margin-right: 15px;
    transition: background 0.3s ease;
}
.sidebar-toggle:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Collapsed sidebar style */
.sidebar.collapsed {
    width: 0;
    padding: 0;
    overflow: hidden;
    border-radius: 0;
}

/* Make chat container take full width when sidebar is hidden */
.sidebar.collapsed ~ .chat-container {
    flex: 1;
    width: 100%;
}
/* Hide sidebar on small screens (mobile) */
@media (max-width: 768px) {
    

    .chat-container {
        margin-left: 0; /* take full width */
        width: 100%;
    }

    
}
/* Make chat-container full height and flex column */
.chat-container {
    display: flex;
    flex-direction: column;
    height: 100vh;
}

/* Chat header fixed at top */
.chat-header {
    flex-shrink: 0;
    position: sticky;
    top: 0;
    z-index: 10;
}

/* Chat body scrollable */
.chat-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}

/* Quick questions styling */
.quick-questions {
    flex-wrap: wrap;
    gap: 10px;
    padding: 10px 20px;
}

/* Quick questions toggle button */
.quick-questions-toggle {
    flex-shrink: 0;
}

/* Chat input fixed at bottom */
.chat-input {
    flex-shrink: 0;
    display: flex;
    padding: 15px 20px;
    background: var(--color-card);
    border-top: 1px solid #ddd;
}

/* Mobile adjustments */
@media (max-width: 768px) {
    .chat-container {
        width: 100%;
        border-radius: 0;
    }
    .chat-body {
        padding: 15px;
    }
    .quick-questions {
        padding: 10px 15px;
    }
    .chat-input {
        padding: 10px 15px;
    }
}

/* Mobile: hide quick questions by default */
@media (max-width: 768px) {
    .quick-questions {
        display: none; /* hidden initially on mobile */
    }
}

.typing-indicator .dots::after {
  content: '';
  display: inline-block;
  animation: dots 1.5s steps(3, end) infinite;
}

@keyframes dots {
  0%   { content: ''; }
  33%  { content: '.'; }
  66%  { content: '..'; }
  100% { content: '...'; }
}



@media (max-width: 768px) {
    .sidebar-toggle {
        padding: 8px 12px !important;
        font-size: 1.1rem !important;
    }
}

@media (max-width: 768px) {
    .mode-toggle {
        font-size: 1.1rem !important;
        padding: 8px 12px !important;
        
      
    }
}
</style>
</head>
<body>

 <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">

      <h2 class="logo">EmpowerHer</h2>
      <nav>
        <ul>
          <li><i class="fas fa-home"></i><a href="dashboard.php">Dashboard</a></li>
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
                  <p style="padding:10px;">No new notifications.</p>
                <?php endif; ?>
              </div>
              <div class="notif-footer">
                <a id="markAllRead">Mark all as read</a>
                <a href="community.php">View all</a>
              </div>
            </div>
          </li>
          <li class="active"><i class="fas fa-comments"></i> <a href="chatbot.php" style = "text-decoration: none; color: white;">AI Chatbot</a></li>
          <li><i class="fas fa-book"></i> <a href="modules.php" style = "text-decoration: none; color: white;">Learning Modules</a></li>
          <li><i class="fas fa-users"></i><a href="community.php" style = "text-decoration: none; color: white;">Community</a></li>
         <li><i class="fas fa-hand-holding-heart"></i><a href="feedback.php" style = "text-decoration: none; color: white;">Feedback</a></li>
          


        </ul>
      </nav>
    </aside>


<!-- Chat container -->
<div class="chat-container" id="chatContainer">
   <div class="chat-header" id="chatHeader">
    <!-- NEW TOGGLE BUTTON -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fa-solid fa-bars"></i>
    </button>

    <h1>EmpowerHer AI Chatbot</h1>
    <button class="mode-toggle" id="toggleMode"><i class="fa-solid fa-moon"></i></button>
</div>

  <div id="chatBody" class="chat-body">
    <div class="msg bot">
        <div class="avatar"><i class="fa-solid fa-robot"></i></div>
        <div class="bubble">
            Welcome, <?php echo htmlspecialchars($userName); ?>! 🌸  
            I’m <strong>Nova</strong>, your supportive companion 🤖✨  
            Ask me anything about parenting, self-care, or daily challenges 💬
        </div>
    </div>
</div>

<!-- Toggle Questions Button -->
<div style="padding: 10px 25px; text-align:right;">
    <button id="toggleQuestions" class="question-btn" style="background:#555;">Show/Hide Questions</button>
</div>



<!-- Pre-made clickable questions -->
<div class="quick-questions" id="quickQuestions">
    <button class="question-btn">How to take care of a newborn?</button>
    <button class="question-btn">Best tips for self-care?</button>
    <button class="question-btn">How to handle toddler tantrums?</button>
    <button class="question-btn">Ideas for healthy family meals?</button>
    <button class="question-btn">How to improve my sleep schedule?</button>
    <button class="question-btn">Ways to manage parenting stress?</button>
    <button class="question-btn">Fun indoor activities for kids?</button>
    <button class="question-btn">Tips for balancing work and family?</button>
    <button class="question-btn">Ways to save money as a parent?</button>
    <button class="question-btn">How to boost my confidence as a parent?</button>
</div>



   <div class="chat-input" id="chatInputDiv">
    <input id="input" type="text" placeholder="Type your message...">
    <button id="send"><i class="fa-solid fa-paper-plane"></i></button>
</div>

</div>

<script>
const chatBody=document.getElementById('chatBody');
const input=document.getElementById('input');
const send=document.getElementById('send');
const toggleModeBtn=document.getElementById('toggleMode');
const body=document.body;
const chatContainer=document.getElementById('chatContainer');
const chatHeader=document.getElementById('chatHeader');
const chatInputDiv=document.getElementById('chatInputDiv');
const sidebar=document.getElementById('sidebar');
const quickQuestions=document.getElementById('quickQuestions');
let history=[];

function addMessage(role,text){
    const msg=document.createElement('div');
    msg.className='msg '+role;
    msg.innerHTML=`<div class="avatar"><i class="fa-solid ${role==='user'?'fa-user':'fa-robot'}"></i></div>
                   <div class="bubble">${text}</div>`;
    chatBody.appendChild(msg);
    chatBody.scrollTop=chatBody.scrollHeight;
}

async function sendMessage(){
    const text=input.value.trim();
    if(!text) return;
    addMessage('user',text);
    history.push({role:'user',content:text});
    input.value='';
    try{
        const res=await fetch('chatbot_api.php',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({history})
        });
        const data=await res.json();
        const reply=data.reply||"⚠️ Error, no reply.";
        addMessage('bot',reply);
        history.push({role:'assistant',content:reply});
    }catch(e){
        addMessage('bot',"⚠️ Server error. Check API key and connection.");
    }
}

send.addEventListener('click',sendMessage);
input.addEventListener('keydown',e=>{ if(e.key==='Enter') sendMessage(); });



// Dark mode toggle
if(localStorage.getItem('theme')==='dark'){
    body.classList.add('dark-mode');
    chatContainer.classList.add('dark-mode');
    chatHeader.classList.add('dark-mode');
    chatInputDiv.classList.add('dark-mode');
    sidebar.classList.add('dark-mode');
    quickQuestions.classList.add('dark-mode');
    document.querySelectorAll('.notif-dropdown').forEach(d=>d.classList.add('dark-mode'));
    toggleModeBtn.innerHTML='<i class="fa-solid fa-sun"></i>';
}

toggleModeBtn.addEventListener('click',()=>{
    const isDark = !body.classList.contains('dark-mode');
    body.classList.toggle('dark-mode', isDark);
    chatContainer.classList.toggle('dark-mode', isDark);
    chatHeader.classList.toggle('dark-mode', isDark);
    chatInputDiv.classList.toggle('dark-mode', isDark);
    sidebar.classList.toggle('dark-mode', isDark);
    quickQuestions.classList.toggle('dark-mode', isDark);
    document.querySelectorAll('.notif-dropdown').forEach(d=>d.classList.toggle('dark-mode', isDark));
    toggleModeBtn.innerHTML = isDark ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';
    localStorage.setItem('theme', isDark ? 'dark':'light');
});

// Notifications toggle
const bell=document.getElementById("notifBell");
const dropdown=document.getElementById("notifDropdown");
bell.addEventListener("click",()=>{ dropdown.style.display=dropdown.style.display==="block"?"none":"block"; });
document.addEventListener("click",e=>{ if(!bell.contains(e.target)) dropdown.style.display="none"; });

// Quick questions handling
quickQuestions.addEventListener('click', e => {
    if(e.target.classList.contains('question-btn')){
        const question = e.target.textContent;
        input.value = question;
        sendMessage(); // send only once
    }
});

// Toggle show/hide quick questions
const toggleQuestions = document.getElementById('toggleQuestions');
toggleQuestions.addEventListener('click', () => {
    if (quickQuestions.style.display === 'none') {
        quickQuestions.style.display = 'flex';
    } else {
        quickQuestions.style.display = 'none';
    }
});












function addMessage(role, text, isTemp = false) {
    const msg = document.createElement('div');
    msg.className = 'msg ' + role;

    if (isTemp) {
        // Typing indicator with animated dots
        msg.innerHTML = `
            <div class="avatar"><i class="fa-solid fa-robot"></i></div>
            <div class="bubble typing-indicator">Nova is typing<span class="dots"></span></div>
        `;
        msg.classList.add("typing-temp");
    } else {
        // Normal message
        msg.innerHTML = `
            <div class="avatar"><i class="fa-solid ${role === 'user' ? 'fa-user' : 'fa-robot'}"></i></div>
            <div class="bubble">${text}</div>
        `;
    }

    chatBody.appendChild(msg);
    chatBody.scrollTop = chatBody.scrollHeight;
    return msg;
}

async function sendMessage() {
    const text = input.value.trim();
    if (!text) return;

    // User bubble
    addMessage('user', text);
    history.push({ role: 'user', content: text });
    input.value = '';

    // Show typing bubble with animated dots
    const typingBubble = addMessage('bot', "", true);

    try {
        const res = await fetch('chatbot_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ history })
        });
        const data = await res.json();
        const reply = data.reply || "⚠️ Error, no reply.";

        // Remove typing bubble
        typingBubble.remove();

        // Add AI's real reply
        addMessage('bot', reply);
        history.push({ role: 'assistant', content: reply });
    } catch (e) {
        typingBubble.remove();
        addMessage('bot', "⚠️ Server error. Check API key and connection.");
    }
}




const sidebarToggle = document.getElementById('sidebarToggle');

sidebarToggle.addEventListener('click', () => {
  if (window.innerWidth <= 768) {
    sidebar.classList.toggle('active');
  } else {
    sidebar.classList.toggle('collapsed');
  }

  if (
    (window.innerWidth <= 768 && sidebar.classList.contains('active')) ||
    (window.innerWidth > 768 && !sidebar.classList.contains('collapsed'))
  ) {
    sidebarToggle.innerHTML = '<i class="fa-solid fa-xmark"></i>';
  } else {
    sidebarToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
  }
});



</script>
</body>
</html>
