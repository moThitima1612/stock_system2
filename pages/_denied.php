<?php
require_once __DIR__ . '/../includes/layout.php';
render_header('ไม่มีสิทธิ์เข้าถึง');
?>
<div class="card center pad-xl">
  <h2>ไม่มีสิทธิ์เข้าถึงหน้านี้</h2>
  <p class="muted">บัญชีของคุณ (<?= h(role_label(user_role())) ?>) ไม่ได้รับอนุญาตให้ใช้งานส่วนนี้<br>
     ติดต่อผู้ดูแลระบบหากต้องการสิทธิ์เพิ่มเติม</p>
  <p><a class="btn" href="<?= h(url('dashboard')) ?>">กลับหน้าแดชบอร์ด</a></p>
</div>
<?php render_footer(); ?>
