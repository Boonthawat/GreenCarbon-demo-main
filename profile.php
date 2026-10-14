<?php
require 'init.php';
$u = need_login();

if (isset($_POST['save'])) {
    if ($err = check_user($u['id'])) go('profile.php', $err, 'danger');
    $avatar = upload('avatar');
    if (!$avatar && bad_upload('avatar')) go('profile.php', bad_upload('avatar'), 'danger');
    q('UPDATE users SET name = ?, email = ?, phone = ?, avatar = ? WHERE id = ?', [p('name'), p('email'), p('phone'), $avatar ?: $u['avatar'], $u['id']]);
    go('profile.php', 'บันทึกโปรไฟล์แล้ว');
}
if (isset($_POST['change'])) {
    if (!password_verify(p('current'), $u['password'])) go('profile.php', 'รหัสผ่านปัจจุบันไม่ถูกต้อง', 'danger');
    if ($err = check_pass()) go('profile.php', $err, 'danger');
    q('UPDATE users SET password = ? WHERE id = ?', [password_hash(p('password'), PASSWORD_DEFAULT), $u['id']]);
    go('profile.php', 'เปลี่ยนรหัสผ่านแล้ว');
}

head('โปรไฟล์ของฉัน');
?>
<div class="row">
  <div class="col-lg-6">
    <form method="post" enctype="multipart/form-data" class="card card-body mb-3">
      <div class="text-center"><?= pic($u['avatar'], 110, '👤', true) ?><div class="mt-2"><?= level($u['points']) ?> · <?= $u['points'] ?> แต้ม</div></div>
      <?php input('เปลี่ยนรูปโปรไฟล์ (JPG PNG GIF ไม่เกิน 2MB)', 'avatar', 'file', '', 'accept="image/*"') ?>
      <?php input('ชื่อ-นามสกุล', 'name', 'text', $u['name']) ?>
      <?php input('อีเมล', 'email', 'email', $u['email']) ?>
      <?php input('เบอร์โทรศัพท์ (ตัวเลข 10 หลัก)', 'phone', 'text', $u['phone'], 'required pattern="0[0-9]{9}" maxlength="10" inputmode="numeric" title="ตัวเลข 10 หลัก ขึ้นต้นด้วย 0"') ?>
      <button class="btn btn-success mt-3" name="save">บันทึก</button>
    </form>
  </div>
  <div class="col-lg-6">
    <form method="post" class="card card-body mb-3">
      <h5>เปลี่ยนรหัสผ่าน</h5>
      <?php input('รหัสผ่านปัจจุบัน', 'current', 'password') ?>
      <?php input('รหัสผ่านใหม่ (อย่างน้อย 8 ตัวอักษร)', 'password', 'password', '', 'required minlength="8"') ?>
      <?php input('ยืนยันรหัสผ่านใหม่', 'password2', 'password') ?>
      <button class="btn btn-outline-success mt-3" name="change">เปลี่ยนรหัสผ่าน</button>
    </form>
  </div>
</div>
<?php foot() ?>
