<?php
// แต้มสะสม ระดับสมาชิก และอันดับ (กลไกสร้างแรงจูงใจ)
require 'init.php';
$u = need_login();

$sum = one("SELECT COUNT(*) AS n, COALESCE(SUM(co2_kg), 0) AS co2 FROM recycles WHERE status = 'done' AND user_id = ?", [$u['id']]);
$top = all("SELECT id, name, avatar, points FROM users WHERE role = 'user' AND status = 'active' ORDER BY points DESC LIMIT 5");
$medals = ['🥇', '🥈', '🥉', '4', '5'];
$next = 0;
foreach (LEVELS as $min => $name) if (!$next && $min > $u['points']) $next = $min;

head('แต้มของฉัน', false);
?>
<div class="hero text-center mb-4">
  <?= pic($u['avatar'], 80) ?>
  <div class="big"><?= level($u['points']) ?></div>
  <p class="lead"><?= $u['points'] ?> แต้ม<?= $next ? ' · อีก ' . ($next - $u['points']) . ' แต้ม จะเลื่อนเป็น ' . LEVELS[$next] : ' · ระดับสูงสุดแล้ว' ?></p>
  <?php if ($next): ?>
    <div class="progress"><div class="progress-bar bg-warning" style="width:<?= round($u['points'] / $next * 100) ?>%"></div></div>
  <?php endif; ?>
</div>
<div class="row justify-content-center">
  <?php box('✅ รีไซเคิลสำเร็จ', $sum['n'] . ' ครั้ง') ?>
  <?php box('🌍 คาร์บอนที่ลดได้', $sum['co2'] . ' kgCO₂e') ?>
  <?php box('🌳 เทียบเท่าต้นไม้', round($sum['co2'] / TREE_KG, 1) . ' ต้น') ?>
</div>
<div class="row">
  <div class="col-lg-6">
    <div class="card card-body mb-3">
      <h5>🏅 อันดับสมาชิกแต้มสูงสุด</h5>
      <table class="table mb-0">
        <?php foreach ($top as $i => $t): ?>
          <tr class="<?= $t['id'] == $u['id'] ? 'table-success' : '' ?>"><td><?= $medals[$i] ?></td><td><?= pic($t['avatar'], 32) ?> <?= e($t['name']) ?></td><td><?= level($t['points']) ?></td><td class="text-end"><?= $t['points'] ?> แต้ม</td></tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card card-body mb-3">
      <h5>✨ วิธีรับแต้ม</h5>
      <table class="table mb-0">
        <tr><td><a href="recycle.php">♻️ รีไซเคิลขยะอิเล็กทรอนิกส์ 1 กก.</a></td><td class="text-end">+<?= POINT_PER_KG ?></td></tr>
      </table>
      <a class="btn btn-success mt-3" href="coupons.php">🎟️ ใช้แต้มแลกคูปอง</a>
    </div>
  </div>
</div>
<?php foot() ?>
