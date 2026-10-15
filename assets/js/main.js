// Flowork Agency Software - small UI helpers
document.addEventListener('DOMContentLoaded', function () {
  // Delete confirmations
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(el.getAttribute('data-confirm') || 'Are you sure?')) {
        e.preventDefault();
      }
    });
  });

  // Client select -> project select filtering (invoice create page)
  var clientSel = document.getElementById('f_client');
  var projectSel = document.getElementById('f_project');
  if (clientSel && projectSel) {
    clientSel.addEventListener('change', function () {
      var cid = clientSel.value;
      Array.prototype.forEach.call(projectSel.options, function (opt) {
        if (!opt.value) { opt.hidden = false; return; }
        opt.hidden = (opt.getAttribute('data-client') !== cid);
      });
      projectSel.value = '';
      var msSel = document.getElementById('f_milestone');
      if (msSel) { msSel.value = ''; syncMilestone(); }
    });
  }

  // Milestone select -> amount auto-fill (invoice create page)
  var msSel = document.getElementById('f_milestone');
  if (msSel) {
    msSel.addEventListener('change', syncMilestone);
    syncMilestone();
  }
  function syncMilestone() {
    var amt = document.getElementById('f_amount');
    if (!amt || !msSel) return;
    var opt = msSel.options[msSel.selectedIndex];
    if (opt && opt.getAttribute('data-amount')) {
      amt.value = opt.getAttribute('data-amount');
    }
  }
});
