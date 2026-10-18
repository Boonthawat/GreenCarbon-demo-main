<?php
// แจ้งรีไซเคิลขยะอิเล็กทรอนิกส์และติดตามสถานะคำขอ
require 'init.php';
$u = need_login();
$cats = all('SELECT * FROM categories ORDER BY id');

if (isset($_POST['cancel'])) {
    q("DELETE FROM recycles WHERE id = ? AND user_id = ? AND status = 'pending'", [p('id'), $u['id']]);
    go('recycle.php', 'ยกเลิกคำขอแล้ว', 'warning');
}
if ($_POST) {
    $category = one('SELECT * FROM categories WHERE id = ?', [p('category_id')]);
    $weight = (float) p('weight_kg');
    if (mb_strlen(p('item_name')) < 3 || $weight <= 0 || !$category) {
        go('recycle.php', 'กรุณาเลือกหมวด กรอกรายการอย่างน้อย 3 ตัวอักษร และน้ำหนักมากกว่า 0', 'danger');
    }
    $image = upload('image');
    if (!$image && bad_upload('image')) go('recycle.php', bad_upload('image'), 'danger');
    $estimatedCo2 = $weight * $category['co2_per_kg'];
    q('INSERT INTO recycles (user_id, category_id, item_name, weight_kg, image) VALUES (?, ?, ?, ?, ?)', [$u['id'], p('category_id'), p('item_name'), $weight, $image]);
    notify(null, 'คำขอรีไซเคิลใหม่', $u['name'] . ': ' . p('item_name'), 'admin/recycles.php?status=pending');
    go('recycle.php#recycle-form', 'ส่งคำขอแล้ว คาดว่าจะลดคาร์บอนได้ ' . number_format($estimatedCo2, 2) . ' kgCO₂e (ยืนยันผลจริงเมื่อผู้ดูแลตรวจรับ) คุณจะได้ ' . POINT_PER_KG . ' แต้มต่อ 1 กก.');
}

