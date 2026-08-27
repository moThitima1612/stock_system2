<?php
/**
 * หน้าจัดการข้อมูลหลักแบบใช้ร่วมกัน (หมวดหมู่ / หน่วยนับ / คลัง / ผู้ขาย)
 * ผู้เรียกกำหนด $CRUD = ['table','title','key','fields','order','guard'?,'columns'?]
 */
require_once __DIR__ . '/../includes/layout.php';

$T      = $CRUD['table'];
$FIELDS = $CRUD['fields'];
$ORDER  = $CRUD['order'] ?? 'id';
$KEY    = $CRUD['key'];
$err    = '';
$edit   = null;

/* ---------- บันทึก ---------- */
if (is_post() && inp('action') === 'save') {
    csrf_check();
    $id   = inp_int('id', 0);
    $data = [];
    foreach ($FIELDS as $name => $f) {
        $v = ($f['type'] ?? 'text') === 'checkbox' ? (inp($name) ? 1 : 0) : inp($name, '');
        if (($f['type'] ?? '') === 'number') {
            $v = $v === '' ? 0 : (float)$v;
        }
        if (!empty($f['required']) && ($v === '' || $v === null)) {
            $err = 'กรุณากรอก "' . $f['label'] . '"';
        }
        $data[$name] = ($v === '' && empty($f['required'])) ? null : $v;
    }

    if (!$err) {
        foreach ($FIELDS as $name => $f) {
            if (!empty($f['unique']) && $data[$name] !== null) {
                if (qv("SELECT id FROM `$T` WHERE `$name` = ? AND id <> ?", [$data[$name], $id])) {
                    $err = '"' . $data[$name] . '" มีอยู่ในระบบแล้ว';
                    break;
                }
            }
        }
    }

    if (!$err) {
        $cols = array_keys($data);
        if ($id) {
            $set = implode(', ', array_map(fn($c) => "`$c` = ?", $cols));
            q("UPDATE `$T` SET $set WHERE id = ?", array_merge(array_values($data), [$id]));
            flash('success', 'บันทึกการแก้ไขเรียบร้อยแล้ว', 'แก้ไขข้อมูลสำเร็จ');
        } else {
            $ph = implode(',', array_fill(0, count($cols), '?'));
            q("INSERT INTO `$T` (`" . implode('`,`', $cols) . "`) VALUES ($ph)", array_values($data));
            flash('success', 'เพิ่มข้อมูลเรียบร้อยแล้ว', 'เพิ่มข้อมูลสำเร็จ');
        }
        redirect(url($KEY));
    }
    $edit = array_merge(['id' => $id], $data);
}

/* ---------- ลบ ---------- */
if (is_post() && inp('action') === 'delete') {
    csrf_check();
    $id = inp_int('id', 0);
    $problem = isset($CRUD['guard']) ? ($CRUD['guard'])($id) : null;
    if ($problem) {
        flash('danger', h($problem));
    } else {
        try {
            q("DELETE FROM `$T` WHERE id = ?", [$id]);
            flash('success', 'ลบข้อมูลเรียบร้อยแล้ว', 'ลบข้อมูลสำเร็จ');
        } catch (PDOException $e) {
            flash('danger', 'ลบไม่ได้ เพราะข้อมูลนี้ถูกใช้งานอยู่ในระบบ', 'ลบข้อมูลไม่ได้');
        }
    }
    redirect(url($KEY));
}

if (!$edit && inp_int('edit')) {
    $edit = q1("SELECT * FROM `$T` WHERE id = ?", [inp_int('edit')]);
}

$rows = qa("SELECT * FROM `$T` ORDER BY $ORDER");

render_header($CRUD['title'], $KEY);
?>
<?= error_alert($err) ?>

<div class="grid" style="grid-template-columns:minmax(280px,340px) 1fr;gap:18px;align-items:start">
  <div class="card">
    <div class="card-head"><h2><?= $edit ? 'แก้ไขข้อมูล' : 'เพิ่มใหม่' ?></h2></div>
    <div class="card-body">
      <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">

        <?php foreach ($FIELDS as $name => $f):
          $val  = $edit[$name] ?? ($f['default'] ?? '');
          $type = $f['type'] ?? 'text'; ?>
          <?php if ($type === 'checkbox'): ?>
            <label style="display:flex;gap:8px;align-items:center">
              <input type="checkbox" name="<?= h($name) ?>" value="1" style="width:auto;margin:0"
                     <?= $val ? 'checked' : '' ?>> <?= h($f['label']) ?>
            </label>
          <?php elseif ($type === 'textarea'): ?>
            <label><?= h($f['label']) ?><?= !empty($f['required']) ? ' *' : '' ?>
              <textarea name="<?= h($name) ?>" <?= !empty($f['required']) ? 'required' : '' ?>><?= h($val) ?></textarea>
            </label>
          <?php else: ?>
            <label><?= h($f['label']) ?><?= !empty($f['required']) ? ' *' : '' ?>
              <input type="<?= h($type) ?>" name="<?= h($name) ?>" value="<?= h($val) ?>"
                     <?= isset($f['step']) ? 'step="' . h($f['step']) . '"' : '' ?>
                     <?= !empty($f['required']) ? 'required' : '' ?>
                     <?= isset($f['placeholder']) ? 'placeholder="' . h($f['placeholder']) . '"' : '' ?>>
            </label>
          <?php endif; ?>
        <?php endforeach; ?>

        <div class="btnrow">
          <button class="btn primary" type="submit"><?= $edit ? 'บันทึกการแก้ไข' : 'เพิ่มข้อมูล' ?></button>
          <?php if ($edit): ?><a class="btn" href="<?= h(url($KEY)) ?>">ยกเลิก</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h2>รายการทั้งหมด</h2>
      <span class="spacer"></span>
      <span class="badge muted"><?= number_format(count($rows)) ?> รายการ</span>
    </div>
    <?php if (!$rows): ?>
      <div class="empty">ยังไม่มีข้อมูล — เพิ่มรายการแรกจากฟอร์มด้านซ้าย</div>
    <?php else: ?>
    <div class="tablewrap">
      <table class="tbl">
        <thead><tr>
          <?php foreach ($FIELDS as $name => $f): ?><th><?= h($f['label']) ?></th><?php endforeach; ?>
          <?php if (!empty($CRUD['columns'])): foreach ($CRUD['columns'] as $c): ?>
            <th class="num"><?= h($c['label']) ?></th>
          <?php endforeach; endif; ?>
          <th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <?php foreach ($FIELDS as $name => $f): ?>
              <td<?= ($f['type'] ?? '') === 'number' ? ' class="num"' : '' ?>>
                <?php if (($f['type'] ?? '') === 'checkbox'): ?>
                  <span class="badge <?= $r[$name] ? 'ok' : 'muted' ?>"><?= $r[$name] ? 'ใช้งาน' : 'ปิด' ?></span>
                <?php else: ?>
                  <?= h($r[$name] ?? '—') ?>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
            <?php if (!empty($CRUD['columns'])): foreach ($CRUD['columns'] as $c): ?>
              <td class="num"><?= ($c['value'])($r) ?></td>
            <?php endforeach; endif; ?>
            <td class="right nowrap">
              <a class="btn sm ghost" href="<?= h(url($KEY, ['edit' => $r['id']])) ?>">แก้ไข</a>
              <form method="post" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn sm ghost" style="color:var(--danger)"
                        data-confirm="ยืนยันการลบรายการนี้?">ลบ</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php render_footer(); ?>
