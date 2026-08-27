<?php
require_once __DIR__ . '/../includes/layout.php';

$err  = '';
$edit = null;
$me   = (int)(current_user()['id'] ?? 0);

if (is_post() && inp('action') === 'save') {
    csrf_check();
    $id       = inp_int('id', 0);
    $username = (string)inp('username');
    $fullname = (string)inp('fullname');
    $role     = (string)inp('role', 'staff');
    $active   = inp('is_active') ? 1 : 0;
    $pass     = (string)($_POST['password'] ?? '');

    if ($username === '' || $fullname === '') {
        $err = 'กรุณากรอกชื่อผู้ใช้และชื่อ-นามสกุล';
    } elseif (!in_array($role, ['admin', 'staff', 'viewer'], true)) {
        $err = 'ระดับสิทธิ์ไม่ถูกต้อง';
    } elseif (!$id && strlen($pass) < 6) {
        $err = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    } elseif ($id && $pass !== '' && strlen($pass) < 6) {
        $err = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร';
    } elseif (qv('SELECT id FROM users WHERE username = ? AND id <> ?', [$username, $id])) {
        $err = 'ชื่อผู้ใช้ "' . $username . '" ถูกใช้ไปแล้ว';
    } elseif ($id === $me && (!$active || $role !== 'admin')) {
        $err = 'ไม่สามารถลดสิทธิ์หรือปิดใช้งานบัญชีของตัวเองได้';
    }

    if (!$err) {
        if ($id) {
            q('UPDATE users SET username=?, fullname=?, role=?, is_active=? WHERE id=?',
                [$username, $fullname, $role, $active, $id]);
            if ($pass !== '') {
                q('UPDATE users SET password_hash=? WHERE id=?',
                    [password_hash($pass, PASSWORD_DEFAULT), $id]);
            }
            flash('success', 'บันทึกข้อมูลผู้ใช้เรียบร้อยแล้ว', 'แก้ไขผู้ใช้สำเร็จ');
        } else {
            q('INSERT INTO users (username, password_hash, fullname, role, is_active) VALUES (?,?,?,?,?)',
                [$username, password_hash($pass, PASSWORD_DEFAULT), $fullname, $role, $active]);
            flash('success', 'เพิ่มผู้ใช้งานใหม่เรียบร้อยแล้ว', 'เพิ่มผู้ใช้สำเร็จ');
        }
        redirect(url('users'));
    }
    $edit = ['id' => $id, 'username' => $username, 'fullname' => $fullname,
             'role' => $role, 'is_active' => $active];
}

if (is_post() && inp('action') === 'delete') {
    csrf_check();
    $id = inp_int('id', 0);
    if ($id === $me) {
        flash('danger', 'ลบบัญชีของตัวเองไม่ได้');
    } elseif ((int)qv('SELECT COUNT(*) FROM users WHERE role = \'admin\' AND is_active = 1') <= 1
              && qv('SELECT role FROM users WHERE id = ?', [$id]) === 'admin') {
        flash('danger', 'ต้องมีผู้ดูแลระบบอย่างน้อย 1 บัญชี');
    } else {
        q('UPDATE users SET is_active = 0 WHERE id = ?', [$id]);
        flash('success', 'ปิดใช้งานบัญชีเรียบร้อยแล้ว (เก็บประวัติการทำรายการไว้)', 'ปิดใช้งานบัญชีแล้ว');
    }
    redirect(url('users'));
}

if (!$edit && inp_int('edit')) {
    $edit = q1('SELECT * FROM users WHERE id = ?', [inp_int('edit')]);
}

$rows = qa('SELECT * FROM users ORDER BY role, username');

render_header('ผู้ใช้งาน', 'users');
?>
<?= error_alert($err) ?>

<div class="grid" style="grid-template-columns:minmax(280px,340px) 1fr;gap:18px;align-items:start">
  <div class="card">
    <div class="card-head"><h2><?= $edit ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้ใหม่' ?></h2></div>
    <div class="card-body">
      <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">

        <label>ชื่อผู้ใช้ *
          <input type="text" name="username" value="<?= h($edit['username'] ?? '') ?>" required maxlength="50">
        </label>
        <label>ชื่อ - นามสกุล *
          <input type="text" name="fullname" value="<?= h($edit['fullname'] ?? '') ?>" required maxlength="120">
        </label>
        <label>รหัสผ่าน <?= $edit ? '<span class="muted small">(เว้นว่างหากไม่เปลี่ยน)</span>' : '*' ?>
          <input type="password" name="password" <?= $edit ? '' : 'required' ?> minlength="6">
        </label>
        <label>ระดับสิทธิ์
          <select name="role">
            <?php foreach (['admin', 'staff', 'viewer'] as $r): ?>
              <option value="<?= $r ?>" <?= ($edit['role'] ?? 'staff') === $r ? 'selected' : '' ?>>
                <?= h(role_label($r)) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label style="display:flex;gap:8px;align-items:center">
          <input type="checkbox" name="is_active" value="1" style="width:auto;margin:0"
                 <?= ($edit['is_active'] ?? 1) ? 'checked' : '' ?>> เปิดใช้งานบัญชี
        </label>

        <div class="btnrow">
          <button class="btn primary" type="submit"><?= $edit ? 'บันทึก' : 'เพิ่มผู้ใช้' ?></button>
          <?php if ($edit): ?><a class="btn" href="<?= h(url('users')) ?>">ยกเลิก</a><?php endif; ?>
        </div>
      </form>

      <div class="alert info" style="margin:16px 0 0">
        <b>ผู้ดูแลระบบ</b> ทำได้ทุกอย่างรวมถึงยกเลิกเอกสาร<br>
        <b>เจ้าหน้าที่</b> บันทึกเอกสารและจัดการข้อมูลสินค้าได้<br>
        <b>ดูอย่างเดียว</b> เปิดดูรายงานได้เท่านั้น
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><h2>ผู้ใช้งานทั้งหมด</h2>
      <span class="spacer"></span><span class="badge muted"><?= count($rows) ?> บัญชี</span></div>
    <div class="tablewrap">
      <table class="tbl">
        <thead><tr><th>ชื่อผู้ใช้</th><th>ชื่อ - นามสกุล</th><th>สิทธิ์</th>
                   <th>สถานะ</th><th>เข้าใช้ล่าสุด</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $u): ?>
          <tr<?= $u['is_active'] ? '' : ' style="opacity:.55"' ?>>
            <td><code><?= h($u['username']) ?></code>
                <?= (int)$u['id'] === $me ? ' <span class="badge info">คุณ</span>' : '' ?></td>
            <td><?= h($u['fullname']) ?></td>
            <td><span class="badge <?= $u['role'] === 'admin' ? 'danger' : ($u['role'] === 'staff' ? 'info' : 'muted') ?>">
                <?= h(role_label($u['role'])) ?></span></td>
            <td><span class="badge <?= $u['is_active'] ? 'ok' : 'muted' ?>">
                <?= $u['is_active'] ? 'ใช้งาน' : 'ปิด' ?></span></td>
            <td class="small muted nowrap"><?= h(thai_date($u['last_login_at'], true)) ?></td>
            <td class="right nowrap">
              <a class="btn sm ghost" href="<?= h(url('users', ['edit' => $u['id']])) ?>">แก้ไข</a>
              <?php if ((int)$u['id'] !== $me && $u['is_active']): ?>
                <form method="post" style="display:inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <button class="btn sm ghost" style="color:var(--danger)"
                          data-confirm="ปิดใช้งานบัญชี <?= h($u['username']) ?> ?">ปิดใช้งาน</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php render_footer(); ?>
