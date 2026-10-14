<?php
require 'init.php';

if ($_POST) {
    $err = check_user() ?: check_pass();
    if (!$err && (!in_array(p('question'), QUESTIONS) || mb_strlen(p('answer')) < 2)) $err = 'กรุณาเลือกคำถามกันลืมรหัสผ่าน และกรอกคำตอบ';
    $avatar = $err ? '' : upload('avatar');
    if (!$err && !$avatar) $err = bad_upload('avatar');
    if (!$err) {
        q('INSERT INTO users (name, email, phone, avatar, password, question, answer) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [p('name'), p('email'), p('phone'), $avatar, password_hash(p('password'), PASSWORD_DEFAULT), p('question'), password_hash(p('answer'), PASSWORD_DEFAULT)]);
        session_regenerate_id(true);
        $_SESSION['uid'] = $db->lastInsertId();
        notify(null, 'สมาชิกใหม่', p('name') . ' (' . p('email') . ')', 'admin/users.php');
        notify($_SESSION['uid'], 'ยินดีต้อนรับสู่ GreenLoop', 'เริ่มส่งขยะอิเล็กทรอนิกส์ไปรีไซเคิลและสะสมแต้มได้เลย', 'recycle.php');
        go('recycle.php', 'สมัครสมาชิกสำเร็จ เริ่มส่งขยะอิเล็กทรอนิกส์ไปรีไซเคิลได้เลย');
    }
    flash($err);
}

head('สมัครสมาชิก');
?>
<form method="post" enctype="multipart/form-data" class="card card-body mx-auto" style="max-width:520px">
  <?php input('ชื่อ-นามสกุล', 'name', 'text', p('name')) ?>
  <?php input('อีเมล', 'email', 'email', p('email')) ?>
  <?php input('เบอร์โทรศัพท์ (ตัวเลข 10 หลัก)', 'phone', 'text', p('phone'), 'required pattern="0[0-9]{9}" maxlength="10" inputmode="numeric" title="ตัวเลข 10 หลัก ขึ้นต้นด้วย 0"') ?>
  <?php input('รูปโปรไฟล์ (ไม่บังคับ)', 'avatar', 'file', '', 'accept="image/*"') ?>
  <?php input('รหัสผ่าน (อย่างน้อย 8 ตัวอักษร)', 'password', 'password', '', 'required minlength="8"') ?>
  <?php input('ยืนยันรหัสผ่าน', 'password2', 'password') ?>
  <?php question(p('question')) ?>
  <?php input('คำตอบ (ใช้ตั้งรหัสผ่านใหม่เมื่อลืม)', 'answer', 'text', p('answer')) ?>
  <button class="btn btn-success mt-3">สมัครสมาชิก</button>
</form>
<?php foot() ?>
