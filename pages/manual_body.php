<?php
/**
 * เนื้อหาคู่มือระบบ (ใช้ร่วมกันระหว่างหน้าในระบบ และหน้าเลย์เอาต์ A4 สำหรับพิมพ์ PDF)
 * ผู้เรียกต้องโหลด layout.php และเปิด session ไว้แล้ว
 */
require_once __DIR__ . '/manual_toc.php';
?>
<style>
.man{display:grid;grid-template-columns:minmax(230px,262px) minmax(0,1fr);gap:20px;align-items:start}
@media (max-width:900px){.man{grid-template-columns:minmax(0,1fr)}}
.man-toc{position:sticky;top:88px}
@media (max-width:900px){.man-toc{position:static}}
.man-toc .card-body{padding:14px 16px}
.man-toc .g{font-size:.63rem;letter-spacing:.16em;text-transform:uppercase;color:var(--muted);
  font-weight:600;margin:14px 0 5px;padding-bottom:5px;border-bottom:1px solid var(--line-2)}
.man-toc .g:first-child{margin-top:0}
.man-toc a{display:flex;gap:8px;align-items:baseline;padding:3px 0;color:var(--text);font-size:.85rem}
.man-toc a:hover{color:var(--brand)}
.man-toc a i{font-style:normal;color:var(--brand);font-size:.74rem;min-width:17px;
  font-variant-numeric:tabular-nums}
.man-sec{scroll-margin-top:92px}
.man-sec .card-head h2{display:flex;gap:10px;align-items:baseline}
.man-sec .card-head h2 i{font-style:normal;color:var(--brand);font-family:var(--serif);
  font-size:1.5rem;font-weight:600;line-height:1}
.man-sec .card-body>p:first-child{margin-top:0}
.man-lede{color:var(--muted);margin:0 0 16px}
.man h3{margin:22px 0 9px;padding-bottom:6px;border-bottom:1px solid var(--line-2);font-size:.97rem}
.man h3:first-of-type{margin-top:0}
.man ol.steps{counter-reset:s;list-style:none;padding:0;margin:0 0 16px}
.man ol.steps>li{counter-increment:s;position:relative;padding:0 0 13px 38px;
  border-left:1px solid var(--line)}
.man ol.steps>li:last-child{border-left-color:transparent;padding-bottom:0}
.man ol.steps>li::before{content:counter(s);position:absolute;left:-12px;top:0;width:24px;height:24px;
  border-radius:50%;background:var(--surface);border:1px solid var(--brand);color:var(--brand);
  font-size:.8rem;font-weight:600;display:grid;place-items:center}
.man kbd{background:var(--surface-2);border:1px solid var(--line);border-radius:5px;
  padding:1px 6px;font-family:"Consolas","SF Mono",monospace;font-size:.85em;white-space:nowrap}
.man .tbl td:first-child{white-space:nowrap}
.man .field{color:var(--brand);font-weight:600}
.man .req{color:var(--danger);font-weight:700}
.man pre{background:var(--side-bg-2);color:var(--side-text);border-radius:10px;padding:15px 17px;
  overflow-x:auto;font-family:"Consolas","SF Mono",monospace;font-size:.82rem;line-height:1.7;margin:0 0 16px}
.man pre b{color:var(--brand-2);font-weight:500}
.man pre span{color:var(--side-muted)}
@media print{.man{display:block}.man-toc{display:none}}
</style>

