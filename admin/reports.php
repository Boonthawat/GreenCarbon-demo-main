<?php
// รายงาน 3 ชุด: กรองช่วงวันที่ ค้นหา กราฟ พิมพ์ และส่งออก CSV
// แต่ละรายงานกำหนด $cols (หัวตาราง), $rows (ข้อมูล), $by และ $of (กราฟ: จัดกลุ่มตามคอลัมน์ $by แล้วรวมคอลัมน์ $of)
require '../init.php';
need_admin();

const REPORTS = ['green' => 'ผลกระทบ Green Digital', 'members' => 'ข้อมูลสมาชิก', 'contents' => 'เนื้อหา'];
$type = isset(REPORTS[g('type')]) ? g('type') : 'green';
$range = [like(), like(), g('from', '2000-01-01'), g('to', '2100-01-01') . ' 23:59:59'];

if ($type == 'green') {
    $cols = ['วันที่', 'สมาชิก', 'หมวด', 'รายการ', 'น้ำหนัก (กก.)', 'สถานะ', 'แต้ม', 'CO₂e ที่ลด (kg)'];
    $rows = all("SELECT r.created_at, u.name, c.name AS category, r.item_name, r.weight_kg, r.status, r.points, r.co2_kg
        FROM recycles r JOIN users u ON u.id = r.user_id JOIN categories c ON c.id = r.category_id
        WHERE (u.name LIKE ? OR r.item_name LIKE ?) AND r.created_at BETWEEN ? AND ? ORDER BY r.id DESC", $range);
    list($by, $of, $unit) = ['category', 'co2_kg', 'kgCO₂e ที่ลดได้'];
} elseif ($type == 'members') {
    $cols = ['ชื่อ', 'อีเมล', 'เบอร์โทร', 'สถานะ', 'แต้ม', 'วันที่สมัคร'];
    $rows = all("SELECT name, email, phone, status, points, created_at FROM users
        WHERE role = 'user' AND (name LIKE ? OR email LIKE ?) AND created_at BETWEEN ? AND ? ORDER BY id DESC", $range);
    list($by, $of, $unit) = ['name', 'points', 'แต้มสะสม'];
} else {
    $cols = ['หัวข้อ', 'เริ่มแสดง', 'สิ้นสุด', 'สถานะ', 'ผู้สร้าง', 'จำนวน'];
    $rows = all('SELECT c.title, c.start_at, c.end_at, ' . LIVE . ' AS live, u.name, 1 AS n FROM contents c JOIN users u ON u.id = c.user_id
        WHERE (c.title LIKE ? OR c.body LIKE ?) AND c.start_at BETWEEN ? AND ? ORDER BY c.start_at DESC', $range);
    list($by, $of, $unit) = ['live', 'n', 'เรื่อง'];
}

$sum = [];
foreach ($rows as $r) $sum[$r[$by]] = ($sum[$r[$by]] ?? 0) + $r[$of];
$total = array_sum($sum);

if (g('export')) {
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=report-$type.csv");
    echo "\xEF\xBB\xBF";   // BOM ให้ Excel อ่านภาษาไทยถูกต้อง
    $out = fopen('php://output', 'w');
    fputcsv($out, $cols);
    foreach ($rows as $r) fputcsv($out, $r);
    exit;
}

head('รายงาน' . REPORTS[$type]);
?>
<div class="mb-3 d-print-none">
  <?php foreach (REPORTS as $k => $name): ?>
    <a class="btn btn-<?= $k == $type ? '' : 'outline-' ?>success" href="?type=<?= $k ?>"><?= $name ?></a>
  <?php endforeach; ?>
</div>
<?php search("<input type='hidden' name='type' value='$type'>
    <input class='form-control w-auto' type='date' name='from' value='" . e(g('from')) . "'>
    <input class='form-control w-auto' type='date' name='to' value='" . e(g('to')) . "'>") ?>
<div class="mb-3 d-print-none">
  <button class="btn btn-success" onclick="window.print()">🖨️ พิมพ์ / บันทึก PDF</button>
  <a class="btn btn-outline-success" href="?<?= e(http_build_query(['export' => 1] + $_GET)) ?>">⬇️ ส่งออก Excel (CSV)</a>
</div>
<div class="row">
  <?php box('จำนวนรายการ', count($rows)) ?>
  <?php box('รวม' . $unit, $total) ?>
  <?php if ($type == 'green') box('เทียบเท่าต้นไม้ดูดซับ 1 ปี', '🌳 ' . round($total / TREE_KG, 1) . ' ต้น') ?>
</div>
<div class="row"><div class="col-lg-6"><?php chart($unit, $type == 'members' ? 'bar' : 'doughnut', array_keys($sum), array_values($sum)) ?></div></div>
<div class="table-responsive">
<table class="table table-striped bg-white">
  <tr><?php foreach ($cols as $c): ?><th><?= $c ?></th><?php endforeach; ?></tr>
  <?php foreach ($rows as $r): ?>
    <tr><?php foreach ($r as $v): ?><td><?= e(STATUS[$v] ?? $v) ?></td><?php endforeach; ?></tr>
  <?php endforeach; ?>
</table>
</div>
<?php foot() ?>
