<?php
// การแจ้งเตือนของผู้ใช้ปัจจุบัน (ใช้ร่วมกันทั้งสมาชิกและผู้ดูแลระบบ) เปิดหน้านี้แล้วถือว่าอ่านทั้งหมด
require 'init.php';
need_login();

if ($_POST) {
    q('DELETE FROM notifications WHERE id = ? AND user_id <=> ?', [p('id'), inbox()]);
    go('notifications.php', 'ลบการแจ้งเตือนแล้ว');
}

$rows = all('SELECT * FROM notifications WHERE user_id <=> ? AND (title LIKE ? OR message LIKE ?) ORDER BY id DESC', [inbox(), like(), like()]);
q('UPDATE notifications SET is_read = 1 WHERE user_id <=> ?', [inbox()]);

head('การแจ้งเตือน');
search();
?>
<?php foreach ($rows as $r): ?>
  <div class="card card-body mb-2 <?= $r['is_read'] ? '' : 'border border-success' ?>">
    <form method="post" class="d-flex justify-content-between gap-2">
      <div><b><?= e($r['title']) ?></b> <?= $r['is_read'] ? '' : '<span class="badge bg-danger">ใหม่</span>' ?><br><?= e($r['message']) ?><br><small class="text-muted"><?= dt($r['created_at']) ?></small></div>
      <div class="text-nowrap">
        <input type="hidden" name="id" value="<?= $r['id'] ?>">
        <?php if ($r['link']): ?><a class="btn btn-sm btn-success" href="<?= e($r['link']) ?>">เปิด</a><?php endif; ?>
        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('ลบการแจ้งเตือนนี้?')">ลบ</button>
      </div>
    </form>
  </div>
<?php endforeach; ?>
<?php if (!$rows): ?><div class="alert alert-secondary">ไม่มีการแจ้งเตือน</div><?php endif; ?>
<?php foot() ?>
