<?php
// จัดการรีไซเคิล: ดูรูปสินค้า ตรวจรับด้วยน้ำหนักที่ชั่งจริง (ให้แต้ม + บันทึกคาร์บอนที่ลดได้) หรือปฏิเสธ แล้วแจ้งเตือนสมาชิก
require '../init.php';
need_admin();

if ($_POST) {
    $db->beginTransaction();
    $r = one("SELECT r.*, c.co2_per_kg FROM recycles r JOIN categories c ON c.id = r.category_id WHERE r.id = ? AND r.status = 'pending' FOR UPDATE", [p('id')]);
    if (!$r) {
        $db->rollBack();
        go('recycles.php', 'ทำรายการไม่ได้', 'danger');
    }
    if (isset($_POST['reject'])) {
        q("UPDATE recycles SET status = 'rejected' WHERE id = ?", [$r['id']]);
        $db->commit();
        notify($r['user_id'], 'คำขอรีไซเคิลถูกปฏิเสธ', $r['item_name'], 'recycle.php');
        go('recycles.php', 'ปฏิเสธคำขอแล้ว', 'warning');
    }
    $kg = (float) p('weight_kg');
    if ($kg <= 0) {
        $db->rollBack();
        go('recycles.php', 'กรุณาตรวจสอบน้ำหนัก', 'danger');
    }
    $points = round($kg * POINT_PER_KG);
    q("UPDATE recycles SET status = 'done', weight_kg = ?, points = ?, co2_kg = ? WHERE id = ?", [$kg, $points, $kg * $r['co2_per_kg'], $r['id']]);
    q('UPDATE users SET points = points + ? WHERE id = ?', [$points, $r['user_id']]);
    q("INSERT INTO point_transactions (user_id, amount, description, related_type, related_id) VALUES (?, ?, ?, 'recycle', ?)",
        [$r['user_id'], $points, 'แต้มจากรีไซเคิล: ' . $r['item_name'], $r['id']]);
    $db->commit();
    notify($r['user_id'], 'รีไซเคิลสำเร็จ รับ ' . $points . ' แต้ม', $r['item_name'] . ' ลดคาร์บอนได้ ' . $kg * $r['co2_per_kg'] . ' kgCO₂e', 'points.php');
    go('recycles.php', "ตรวจรับแล้ว สมาชิกได้รับ $points แต้ม");
}

$rows = all("SELECT r.*, c.name AS category, u.name AS member FROM recycles r JOIN categories c ON c.id = r.category_id JOIN users u ON u.id = r.user_id
    WHERE (r.item_name LIKE ? OR u.name LIKE ?) AND (? = '' OR r.status = ?) ORDER BY r.status = 'pending' DESC, r.id DESC", [like(), like(), g('status'), g('status')]);

$options = '<option value="">ทุกสถานะ</option>';
foreach (['pending', 'done', 'rejected'] as $s) $options .= "<option value='$s'" . (g('status') == $s ? ' selected' : '') . '>' . STATUS[$s] . '</option>';

head('จัดการรีไซเคิล');
search("<select class='form-select w-auto' name='status'>$options</select>");
?>
<div class="table-responsive">
<table class="table table-striped bg-white align-middle">
  <tr><th>รูป</th><th>วันที่</th><th>สมาชิก</th><th>รายการ</th><th>หมวด</th><th>กก.</th><th>สถานะ</th><th>แต้ม</th><th>CO₂e ที่ลด</th><th>ตรวจรับ</th></tr>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= pic($r['image'], 64, '📦', true) ?></td><td><?= dt($r['created_at']) ?></td><td><?= e($r['member']) ?></td><td><?= e($r['item_name']) ?></td><td><?= e($r['category']) ?></td>
      <td><?= $r['weight_kg'] ?></td><td><?= STATUS[$r['status']] ?></td><td><?= $r['points'] ?></td><td><?= $r['co2_kg'] ?></td>
      <td><?php if ($r['status'] == 'pending'): ?>
        <form method="post" class="d-flex gap-1">
          <input type="hidden" name="id" value="<?= $r['id'] ?>">
          <input class="form-control form-control-sm" style="width:80px" type="number" step="0.01" min="0.01" name="weight_kg" value="<?= $r['weight_kg'] ?>">
          <button class="btn btn-sm btn-success">ตรวจรับ</button>
          <button class="btn btn-sm btn-outline-danger" name="reject" onclick="return confirm('ปฏิเสธคำขอนี้?')">ปฏิเสธ</button>
        </form>
      <?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
</table>
</div>
<?php foot() ?>
