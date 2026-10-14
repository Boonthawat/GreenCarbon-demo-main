<?php
// จัดการสมาชิก: ค้นหา และระงับ / ยกเลิกระงับสิทธิ์
require '../init.php';
need_admin();

if ($_POST) {
    q("UPDATE users SET status = IF(status = 'active', 'suspended', 'active') WHERE id = ? AND role = 'user'", [p('id')]);
    go('users.php', 'เปลี่ยนสถานะสมาชิกแล้ว');
}

$rows = all("SELECT * FROM users WHERE role = 'user' AND (name LIKE ? OR email LIKE ? OR phone LIKE ?) ORDER BY id DESC", [like(), like(), like()]);

head('จัดการสมาชิก');
search();
?>
<div class="table-responsive">
<table class="table table-striped bg-white align-middle">
  <tr><th>ชื่อ</th><th>อีเมล</th><th>เบอร์โทร</th><th>แต้ม</th><th>ระดับ</th><th>วันที่สมัคร</th><th>สถานะ</th><th></th></tr>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= pic($r['avatar'], 36) ?> <?= e($r['name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['phone']) ?></td><td><?= $r['points'] ?></td>
      <td><?= level($r['points']) ?></td><td><?= dt($r['created_at']) ?></td><td><?= STATUS[$r['status']] ?></td>
      <td><form method="post"><input type="hidden" name="id" value="<?= $r['id'] ?>">
        <button class="btn btn-sm btn-outline-<?= $r['status'] == 'active' ? 'danger' : 'success' ?>" onclick="return confirm('ยืนยันเปลี่ยนสถานะ?')"><?= $r['status'] == 'active' ? 'ระงับสิทธิ์' : 'ยกเลิกระงับ' ?></button></form></td>
    </tr>
  <?php endforeach; ?>
</table>
</div>
<?php foot() ?>
