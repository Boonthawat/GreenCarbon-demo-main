<?php
// แชทกับสมาชิก: ผู้ดูแลเห็นสมาชิกทุกคนในรายการด้านซ้าย เลือกแล้วอ่าน ตอบกลับ เริ่มแชทก่อน หรือลบแชททั้งหมดของสมาชิกคนนั้นได้
require '../init.php';
$admin = need_admin();
$member = one("SELECT * FROM users WHERE id = ? AND role = 'user'", [g('user')]);
if ($member) q('UPDATE messages SET is_read = 1 WHERE user_id = ?', [$member['id']]);   // เปิดแชทของใคร ถือว่าอ่านข้อความของคนนั้นแล้ว

if (isset($_POST['clear']) && $member) {
    q('DELETE FROM messages WHERE user_id = ?', [$member['id']]);
    go('issues.php?user=' . $member['id'], 'ลบแชททั้งหมดของ ' . $member['name'] . ' แล้ว', 'warning');
}
if ($_POST && $member) {
    if (p('body') == '') go('issues.php?user=' . $member['id'], 'กรุณาพิมพ์ข้อความ', 'danger');
    q('INSERT INTO messages (user_id, sender_id, body) VALUES (?, ?, ?)', [$member['id'], $admin['id'], mb_substr(p('body'), 0, 500)]);
    notify($member['id'], 'ข้อความจากผู้ดูแลระบบ', p('body'), 'issue.php');
    go('issues.php?user=' . $member['id']);
}

// สมาชิกทุกคน (LEFT JOIN จึงรวมคนที่ยังไม่เคยแชท) n = จำนวนข้อความจากสมาชิกที่ผู้ดูแลยังไม่ได้อ่าน คนที่มีข้อความยังไม่อ่านอยู่บนสุด
$threads = all("SELECT u.id, u.name, u.avatar, COALESCE(SUM(m.sender_id = u.id AND m.is_read = 0), 0) AS n, MAX(m.created_at) AS last
    FROM users u LEFT JOIN messages m ON m.user_id = u.id
    WHERE u.role = 'user' AND u.name LIKE ? GROUP BY u.id, u.name, u.avatar ORDER BY n DESC, last DESC, u.name", [like()]);

head('แชทกับสมาชิก');
?>
<div class="row">
  <div class="col-lg-4">
    <?php search() ?>
    <div class="list-group mb-3">
      <?php foreach ($threads as $t): ?>
        <a class="list-group-item list-group-item-action <?= $member && $member['id'] == $t['id'] ? 'active' : '' ?>" href="?user=<?= $t['id'] ?>">
          <?= pic($t['avatar'], 32) ?> <?= e($t['name']) ?> <?php if ($t['n']): ?><span class="badge bg-danger"><?= $t['n'] ?></span><?php endif; ?><br><small><?= $t['last'] ? dt($t['last']) : 'ยังไม่มีข้อความ' ?></small>
        </a>
      <?php endforeach; ?>
    </div>
    <?php if (!$threads): ?><div class="alert alert-secondary">ไม่พบสมาชิก</div><?php endif; ?>
  </div>
  <div class="col-lg-8">
    <?php if ($member): ?>
      <form method="post" class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0"><?= pic($member['avatar'], 36) ?> <?= e($member['name']) ?> <small class="text-muted"><?= e($member['email']) ?> · <?= e($member['phone']) ?></small></h5>
        <button class="btn btn-sm btn-outline-danger" name="clear" onclick="return confirm('ลบแชททั้งหมดของสมาชิกคนนี้? กู้คืนไม่ได้')">🗑 ลบแชททั้งหมด</button>
      </form>
      <?php chat($member['id']) ?>
    <?php else: ?>
      <div class="alert alert-success">เลือกสมาชิกจากรายการเพื่ออ่าน ตอบกลับ หรือเริ่มแชท</div>
    <?php endif; ?>
  </div>
</div>
<?php foot() ?>
