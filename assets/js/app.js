// LaporPhish — skrip kecil untuk interaksi halaman.
// Formulir tetap bisa dikirim tanpa JavaScript; skrip ini hanya mempermudah.

// Dialog konfirmasi untuk form hapus (NFR-10).
// Pemakaian: <form method="post" data-confirm="Hapus kategori ini?">
document.addEventListener('submit', function (event) {
  var message = event.target.getAttribute('data-confirm');
  if (message && !window.confirm(message)) {
    event.preventDefault();
  }
});

// Teks bantuan yang mengikuti pilihan dropdown (deskripsi kategori di formulir lapor).
document.querySelectorAll('select[data-help-target]').forEach(function (select) {
  var target = document.getElementById(select.getAttribute('data-help-target'));
  function update() {
    var option = select.options[select.selectedIndex];
    if (target && option) target.textContent = option.getAttribute('data-desc') || '';
  }
  select.addEventListener('change', update);
  update();
});

// Baris indikator di formulir lapor: tambah dan hapus, maksimal sesuai data-max.
(function () {
  var rows = document.getElementById('indicatorRows');
  var addBtn = document.getElementById('addIndicator');
  var counter = document.getElementById('indicatorCount');
  if (!rows || !addBtn) return;
  var max = parseInt(rows.getAttribute('data-max'), 10) || 5;

  function refresh() {
    var all = rows.querySelectorAll('.indicator-row');
    if (counter) counter.textContent = all.length;
    addBtn.disabled = all.length >= max;
    all.forEach(function (row, i) {
      var btn = row.querySelector('.remove-indicator');
      if (btn) btn.classList.toggle('invisible', i === 0);
    });
  }

  addBtn.addEventListener('click', function () {
    var all = rows.querySelectorAll('.indicator-row');
    if (all.length >= max) return;
    var clone = all[0].cloneNode(true);
    clone.querySelector('input').value = '';
    clone.querySelector('select').selectedIndex = 0;
    rows.appendChild(clone);
    refresh();
    clone.querySelector('input').focus();
  });

  rows.addEventListener('click', function (event) {
    var btn = event.target.closest('.remove-indicator');
    if (!btn) return;
    var all = rows.querySelectorAll('.indicator-row');
    if (all.length > 1) btn.closest('.indicator-row').remove();
    refresh();
  });

  refresh();
})();
