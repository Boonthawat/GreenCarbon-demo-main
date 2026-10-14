<?php
// ลืมรหัสผ่าน: ยืนยันตัวตนด้วยคำถามและคำตอบกันลืมที่เลือกไว้ตอนสมัคร
require 'init.php';

if ($_POST) {
    $u = one('SELECT * FROM users WHERE email = ?', [p('email')]);
    if (!$u || $u['question'] != p('question') || !password_verify(p('answer'), $u['answer'])) go('forgot.php', 'อีเมล คำถาม หรือคำตอบไม่ถูกต้อง', 'danger');
    if ($err = check_pass()) go('forgot.php', $err, 'danger');
    q('UPDATE users SET password = ? WHERE id = ?', [password_hash(p('password'), PASSWORD_DEFAULT), $u['id']]);
    go('login.php', 'ตั้งรหัสผ่านใหม่แล้ว กรุณาเข้าสู่ระบบ');
}

head('ลืมรหัสผ่าน');
?>
<form method="post" class="card card-body mx-auto" style="max-width:420px">
  <?php input('อีเมลที่ใช้สมัคร', 'email', 'email') ?>
  <?php question() ?>
  <?php input('คำตอบ', 'answer') ?>
  <?php input('รหัสผ่านใหม่ (อย่างน้อย 8 ตัวอักษร)', 'password', 'password', '', 'required minlength="8"') ?>
  <?php input('ยืนยันรหัสผ่านใหม่', 'password2', 'password') ?>
  <button class="btn btn-success mt-3">ตั้งรหัสผ่านใหม่</button>
</form>
<?php foot() ?>
