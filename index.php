<?php
// หน้าแรก: ส่วนต้อนรับ ขั้นตอนการใช้งาน ภาพรวมผลกระทบ และข่าวสารที่อยู่ในช่วงเวลาแสดงผล
require 'init.php';
$u = me();
if ($u && $u['role'] == 'admin') go('admin/index.php');

$news = all('SELECT * FROM contents WHERE start_at <= NOW() AND (end_at IS NULL OR end_at > NOW()) AND title LIKE ? ORDER BY start_at DESC', [like()]);
$sum = one("SELECT COUNT(*) AS n, COALESCE(SUM(weight_kg), 0) AS kg, COALESCE(SUM(co2_kg), 0) AS co2 FROM recycles WHERE status = 'done'");
$steps = [
    ['📦', '1. เลือก', 'ตรวจสอบหมวดและน้ำหนักขยะอิเล็กทรอนิกส์', 'recycle.php#recycle-form'],
    ['♻️', '2. ส่งรีไซเคิล', 'แจ้งรายการ แล้วรอผู้ดูแลตรวจรับ', 'recycle.php#recycle-form'],
    ['🏆', '3. รับแต้ม', 'สะสมแต้ม เลื่อนระดับ และไต่อันดับ', 'points.php'],
];

head('หน้าแรก', false);
?>
<div class="hero mb-4">
  <?php if ($u): ?>
    <h1>สวัสดี <?= e($u['name']) ?> 👋</h1>
    <p class="lead">ระดับ <?= level($u['points']) ?> · สะสมแล้ว <?= $u['points'] ?> แต้ม</p>
    <a class="btn btn-light" href="recycle.php#recycle-form">♻️ เริ่มรีไซเคิล</a>
    <a class="btn btn-outline-light" href="points.php">🏆 ดูแต้มสะสม</a>
  <?php else: ?>
    <h1>ส่งขยะอิเล็กทรอนิกส์ไปรีไซเคิล ♻️</h1>
    <p class="lead">ร่วมจัดการ E-Waste อย่างถูกวิธี ลดผลกระทบต่อสิ่งแวดล้อม และรับแต้มสะสมเมื่อรีไซเคิลสำเร็จ</p>
    <a class="btn btn-light" href="register.php">เริ่มต้นใช้งานฟรี</a>
    <a class="btn btn-outline-light" href="login.php">เข้าสู่ระบบ</a>
  <?php endif; ?>
</div>

<div class="row">
  <?php foreach ($steps as $s): ?>
    <div class="col-md-4">
      <a class="card card-body box text-center mb-3 text-decoration-none text-dark" href="<?= $s[3] ?>">
        <div class="big"><?= $s[0] ?></div><h5><?= $s[1] ?></h5><span class="text-muted"><?= $s[2] ?></span>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<h4 class="mt-3">🌱 ผลกระทบที่เราสร้างร่วมกัน</h4>
<div class="row">
  <?php box('👥 สมาชิก', val("SELECT COUNT(*) FROM users WHERE role = 'user'") . ' คน') ?>
  <?php box('✅ รีไซเคิลสำเร็จ', $sum['n'] . ' ครั้ง') ?>
  <?php box('📦 E-Waste ที่รีไซเคิล', $sum['kg'] . ' กก.') ?>
  <?php box('🌳 คาร์บอนที่ลดได้', $sum['co2'] . ' kgCO₂e') ?>
</div>

<h4 class="mt-3">📰 ข่าวสารและความรู้</h4>
<?php search() ?>
<div class="row">
  <?php foreach ($news as $n): ?>
    <div class="col-md-6">
      <div class="card card-body mb-3">
        <h5 class="text-success"><?= e($n['title']) ?></h5>
        <p class="mb-1"><?= nl2br(e($n['body'])) ?></p>
        <small class="text-muted">📅 <?= dt($n['start_at']) ?></small>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php if (!$news): ?><div class="alert alert-secondary">ไม่พบข่าวสาร</div><?php endif; ?>
<?php foot() ?>
