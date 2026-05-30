<?php
/**
 * HAVELI Admin — Shared Footer
 */
?>
</div><!-- /page-content -->
</main><!-- /admin-main -->
</div><!-- /admin-layout -->

<!-- Confirm Modal -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal">
    <div class="modal-header">
      <strong id="confirmTitle">Confirm Action</strong>
      <button class="btn btn-ghost btn-icon btn-sm" onclick="closeConfirm()">×</button>
    </div>
    <div class="modal-body">
      <p id="confirmMessage" style="color:var(--text-2)">Are you sure?</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeConfirm()">Cancel</button>
      <button class="btn btn-danger" id="confirmAction">Confirm</button>
    </div>
  </div>
</div>

<!-- Toast -->
<div id="adminToastContainer" style="position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px;align-items:flex-end"></div>

<script>
// ── SIDEBAR TOGGLE ────────────────────────────────────────
function toggleSidebar() {
  const s = document.getElementById('adminSidebar');
  const o = document.getElementById('sidebarOverlay');
  s.classList.toggle('open');
  o.style.display = s.classList.contains('open') ? 'block' : 'none';
}
function closeSidebar() {
  document.getElementById('adminSidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').style.display = 'none';
}

// ── TOAST ────────────────────────────────────────
function adminToast(msg, type='info') {
  const icons = {success:'✅',error:'❌',info:'ℹ️',warning:'⚠️'};
  const colors = {success:'#22c55e',error:'#ef4444',info:'#FFD700',warning:'#f97316'};
  const t = document.createElement('div');
  t.style.cssText = `display:inline-flex;align-items:center;gap:10px;padding:11px 18px;background:rgba(18,18,20,.97);border:1px solid rgba(255,255,255,.08);border-left:3px solid ${colors[type]};border-radius:10px;color:#F4F0E8;font-size:.84rem;font-weight:500;box-shadow:0 8px 24px rgba(0,0,0,.5);animation:toastSlide .3s ease both;max-width:320px`;
  t.innerHTML = `<span>${icons[type]}</span><span>${msg}</span>`;
  document.getElementById('adminToastContainer').appendChild(t);
  setTimeout(()=>{t.style.opacity='0';t.style.transform='translateX(20px)';t.style.transition='.3s ease';setTimeout(()=>t.remove(),300);},3000);
}
window.adminToast = adminToast;

// ── CONFIRM MODAL ────────────────────────────────
let confirmCallback = null;
function showConfirm(title, msg, callback, btnText='Delete', btnClass='btn-danger') {
  document.getElementById('confirmTitle').textContent = title;
  document.getElementById('confirmMessage').textContent = msg;
  const btn = document.getElementById('confirmAction');
  btn.textContent = btnText;
  btn.className = `btn ${btnClass}`;
  confirmCallback = callback;
  document.getElementById('confirmModal').classList.add('open');
}
function closeConfirm() { document.getElementById('confirmModal').classList.remove('open'); confirmCallback = null; }
document.getElementById('confirmAction').onclick = () => { if(confirmCallback) confirmCallback(); closeConfirm(); };
window.showConfirm = showConfirm;

// ── AJAX DELETE ────────────────────────────────
async function ajaxAction(url, data, successMsg) {
  try {
    const res = await fetch(url, {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify(data)
    });
    const result = await res.json();
    if (result.success) { adminToast(successMsg || 'Done!','success'); return true; }
    else { adminToast(result.message || 'Action failed','error'); return false; }
  } catch { adminToast('Connection error','error'); return false; }
}
window.ajaxAction = ajaxAction;

// ── TABLE ROW HIGHLIGHT ────────────────────────────────
document.querySelectorAll('tbody tr').forEach(row => {
  row.addEventListener('mouseenter', ()=>row.style.background='rgba(255,107,0,.03)');
  row.addEventListener('mouseleave', ()=>row.style.background='');
});

// ── QUICK SEARCH ────────────────────────────────
function adminSearch(q) {
  if (q.length < 2) return;
  window.location.href = `<?= BASE_URL ?>/admin/pages/foods.php?q=${encodeURIComponent(q)}`;
}

// ── TOGGLE SWITCH ────────────────────────────────
document.querySelectorAll('.toggle').forEach(t => {
  t.addEventListener('click', () => {
    t.classList.toggle('on');
    const input = t.nextElementSibling;
    if (input && input.tagName === 'INPUT') input.value = t.classList.contains('on') ? '1' : '0';
  });
});

// ── UPLOAD PREVIEW ────────────────────────────────
document.querySelectorAll('.upload-box input[type=file]').forEach(inp => {
  inp.addEventListener('change', e => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => {
      let preview = inp.closest('.upload-box').querySelector('.upload-preview');
      if (!preview) {
        preview = document.createElement('img');
        preview.className = 'upload-preview';
        inp.closest('.upload-box').appendChild(preview);
      }
      preview.src = ev.target.result;
    };
    reader.readAsDataURL(file);
  });
});

// CSS for toast
const style = document.createElement('style');
style.textContent = '@keyframes toastSlide{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}';
document.head.appendChild(style);
</script>
<?php if (isset($extraAdminJS)): ?>
<script src="<?= BASE_URL ?>/admin/assets/js/<?= $extraAdminJS ?>"></script>
<?php endif; ?>
</body>
</html>
