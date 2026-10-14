<?php
// จัดการเนื้อหา: เพิ่ม ลบ แก้ไข ค้นหา และตั้งวันเวลาเปิด-ปิดการแสดงผล
require '../init.php';
$admin = need_admin();

if (isset($_POST['delete'])) {
    q('DELETE FROM contents WHERE id = ?', [p('id')]);
    go('contents.php', 'ลบเนื้อหาแล้ว');
}
if ($_POST) {
    if (mb_strlen(p('title')) < 3 || p('body') == '' || p('start_at') == '') go('contents.php', 'กรุณากรอกหัวข้อ เนื้อหา และวันเวลาเริ่มแสดง', 'danger');
    if (p('end_at') != '' && p('end_at') <= p('start_at')) go('contents.php', 'วันเวลาสิ้นสุดต้องอยู่หลังวันเวลาเริ่มแสดง', 'danger');
    $data = [p('title'), p('body'), p('start_at'), p('end_at') ?: null];
    if (p('id')) {
        q('UPDATE contents SET title = ?, body = ?, start_at = ?, end_at = ? WHERE id = ?', array_merge($data, [p('id')]));
    } else {
        q('INSERT INTO contents (title, body, start_at, end_at, user_id) VALUES (?, ?, ?, ?, ?)', array_merge($data, [$admin['id']]));
    }
    go('contents.php', 'บันทึกเนื้อหาแล้ว');
}

$edit = one('SELECT * FROM contents WHERE id = ?', [g('edit')]) ?: ['id' => '', 'title' => '', 'body' => '', 'start_at' => date('Y-m-d H:i'), 'end_at' => ''];
$rows = all('SELECT *, ' . LIVE . ' AS live FROM contents WHERE title LIKE ? OR body LIKE ? ORDER BY start_at DESC', [like(), like()]);
$local = function ($s) { return $s ? date('Y-m-d\TH:i', strtotime($s)) : ''; };   // รูปแบบของช่อง datetime-local

head('จัดการเนื้อหา');
?>
<form method="post" class="card card-body mb-4">
  <h5><?= $edit['id'] ? 'แก้ไขเนื้อหา' : 'เพิ่มเนื้อหา' ?></h5>
  <input type="hidden" name="id" value="<?= $edit['id'] ?>">
  <?php input('หัวข้อ', 'title', 'text', $edit['title'], 'required minlength="3"') ?>
  <label class="form-label mt-2">เนื้อหา</label>
  <textarea class="form-control" name="body" rows="3" required><?= e($edit['body']) ?></textarea>
  <div class="row">
    <div class="col-md-6"><?php input('เริ่มแสดงผล', 'start_at', 'datetime-local', $local($edit['start_at'])) ?></div>
    <div class="col-md-6"><?php input('สิ้นสุดการแสดงผล (เว้นว่าง = ไม่มีกำหนด)', 'end_at', 'datetime-local', $local($edit['end_at']), '') ?></div>
  </div>
  <div class="mt-3"><button class="btn btn-success">บันทึก</button> <a class="btn btn-outline-secondary" href="contents.php">ล้างฟอร์ม</a></div>
</form>

<?php search() ?>
<div class="table-responsive">
<table class="table table-striped bg-white">
  <tr><th>หัวข้อ</th><th>เริ่มแสดง</th><th>สิ้นสุด</th><th>สถานะ</th><th></th></tr>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['title']) ?></td><td><?= dt($r['start_at']) ?></td><td><?= dt($r['end_at']) ?></td><td><?= $r['live'] ?></td>
      <td><form method="post"><input type="hidden" name="id" value="<?= $r['id'] ?>">
        <a class="btn btn-sm btn-outline-success" href="?edit=<?= $r['id'] ?>">แก้ไข</a>
        <button class="btn btn-sm btn-outline-danger" name="delete" onclick="return confirm('ลบเนื้อหานี้?')">ลบ</button></form></td>
    </tr>
  <?php endforeach; ?>
</table>
</div>
<?php foot() ?>
