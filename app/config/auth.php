<?php
require_once __DIR__ . '/db.php';
function current_user(){
  try{ global $pdo; $pdo->query("SELECT 1 FROM users LIMIT 1"); } catch(Exception $e){ return null; }
  if(!empty($_SESSION['user_id'])){
    static $cache=null; if($cache && empty($_SESSION['__flush_user_cache'])) return $cache;
    global $pdo; $st=$pdo->prepare("SELECT id,name,email,username,role,phone,avatar FROM users WHERE id=?"); $st->execute([$_SESSION['user_id']]); $cache=$st->fetch(); $_SESSION['__flush_user_cache']=false; return $cache?:null;
  } return null;
}
function is_logged_in(){ return !!current_user(); }
function ensure_login(){ if(!is_logged_in()){ header('Location: '.url('login.php')); exit; } }
function ensure_role($roles){ $u=current_user(); if(!$u){ header('Location: '.url('login.php')); exit; } if(is_string($roles)) $roles=[$roles]; if(!in_array($u['role'],$roles,true)){ http_response_code(403); echo "<h3>Forbidden</h3>"; exit; } }
function login($username,$password){
  global $pdo; try{$st=$pdo->prepare("SELECT id,password_hash FROM users WHERE username=?"); $st->execute([$username]);}catch(Exception $e){ return false; }
  $row=$st->fetch(); if($row && password_verify($password,$row['password_hash'])){ $_SESSION['user_id']=$row['id']; log_event('login','user',$row['id'],null); return true; } return false;
}
function logout(){ if(!empty($_SESSION['user_id'])) log_event('logout','user',$_SESSION['user_id'],null); $_SESSION=[]; if(ini_get('session.use_cookies')){ $p=session_get_cookie_params(); setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']); } session_destroy(); }
?>