$sort = g('sort') == 'weight' ? 'r.weight_kg DESC' : 'r.id DESC';
$rows = all("SELECT r.*, c.name AS category, c.co2_per_kg FROM recycles r JOIN categories c ON c.id = r.category_id
    WHERE r.user_id = ? AND r.item_name LIKE ? ORDER BY $sort", [$u['id'], like()]);
$year = date('Y');
$saved = val("SELECT COALESCE(SUM(co2_kg), 0) FROM recycles WHERE user_id = ? AND status = 'done' AND YEAR(created_at) = ?", [$u['id'], $year]);

head('รีไซเคิล');
?>
<div class="hero page-hero recycle-hero mb-4">
  <div class="page-hero-copy">
    <span class="page-hero-eyebrow">RECYCLE &amp; MAKE A DIFFERENCE</span>
    <p class="lead mb-1">🌱 คาร์บอนที่ลดได้ในปี <?= $year + 543 ?></p>
    <div class="big"><?= number_format($saved, 2) ?> <span>kgCO₂e</span></div>
    <p class="mb-0">ส่งต่อขยะอิเล็กทรอนิกส์ให้กลับมาใช้ประโยชน์ และช่วยลดผลกระทบต่อสิ่งแวดล้อม</p>
  </div>
  <div class="page-hero-art recycle-hero-art" aria-hidden="true">
    <span class="hero-art-ring"></span>
    <span class="hero-art-icon">📦</span>
    <span class="hero-art-spark">✦</span>
    <span class="hero-art-leaf">🌿</span>
  </div>
</div>

<form method="post" enctype="multipart/form-data" class="card recycle-form-card mb-4">
  <aside class="recycle-form-aside">
    <span class="form-aside-icon" aria-hidden="true">♻️</span>
    <span class="page-hero-eyebrow">GIVE IT A NEW LIFE</span>
    <h2>เริ่มต้นจากของชิ้นเล็ก ๆ</h2>
    <p>แจ้งข้อมูลอุปกรณ์และน้ำหนักโดยประมาณ ทีมงานจะตรวจสอบก่อนยืนยันผลการรีไซเคิล</p>
    <div class="form-aside-note">✨ รับ <?= POINT_PER_KG ?> แต้มต่อขยะที่รีไซเคิลสำเร็จ 1 กก.</div>
  </aside>
  <div class="recycle-form-fields">
    <h5 id="recycle-form">แจ้งรายการรีไซเคิล</h5>
    <p class="text-muted mb-3">กรอกข้อมูลอุปกรณ์ที่ต้องการส่งรีไซเคิล</p>
    <label class="form-label mt-2">หมวดอุปกรณ์</label>
    <select class="form-select" id="recycle-category" name="category_id" required>
      <option value="">เลือกหมวด</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= $c['id'] ?>" data-factor="<?= e($c['co2_per_kg']) ?>"><?= e($c['name']) ?> (ลด <?= $c['co2_per_kg'] ?> kgCO₂e ต่อ กก.)</option>
      <?php endforeach; ?>
    </select>
    <?php input('รายการ', 'item_name', 'text', '', 'required minlength="3"') ?>
    <?php input('น้ำหนักโดยประมาณ (กก.)', 'weight_kg', 'number', '', 'required min="0.01" step="0.01" id="recycle-weight"') ?>
    <div id="recycle-estimate" class="form-text mt-2" aria-live="polite">เลือกหมวดและกรอกน้ำหนักเพื่อดูปริมาณคาร์บอนที่คาดว่าจะลดได้</div>
    <?php input('รูปสินค้าที่จะรีไซเคิล (ไม่บังคับ JPG PNG GIF ไม่เกิน 2MB)', 'image', 'file', '', 'accept="image/*"') ?>
    <button class="btn btn-success mt-3">ส่งคำขอ</button>
  </div>
</form>
<script>
  const recycleCategory = document.getElementById('recycle-category');
  const recycleWeight = document.getElementById('recycle-weight');
  const recycleEstimate = document.getElementById('recycle-estimate');
  const updateRecycleEstimate = () => {
    const factor = Number(recycleCategory.selectedOptions[0].dataset.factor);
    const weight = Number(recycleWeight.value);
    if (!factor || !weight || weight <= 0) {
      recycleEstimate.textContent = 'เลือกหมวดและกรอกน้ำหนักเพื่อดูปริมาณคาร์บอนที่คาดว่าจะลดได้';
      return;
    }
    const estimate = new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(factor * weight);
    recycleEstimate.textContent = `คาดว่าจะลดได้ ${estimate} kgCO₂e (ผลจริงยืนยันหลังผู้ดูแลตรวจรับ)`;
  };
  recycleCategory.addEventListener('change', updateRecycleEstimate);
  recycleWeight.addEventListener('input', updateRecycleEstimate);
</script>

<h5 class="page-section-title">คำขอของฉัน</h5>
<?php search('<select class="form-select w-auto" name="sort"><option value="">ใหม่ล่าสุด</option><option value="weight"' . (g('sort') == 'weight' ? ' selected' : '') . '>น้ำหนักมากสุด</option></select>') ?>
<div class="table-responsive">
<table class="table table-striped bg-white align-middle">
  <tr><th>รูป</th><th>วันที่</th><th>รายการ</th><th>หมวด</th><th>กก.</th><th>สถานะ</th><th>แต้ม</th><th>CO₂e ที่ลด/คาดการณ์</th><th></th></tr>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= pic($r['image'], 56, '📦', true) ?></td><td><?= dt($r['created_at']) ?></td><td><?= e($r['item_name']) ?></td><td><?= e($r['category']) ?></td>
      <td><?= $r['weight_kg'] ?></td><td><?= STATUS[$r['status']] ?></td><td><?= $r['points'] ?></td><td><?= $r['status'] == 'pending' ? number_format($r['weight_kg'] * $r['co2_per_kg'], 2) . ' (ประมาณ)' : $r['co2_kg'] ?></td>
      <td><?php if ($r['status'] == 'pending'): ?>
        <form method="post"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-outline-danger" name="cancel" onclick="return confirm('ยกเลิกคำขอนี้?')">ยกเลิก</button></form>
      <?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
</table>
</div>
<?php foot() ?>