<div class="man">

  <!-- ================= สารบัญ ================= -->
  <aside class="card man-toc noprint">
    <div class="card-head"><h2>สารบัญ</h2></div>
    <div class="card-body">
      <?php $n = 0; foreach (MANUAL_TOC as $group => $items): ?>
        <div class="g"><?= h($group) ?></div>
        <?php foreach ($items as $id => $label): $n++; ?>
          <a href="#<?= h($id) ?>"><i><?= sprintf('%02d', $n) ?></i><span><?= h($label) ?></span></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </div>
  </aside>

  <div>


  <!-- ================= 01 ภาพรวม ================= -->
  <div class="card man-sec" id="overview">
    <div class="card-head"><h2><i>01</i> ระบบนี้ทำอะไรได้</h2></div>
    <div class="card-body">
      <p class="man-lede">ระบบคุมสต๊อกวัตถุดิบและสินค้าสำเร็จรูปแบบหลายคลัง
        ทุกการเปลี่ยนแปลงยอดคงเหลือต้องผ่านเอกสารเสมอ จึงตรวจย้อนหลังได้ทุกบรรทัดว่าใครทำอะไรเมื่อไร</p>

      <h3>เส้นทางเดินของข้อมูล</h3>
      <pre>ผู้ใช้กรอกเอกสาร  →  <b>post_document()</b>  →  ┌─ <b>stock_movements</b>  <span>บัญชีการเคลื่อนไหว (เขียนอย่างเดียว)</span>
                                        └─ <b>stock_balances</b>   <span>ยอดคงเหลือ ต่อ สินค้า × คลัง</span></pre>
      <p>ไม่มีหน้าจอไหนแก้ยอดคงเหลือได้โดยตรง แม้แต่หน้าแก้ไขสินค้า ยอดจึงตรงกับบัญชีการเคลื่อนไหวเสมอ</p>

      <h3>เอกสาร 4 ชนิดที่ระบบออกให้</h3>
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th>ชนิด</th><th>เลขที่</th><th>ผลต่อสต๊อก</th><th>ใช้เมื่อ</th></tr></thead>
          <tbody>
            <tr><td><?= doc_type_badge('IN') ?></td><td><kbd>RC-202608-0001</kbd></td>
                <td><span class="badge ok">เพิ่ม</span></td><td>ซื้อของเข้า รับคืน ใส่ยอดตั้งต้น</td></tr>
            <tr><td><?= doc_type_badge('OUT') ?></td><td><kbd>IS-202608-0001</kbd></td>
                <td><span class="badge warn">ลด</span></td><td>ส่งลูกค้า เบิกใช้ ตัดของเสีย</td></tr>
            <tr><td><?= doc_type_badge('ADJUST') ?></td><td><kbd>AJ-202608-0001</kbd></td>
                <td><span class="badge muted">ปรับตามผลต่าง</span></td><td>ตรวจนับแล้วยอดไม่ตรง</td></tr>
            <tr><td><?= doc_type_badge('PROD') ?></td><td><kbd>PD-202608-0001</kbd></td>
                <td><span class="badge gold">ลดวัตถุดิบ + เพิ่มสินค้า</span></td><td>ผลิตสินค้าสำเร็จรูป</td></tr>
          </tbody>
        </table>
      </div>
      <p class="small muted">เลขที่เอกสารออกอัตโนมัติ รูปแบบ <kbd>คำนำหน้า-ปีเดือน-ลำดับ 4 หลัก</kbd>
         ตัวนับเริ่มใหม่ทุกเดือน และไม่มีทางซ้ำเพราะล็อกแถวตัวนับระหว่างออกเลข</p>

      <h3>ประเภทสินค้า</h3>
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th>ประเภท</th><th>ความหมาย</th><th>ใช้ในใบผลิตเป็น</th><th>ตัวอย่าง</th></tr></thead>
          <tbody>
            <tr><td><?= product_type_badge('MAT') ?></td><td>วัตถุดิบที่ซื้อเข้ามาใช้ผลิต</td>
                <td>วัตถุดิบ</td><td>กระดาษอาร์ต หมึก CMYK เพลท CTP</td></tr>
            <tr><td><?= product_type_badge('WIP') ?></td><td>กึ่งสำเร็จรูป ผลิตต่อได้</td>
                <td>ได้ทั้งผลผลิตและวัตถุดิบ</td><td>แผ่นพิมพ์ที่ยังไม่เข้าเล่ม</td></tr>
            <tr><td><?= product_type_badge('FG') ?></td><td>สินค้าสำเร็จรูปพร้อมขาย</td>
                <td>ผลผลิต</td><td>นามบัตร โบรชัวร์ หนังสือไสกาว</td></tr>
            <tr><td><?= product_type_badge('PACK') ?></td><td>บรรจุภัณฑ์</td>
                <td>วัตถุดิบ</td><td>กล่องบรรจุงานพิมพ์ ฟิล์มยืด</td></tr>
            <tr><td><?= product_type_badge('OTHER') ?></td><td>อื่น ๆ</td><td>วัตถุดิบ</td><td>—</td></tr>
          </tbody>
        </table>
      </div>
      <div class="alert info">หน้าใบผลิตให้เลือก <b>ผลผลิต</b> ได้เฉพาะ FG และ WIP เท่านั้น
        ถ้าตั้งประเภทผิดจะเปิดใบผลิตให้สินค้าตัวนั้นไม่ได้</div>
    </div>
  </div>

  <!-- ================= 02 ติดตั้ง ================= -->
  <div class="card man-sec" id="install">
    <div class="card-head"><h2><i>02</i> ติดตั้งและตั้งค่า</h2></div>
    <div class="card-body">
      <p class="man-lede">ติดตั้งครั้งเดียวผ่าน <kbd>install.php</kbd> ระบบจะสร้างฐานข้อมูล ตารางทั้งหมด
        และบัญชีผู้ดูแลระบบให้อัตโนมัติ</p>

      <ol class="steps">
        <li>วางโปรเจกต์ไว้ใต้ web root แล้วเปิด Apache กับ MySQL ใน MAMP</li>
        <li>แก้ <kbd>config.php</kbd> — ที่สำคัญคือ <span class="field">DB_PORT</span>
            (MAMP Windows มัก 3306 หรือ 3307, Mac คือ 8889) และ <span class="field">BASE_URL</span>
            ให้ตรงกับโฟลเดอร์ที่วาง</li>
        <li>เปิด <kbd>install.php</kbd> — ถ้าพอร์ตผิด หน้านี้จะไล่ลองพอร์ตอื่นแล้วบอกค่าที่ใช้ได้</li>
        <li>ตั้งชื่อผู้ใช้และรหัสผ่านผู้ดูแลระบบ (อย่างน้อย 6 ตัวอักษร)</li>
        <li>ติ๊ก “ใส่ข้อมูลตัวอย่าง” ถ้าต้องการลองใช้ก่อน (คลัง 2 แห่ง สินค้า 12 รายการพร้อมยอด)</li>
        <li>กดติดตั้ง แล้ว <b>ลบไฟล์ <kbd>install.php</kbd> ทิ้ง</b></li>
      </ol>

      <h3>ค่าที่ใช้บ่อยใน config.php</h3>
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th>ค่า</th><th>ค่าปัจจุบัน</th><th>ความหมาย</th></tr></thead>
          <tbody>
            <tr><td><span class="field">BASE_URL</span></td><td><kbd><?= h(BASE_URL ?: '(ราก)') ?></kbd></td>
                <td>path ที่วางโปรเจกต์ ใช้ประกอบลิงก์ทั้งระบบ</td></tr>
            <tr><td><span class="field">APP_VERSION</span></td><td><kbd><?= h(APP_VERSION) ?></kbd></td>
                <td>ต่อท้าย URL ของ CSS/JS เพื่อล้างแคช — <b>บวกเลขทุกครั้งที่แก้ไฟล์ใน assets/</b></td></tr>
            <tr><td><span class="field">ROWS_PER_PAGE</span></td><td><kbd><?= (int)ROWS_PER_PAGE ?></kbd></td>
                <td>จำนวนแถวต่อหน้า (หน้าความเคลื่อนไหวใช้ 2 เท่าของค่านี้)</td></tr>
            <tr><td><span class="field">LOW_STOCK_LIMIT</span></td><td><kbd><?= (int)LOW_STOCK_LIMIT ?></kbd></td>
                <td>จำนวนรายการใกล้หมดที่ยกมาโชว์บนแดชบอร์ด</td></tr>
            <tr><td><span class="field">ALLOW_NEGATIVE_STOCK</span></td>
                <td><span class="badge <?= ALLOW_NEGATIVE_STOCK ? 'warn' : 'ok' ?>">
                    <?= ALLOW_NEGATIVE_STOCK ? 'true — ยอมให้ติดลบ' : 'false — ห้ามติดลบ' ?></span></td>
                <td>ถ้าปิดไว้ เอกสารที่ทำให้ยอดติดลบจะถูกปฏิเสธทั้งใบ</td></tr>
            <tr><td><span class="field">COMPANY_*</span></td><td class="small"><?= h(COMPANY_NAME) ?></td>
                <td>หัวกระดาษบนใบส่งของ</td></tr>
            <tr><td><span class="field">APP_DEBUG</span></td>
                <td><span class="badge <?= APP_DEBUG ? 'warn' : 'ok' ?>"><?= APP_DEBUG ? 'true' : 'false' ?></span></td>
                <td>แสดงรายละเอียด error — ใช้งานจริงควรตั้งเป็น false</td></tr>
          </tbody>
        </table>
      </div>
      <div class="alert warn">ตอนอัปเกรดเวอร์ชัน ให้รัน <kbd>install.php</kbd> อีกครั้ง
        ตัวอัปเกรดโครงสร้างฐานข้อมูลรันซ้ำได้โดยข้อมูลเดิมไม่หาย</div>
    </div>
  </div>

  <!-- ================= 03 สิทธิ์ ================= -->
  <div class="card man-sec" id="roles">
    <div class="card-head"><h2><i>03</i> ระดับสิทธิ์ผู้ใช้</h2></div>
    <div class="card-body">
      <p class="man-lede">สิทธิ์เป็นลำดับชั้น — <kbd>viewer</kbd> &lt; <kbd>staff</kbd> &lt; <kbd>admin</kbd>
         ระดับสูงกว่าทำสิ่งที่ระดับต่ำกว่าทำได้ทั้งหมด เมนูที่เข้าไม่ได้จะถูกซ่อนจากแถบด้านข้าง</p>
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th>ทำอะไรได้</th>
            <th class="center"><span class="badge muted">ดูอย่างเดียว</span></th>
            <th class="center"><span class="badge info">เจ้าหน้าที่</span></th>
            <th class="center"><span class="badge danger">ผู้ดูแลระบบ</span></th></tr></thead>
          <tbody>
            <tr><td>ดูแดชบอร์ด รายงาน เอกสารย้อนหลัง</td>
                <td class="center">✓</td><td class="center">✓</td><td class="center">✓</td></tr>
            <tr><td>บันทึกรับเข้า / เบิกออก / ปรับปรุง / ใบผลิต</td>
                <td class="center muted">—</td><td class="center">✓</td><td class="center">✓</td></tr>
            <tr><td>เพิ่ม-แก้ไขสินค้า สูตรการผลิต และข้อมูลหลัก</td>
                <td class="center muted">—</td><td class="center">✓</td><td class="center">✓</td></tr>
            <tr><td>ยกเลิกเอกสารและคืนยอดสต๊อก</td>
                <td class="center muted">—</td><td class="center muted">—</td><td class="center">✓</td></tr>
            <tr><td>ลบสินค้า</td>
                <td class="center muted">—</td><td class="center muted">—</td><td class="center">✓</td></tr>
            <tr><td>จัดการผู้ใช้งาน</td>
                <td class="center muted">—</td><td class="center muted">—</td><td class="center">✓</td></tr>
          </tbody>
        </table>
      </div>
      <p>บัญชีที่คุณใช้อยู่ตอนนี้คือ <b><?= h(current_user()['fullname'] ?? '') ?></b>
         สิทธิ์ <span class="badge info"><?= h(role_label(user_role())) ?></span></p>
    </div>
  </div>

  <!-- ================= 04 ตั้งค่าตั้งต้น ================= -->
  <div class="card man-sec" id="setup">
    <div class="card-head"><h2><i>04</i> ลำดับตั้งค่าข้อมูลตั้งต้น</h2></div>
    <div class="card-body">
      <p class="man-lede">ทำตามลำดับนี้ก่อนเริ่มงานจริง เพราะข้อมูลแต่ละชั้นถูกอ้างอิงในชั้นถัดไป</p>
      <ol class="steps">
        <li><b><a href="<?= h(url('warehouses')) ?>">คลังสินค้า</a></b> — ต้องมีอย่างน้อย 1 คลัง
            รหัสคลังสั้น ๆ เช่น <kbd>WH01</kbd> เพราะจะไปขึ้นบนใบส่งของและรายงาน</li>
        <li><b><a href="<?= h(url('units')) ?>">หน่วยนับ</a></b> — แผ่น รีม กก. ลิตร เพลท เล่ม ชุด</li>
        <li><b><a href="<?= h(url('categories')) ?>">หมวดหมู่</a></b> — จัดกลุ่มตามที่ใช้ค้นจริง</li>
        <li><b><a href="<?= h(url('suppliers')) ?>">ผู้ขาย</a></b> — ระบบสรุปจำนวนใบรับเข้าและยอดซื้อสะสมให้เอง</li>
        <li><b><a href="<?= h(url('product_form')) ?>">สินค้า</a></b> — SKU ห้ามซ้ำ เลือกประเภทให้ถูก
            และตั้งจุดสั่งซื้อ</li>
        <li><b>ยอดตั้งต้น</b> — ใส่ของที่มีอยู่จริงด้วย <a href="<?= h(url('receive')) ?>">ใบรับเข้า</a>
            (ได้ต้นทุนติดไปด้วย) หรือ <a href="<?= h(url('adjust')) ?>">ปรับปรุงยอด</a>
            ถ้าต้องการแค่ตั้งยอดให้ตรง</li>
      </ol>
      <div class="alert warn">ข้อมูลหลักที่ถูกใช้งานแล้วจะลบไม่ได้ — คลังที่ยังมีของหรือมีเอกสารอ้างอิง
        และสินค้าที่มีประวัติเคลื่อนไหว ระบบจะเปลี่ยนเป็น <b>ปิดใช้งาน</b> แทนการลบ เพื่อไม่ให้ประวัติขาด</div>
    </div>
  </div>

  <!-- ================= 05 แดชบอร์ด ================= -->
  <div class="card man-sec" id="dashboard">
    <div class="card-head"><h2><i>05</i> แดชบอร์ด</h2>
      <span class="spacer"></span><a class="btn sm ghost" href="<?= h(url('dashboard')) ?>">เปิดหน้านี้</a></div>
    <div class="card-body">
      <p class="man-lede">หน้าแรกหลังเข้าสู่ระบบ สรุปสถานะคลังทั้งหมดในหน้าเดียว
        ตัวเลือก <span class="field">มุมมองคลัง</span> ด้านบนมีผลกับตัวเลขเกือบทั้งหน้า</p>

      <h3>ตัวเลข 4 ช่องบนสุด</h3>
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th>ช่อง</th><th>คิดจาก</th></tr></thead>
          <tbody>
            <tr><td>มูลค่าสต๊อก</td><td>ผลรวมของ ยอดคงเหลือ × ราคาทุนล่าสุด ของทุกสินค้า</td></tr>
            <tr><td>จำนวนชิ้นคงคลัง</td><td>ผลรวมยอดคงเหลือทุกรายการ พร้อมจำนวนสินค้าที่เปิดใช้งาน</td></tr>
            <tr><td>สินค้าถึงจุดสั่งซื้อ</td><td>นับรายการที่ <b>ยอดคงเหลือ ≤ จุดสั่งซื้อ</b> ขึ้นสีแดงเมื่อมีมากกว่า 0</td></tr>
            <tr><td>เข้า / ออก วันนี้</td><td>ผลรวมการเคลื่อนไหวบวกและลบ เฉพาะ<b>วันนี้</b>เท่านั้น</td></tr>
          </tbody>
        </table>
      </div>

      <h3>ตารางด้านล่าง</h3>
      <ul>
        <li><b>สต๊อกแยกตามประเภทสินค้า</b> — สัดส่วนมูลค่าของ MAT / WIP / FG / PACK พร้อมยอดรวมท้ายตาราง</li>
        <li><b>สินค้าถึงจุดสั่งซื้อ</b> — เรียงจากที่ขาดมากที่สุด แสดงสูงสุด <?= (int)LOW_STOCK_LIMIT ?> รายการ</li>
        <li><b>มูลค่าสต๊อกแยกตามคลัง</b> — เทียบสัดส่วนระหว่างคลัง</li>
        <li><b>เอกสารล่าสุด</b> 8 ใบ — เอกสารที่ถูกยกเลิกแสดงจาง ๆ</li>
        <li><b>สินค้าเคลื่อนไหวสูงสุด 30 วัน</b> — นับเฉพาะที่จ่ายออกจริง (เบิกออก และใช้ในการผลิต)</li>
      </ul>
    </div>
  </div>

  <!-- ================= 06 สินค้า ================= -->
  <div class="card man-sec" id="products">
    <div class="card-head"><h2><i>06</i> สินค้า</h2>
      <span class="spacer"></span><a class="btn sm ghost" href="<?= h(url('products')) ?>">เปิดหน้านี้</a></div>
    <div class="card-body">
      <p class="man-lede">รายการสินค้าทั้งหมด ค้นได้จากรหัส ชื่อ หรือบาร์โค้ด กรองตามประเภท หมวดหมู่ และสถานะ
        ค่าเริ่มต้นแสดงเฉพาะสินค้าที่ <b>เปิดใช้งาน</b></p>

      <h3>ช่องกรอกในหน้าเพิ่ม / แก้ไขสินค้า</h3>
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th>ช่อง</th><th>จำเป็น</th><th>คำอธิบาย</th></tr></thead>
          <tbody>
            <tr><td><span class="field">รหัสสินค้า (SKU)</span></td><td class="center"><span class="req">✱</span></td>
                <td>ห้ามซ้ำกับรายการอื่น ระบบเตือนทันทีถ้าซ้ำ</td></tr>
            <tr><td><span class="field">บาร์โค้ด</span></td><td class="center muted">—</td>
                <td>ใช้ค้นหาได้เหมือนรหัสสินค้า</td></tr>
            <tr><td><span class="field">ชื่อสินค้า</span></td><td class="center"><span class="req">✱</span></td>
                <td>ชื่อที่ขึ้นบนเอกสารและใบส่งของ</td></tr>
            <tr><td><span class="field">ประเภทสินค้า</span></td><td class="center"><span class="req">✱</span></td>
                <td>MAT / WIP / FG / PACK / OTHER มีผลต่อการผลิตโดยตรง</td></tr>
            <tr><td><span class="field">หมวดหมู่ · หน่วยนับ</span></td><td class="center muted">—</td>
                <td>ใช้จัดกลุ่ม และแสดงหน่วยต่อท้ายจำนวน</td></tr>
            <tr><td><span class="field">ราคาทุน</span></td><td class="center muted">—</td>
                <td>ใช้คิดมูลค่าสต๊อกทุกรายงาน ระบบอัปเดตให้เองเมื่อรับเข้าโดยระบุราคา และเมื่อผลิตเสร็จ</td></tr>
            <tr><td><span class="field">ราคาขาย</span></td><td class="center muted">—</td>
                <td>ราคาที่ขึ้นบนใบส่งของ ถ้าเว้นว่างจะใช้ราคาที่บันทึกในเอกสารแทน</td></tr>
            <tr><td><span class="field">จุดสั่งซื้อ</span></td><td class="center muted">—</td>
                <td>เมื่อยอดคงเหลือ ≤ ค่านี้ จะขึ้นในรายงานสินค้าใกล้หมดและบนแดชบอร์ด</td></tr>
            <tr><td><span class="field">เปิดใช้งาน</span></td><td class="center muted">—</td>
                <td>ปิดแล้วจะไม่ขึ้นในฟอร์มเอกสารใหม่ แต่ประวัติเดิมยังอยู่</td></tr>
          </tbody>
        </table>
      </div>

      <div class="alert warn">ปุ่มลบขึ้นเฉพาะผู้ดูแลระบบ และถ้าสินค้ามีประวัติการเคลื่อนไหวแล้ว
        ระบบจะ<b>เปลี่ยนเป็นปิดใช้งานให้แทน</b> — สินค้าที่ไม่เคยเคลื่อนไหวเลยเท่านั้นที่ลบจริงได้</div>
      <p>สีของยอดคงเหลือในตาราง <span class="badge ok">เขียว</span> ปกติ ·
         <span class="badge warn">เหลือง</span> ถึงจุดสั่งซื้อ · <span class="badge danger">แดง</span> หมดสต๊อก</p>
    </div>
  </div>

  <!-- ================= 07 รับเข้า ================= -->
  <div class="card man-sec" id="receive">
    <div class="card-head"><h2><i>07</i> รับสินค้าเข้าคลัง</h2>
      <span class="spacer"></span><?= doc_type_badge('IN') ?>
      <a class="btn sm ghost" href="<?= h(url('receive')) ?>">เปิดหน้านี้</a></div>
    <div class="card-body">
      <p class="man-lede">ใช้เมื่อของเข้าคลัง — ซื้อจากผู้ขาย รับคืนจากลูกค้า หรือใส่ยอดตั้งต้นครั้งแรก</p>

      <h3>หัวเอกสาร</h3>
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th>ช่อง</th><th>ค่าเริ่มต้น</th><th>คำอธิบาย</th></tr></thead>
          <tbody>
            <tr><td><span class="field">วันที่เอกสาร</span></td><td>วันนี้</td><td>วันที่ที่บันทึกลงเอกสาร</td></tr>
            <tr><td><span class="field">คลังสินค้า</span></td><td>คลังแรก</td>
                <td>ของจะเข้าคลังนี้ เปลี่ยนแล้วยอดคงเหลือข้างทุกแถวจะอัปเดตตาม</td></tr>
            <tr><td><span class="field">ผู้ขาย</span></td><td>ไม่ระบุ</td>
                <td>ใช้สรุปยอดซื้อรายผู้ขาย และแสดงเป็นผู้ขายล่าสุดในรายงานสินค้าใกล้หมด</td></tr>
            <tr><td><span class="field">เลขที่อ้างอิง</span></td><td>ว่าง</td>
                <td>เลขที่ใบส่งของหรือ PO ของผู้ขาย ค้นหาได้จากหน้าเอกสารทั้งหมด</td></tr>
            <tr><td><span class="field">ผู้รับสินค้า · หมายเหตุ</span></td><td>ว่าง</td><td>บันทึกไว้อ้างอิงภายหลัง</td></tr>
          </tbody>
        </table>
      </div>

      <h3>ตารางรายการสินค้า</h3>
      <ul>
        <li>เปิดมาให้ 3 แถวว่าง กดปุ่มเพิ่มแถวได้เรื่อย ๆ ปุ่มกากบาทท้ายแถวคือลบแถว
            ถ้าเหลือแถวเดียวจะล้างค่าแทนการลบ</li>
        <li>เลือกสินค้าแล้วระบบเติม <b>ราคาทุนล่าสุด</b> ให้อัตโนมัติ แก้ทับได้</li>
        <li>ช่องยอดปัจจุบันคือยอดคงเหลือของสินค้านั้นในคลังที่เลือกไว้ด้านบน</li>
        <li>แถบล่างสรุปจำนวนแถว จำนวนรวม และมูลค่ารวมแบบทันที</li>
      </ul>

      <div class="alert info"><b>ราคาต่อหน่วยสำคัญกว่าที่คิด</b> — ถ้ากรอกมากกว่า 0
        ระบบจะอัปเดตราคาทุนล่าสุดของสินค้าตามราคานั้น ซึ่งกระทบมูลค่าสต๊อกทุกรายงาน และต้นทุนในสูตรการผลิต</div>
      <p class="small muted">สินค้าตัวเดียวกันที่ใส่ซ้ำหลายแถวจะถูกรวมเป็นบรรทัดเดียวตอนบันทึก
         แถวที่ไม่ได้เลือกสินค้าหรือไม่ได้ใส่จำนวนจะถูกข้าม</p>
    </div>
  </div>

  <!-- ================= 08 เบิกออก ================= -->
  <div class="card man-sec" id="issue">
    <div class="card-head"><h2><i>08</i> เบิก / จ่ายสินค้าออก</h2>
      <span class="spacer"></span><?= doc_type_badge('OUT') ?>
      <a class="btn sm ghost" href="<?= h(url('issue')) ?>">เปิดหน้านี้</a></div>
    <div class="card-body">
      <p class="man-lede">ใช้เมื่อของออกจากคลัง และเป็นเอกสารชนิดเดียวที่พิมพ์ <b>ใบส่งของ</b> ได้</p>

      <h3>ช่องที่มีเฉพาะหน้านี้</h3>
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th>ช่อง</th><th>ไปโผล่ที่ไหน</th></tr></thead>
          <tbody>
            <tr><td><span class="field">ลูกค้า / ผู้เบิก</span></td><td>ช่องส่งถึงบนหัวใบส่งของ</td></tr>
            <tr><td><span class="field">วันที่ส่งของ</span></td><td>ช่องวันที่ส่งบนใบส่งของ ถ้าเว้นว่างใช้วันที่เอกสาร</td></tr>
            <tr><td><span class="field">โทรศัพท์ผู้รับ</span></td><td>ใต้ชื่อผู้รับบนใบส่งของ</td></tr>
            <tr><td><span class="field">ที่อยู่จัดส่ง</span></td><td>กล่องที่อยู่บนใบส่งของ</td></tr>
          </tbody>
        </table>
      </div>

      <h3>การเตือนของไม่พอ</h3>
      <p>ขณะกรอก ถ้าจำนวนที่ใส่มากกว่ายอดคงเหลือ ข้อความคงเหลือข้างแถวนั้นจะเปลี่ยนเป็น<b>สีแดง</b>ทันที
         และเมื่อกดบันทึก ระบบจะตรวจซ้ำอีกครั้งที่ฝั่งเซิร์ฟเวอร์</p>
      <div class="alert danger"><b>ของไม่พอ เท่ากับไม่บันทึกทั้งใบ</b> —
        ระบบจะแจ้งว่าสินค้าตัวไหน ที่คลังไหน คงเหลือเท่าไร และต้องการตัดเท่าไร
        ไม่มีการบันทึกครึ่ง ๆ กลาง ๆ (เมื่อ ALLOW_NEGATIVE_STOCK เป็น false)</div>
    </div>
  </div>

  <!-- ================= 09 ปรับปรุงยอด ================= -->
  <div class="card man-sec" id="adjust">
    <div class="card-head"><h2><i>09</i> ปรับปรุงยอดสต๊อก</h2>
      <span class="spacer"></span><?= doc_type_badge('ADJUST') ?>
      <a class="btn sm ghost" href="<?= h(url('adjust')) ?>">เปิดหน้านี้</a></div>
    <div class="card-body">
      <p class="man-lede">ใช้หลังตรวจนับของจริง เพื่อดึงยอดในระบบให้ตรงกับของในคลัง</p>
      <div class="alert info"><b>จุดที่ต่างจากหน้าอื่น</b> — ช่องจำนวนในหน้านี้คือ
        <b>ยอดนับได้จริง</b> ไม่ใช่ผลต่าง กรอกตัวเลขที่นับได้ลงไปตรง ๆ แล้วระบบคำนวณผลต่างให้เอง
        โดยเติมยอดปัจจุบันไว้ให้ก่อน จะได้แก้เฉพาะตัวที่ไม่ตรง</div>
      <ol class="steps">
        <li>เลือกวันที่และ<b>คลังที่ตรวจนับ</b></li>
        <li>ใส่รายการที่นับ แล้วกรอกยอดที่นับได้จริง</li>
        <li>กดบันทึก ระบบบันทึกเฉพาะรายการที่ยอดไม่ตรง ส่วนที่ตรงอยู่แล้วจะถูกข้าม
            ไม่สร้างการเคลื่อนไหวเปล่า ๆ</li>
      </ol>
      <p>ทุกบรรทัดที่ปรับจะมีหมายเหตุอัตโนมัติว่า <kbd>ปรับยอดจาก x เป็น y</kbd>
         ทำให้ย้อนดูได้ว่ายอดขยับเพราะการนับครั้งไหน</p>
    </div>
  </div>

  <!-- ================= 10 BOM ================= -->
  <div class="card man-sec" id="bom">
    <div class="card-head"><h2><i>10</i> สูตรการผลิต (BOM)</h2>
      <span class="spacer"></span><a class="btn sm ghost" href="<?= h(url('bom')) ?>">เปิดหน้านี้</a></div>
    <div class="card-body">
      <p class="man-lede">สูตรบอกว่าการผลิตสินค้า <b>1 หน่วย</b> ใช้วัตถุดิบอะไรเท่าไร
        ตั้งครั้งเดียวใช้ได้ตลอด เพราะใบผลิตจะดึงสูตรมาคูณจำนวนที่ผลิตให้อัตโนมัติ</p>

      <ol class="steps">
        <li>เลือกสินค้าที่จะกำหนดสูตร — รายการนี้มีเฉพาะสินค้าประเภท <b>FG</b> และ <b>WIP</b></li>
        <li>เพิ่มวัตถุดิบทีละรายการจากฟอร์มด้านซ้าย ระบุจำนวนที่ใช้ต่อการผลิต 1 หน่วย
            ใส่ทศนิยมได้ถึง 3 ตำแหน่ง เช่น หมึก <kbd>0.004</kbd> กก. ต่อชุด</li>
        <li>ใส่หมายเหตุได้ เช่น เผื่อเสีย 2 เปอร์เซ็นต์</li>
        <li>ถ้าสินค้าตัวใหม่คล้ายของเดิม ใช้ <b>คัดลอกสูตรจากสินค้าอื่น</b> แล้วค่อยแก้รายละเอียด</li>
      </ol>

      <h3>ตัวเลขที่หน้านี้คำนวณให้</h3>
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th>ตัวเลข</th><th>คิดจาก</th></tr></thead>
          <tbody>
            <tr><td>ต้นทุนวัตถุดิบต่อหน่วย</td><td>ผลรวมของ จำนวนที่ใช้ × ราคาทุนล่าสุด ของวัตถุดิบทุกตัวในสูตร</td></tr>
            <tr><td>กำไรขั้นต้นต่อหน่วย</td><td>ราคาขายที่ตั้งไว้ ลบ ต้นทุนวัตถุดิบ — ติดลบจะขึ้นสีแดง</td></tr>
            <tr><td>ผลิตได้สูงสุด</td>
                <td>ดูวัตถุดิบทุกตัวว่าของที่มีผลิตได้กี่หน่วย แล้วยึด<b>ตัวที่จำกัดที่สุด</b>เป็นคำตอบ</td></tr>
          </tbody>
        </table>
      </div>

      <div class="alert warn"><b>ข้อจำกัดของสูตร</b> — สินค้าเป็นวัตถุดิบของตัวเองไม่ได้ ·
        ตั้งสูตรวนกลับไปมาระหว่างสินค้าสองตัวไม่ได้ ·
        เพิ่มวัตถุดิบที่มีอยู่ในสูตรแล้ว ระบบจะถือว่าเป็นการ<b>แก้จำนวน</b>ของรายการเดิม</div>
    </div>
  </div>

  <!-- ================= 11 ใบผลิต ================= -->
  <div class="card man-sec" id="production">
    <div class="card-head"><h2><i>11</i> ใบผลิต</h2>
      <span class="spacer"></span><?= doc_type_badge('PROD') ?>
      <a class="btn sm ghost" href="<?= h(url('production')) ?>">เปิดหน้านี้</a></div>
    <div class="card-body">
      <p class="man-lede">เอกสารเดียวที่ทำสองอย่างพร้อมกัน — ตัดวัตถุดิบออกจากสต๊อก
        และรับสินค้าสำเร็จรูปเข้าสต๊อก</p>

      <ol class="steps">
        <li>ตั้งหัวเอกสาร — วันที่ผลิต คลังที่ใช้ผลิต เลขที่ใบสั่งผลิต และผู้ควบคุมการผลิต</li>
        <li><b>ตาราง ① สินค้าที่ผลิตได้</b> เลือกสินค้า FG หรือ WIP แล้วใส่จำนวนที่ผลิตได้จริง</li>
        <li>กดปุ่ม <b>ดึงสูตรการผลิต</b> ระบบจะคูณสูตรกับจำนวนที่ผลิตแล้วเติมตาราง ② ให้ทั้งหมด
            ถ้าสินค้าตัวไหนยังไม่มีสูตร ระบบจะบอกชื่อไว้ให้กรอกวัตถุดิบเอง</li>
        <li><b>ตาราง ② วัตถุดิบที่ใช้ไป</b> ตรวจแล้วแก้จำนวนจริงได้ตามหน้างาน เช่น กรณีเสียหายเกินสูตร</li>
        <li>ดูต้นทุนต่อหน่วยที่ระบบคำนวณให้ก่อนบันทึก แล้วกดบันทึกใบผลิต</li>
      </ol>

      <h3>ต้นทุนคิดอย่างไร</h3>
      <pre><span>// ถ้าไม่กรอกต้นทุนต่อหน่วยเองในตาราง ①</span>
