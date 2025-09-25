<?php
/* login.php — Sign In + Register (single file) */
// require __DIR__ . '/db_connect.php';
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
require_once 'admin/db_connect.php';

// register.php  (Sign In + Register in one file)
// require __DIR__ . '/db_connect.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// DEV: show PHP errors in browser so AJAX error shows useful info
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Create users table (matches your screenshot)
$conn->query("
  CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name TEXT NOT NULL,
    address TEXT NOT NULL,
    contact VARCHAR(50) NOT NULL,
    user_type TINYINT(1) NOT NULL DEFAULT 3,
    username TEXT NOT NULL,
    password TEXT NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ---------- AJAX endpoints on same page ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
  header('Content-Type: text/plain; charset=utf-8');

  if ($_GET['action'] === 'register') {
    $name     = trim($_POST['name'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $contact  = trim($_POST['contact'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($name==='' || $address==='' || $contact==='' || $username==='' || strlen($password)<6) { echo '0'; exit; }

    // unique username check
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute(); $stmt->store_result();
    if ($stmt->num_rows > 0) { echo 'exists'; $stmt->close(); exit; }
    $stmt->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $user_type = 3;
    $stmt = $conn->prepare("INSERT INTO users (name,address,contact,user_type,username,password) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param('sssiss', $name,$address,$contact,$user_type,$username,$hash);
    $ok = $stmt->execute();
    $stmt->close();

    echo $ok ? '1' : '0';
    exit;
  }

  if ($_GET['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    $stmt = $conn->prepare("SELECT id,name,username,password,user_type FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    $ok = false;
    if ($row) {
      $stored = (string)$row['password'];
      $info = password_get_info($stored);
      if (!empty($info['algo'])) {
        $ok = password_verify($password, $stored);
      } else {
        // old plaintext fallback
        $ok = hash_equals($stored, $password);
        if ($ok) {
          $new = password_hash($password, PASSWORD_DEFAULT);
          $u = $conn->prepare("UPDATE users SET password=? WHERE id=?");
          $u->bind_param('si', $new, $row['id']); $u->execute(); $u->close();
        }
      }
    }

    if ($ok) {
      $_SESSION['login_id']        = (int)$row['id'];
      $_SESSION['login_name']      = $row['name'];
      $_SESSION['login_username']  = $row['username'];
      $_SESSION['login_user_type'] = (int)$row['user_type'];
      echo ($row['user_type']==1) ? '1' : '2';
    } else {
      echo '0';
    }
    exit;
  }

  echo '0'; exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fashion  • Sign In / Register</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
<style>
  :root{--gold:#d4af37;--line:#e8e8e8}
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1a1a1a 0%,#2d1b2e 25%,#1a1a1a 50%,#2e2420 75%,#1a1a1a 100%);overflow:hidden}
  .bg{position:fixed;inset:0;pointer-events:none;z-index:1}
  .bg i{position:absolute;opacity:.04;color:var(--gold);animation:float 24s ease-in-out infinite}
  .bg i:nth-child(1){font-size:6rem;left:-6%;top:10%}
  .bg i:nth-child(2){font-size:5rem;right:-6%;bottom:15%;animation-delay:8s}
  .bg i:nth-child(3){font-size:7rem;left:10%;bottom:-8%;animation-delay:16s}
  @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-14px)}}
  .wrap{position:relative;z-index:2;width:100%;max-width:520px;padding:1rem}
  .card{background:rgba(255,255,255,.98);backdrop-filter:blur(28px);border-radius:26px;padding:2.2rem 1.8rem;border:1px solid rgba(212,175,55,.12);box-shadow:0 36px 72px rgba(0,0,0,.22);position:relative;overflow:hidden}
  .card::before{content:'';position:absolute;left:0;right:0;top:0;height:5px;background:linear-gradient(90deg,#d4af37,#f4e4bc,#d4af37);background-size:200% 100%;animation:shimmer 4s ease-in-out infinite}
  @keyframes shimmer{0%,100%{background-position:-100% 0}50%{background-position:100% 0}}
  .head{text-align:center;margin-bottom:.8rem}.brand{font-size:2.2rem;color:#d4af37;margin-bottom:.5rem}
  h1{font-family:'Playfair Display',serif;font-size:1.8rem}.sub{color:#666;font-size:.9rem;letter-spacing:1px;text-transform:uppercase;margin-top:.2rem}
  .tabs{display:flex;gap:.6rem;margin:1rem 0 1.1rem}.tab{flex:1;text-align:center;padding:.7rem;border:1px solid var(--line);border-radius:12px;font-weight:700;cursor:pointer}.tab.active{background:linear-gradient(135deg,#d4af37,#f4e4bc);border-color:transparent;color:#111}
  .pane{display:none}.pane.active{display:block}
  .form-group{margin-bottom:.8rem}label{display:block;margin-bottom:.35rem;font-weight:600}
  .input-wrap{position:relative}
  .form-control{width:100%;padding:.85rem 1rem .85rem 3rem;border:2px solid #e8e8e8;border-radius:14px;background:#f7f7f7;transition:.25s}
  textarea.form-control{min-height:80px;padding-left:1rem}
  .form-control:focus{outline:none;border-color:#d4af37;background:#fff;box-shadow:0 0 0 4px rgba(212,175,55,.12)}
  .icon{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#999}
  .btn{width:100%;padding:.95rem;border:none;border-radius:14px;font-weight:800;letter-spacing:1.4px;text-transform:uppercase;background:linear-gradient(135deg,#d4af37 0%,#f4e4bc 50%,#d4af37 100%);color:#111;cursor:pointer;transition:.25s}
  .btn:hover{transform:translateY(-2px);box-shadow:0 12px 26px rgba(212,175,55,.35)}
  .btn:disabled{opacity:.7;cursor:not-allowed;transform:none}
  .spinner{width:18px;height:18px;border:2px solid rgba(17,17,17,.3);border-top-color:#111;border-radius:50%;display:none;margin-left:.5rem;animation:spin 1s linear infinite;vertical-align:-3px}
  @keyframes spin{to{transform:rotate(360deg)}}
  .alert{padding:.85rem 1rem;border-radius:14px;margin-bottom:.8rem;display:flex;gap:.6rem;align-items:center}
  .alert-danger{background:#fee2e2;border:1px solid #f87171;color:#b91c1c}
  .alert-success{background:#dcfce7;border:1px solid #22c55e;color:#166534}
  .muted{text-align:center;margin-top:.7rem;color:#666}.muted a{color:#b8941f;text-decoration:none}.muted a:hover{text-decoration:underline}
</style>
</head>
<body>
  <div class="bg"><i class="fas fa-gem"></i><i class="fas fa-crown"></i><i class="fas fa-star"></i></div>
  <div class="wrap">
    <div class="card">
      <div class="head">
        <div class="brand"><i class="fas fa-tshirt"></i></div>
        <h1>Fashion Admin</h1>
        <div class="sub">Style • Elegance • Luxury</div>
      </div>

      <div class="tabs">
        <div class="tab active" data-tab="login">Sign In</div>
        <div class="tab" data-tab="register">Register</div>
      </div>

      <div id="alert-wrap"></div>

      <!-- LOGIN -->
      <div id="pane-login" class="pane active">
        <form id="login-form" autocomplete="off">
          <div class="form-group">
            <label for="l-username">Username</label>
            <div class="input-wrap">
              <input type="text" id="l-username" name="username" class="form-control" required placeholder="your username">
              <i class="fas fa-user icon"></i>
            </div>
          </div>
          <div class="form-group">
            <label for="l-password">Password</label>
            <div class="input-wrap">
              <input type="password" id="l-password" name="password" class="form-control" required placeholder="your password">
              <i class="fas fa-lock icon"></i>
            </div>
          </div>
          <button type="submit" class="btn" id="loginBtn"><span class="btn-text">Sign In</span><span class="spinner" id="spinLogin"></span></button>
        </form>
        <div class="muted">No account? <a href="#" class="go-register">Create one</a></div>
      </div>

      <!-- REGISTER -->
      <div id="pane-register" class="pane">
        <form id="reg-form" autocomplete="off">
          <div class="form-group">
            <label for="r-name">Full name</label>
            <div class="input-wrap">
              <input type="text" id="r-name" name="name" class="form-control" placeholder="Jane Doe" required>
              <i class="fas fa-id-card icon"></i>
            </div>
          </div>
          <div class="form-group">
            <label for="r-address">Address</label>
            <div class="input-wrap">
              <textarea id="r-address" name="address" class="form-control" placeholder="Street, City, Country" required></textarea>
            </div>
          </div>
          <div class="form-group">
            <label for="r-contact">Contact</label>
            <div class="input-wrap">
              <input type="text" id="r-contact" name="contact" class="form-control" placeholder="+250 783442098" required>
              <i class="fas fa-phone icon"></i>
            </div>
          </div>
          <div class="form-group">
            <label for="r-username">Username</label>
            <div class="input-wrap">
              <input type="text" id="r-username" name="username" class="form-control" placeholder="jane" required>
              <i class="fas fa-user icon"></i>
            </div>
          </div>
          <div class="form-group">
            <label for="r-password">Password</label>
            <div class="input-wrap">
              <input type="password" id="r-password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
              <i class="fas fa-lock icon"></i>
            </div>
          </div>
          <div class="form-group">
            <label for="r-confirm">Confirm password</label>
            <div class="input-wrap">
              <input type="password" id="r-confirm" name="confirm" class="form-control" placeholder="Re-enter your password" required>
              <i class="fas fa-check icon"></i>
            </div>
          </div>
          <button type="submit" class="btn" id="regBtn"><span class="btn-text">Create Account</span><span class="spinner" id="spinReg"></span></button>
        </form>
        <div class="muted">Already registered? <a href="#" class="go-login">Sign in</a></div>
      </div>
    </div>
  </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
  // tabs
  $('.tab').on('click', function(){
    $('.tab').removeClass('active'); $(this).addClass('active');
    const t=$(this).data('tab'); $('.pane').removeClass('active'); $('#pane-'+t).addClass('active');
    $('#alert-wrap').empty();
  });
  $('.go-register').on('click', e=>{e.preventDefault();$('.tab[data-tab=register]').click();});
  $('.go-login').on('click', e=>{e.preventDefault();$('.tab[data-tab=login]').click();});

  function alertBox(type,msg){
    $('#alert-wrap').html(
      `<div class="alert alert-${type}">
         <i class="fas ${type==='danger'?'fa-exclamation-triangle':'fa-check-circle'}"></i>
         <span>${msg}</span>
       </div>`
    );
  }

  // helper: same-page endpoint
  const endpoint = window.location.pathname.replace(/\/+$/,'') + '?';

  // LOGIN
  $('#login-form').on('submit', function(e){
    e.preventDefault();
    const $btn=$('#loginBtn'), $spin=$('#spinLogin'), $txt=$('#loginBtn .btn-text');
    $btn.prop('disabled',true); $spin.show(); $txt.text('Signing in…');
    $.ajax({
      url: endpoint + 'action=login',
      method:'POST',
      data: $(this).serialize(),
      dataType:'text'
    }).done(function(resp){
      resp=(resp||'').trim();
      if(resp==='1'){ location.href='index.php?page=home'; }
      else if(resp==='2'){ location.href='index.php'; }
      else { alertBox('danger','Username or password is incorrect.'); }
    }).fail(function(xhr){
      alertBox('danger', 'Network error. ' + (xhr.status ? ('HTTP '+xhr.status) : '') + ' ' + (xhr.responseText||''));
    }).always(function(){ $btn.prop('disabled',false); $spin.hide(); $txt.text('Sign In'); });
  });

  // REGISTER
  $('#reg-form').on('submit', function(e){
    e.preventDefault();
    const pass=$('#r-password').val(), confirm=$('#r-confirm').val();
    if(pass.length<6){ alertBox('danger','Password must be at least 6 characters.'); return; }
    if(pass!==confirm){ alertBox('danger','Passwords do not match.'); return; }

    const $btn=$('#regBtn'), $spin=$('#spinReg'), $txt=$('#regBtn .btn-text');
    $btn.prop('disabled',true); $spin.show(); $txt.text('Creating…');

    $.ajax({
      url: endpoint + 'action=register',
      method:'POST',
      data:{
        name: $('#r-name').val().trim(),
        address: $('#r-address').val().trim(),
        contact: $('#r-contact').val().trim(),
        username: $('#r-username').val().trim(),
        password: pass
      },
      dataType:'text'
    }).done(function(resp){
      resp=(resp||'').trim();
      if(resp==='1'){ alertBox('success','Account created! You can sign in now.'); setTimeout(()=>$('.tab[data-tab=login]').click(), 800); }
      else if(resp==='exists'){ alertBox('danger','Username already taken.'); }
      else { alertBox('danger','Registration failed.'); }
    }).fail(function(xhr){
      alertBox('danger', 'Network error. ' + (xhr.status ? ('HTTP '+xhr.status) : '') + ' ' + (xhr.responseText||''));
    }).always(function(){ $btn.prop('disabled',false); $spin.hide(); $txt.text('Create Account'); });
  });
</script>
</body>
</html>
