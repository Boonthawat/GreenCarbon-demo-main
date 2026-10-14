<?php
// แชทกับผู้ดูแลระบบ: ทุกข้อความแจ้งเตือนไปยังผู้ดูแล (สมาชิกลบแชทไม่ได้ ผู้ดูแลลบแชททั้งหมดได้ที่ admin/issues.php)
require 'init.php';
$u = need_login();

if ($_POST) {
    if (p('body') == '') go('issue.php', 'กรุณาพิมพ์ข้อความ', 'danger');
    q('INSERT INTO messages (user_id, sender_id, body) VALUES (?, ?, ?)', [$u['id'], $u['id'], mb_substr(p('body'), 0, 500)]);
    notify(null, 'ข้อความจาก ' . $u['name'], p('body'), 'admin/issues.php?user=' . $u['id']);
    go('issue.php');
}

head('แชทกับผู้ดูแลระบบ');
?>
<p class="text-muted">พิมพ์ปัญหาหรือข้อเสนอแนะ ผู้ดูแลระบบจะตอบกลับที่นี่ และคุณจะได้รับการแจ้งเตือนที่ 🔔</p>
<?php chat($u['id']) ?>
<?php foot() ?>