ต้นทุนต่อหน่วย  =  <b>ต้นทุนวัตถุดิบรวมทั้งใบ</b>  ÷  <b>จำนวนที่ผลิตได้รวมทั้งใบ</b>

<span>// แล้วนำค่าที่ได้ไปอัปเดตราคาทุนล่าสุดของสินค้านั้นด้วย</span></pre>
      <p>ถ้ากรอกต้นทุนต่อหน่วยเองในตาราง ① ระบบจะใช้ค่าที่กรอกแทนการเฉลี่ย</p>

      <div class="alert warn"><b>ข้อควรรู้</b> — วัตถุดิบที่ตัดและสินค้าที่รับเข้าอยู่ใน<b>คลังเดียวกัน</b>
        ตามที่เลือกบนหัวเอกสาร · ต้องมีวัตถุดิบอย่างน้อย 1 รายการ ·
        สินค้าตัวเดียวกันเป็นทั้งผลผลิตและวัตถุดิบในใบเดียวกันไม่ได้ ·
        ถ้าวัตถุดิบไม่พอ ระบบปฏิเสธทั้งใบเหมือนการเบิกออก</div>
    </div>
  </div>

  <!-- ================= 12 เอกสาร ================= -->
  <div class="card man-sec" id="docs">
    <div class="card-head"><h2><i>12</i> เอกสารและการยกเลิก</h2>
      <span class="spacer"></span><a class="btn sm ghost" href="<?= h(url('docs')) ?>">เปิดหน้านี้</a></div>
    <div class="card-body">
      <p class="man-lede">เอกสารทุกใบเก็บถาวร ค้นย้อนหลังได้ และ<b>แก้ไขไม่ได้</b>
        ถ้าบันทึกผิดต้องยกเลิกแล้วออกใบใหม่</p>

      <h3>หน้าเอกสารทั้งหมด</h3>
      <p>กรองได้ตามชนิดเอกสาร คลัง ช่วงวันที่ และคำค้น — คำค้นจะไล่หาทั้งเลขที่เอกสาร เลขที่อ้างอิง
         ชื่อผู้เบิก และหมายเหตุ เอกสารที่ถูกยกเลิกแสดงจาง ๆ พร้อมป้าย
         <span class="badge danger">ยกเลิก</span></p>

      <h3>หน้ารายละเอียดเอกสาร</h3>
      <ul>
        <li>ส่วนบนคือหัวเอกสาร ตามด้วยรายการสินค้า และตาราง <b>ผลกระทบต่อสต๊อก</b>
            ที่บอกรายบรรทัดว่าเปลี่ยนไปเท่าไร และคงเหลือหลังทำรายการเป็นเท่าไร</li>
        <li>ใบผลิตจะแยกเป็นสองตาราง ① ผลผลิต ② วัตถุดิบ พร้อมสรุปส่วนต่างมูลค่า</li>
        <li>ปุ่มบนหัว — <b>Excel</b> ส่งออกรายการ · <b>พิมพ์</b> · <b>ใบส่งของ / PDF</b>
            (เฉพาะเอกสารเบิกออก) · <b>ยกเลิกเอกสาร</b> (เฉพาะผู้ดูแลระบบ)</li>
      </ul>

      <h3>การยกเลิกเอกสาร</h3>
      <div class="alert danger"><b>เฉพาะผู้ดูแลระบบ</b> — การยกเลิกจะสร้างการเคลื่อนไหวชนิด VOID
        ที่กลับทิศทุกบรรทัดของเอกสารเดิม แล้วเปลี่ยนสถานะเป็นยกเลิกแล้ว
        <b>เอกสารเดิมไม่ถูกลบ</b> ประวัติยังอยู่ครบ และยกเลิกซ้ำใบเดิมไม่ได้</div>
      <p>เมื่อยกเลิกแล้ว ยอดสต๊อกกลับสู่สภาพก่อนบันทึกเอกสารนั้นทันที
         และใบส่งของที่พิมพ์จากเอกสารที่ถูกยกเลิกจะมีลายน้ำ “ยกเลิก” พาดกลางหน้ากระดาษ</p>
    </div>
  </div>

  <!-- ================= 13 ใบส่งของ ================= -->
  <div class="card man-sec" id="delivery">
    <div class="card-head"><h2><i>13</i> ใบส่งของ</h2></div>
    <div class="card-body">
      <p class="man-lede">เลย์เอาต์ A4 พร้อมพิมพ์หรือบันทึกเป็น PDF ผ่านหน้าต่างพิมพ์ของเบราว์เซอร์</p>
      <ol class="steps">
        <li>เปิดเอกสาร <b>เบิก / จ่ายออก</b> ที่ต้องการ แล้วกดปุ่ม <b>ใบส่งของ / PDF</b></li>
        <li>เลือกจำนวนฉบับจากแถบด้านบน — 1 ฉบับ หรือ 2 ฉบับ
            (ต้นฉบับสำหรับลูกค้า และสำเนาสำหรับบริษัท ระบบพิมพ์ป้ายกำกับให้เอง)</li>
        <li>กด <b>พิมพ์ / บันทึก PDF</b> แล้วเลือกเครื่องพิมพ์ หรือ Save as PDF</li>
      </ol>

      <h3>สิ่งที่ปรากฏบนใบส่งของ</h3>
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th>ส่วน</th><th>ดึงมาจาก</th></tr></thead>
          <tbody>
            <tr><td>หัวกระดาษ</td><td>ค่า <span class="field">COMPANY_*</span> ใน config.php
                (ตอนนี้คือ <?= h(COMPANY_NAME) ?>)</td></tr>
            <tr><td>ส่งถึง</td><td>ลูกค้า ที่อยู่จัดส่ง และโทรศัพท์ผู้รับ จากหัวเอกสารเบิกออก</td></tr>
            <tr><td>ราคาต่อหน่วย</td><td>ราคาขายของสินค้า ถ้าไม่ได้ตั้งไว้จะใช้ราคาที่บันทึกในเอกสาร</td></tr>
            <tr><td>ยอดรวมเป็นตัวอักษร</td><td>ระบบแปลงเป็นภาษาไทยให้เอง เช่น หนึ่งพันสองร้อยห้าสิบบาทห้าสิบสตางค์</td></tr>
            <tr><td>ช่องลงนาม</td><td>ผู้ส่งของ · ผู้ตรวจสอบ · ผู้รับสินค้า</td></tr>
          </tbody>
        </table>
      </div>
      <div class="alert info"><b>ตั้งค่าเครื่องพิมพ์</b> — เลือกกระดาษ A4 ตั้งขอบกระดาษเป็น None
        และปิดหัว-ท้ายกระดาษของเบราว์เซอร์ เพราะเลย์เอาต์กำหนดขอบไว้ในตัวเองแล้ว</div>
    </div>
  </div>

  <!-- ================= 14 รายงาน ================= -->
  <div class="card man-sec" id="reports">
    <div class="card-head"><h2><i>14</i> รายงานทั้งหมด</h2></div>
    <div class="card-body">
      <p class="man-lede">ทุกรายงานกรองตามคลังได้ และส่วนใหญ่ส่งออกเป็น CSV หรือ Excel
        ที่รองรับภาษาไทยเต็มรูปแบบ</p>
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th>รายงาน</th><th>ตอบคำถามว่า</th><th>ตัวกรอง</th><th>ส่งออก</th></tr></thead>
          <tbody>
            <tr><td><a href="<?= h(url('report_balance')) ?>">ยอดคงเหลือ</a></td>
                <td>ตอนนี้มีอะไรอยู่เท่าไร มูลค่าเท่าไร เรียงจากมูลค่ามากไปน้อย</td>
                <td class="small">คลัง · ประเภท · หมวดหมู่ · คำค้น · ซ่อนยอดศูนย์</td>
                <td class="small">CSV · Excel · พิมพ์</td></tr>
            <tr><td><a href="<?= h(url('report_lowstock')) ?>">สินค้าใกล้หมด</a></td>
                <td>ต้องสั่งซื้ออะไร เท่าไร เคยซื้อจากใครล่าสุด และใช้งบประมาณราวเท่าไร</td>
                <td class="small">คลัง</td>
                <td class="small">CSV · Excel · พิมพ์เป็นใบสั่งซื้อร่าง</td></tr>
            <tr><td><a href="<?= h(url('stock_card')) ?>">การ์ดสินค้า</a></td>
                <td>สินค้าตัวเดียวเคลื่อนไหวอย่างไรในช่วงเวลาหนึ่ง — ยอดยกมา รับ จ่าย คงเหลือสะสม</td>
                <td class="small">สินค้า · คลัง · ช่วงวันที่ (ค่าเริ่มต้นคือเดือนปัจจุบัน)</td>
                <td class="small">พิมพ์</td></tr>
            <tr><td><a href="<?= h(url('movements')) ?>">ความเคลื่อนไหว</a></td>
                <td>ทุกบรรทัดที่ทำให้สต๊อกขยับทั้งระบบ ใครทำ เมื่อไร จากเอกสารใบไหน</td>
                <td class="small">คลัง · ชนิด · ช่วงวันที่ · คำค้น</td>
                <td class="small">CSV · Excel</td></tr>
          </tbody>
        </table>
      </div>
      <div class="alert info"><b>CSV หรือ Excel</b> — Excel ได้หัวเรื่องและตัวเลขจัดรูปแบบสวยกว่า
        เหมาะกับการส่งต่อให้คนอ่าน ส่วน CSV เหมาะกับการนำไปประมวลผลต่อ
        ทั้งสองแบบฝัง BOM ไว้แล้ว ภาษาไทยจึงไม่เพี้ยนเมื่อเปิดใน Excel</div>
      <p class="small muted">การส่งออกจากหน้าความเคลื่อนไหวจำกัดที่ 10,000 แถวล่าสุดตามเงื่อนไขที่กรอง
         ถ้าต้องการมากกว่านั้นให้แบ่งช่วงวันที่แล้วส่งออกทีละช่วง</p>
    </div>
  </div>

  <!-- ================= 15 กระดิ่ง PO ================= -->
  <div class="card man-sec" id="pobell">
    <div class="card-head"><h2><i>15</i> กระดิ่งใบ PO จากอีเมล</h2>
      <span class="spacer"></span>
      <?php [$poReady, $poWhy] = po_mail_ready(); ?>
      <span class="badge <?= $poReady ? 'ok' : 'muted' ?>"><?= $poReady ? 'พร้อมใช้งาน' : 'ยังไม่ได้ตั้งค่า' ?></span>
    </div>
    <div class="card-body">
      <p class="man-lede">กระดิ่งบนแถบด้านบนจะดึงอีเมลที่ <b>หัวเรื่องมีคำว่า
        “<?= h(MAIL_PO_KEYWORD) ?>”</b> มาแสดงเป็นรายการแจ้งเตือน กดแล้วเปิดใบรับเข้าต่อได้ทันที
        โดยระบบเติมเลขที่อ้างอิงและชื่อผู้ส่งให้ล่วงหน้า</p>

      <?php if (!$poReady): ?>
        <div class="alert warn"><b>สถานะตอนนี้:</b> <?= h($poWhy) ?></div>
      <?php endif; ?>

      <h3>ตั้งค่าที่ไฟล์ <kbd>config.php</kbd></h3>
      <pre><span>// ---------- กล่องอีเมลรับใบสั่งซื้อ (PO) ----------</span>
