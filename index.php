<?php
// หน้าแรก: ส่วนต้อนรับ ขั้นตอนการใช้งาน ภาพรวมผลกระทบ และข่าวสารที่อยู่ในช่วงเวลาแสดงผล
require 'init.php';
$u = me();
if ($u && $u['role'] == 'admin') go('admin/index.php');

$news = all('SELECT * FROM contents WHERE start_at <= NOW() AND (end_at IS NULL OR end_at > NOW()) AND title LIKE ? ORDER BY start_at DESC', [like()]);
$featuredNews = $news ? array_shift($news) : null;
$sum = one("SELECT COUNT(*) AS n, COALESCE(SUM(weight_kg), 0) AS kg, COALESCE(SUM(co2_kg), 0) AS co2 FROM recycles WHERE status = 'done'");
$memberCount = val("SELECT COUNT(*) FROM users WHERE role = 'user'");
$steps = [
    ['📦', '1. แจ้งรายการ', 'เลือกหมวด กรอกน้ำหนัก และแนบรูปอุปกรณ์ที่ต้องการส่ง', 'recycle.php#recycle-form'],
    ['🔎', '2. รอตรวจรับ', 'ทีมงานตรวจสอบข้อมูลและยืนยันรายการรีไซเคิล', 'recycle.php'],
    ['🏆', '3. รับแต้มและเติบโต', 'เมื่อรายการสำเร็จ รับแต้มสะสมเพื่อแลกของรางวัล', 'points.php'],
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

<section class="home-impact-section">
  <div class="home-section-heading">
    <span class="section-eyebrow">SMALL ACTION, BIG IMPACT</span>
    <h2>ผลกระทบที่เราสร้างร่วมกัน 🌱</h2>
    <p>ทุกชิ้นที่ส่งเข้าระบบ คืออีกหนึ่งก้าวของการจัดการ E-Waste อย่างรับผิดชอบ</p>
  </div>
  <div class="row g-3 home-impact-grid">
    <div class="col-6 col-xl-3">
      <div class="impact-card">
        <span class="impact-icon">👥</span>
        <span class="impact-label">คนที่ร่วมดูแลโลก</span>
        <strong><?= number_format($memberCount) ?></strong>
        <small>สมาชิก GreenLoop</small>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="impact-card">
        <span class="impact-icon">♻️</span>
        <span class="impact-label">ส่งต่ออย่างถูกวิธี</span>
        <strong><?= number_format($sum['n']) ?></strong>
        <small>รายการที่รีไซเคิลสำเร็จ</small>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="impact-card">
        <span class="impact-icon">📦</span>
        <span class="impact-label">ขยะที่ไม่ถูกทิ้งเปล่า</span>
        <strong><?= number_format((float) $sum['kg'], 2) ?></strong>
        <small>กิโลกรัม E-Waste</small>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="impact-card impact-card-highlight">
        <span class="impact-icon">🌍</span>
        <span class="impact-label">ลดการปล่อยคาร์บอน</span>
        <strong><?= number_format((float) $sum['co2'], 2) ?></strong>
        <small>kgCO₂e จากรายการที่สำเร็จ</small>
      </div>
    </div>
  </div>
  <p class="impact-footnote">* สรุปจากรายการรีไซเคิลที่ผู้ดูแลตรวจรับและยืนยันแล้ว</p>
</section>

<section class="home-journey-section">
  <div class="home-section-heading">
    <span class="section-eyebrow">FROM E-WASTE TO NEW VALUE</span>
    <h2>ส่งรีไซเคิลแล้ว ได้อะไรบ้าง?</h2>
    <p>GreenLoop ช่วยให้การจัดการขยะอิเล็กทรอนิกส์เป็นเรื่องง่าย ตรวจสอบได้ และมีแรงจูงใจ</p>
  </div>
  <div class="row g-3">
    <?php foreach ($steps as $index => $s): ?>
      <div class="col-md-4">
        <a class="card journey-card h-100 text-decoration-none" href="<?= $s[3] ?>">
          <div class="journey-card-top">
            <span class="journey-icon"><?= $s[0] ?></span>
            <span class="journey-number">0<?= $index + 1 ?></span>
          </div>
          <h3><?= $s[1] ?></h3>
          <p><?= $s[2] ?></p>
          <?php if ($index === 2): ?>
            <span class="journey-reward">🎁 <?= POINT_PER_KG ?> แต้มต่อกิโลกรัมที่สำเร็จ</span>
          <?php endif; ?>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="home-campaign">
  <div class="campaign-decoration" aria-hidden="true">🌿</div>
  <div class="campaign-copy">
    <span class="section-eyebrow">ชวนกันเปลี่ยนของเก่า ให้เป็นคุณค่าใหม่</span>
    <h2>ลิ้นชักของคุณอาจมี “ก้าวเล็ก ๆ” ซ่อนอยู่</h2>
    <p>โทรศัพท์เสีย สายชาร์จเก่า หรืออุปกรณ์ที่ไม่ได้ใช้ ไม่ควรถูกทิ้งรวมกับขยะทั่วไป เริ่มต้นส่งต่ออย่างถูกวิธี แล้วชวนคนรอบตัวมาร่วมสร้างชุมชนที่ใส่ใจโลกไปด้วยกัน</p>
  </div>
  <a class="btn btn-light campaign-action" href="<?= $u ? 'recycle.php#recycle-form' : 'register.php' ?>">
    <?= $u ? 'เริ่มส่งรีไซเคิล ♻️' : 'มาร่วมแคมเปญกับเรา 🌱' ?>
  </a>
</section>
</div>

<section class="articles-section mt-5">
  <div class="articles-heading">
    <div>
      <span class="section-eyebrow">GREENLOOP JOURNAL</span>
      <h2 class="mt-2 mb-2">เรื่องเล็ก ๆ ที่ช่วยโลกได้ 🌱</h2>
      <p class="text-muted mb-0">ข่าวสารและความรู้เรื่อง E-Waste ที่อ่านง่ายและนำไปใช้ได้จริง</p>
    </div>
    <span class="article-count"><?= $featuredNews ? count($news) + 1 : 0 ?> เรื่อง</span>
  </div>

  <div class="article-toolbar">
    <span>บทความและข่าวสาร</span>
    <?php search() ?>
  </div>

  <?php if ($featuredNews): ?>
    <article class="card article-feature mb-4">
      <div class="article-art" aria-hidden="true">
        <span class="article-art-orbit"></span>
        <span class="article-art-icon">♻️</span>
        <span class="article-art-leaf">✦</span>
      </div>
      <div class="article-feature-content">
        <span class="article-label">เรื่องเด่น</span>
        <h3><?= e($featuredNews['title']) ?></h3>
        <p><?= nl2br(e($featuredNews['body'])) ?></p>
        <small class="text-muted">📅 <?= dt($featuredNews['start_at']) ?></small>
      </div>
    </article>

    <?php if ($news): ?>
      <div class="row">
        <?php foreach ($news as $index => $n): ?>
          <div class="col-md-6 col-xl-4">
            <article class="card article-card mb-3">
              <div class="article-card-top">
                <span class="article-card-icon"><?= $index % 2 === 0 ? '🌿' : '💡' ?></span>
                <small class="text-muted"><?= dt($n['start_at']) ?></small>
              </div>
              <h4><?= e($n['title']) ?></h4>
              <p><?= nl2br(e($n['body'])) ?></p>
              <span class="article-read-label">GREENLOOP JOURNAL</span>
            </article>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <div class="alert alert-secondary"><?= g('q') ? 'ไม่พบข่าวสารที่ตรงกับคำค้นหา' : 'ยังไม่มีข่าวสารในขณะนี้' ?></div>
  <?php endif; ?>
</section>
<?php foot() ?>
