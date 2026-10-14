<?php
require 'init.php';

if ($_POST) {
    $u = one('SELECT * FROM users WHERE email = ?', [p('email')]);
    if (!$u || !password_verify(p('password'), $u['password'])) go('login.php', 'อีเมลหรือรหัสผ่านไม่ถูกต้อง', 'danger');
    if ($u['status'] != 'active') go('login.php', 'บัญชีนี้ถูกระงับสิทธิ์การใช้งาน', 'danger');
    session_regenerate_id(true);
    $_SESSION['uid'] = $u['id'];
    go($u['role'] == 'admin' ? 'admin/index.php' : 'index.php', 'ยินดีต้อนรับ ' . $u['name']);
}

head('เข้าสู่ระบบ');
?>
<form method="post" class="card card-body mx-auto" style="max-width:420px">
  <?php input('อีเมล', 'email', 'email') ?>
  <?php input('รหัสผ่าน', 'password', 'password') ?>
  <button class="btn btn-success mt-3">เข้าสู่ระบบ</button>
  <div class="d-flex justify-content-between mt-3">
    <a href="forgot.php">ลืมรหัสผ่าน?</a>
    <a href="register.php">สมัครสมาชิก</a>
  </div>
</form>
<?php foot() ?>
