/* ============ ระบบสต๊อกคลังสินค้า — ฟรอนต์เอนด์ ============ */
(function () {
  'use strict';

  /* ยืนยันก่อนทำรายการที่ย้อนกลับไม่ได้ — ใช้กล่องยืนยันของระบบแทน confirm() ของเบราว์เซอร์ */
  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-confirm]');
    if (!el || el.dataset.ppOk === '1') { return; }

    e.preventDefault();
    e.stopPropagation();

    var danger = /ลบ|ยกเลิก|ปิดใช้งาน/.test(el.getAttribute('data-confirm') || '');
    PP.confirm({
      message: el.getAttribute('data-confirm'),
      title:   el.getAttribute('data-confirm-title') || (danger ? 'ยืนยันการทำรายการ' : 'ยืนยัน'),
      okText:  el.getAttribute('data-confirm-ok') || (danger ? 'ยืนยัน ลบ' : 'ยืนยัน'),
      danger:  danger,
      onOk: function () {
        el.dataset.ppOk = '1';
        if (el.form && typeof el.form.requestSubmit === 'function') {
          el.form.requestSubmit(el);
        } else {
          el.click();
        }
        delete el.dataset.ppOk;
      }
    });
  });

  /* ส่งฟอร์มกรองอัตโนมัติเมื่อเปลี่ยน select */
  document.addEventListener('change', function (e) {
    if (e.target.matches('[data-autosubmit]')) {
      e.target.form.submit();
    }
  });

  var PP = window.PP || {};
  window.PP = PP;

  /* ================= แจ้งเตือนแบบ Toast =================
     PP.toast('success', 'ข้อความ')  หรือ
     PP.toast({type:'danger', title:'...', message:'...', timeout:6000})
     ข้อความรองรับ HTML เพราะฝั่ง PHP ส่งลิงก์มาด้วย
  ====================================================== */
  var ICONS = {
    success: '<path d="M20 6 9 17l-5-5"/>',
    danger:  '<circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/>',
    warn:    '<path d="M12 3 2 20h20z"/><path d="M12 10v4M12 17h.01"/>',
    info:    '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
    ask:     '<path d="M12 3 2 20h20z"/><path d="M12 10v4M12 17h.01"/>'
  };
  var TITLES = {
    success: 'ทำรายการสำเร็จ',
    danger:  'ทำรายการไม่สำเร็จ',
    warn:    'โปรดตรวจสอบ',
    info:    'แจ้งให้ทราบ'
  };
  var LIFE = { success: 4600, info: 5200, warn: 6400, danger: 8000 };

  function svg(name) {
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' + (ICONS[name] || ICONS.info) + '</svg>';
  }

  function toastHost() {
    var host = document.getElementById('pp-toasts');
    if (!host) {
      host = document.createElement('div');
      host.id = 'pp-toasts';
      host.setAttribute('role', 'status');
      host.setAttribute('aria-live', 'polite');
      document.body.appendChild(host);
    }
    return host;
  }

  PP.toast = function (type, message, title) {
    var o = (type && typeof type === 'object') ? type
          : { type: type, message: message, title: title };
    o.type = ICONS[o.type] ? o.type : 'info';
    if (!o.message) { return null; }

    var host = toastHost();
    var life = o.timeout || LIFE[o.type] || 5000;

    var el = document.createElement('div');
    el.className = 'toast ' + o.type;
    el.innerHTML =
      '<span class="tico">' + svg(o.type) + '</span>' +
      '<span class="tbody">' +
        '<span class="ttitle">' + (o.title || TITLES[o.type]) + '</span>' +
        '<div class="tmsg">' + o.message + '</div>' +
      '</span>' +
      '<button type="button" class="tclose" aria-label="ปิด">&times;</button>' +
      '<i class="tbar"></i>';

    host.appendChild(el);
    requestAnimationFrame(function () { el.classList.add('in'); });

    var bar = el.querySelector('.tbar');
    var timer = null, started = 0, left = life;

    function close() {
      if (!el.parentNode) { return; }
      clearTimeout(timer);
      el.classList.add('out');
      setTimeout(function () { if (el.parentNode) { el.parentNode.removeChild(el); } }, 340);
    }
    function run() {
      started = Date.now();
      if (bar) {
        bar.style.transition = 'none';
        bar.style.transform  = 'scaleX(' + (left / life) + ')';
        requestAnimationFrame(function () {
          bar.style.transition = 'transform ' + left + 'ms linear';
          bar.style.transform  = 'scaleX(0)';
        });
      }
      timer = setTimeout(close, left);
    }
    function hold() {
      clearTimeout(timer);
      left = Math.max(1200, left - (Date.now() - started));
      if (bar) {
        var w = bar.getBoundingClientRect().width / el.getBoundingClientRect().width;
        bar.style.transition = 'none';
        bar.style.transform  = 'scaleX(' + w + ')';
      }
    }

    el.querySelector('.tclose').addEventListener('click', close);
    el.addEventListener('mouseenter', hold);
    el.addEventListener('mouseleave', run);
    run();
    return { close: close, el: el };
  };

  /* ================= กล่องยืนยัน ================= */
  PP.confirm = function (opt) {
    opt = opt || {};
    var old = document.getElementById('pp-modal');
    if (old) { old.remove(); }

    var wrap = document.createElement('div');
    wrap.id = 'pp-modal';
    wrap.className = opt.danger ? '' : 'ask';
    wrap.innerHTML =
      '<div class="box" role="dialog" aria-modal="true">' +
        '<div class="mico">' + svg(opt.danger ? 'danger' : 'ask') + '</div>' +
        '<h3>' + (opt.title || 'ยืนยันการทำรายการ') + '</h3>' +
        '<p>' + (opt.message || '') + '</p>' +
        '<div class="mbtns">' +
          '<button type="button" class="btn" data-no>ยกเลิก</button>' +
          '<button type="button" class="btn ' + (opt.danger ? 'danger' : 'primary') + '" data-yes>' +
            (opt.okText || 'ยืนยัน') + '</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(wrap);
    requestAnimationFrame(function () { wrap.classList.add('in'); });

    var yes = wrap.querySelector('[data-yes]');
    yes.focus();

    function shut(ok) {
      wrap.classList.remove('in');
      document.removeEventListener('keydown', onKey);
      setTimeout(function () { if (wrap.parentNode) { wrap.remove(); } }, 220);
      if (ok && typeof opt.onOk === 'function') { opt.onOk(); }
    }
    function onKey(e) {
      if (e.key === 'Escape') { shut(false); }
      if (e.key === 'Enter')  { e.preventDefault(); shut(true); }
    }

    yes.addEventListener('click', function () { shut(true); });
    wrap.querySelector('[data-no]').addEventListener('click', function () { shut(false); });
    wrap.addEventListener('click', function (e) { if (e.target === wrap) { shut(false); } });
    document.addEventListener('keydown', onKey);
  };

  /* ข้อความจากฝั่งเซิร์ฟเวอร์ (flash) — เปลี่ยนเป็น toast ตอนโหลดหน้า */
  function seedFlash() {
    var seeds = document.querySelectorAll('.flashseed');
    for (var i = 0; i < seeds.length; i++) {
      (function (s, delay) {
        setTimeout(function () {
          PP.toast({
            type:    s.getAttribute('data-type') || 'info',
            title:   s.getAttribute('data-title') || '',
            message: s.innerHTML
          });
        }, delay);
      })(seeds[i], i * 160);
      seeds[i].classList.add('done');
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', seedFlash);
  } else {
    seedFlash();
  }

  function fmtQty(n) {
    var s = Number(n || 0).toFixed(3).replace(/\.?0+$/, '');
    return s === '' || s === '-' ? '0' : s;
  }
  PP.fmtQty = fmtQty;

  var TYPE_LABEL = {
    MAT: 'วัตถุดิบ', WIP: 'กึ่งสำเร็จรูป', FG: 'สินค้าสำเร็จรูป',
    PACK: 'บรรจุภัณฑ์', OTHER: 'อื่น ๆ'
  };

  /* ---------------- ตารางกรอกรายการสินค้า ----------------
     opt = {
       tbody, addBtn, warehouse, fixedWarehouse,
       mode: 'IN'|'OUT'|'ADJUST'|'PROD_OUT'|'PROD_IN',
       products: [{id,sku,name,unit,cost,type}],   // ไม่ระบุ = PP.products
       names: {product, qty, cost},
       totals: {rows, qty, amt},
       showCost, rows
     }
     คืนค่า instance ที่มี addRow / recalc / clear / setRows
  ------------------------------------------------------- */
  PP.initItems = function (opt) {
    var tbody = document.querySelector(opt.tbody || '#itemRows');
    if (!tbody) return null;

    var addBtn   = opt.addBtn ? document.querySelector(opt.addBtn) : document.querySelector('#addRow');
    var whSel    = opt.warehouse ? document.querySelector(opt.warehouse) : null;
    var products = opt.products || PP.products || [];
    var balances = PP.balances || {};
    var mode     = opt.mode || 'IN';
    var showCost = opt.showCost !== false;
    var names    = opt.names || {};
    var nProduct = names.product || 'product_id[]';
    var nQty     = names.qty     || 'qty[]';
    var nCost    = names.cost    || 'unit_cost[]';
    var totals   = opt.totals || { rows: '#sumRows', qty: '#sumQty', amt: '#sumAmt' };
    var takesOut = mode === 'OUT' || mode === 'PROD_OUT';

    var byId = {};
    products.forEach(function (p) { byId[p.id] = p; });

    function currentWh() { return whSel ? whSel.value : (opt.fixedWarehouse || ''); }
    function balanceOf(pid) {
      var k = currentWh() + '_' + pid;
      return balances[k] ? Number(balances[k]) : 0;
    }

    function optionsHtml() {
      var groups = {}, order = [];
      products.forEach(function (p) {
        var g = p.type || 'OTHER';
        if (!groups[g]) { groups[g] = []; order.push(g); }
        groups[g].push(p);
      });
      var h = '<option value="">— เลือกสินค้า —</option>';
      var multi = order.length > 1;
      order.forEach(function (g) {
        if (multi) h += '<optgroup label="' + (TYPE_LABEL[g] || g) + '">';
        groups[g].forEach(function (p) {
          h += '<option value="' + p.id + '">' + p.sku + ' · ' + p.name + '</option>';
        });
        if (multi) h += '</optgroup>';
      });
      return h;
    }

    function rowHtml() {
      return '' +
        '<td><select name="' + nProduct + '" class="p-sel">' + optionsHtml() + '</select></td>' +
        '<td class="p-stock"><span class="stockhint">—</span></td>' +
        '<td class="num"><input type="number" name="' + nQty + '" class="p-qty" step="0.001" min="0" value=""></td>' +
        (showCost
          ? '<td class="num"><input type="number" name="' + nCost + '" class="p-cost" step="0.01" min="0" value="0"></td>' +
            '<td class="num p-amt">0.00</td>'
          : '<td style="display:none"><input type="hidden" name="' + nCost + '" value="0"></td>') +
        '<td class="right"><button type="button" class="rm" title="ลบแถว">&times;</button></td>';
    }

    function addRow(prefill) {
      var tr = document.createElement('tr');
      tr.innerHTML = rowHtml();
      tbody.appendChild(tr);
      if (prefill && prefill.product_id) {
        tr.querySelector('.p-sel').value = prefill.product_id;
        if (prefill.qty != null) {
          tr.querySelector('.p-qty').value = prefill.qty;
          tr.querySelector('.p-qty').dataset.touched = '1';
        }
        syncRow(tr);
      }
      return tr;
    }

    function syncRow(tr) {
      var sel   = tr.querySelector('.p-sel');
      var pid   = sel.value;
      var hint  = tr.querySelector('.stockhint');
      var qtyI  = tr.querySelector('.p-qty');
      var costI = tr.querySelector('.p-cost');
      var p     = byId[pid];

      if (!p) {
        if (hint) { hint.textContent = '—'; hint.className = 'stockhint'; }
        return;
      }
      var bal  = balanceOf(pid);
      var need = parseFloat(qtyI.value) || 0;
      if (hint) {
        hint.textContent = 'คงเหลือ ' + fmtQty(bal) + (p.unit ? ' ' + p.unit : '');
        hint.className = 'stockhint' + (takesOut && (bal <= 0 || bal < need) ? ' low' : '');
      }
      if (costI && !costI.dataset.touched) costI.value = Number(p.cost || 0).toFixed(2);
      if (mode === 'ADJUST' && !qtyI.dataset.touched && qtyI.value === '') qtyI.value = fmtQty(bal);
    }

    function recalc() {
      var totQty = 0, totAmt = 0;
      tbody.querySelectorAll('tr').forEach(function (tr) {
        var qty   = parseFloat(tr.querySelector('.p-qty').value) || 0;
        var costI = tr.querySelector('.p-cost');
        var cost  = costI ? (parseFloat(costI.value) || 0) : 0;
        var amtC  = tr.querySelector('.p-amt');
        if (amtC) amtC.textContent = (qty * cost).toFixed(2);
        totQty += qty;
        totAmt += qty * cost;
        syncRow(tr);
      });
      var q = document.querySelector(totals.qty);
      var a = document.querySelector(totals.amt);
      var n = document.querySelector(totals.rows);
      if (q) q.textContent = fmtQty(totQty);
      if (a) a.textContent = totAmt.toFixed(2);
      if (n) n.textContent = tbody.querySelectorAll('tr').length;
      if (opt.onChange) opt.onChange({ qty: totQty, amount: totAmt });
      return { qty: totQty, amount: totAmt };
    }

    function clear() { tbody.innerHTML = ''; }

    function setRows(rows) {
      clear();
      (rows && rows.length ? rows : [null]).forEach(function (r) { addRow(r); });
      recalc();
    }

    tbody.addEventListener('change', function (e) {
      if (e.target.matches('.p-sel')) syncRow(e.target.closest('tr'));
      recalc();
    });
    tbody.addEventListener('input', function (e) {
      if (e.target.matches('.p-cost, .p-qty')) e.target.dataset.touched = '1';
      recalc();
    });
    tbody.addEventListener('click', function (e) {
      if (e.target.closest('.rm')) {
        var tr = e.target.closest('tr');
        if (tbody.querySelectorAll('tr').length > 1) {
          tr.remove();
        } else {
          tr.querySelectorAll('input').forEach(function (i) { i.value = ''; delete i.dataset.touched; });
          tr.querySelector('.p-sel').value = '';
          syncRow(tr);
        }
        recalc();
      }
    });
    if (addBtn) addBtn.addEventListener('click', function () { addRow().querySelector('.p-sel').focus(); });
    if (whSel) whSel.addEventListener('change', recalc);

    /* กันส่งฟอร์มว่าง (เฉพาะตารางหลัก) */
    if (opt.validate !== false) {
      var form = tbody.closest('form');
      if (form && !form.dataset.ppValidate) {
        form.dataset.ppValidate = '1';
        form.addEventListener('submit', function (e) {
          var ok = false;
          form.querySelectorAll('tbody tr').forEach(function (tr) {
            var s = tr.querySelector('.p-sel');
            var q = tr.querySelector('.p-qty');
            if (s && s.value && ((parseFloat(q.value) || 0) > 0 || mode === 'ADJUST')) ok = true;
          });
          if (!ok) {
            e.preventDefault();
            alert('กรุณาเลือกสินค้าและระบุจำนวนอย่างน้อย 1 รายการ');
          }
        });
      }
    }

    setRows(opt.rows && opt.rows.length ? opt.rows : [null, null, null]);

    var api = { addRow: addRow, recalc: recalc, clear: clear, setRows: setRows, tbody: tbody };
    return api;
  };

  /* ================= กระดิ่งแจ้งเตือนใบ PO จากอีเมล ================= */
  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function initBell() {
    var bell = document.getElementById('poBell');
    if (!bell) { return; }

    var btn     = bell.querySelector('.bellbtn');
    var dot     = bell.querySelector('.dot');
    var panel   = bell.querySelector('.bellpanel');
    var list    = bell.querySelector('[data-po-list]');
    var API     = bell.dataset.api;
    var CSRF    = bell.dataset.csrf;
    var RECEIVE = bell.dataset.receive;
    var unread  = parseInt(dot.textContent, 10) || 0;

    function post(action, extra) {
      var body = new URLSearchParams(extra || {});
      body.set('_csrf', CSRF);
      return fetch(API + '?a=' + action, {
        method: 'POST', body: body, credentials: 'same-origin',
        headers: { 'X-CSRF': CSRF }
      }).then(function (r) { return r.json(); });
    }
    function get(action) {
      return fetch(API + '?a=' + action, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); });
    }

    function setUnread(n, pulse) {
      unread = n || 0;
      dot.textContent = unread > 99 ? '99+' : unread;
      dot.hidden = unread === 0;
      if (pulse && unread > 0) {
        dot.classList.remove('pulse');
        void dot.offsetWidth;
        dot.classList.add('pulse');
      }
    }

    function itemHtml(it) {
      var href = RECEIVE + (RECEIVE.indexOf('?') > -1 ? '&' : '?') +
                 'po=' + it.id +
                 '&ref_no=' + encodeURIComponent(it.po_no || it.subject || '') +
                 '&contact=' + encodeURIComponent(it.from || '');
      return '' +
        '<div class="poitem' + (it.is_read ? '' : ' unread') + '" data-id="' + it.id + '">' +
          '<div class="potop">' +
            '<span class="posub">' + esc(it.subject) + '</span>' +
            '<span class="powhen">' + esc(it.when) + '</span>' +
          '</div>' +
          '<div class="pometa">' +
            (it.po_no ? '<span class="badge gold">' + esc(it.po_no) + '</span>' : '') +
            (it.from ? '<span>' + esc(it.from) + '</span>' : '') +
            (it.attach ? '<span>· ไฟล์แนบ ' + it.attach + '</span>' : '') +
          '</div>' +
          (it.excerpt ? '<div class="poex">' + esc(it.excerpt) + '</div>' : '') +
          '<div class="poact">' +
            '<a class="btn sm primary" href="' + esc(href) + '">สร้างใบรับเข้า</a>' +
            (it.is_read ? '' : '<button type="button" class="btn sm ghost" data-read="' + it.id + '">อ่านแล้ว</button>') +
          '</div>' +
        '</div>';
    }

    function render(d) {
      if (!d || !d.ok) {
        list.innerHTML = '<div class="bellempty">โหลดรายการไม่สำเร็จ</div>';
        return;
      }
      setUnread(d.unread);
      var html = '';
      if (!d.ready && d.setup) {
        html += '<div class="bellsetup"><b>ยังไม่ได้ตั้งค่ากล่องอีเมล</b><br>' + esc(d.setup) + '</div>';
      }
      if (!d.items.length) {
        html += '<div class="bellempty">ยังไม่มีอีเมลที่หัวเรื่องมีคำว่า “' + esc(d.keyword || 'PO') + '”</div>';
      } else {
        html += d.items.map(itemHtml).join('');
      }
      list.innerHTML = html;
    }

    function load() {
      list.innerHTML = '<div class="bellempty">กำลังโหลด…</div>';
      get('list').then(render).catch(function () {
        list.innerHTML = '<div class="bellempty">เชื่อมต่อเซิร์ฟเวอร์ไม่ได้</div>';
      });
    }

    function open() {
      panel.hidden = false;
      bell.classList.add('on');
      btn.setAttribute('aria-expanded', 'true');
      load();
    }
    function close() {
      panel.hidden = true;
      bell.classList.remove('on');
      btn.setAttribute('aria-expanded', 'false');
    }

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      panel.hidden ? open() : close();
    });
    document.addEventListener('click', function (e) {
      if (!panel.hidden && !bell.contains(e.target)) { close(); }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !panel.hidden) { close(); }
    });

    bell.addEventListener('click', function (e) {
      var act = e.target.closest('[data-po]');
      if (act) {
        e.preventDefault();
        var kind = act.getAttribute('data-po');
        act.disabled = true;

        if (kind === 'fetch') {
          act.textContent = 'กำลังตรวจ…';
          post('fetch').then(function (d) {
            act.disabled = false;
            act.textContent = 'ตรวจอีเมล';
            if (!d.ok) {
              PP.toast('danger', esc(d.error || 'ตรวจอีเมลไม่สำเร็จ'), 'ตรวจอีเมลไม่สำเร็จ');
            } else if (d.new > 0) {
              PP.toast('success', 'พบใบสั่งซื้อใหม่ <b>' + d.new + '</b> ฉบับ จากที่ตรวจ ' +
                       d.scanned + ' ฉบับ', 'มีใบ PO เข้าใหม่');
            } else {
              PP.toast('info', 'ตรวจแล้ว ' + d.scanned + ' ฉบับ — ยังไม่มีใบ PO ใหม่', 'ไม่มีรายการใหม่');
            }
            load();
          });
        } else if (kind === 'readall') {
          post('read', { id: 0 }).then(function (d) {
            act.disabled = false;
            setUnread(d.unread);
            PP.toast('success', 'ทำเครื่องหมายอ่านแล้ว ' + d.marked + ' รายการ', 'อัปเดตแจ้งเตือนแล้ว');
            load();
          });
        }
        return;
      }

      var one = e.target.closest('[data-read]');
      if (one) {
        e.preventDefault();
        post('read', { id: one.getAttribute('data-read') }).then(function (d) {
          setUnread(d.unread);
          load();
        });
      }
    });

    /* เช็คเป็นระยะ — มีใบใหม่เข้ามาก็เด้งแจ้งเตือนให้เห็นทันที */
    setInterval(function () {
      if (document.hidden) { return; }
      get('count').then(function (d) {
        if (!d.ok) { return; }
        if (d.unread > unread) {
          var diff = d.unread - unread;
          PP.toast('info', 'มีใบสั่งซื้อเข้าใหม่ <b>' + diff + '</b> ฉบับ — กดที่กระดิ่งเพื่อดู',
                   'แจ้งเตือนใบ PO');
          setUnread(d.unread, true);
        } else {
          setUnread(d.unread);
        }
      }).catch(function () { /* ออฟไลน์ชั่วคราว ข้ามไป */ });
    }, 90000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBell);
  } else {
    initBell();
  }
})();
