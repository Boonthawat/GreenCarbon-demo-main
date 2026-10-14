<?php
// Dashboard: ข้อมูลสรุป สถิติการใช้งาน และผลกระทบ Green Digital
require '../init.php';
need_admin();
expire_coupons();

$sum = one("SELECT COALESCE(SUM(weight_kg), 0) AS kg, COALESCE(SUM(co2_kg), 0) AS co2 FROM recycles WHERE status = 'done'");
$byMonth = all("SELECT DATE_FORMAT(created_at, '%Y-%m') AS m, SUM(co2_kg) AS v FROM recycles WHERE status = 'done' GROUP BY m ORDER BY m");
$byCat = all("SELECT c.name, SUM(r.weight_kg) AS v FROM recycles r JOIN categories c ON c.id = r.category_id WHERE r.status = 'done' GROUP BY c.name");
$members = all("SELECT DATE_FORMAT(created_at, '%Y-%m') AS m, COUNT(*) AS v FROM users WHERE role = 'user' GROUP BY m ORDER BY m");
$notis = all('SELECT * FROM notifications WHERE user_id IS NULL AND is_read = 0 ORDER BY id DESC LIMIT 5');
$couponRequests = all("SELECT cr.id, cr.points_spent, u.name AS member, r.title AS reward_title FROM coupon_redemptions cr
    JOIN users u ON u.id = cr.user_id JOIN rewards r ON r.id = cr.reward_id
    WHERE cr.status = 'pending' ORDER BY cr.id LIMIT 5");
$fulfillments = all("SELECT coupon_code, recipient_name, recipient_phone, shipping_address, created_at FROM coupon_redemptions
    WHERE status = 'used' ORDER BY used_at DESC LIMIT 5");

head('Dashboard');
?>
<div class="row">
  <?php box('👥 สมาชิกทั้งหมด', val("SELECT COUNT(*) FROM users WHERE role = 'user'") . ' คน') ?>
  <?php box('🚫 สมาชิกที่ถูกระงับ', val("SELECT COUNT(*) FROM users WHERE status = 'suspended'") . ' คน') ?>
  <?php box('⏳ คำขอรีไซเคิลรอตรวจรับ', val("SELECT COUNT(*) FROM recycles WHERE status = 'pending'") . ' รายการ') ?>
  <?php box('🎟️ คำขอแลกคูปองรออนุมัติ', val("SELECT COUNT(*) FROM coupon_redemptions WHERE status = 'pending'") . ' รายการ') ?>
  <?php box('🔔 แจ้งเตือนที่ยังไม่อ่าน', val('SELECT COUNT(*) FROM notifications WHERE user_id IS NULL AND is_read = 0') . ' รายการ') ?>
  <?php box('📦 E-Waste ที่รีไซเคิล', $sum['kg'] . ' กก.') ?>
  <?php box('🌍 คาร์บอนที่ลดได้', $sum['co2'] . ' kgCO₂e') ?>
  <?php box('🌳 เทียบเท่าต้นไม้', round($sum['co2'] / TREE_KG, 1) . ' ต้น') ?>
</div>
<div class="card card-body mb-3">
  <h5>🎟️ คำขอแลกคูปองที่รออนุมัติ</h5>
  <?php foreach ($couponRequests as $r): ?>
    <div class="border-top py-2 d-flex justify-content-between align-items-center gap-2 flex-wrap">
      <span><?= e($r['member']) ?> ขอแลก <?= e($r['reward_title']) ?> (<?= number_format($r['points_spent']) ?> แต้ม)</span>
      <form method="post" action="coupons.php" class="d-flex gap-1">
        <input type="hidden" name="id" value="<?= $r['id'] ?>">
        <button class="btn btn-sm btn-success" name="review">อนุมัติ</button>
        <button class="btn btn-sm btn-outline-danger" name="reject" onclick="return confirm('ไม่อนุมัติและคืนแต้มให้สมาชิก?')">ไม่อนุมัติ</button>
      </form>
    </div>
  <?php endforeach; ?>
  <?php if (!$couponRequests): ?><p class="text-muted mb-0">ไม่มีคำขอที่รออนุมัติ</p><?php endif; ?>
  <a class="mt-2" href="coupons.php">จัดการของรางวัลและดูคำขอทั้งหมด</a>
</div>
<div class="card card-body mb-3">
  <h5>📦 รายการคูปองที่รอจัดส่ง</h5>
  <?php foreach ($fulfillments as $r): ?>
    <div class="border-top py-2">
      <b><?= e($r['recipient_name']) ?> (<?= e($r['recipient_phone']) ?>)</b>
      <span class="text-muted"> · รหัส <?= e($r['coupon_code']) ?></span><br>
      <small><?= nl2br(e($r['shipping_address'])) ?></small>
    </div>
  <?php endforeach; ?>
  <?php if (!$fulfillments): ?><p class="text-muted mb-0">ไม่มีรายการรอจัดส่ง</p><?php endif; ?>
  <a class="mt-2" href="coupons.php?status=used">ดูข้อมูลจัดส่งทั้งหมด</a>
</div>
<div class="row">
  <div class="col-lg-8"><?php chart('คาร์บอนที่ลดได้รายเดือน (kgCO₂e)', 'bar', array_column($byMonth, 'm'), array_column($byMonth, 'v')) ?></div>
  <div class="col-lg-4"><?php chart('น้ำหนักที่รีไซเคิลตามหมวด (กก.)', 'doughnut', array_column($byCat, 'name'), array_column($byCat, 'v')) ?></div>
  <div class="col-lg-8"><?php chart('สมาชิกใหม่รายเดือน (คน)', 'line', array_column($members, 'm'), array_column($members, 'v')) ?></div>
  <div class="col-lg-4">
    <div class="card card-body mb-3">
      <h6>🔔 แจ้งเตือนที่ยังไม่อ่าน</h6>
      <?php foreach ($notis as $n): ?>
        <div class="border-top py-1"><b><?= e($n['title']) ?></b><br><small><?= e($n['message']) ?></small></div>
      <?php endforeach; ?>
      <a class="mt-2" href="../notifications.php">ดูทั้งหมด</a>
    </div>
  </div>
</div>
<?php foot() ?>