define('MAIL_PO_ENABLED', <b>true</b>);
define('MAIL_PO_HOST',    '<b>{imap.gmail.com:993/imap/ssl}INBOX</b>');
define('MAIL_PO_USER',    '<b>po@บริษัทของคุณ.com</b>');   <span>// อีเมลที่ลูกค้าส่ง PO เข้ามา</span>
define('MAIL_PO_PASS',    '<b>รหัสผ่านแอป 16 หลัก</b>');   <span>// Gmail ต้องใช้ App Password</span>
define('MAIL_PO_KEYWORD', '<b>PO</b>');                <span>// คำที่ต้องมีในหัวเรื่อง</span>
define('MAIL_PO_DAYS',    <b>14</b>);                  <span>// ย้อนหลังกี่วัน</span>
define('MAIL_PO_LIMIT',   <b>40</b>);                  <span>// ดึงสูงสุดกี่ฉบับต่อครั้ง</span></pre>

      <h3>ต้องเปิดส่วนขยาย imap ก่อน</h3>
      <ol class="steps">
        <li>เปิดไฟล์ <kbd>C:\MAMP\conf\php<?= h(PHP_VERSION) ?>\php.ini</kbd></li>
        <li>หาบรรทัด <kbd>;extension=imap</kbd> แล้วลบเครื่องหมาย <kbd>;</kbd> ข้างหน้าออก</li>
        <li>Stop แล้ว Start Apache ใน MAMP อีกครั้ง</li>
        <li>กลับมาที่กระดิ่ง แล้วกด <b>ตรวจอีเมล</b> — ถ้าเชื่อมต่อได้ ระบบจะบอกจำนวนที่พบทันที</li>
      </ol>

      <h3>หัวเรื่องแบบไหนที่ระบบจับ</h3>
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th>หัวเรื่อง</th><th>ผล</th><th>เลขที่ PO ที่แกะได้</th></tr></thead>
          <tbody>
            <tr><td>PO-2026-0148 สั่งกระดาษอาร์ต</td><td><span class="badge ok">แจ้งเตือน</span></td><td><kbd>PO-2026-0148</kbd></td></tr>
            <tr><td>ใบสั่งซื้อ PO 4821 ด่วน</td><td><span class="badge ok">แจ้งเตือน</span></td><td><kbd>PO4821</kbd></td></tr>
            <tr><td>Purchase Order (po#77)</td><td><span class="badge ok">แจ้งเตือน</span></td><td><kbd>po#77</kbd></td></tr>
            <tr><td>Position update / Report</td><td><span class="badge muted">ข้าม</span></td><td>—</td></tr>
          </tbody>
        </table>
      </div>
      <p class="small muted">ระบบจับคำว่า PO ที่ยืนเป็นคำของตัวเองหรือเกาะกับตัวเลข
         จึงไม่เผลอจับคำอย่าง Position หรือ Report ที่บังเอิญมีตัวอักษรติดกัน</p>

      <h3>การทำงานของกระดิ่ง</h3>
      <ul>
        <li>ตัวเลขสีแดงคือจำนวนใบที่ยัง<b>ไม่ได้อ่าน</b> ระบบเช็คให้เองทุก 90 วินาที
            ถ้ามีใบใหม่จะเด้งกล่องแจ้งเตือนขึ้นมุมขวาบน</li>
        <li>ปุ่ม <b>ตรวจอีเมล</b> สั่งดึงเมลทันทีโดยไม่ต้องรอรอบ</li>
        <li>ปุ่ม <b>สร้างใบรับเข้า</b> ในแต่ละรายการ จะเปิดหน้ารับสินค้าเข้าคลังพร้อมเติมเลขที่อ้างอิงให้
            และเมื่อบันทึกเอกสารเสร็จ ระบบจะผูกใบรับเข้านั้นกับใบ PO และทำเครื่องหมายว่าอ่านแล้วให้อัตโนมัติ</li>
        <li>อีเมลที่เคยดึงแล้วจะไม่ถูกดึงซ้ำ เพราะเก็บ UID ของเมลไว้เทียบ</li>
      </ul>
    </div>
  </div>

  <!-- ================= 16 ผู้ใช้งาน ================= -->
  <div class="card man-sec" id="users">
    <div class="card-head"><h2><i>16</i> ผู้ใช้งาน</h2>
      <span class="spacer"></span><span class="badge danger">เฉพาะผู้ดูแลระบบ</span></div>
    <div class="card-body">
      <p class="man-lede">เพิ่มบัญชี เปลี่ยนสิทธิ์ รีเซ็ตรหัสผ่าน และปิดใช้งานบัญชี</p>
      <ul>
        <li>ชื่อผู้ใช้ห้ามซ้ำ รหัสผ่านอย่างน้อย 6 ตัวอักษร</li>
        <li>ตอน<b>แก้ไข</b> ถ้าเว้นช่องรหัสผ่านว่างไว้ รหัสเดิมจะไม่ถูกเปลี่ยน — ใช้ช่องนี้รีเซ็ตรหัสให้คนที่ลืมรหัส</li>
        <li>ตารางด้านขวาแสดงสถานะและเวลาเข้าใช้ล่าสุดของทุกบัญชี</li>
      </ul>
      <h3>กติกาที่ระบบบังคับไว้</h3>
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th>สิ่งที่ทำไม่ได้</th><th>เหตุผล</th></tr></thead>
          <tbody>
            <tr><td>ลดสิทธิ์หรือปิดใช้งานบัญชีของตัวเอง</td><td>กันไม่ให้ผู้ดูแลระบบล็อกตัวเองออกจากระบบ</td></tr>
            <tr><td>ลบบัญชีของตัวเอง</td><td>เหตุผลเดียวกัน</td></tr>
            <tr><td>ปิดใช้งานผู้ดูแลระบบคนสุดท้าย</td><td>ต้องเหลือผู้ดูแลระบบที่ใช้งานได้อย่างน้อย 1 บัญชี</td></tr>
          </tbody>
        </table>
      </div>
      <div class="alert info"><b>ลบบัญชี เท่ากับ ปิดใช้งาน</b> — เพราะบัญชีถูกอ้างอิงเป็นผู้บันทึกอยู่ในเอกสาร
        การลบจริงจะทำให้ประวัติขาด บัญชีที่ปิดแล้วล็อกอินไม่ได้ แต่ชื่อยังอยู่บนเอกสารเก่า</div>
    </div>
  </div>

  <!-- ================= 17 กฎสำคัญ ================= -->
  <div class="card man-sec" id="rules">
    <div class="card-head"><h2><i>17</i> กฎสำคัญของระบบ</h2></div>
    <div class="card-body">
      <p class="man-lede">หลักไม่กี่ข้อที่อธิบายพฤติกรรมเกือบทั้งหมดของระบบ</p>
      <ol class="steps">
        <li><b>ยอดคงเหลือเปลี่ยนได้ทางเดียว</b> — ผ่านการบันทึกเอกสารหรือการยกเลิกเอกสารเท่านั้น</li>
        <li><b>บัญชีการเคลื่อนไหวเขียนอย่างเดียว</b> — ทุกบรรทัดเก็บทั้งจำนวนที่เปลี่ยนและยอดคงเหลือหลังรายการ
            ไม่มีการลบหรือแก้ย้อนหลัง แก้ไขได้ด้วยการเพิ่มบรรทัดใหม่เท่านั้น</li>
        <li><b>เอกสารแก้ไม่ได้</b> — บันทึกผิดให้ยกเลิกแล้วออกใบใหม่ ร่องรอยการแก้ไขจึงปรากฏบนระบบเสมอ</li>
        <li><b>ทั้งใบผ่านหรือทั้งใบไม่ผ่าน</b> — บันทึกในทรานแซกชันเดียว ถ้ามีบรรทัดใดผิดพลาด
            ทั้งเอกสารถูกยกเลิกและสต๊อกไม่ขยับแม้แต่ตัวเดียว</li>
        <li><b>สต๊อกติดลบไม่ได้</b> ตามค่าเริ่มต้น เปลี่ยนได้ที่ ALLOW_NEGATIVE_STOCK แต่ไม่แนะนำ</li>
        <li><b>ราคาทุนคือราคาล่าสุด</b> — อัปเดตเมื่อรับเข้าโดยระบุราคา และเมื่อผลิตเสร็จ
            มูลค่าสต๊อกคิดจาก ยอดคงเหลือ × ราคาทุนล่าสุด ไม่ใช่ต้นทุนถัวเฉลี่ยหรือ FIFO</li>
      </ol>
      <h3>ความปลอดภัยที่ระบบทำให้แล้ว</h3>
      <ul>
        <li>รหัสผ่านเก็บเป็น hash ไม่มีการเก็บรหัสจริง</li>
        <li>ทุกฟอร์มที่บันทึกข้อมูลมี CSRF token — เปิดหน้าค้างไว้นานแล้วกดบันทึกจะถูกขอให้โหลดหน้าใหม่</li>
        <li>ล็อกอินผิดมีการหน่วงเวลาเล็กน้อยเพื่อกันการสุ่มรหัสผ่าน และบัญชีที่ปิดใช้งานเข้าไม่ได้</li>
        <li>เปลี่ยน session id ใหม่ทุกครั้งที่ล็อกอินสำเร็จ</li>
      </ul>
      <div class="alert info"><b>สรุปให้ผู้ใช้งานจำ</b> — ของเข้าใช้รับสินค้าเข้า · ของออกใช้เบิกจ่ายออก ·
        นับแล้วไม่ตรงใช้ปรับปรุงยอด · ผลิตเสร็จใช้ใบผลิต · บันทึกผิดแจ้งผู้ดูแลระบบให้ยกเลิกแล้วออกใหม่</div>
    </div>
  </div>

  <!-- ================= 18 แก้ปัญหา ================= -->
  <div class="card man-sec" id="trouble">
    <div class="card-head"><h2><i>18</i> แก้ปัญหาที่พบบ่อย</h2></div>
    <div class="card-body">
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th style="width:24%">อาการ</th><th style="width:32%">สาเหตุ</th><th>วิธีแก้</th></tr></thead>
          <tbody>
            <tr><td><b>เปิดหน้าไหนก็เด้งไปหน้าติดตั้ง</b></td>
                <td>ยังไม่มีตาราง users หรือต่อฐานข้อมูลไม่ได้</td>
                <td>รัน install.php ให้จบ ถ้าติดตั้งแล้วให้ตรวจว่า DB_NAME ชี้ถูกฐาน</td></tr>
            <tr><td><b>เชื่อมต่อฐานข้อมูลไม่ได้</b></td>
                <td>MySQL ไม่ได้เปิด หรือพอร์ตไม่ตรงกับที่ MAMP ใช้จริง</td>
                <td>เปิด MySQL แล้วดูพอร์ตที่ MAMP → Preferences → Ports จากนั้นแก้ DB_PORT
                    หน้า install.php จะไล่ลองพอร์ตอื่นและบอกค่าที่ใช้ได้ให้</td></tr>
            <tr><td><b>ขึ้นว่า could not find driver</b></td>
                <td>PHP ที่รันอยู่ไม่ได้เปิด pdo_mysql มักเกิดจาก Apache โหลด PHP คนละเวอร์ชันกับ php.ini ที่อ่าน</td>
                <td>เปิด httpd.conf แล้วทำให้ PHPIniDir กับ LoadModule php_module เป็นเวอร์ชันเดียวกัน
                    จากนั้น Stop แล้ว Start Apache</td></tr>
            <tr><td><b>ขึ้นว่าเซสชันหมดอายุ</b></td>
                <td>เปิดฟอร์มค้างไว้นานจน CSRF token หมดอายุ หรือเพิ่งล็อกอินใหม่ในอีกแท็บ</td>
                <td>โหลดหน้าใหม่แล้วกรอกอีกครั้ง เอกสารยาว ๆ ควรบันทึกให้จบในคราวเดียว</td></tr>
            <tr><td><b>บันทึกไม่ได้ ขึ้นว่าสต๊อกไม่พอ</b></td>
                <td>ยอดคงเหลือของสินค้านั้นในคลังที่เลือกน้อยกว่าจำนวนที่จะตัด</td>
                <td>ตรวจว่าเลือก<b>คลังถูกตัวหรือไม่</b> แล้วรับของเข้าหรือปรับปรุงยอดให้ตรงกับของจริงก่อน</td></tr>
            <tr><td><b>ลบคลังสินค้าไม่ได้</b></td>
                <td>คลังนั้นยังมีของเหลือ หรือมีเอกสารอ้างอิงอยู่</td>
                <td>เบิกของออกให้หมดก่อน หรือปิดใช้งานคลังแทนการลบ</td></tr>
            <tr><td><b>แก้ CSS หรือ JS แล้วหน้าจอไม่เปลี่ยน</b></td>
                <td>เบราว์เซอร์ยังใช้ไฟล์เดิมจากแคช</td>
                <td>เพิ่มเลข APP_VERSION ใน config.php ทุกครั้งที่แก้ไฟล์ใน assets/</td></tr>
            <tr><td><b>ปุ่มดึงสูตรการผลิตไม่เติมอะไรเลย</b></td>
                <td>สินค้าที่เลือกยังไม่มีสูตร หรือยังไม่ได้ใส่จำนวนที่ผลิต</td>
                <td>ใส่จำนวนที่ผลิตก่อน แล้วไปตั้งสูตรที่เมนูสูตรการผลิต ระหว่างนี้กรอกวัตถุดิบเองได้</td></tr>
            <tr><td><b>กระดิ่งบอกว่ายังไม่ได้ตั้งค่า</b></td>
                <td>ยังไม่ได้เปิดส่วนขยาย imap หรือยังไม่ได้กรอกอีเมลใน config.php</td>
                <td>ทำตามหัวข้อ <a href="#pobell">กระดิ่งใบ PO จากอีเมล</a></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ================= 19 ภาคผนวก ================= -->
  <div class="card man-sec" id="db">
    <div class="card-head"><h2><i>19</i> ภาคผนวก: ไฟล์และฐานข้อมูล</h2></div>
    <div class="card-body">
      <h3>โครงสร้างไฟล์</h3>
      <pre>pp/
├─ <b>config.php</b>          <span>ค่าตั้งต้นทั้งหมด รวมถึงกล่องอีเมลรับ PO</span>
├─ <b>index.php</b>           <span>ตัวจัดเส้นทาง ทุกหน้าเรียกผ่าน index.php?p=xxx</span>
├─ <b>api.php</b>             <span>ปลายทาง JSON ของกระดิ่งแจ้งเตือน</span>
├─ login.php · logout.php · install.php
├─ includes/
│  ├─ bootstrap.php     <span>จุดเริ่มต้นร่วม session และ security headers</span>
│  ├─ db.php            <span>การเชื่อมต่อ PDO และหน้าอธิบายเมื่อต่อไม่ได้</span>
│  ├─ auth.php          <span>ล็อกอินและการตรวจสิทธิ์</span>
│  ├─ helpers.php       <span>CSRF วันที่ไทย บาทเป็นตัวอักษร ส่งออก CSV/Excel</span>
│  ├─ <b>stock.php</b>         <span>แกนกลาง post_document() และ void_document()</span>
│  ├─ <b>po_mail.php</b>       <span>ดึงอีเมลใบ PO เข้ากล่องแจ้งเตือน</span>
│  ├─ layout.php        <span>เมนูข้าง แถบบน กระดิ่ง หัวและท้ายหน้า</span>
│  └─ migrate.php       <span>อัปเกรดโครงสร้างฐานข้อมูลแบบรันซ้ำได้</span>
├─ pages/               <span>หนึ่งไฟล์ต่อหนึ่งหน้าจอ รวมถึง manual.php หน้านี้</span>
├─ assets/              <span>style.css และ app.js (กล่องแจ้งเตือน กระดิ่ง ตารางรายการ)</span>
└─ sql/                 <span>schema.sql และชุดข้อมูลตัวอย่าง</span></pre>

      <h3>ตารางในฐานข้อมูล <?= h(DB_NAME) ?></h3>
      <div class="tablewrap">
        <table class="tbl">
          <thead><tr><th>ตาราง</th><th>เก็บอะไร</th></tr></thead>
          <tbody>
            <tr><td><kbd>users</kbd></td><td>บัญชีผู้ใช้ รหัสผ่านที่ hash แล้ว ระดับสิทธิ์ เวลาเข้าใช้ล่าสุด</td></tr>
            <tr><td><kbd>products</kbd></td><td>สินค้า SKU ประเภท ราคาทุน ราคาขาย จุดสั่งซื้อ</td></tr>
            <tr><td><kbd>categories</kbd> · <kbd>units</kbd></td><td>หมวดหมู่และหน่วยนับ</td></tr>
            <tr><td><kbd>warehouses</kbd></td><td>คลังสินค้าและสถานะเปิด/ปิดใช้งาน</td></tr>
            <tr><td><kbd>suppliers</kbd></td><td>ผู้ขาย</td></tr>
            <tr><td><kbd>stock_balances</kbd></td><td><b>ยอดคงเหลือ</b> หนึ่งแถวต่อคู่ สินค้า × คลัง</td></tr>
            <tr><td><kbd>stock_docs</kbd></td><td>หัวเอกสาร เลขที่ ชนิด วันที่ สถานะ ยอดรวม</td></tr>
            <tr><td><kbd>stock_doc_items</kbd></td><td>รายการในเอกสาร แยก MAIN กับ CONSUME</td></tr>
            <tr><td><kbd>stock_movements</kbd></td><td><b>บัญชีการเคลื่อนไหว</b> เขียนอย่างเดียว เก็บยอดคงเหลือหลังทุกรายการ</td></tr>
            <tr><td><kbd>bom_items</kbd></td><td>สูตรการผลิต สินค้า 1 หน่วยใช้วัตถุดิบใดเท่าไร</td></tr>
            <tr><td><kbd>doc_counters</kbd></td><td>ตัวนับเลขที่เอกสาร แยกตามคำนำหน้าและเดือน</td></tr>
            <tr><td><kbd>po_notifications</kbd></td><td>ใบ PO ที่ดึงมาจากอีเมล พร้อมสถานะอ่านแล้วและใบรับเข้าที่ผูกไว้</td></tr>
          </tbody>
        </table>
      </div>

      <h3>งานบำรุงรักษาที่ควรทำ</h3>
      <ul>
        <li><b>สำรองฐานข้อมูล <?= h(DB_NAME) ?></b> เป็นประจำ ข้อมูลทั้งหมดอยู่ในฐานนี้ที่เดียว</li>
        <li><b>ตั้ง APP_DEBUG เป็น false</b> เมื่อเปิดใช้งานจริง</li>
        <li><b>ลบ install.php</b> ออกจากเซิร์ฟเวอร์หลังติดตั้งเสร็จ</li>
        <li><b>ตรวจนับจริงและปรับปรุงยอด</b> เป็นรอบ เช่น ทุกสิ้นเดือน</li>
      </ul>
    </div>
  </div>

  </div>
</div>
