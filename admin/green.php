<?php
// ตั้งค่าหมวดขยะอิเล็กทรอนิกส์ที่รับรีไซเคิล
require '../init.php';
need_admin();

if (isset($_POST['del_category'])) {
    if (val('SELECT 1 FROM recycles WHERE category_id = ?', [p('id')])) go('green.php', 'ลบไม่ได้: มีคำขอรีไซเคิลใช้หมวดนี้อยู่', 'danger');
    q('DELETE FROM categories WHERE id = ?', [p('id')]);
    go('green.php', 'ลบหมวดแล้ว');
}
if (isset($_POST['save_category'])) {
    if (p('name') == '' || (float) p('co2_per_kg') <= 0) go('green.php', 'กรุณากรอกชื่อหมวด และค่ามากกว่า 0', 'danger');
    if (p('id')) q('UPDATE categories SET name = ?, co2_per_kg = ? WHERE id = ?', [p('name'), p('co2_per_kg'), p('id')]);
    else q('INSERT INTO categories (name, co2_per_kg) VALUES (?, ?)', [p('name'), p('co2_per_kg')]);
    go('green.php', 'บันทึกหมวดแล้ว');
}

$cats = all('SELECT * FROM categories ORDER BY id');
$cats[] = ['id' => '', 'name' => '', 'co2_per_kg' => ''];

head('ตั้งค่า Green');
?>
<div class="card card-body">
  <h5>หมวดขยะอิเล็กทรอนิกส์ (kgCO₂e ที่ลดได้เมื่อรีไซเคิล 1 กก.)</h5>
  <?php foreach ($cats as $c): ?>
    <form method="post" class="row g-2 mb-2">
      <input type="hidden" name="id" value="<?= $c['id'] ?>">
      <div class="col-md-7"><input class="form-control" name="name" value="<?= e($c['name']) ?>" placeholder="ชื่อหมวด" required></div>
      <div class="col-md-2"><input class="form-control" name="co2_per_kg" type="number" step="0.01" min="0.01" value="<?= $c['co2_per_kg'] ?>" placeholder="kgCO₂e ต่อ กก." required></div>
      <div class="col-md-3">
        <button class="btn btn-success" name="save_category"><?= $c['id'] ? 'บันทึก' : 'เพิ่ม' ?></button>
        <?php if ($c['id']): ?><button class="btn btn-outline-danger" name="del_category" onclick="return confirm('ลบหมวดนี้?')">ลบ</button><?php endif; ?>
      </div>
    </form>
  <?php endforeach; ?>
</div>
<?php foot() ?>
