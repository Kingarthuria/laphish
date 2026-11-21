// LaPhish — skrip kecil untuk interaksi halaman.
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

// Tombol mata di kolom kata sandi: tampilkan atau sembunyikan isinya.
// Pemakaian: <button type="button" data-lihat-sandi="id-kolom">
document.querySelectorAll('[data-lihat-sandi]').forEach(function (button) {
  var field = document.getElementById(button.getAttribute('data-lihat-sandi'));
  var icon = button.querySelector('i');
  if (!field) return;
  button.addEventListener('click', function () {
    var show = field.type === 'password';
    field.type = show ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(show));
    button.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    if (icon) icon.className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');
    field.focus();
  });
});

// Halaman Cek: petunjuk di atas formulir dan contoh isian mengikuti jenis yang dipilih (URL, email, atau nomor).
(function () {
  var select = document.querySelector('select[data-cek-petunjuk]');
  var hint = document.getElementById('cekHint');
  var field = document.getElementById('value');
  if (!select || !hint || !field) return;
  function update() {
    var option = select.options[select.selectedIndex];
    if (!option) return;
    hint.textContent = option.getAttribute('data-hint') || '';
    field.placeholder = option.getAttribute('data-placeholder') || '';
  }
  select.addEventListener('change', update);
  update();
})();

// Alamat yang dibedah (hasil Cek) di layar sempit bisa digeser; geser otomatis agar bagian domain langsung terlihat.
document.querySelectorAll('.lp-url').forEach(function (box) {
  var key = box.querySelector('.lp-seg.key');
  if (key && box.scrollWidth > box.clientWidth) {
    box.scrollLeft = Math.max(0, key.offsetLeft - (box.clientWidth - key.offsetWidth) / 2);
  }
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

  // Contoh isian (placeholder) mengikuti jenis yang dipilih pada baris itu.
  function updatePlaceholder(row) {
    var select = row.querySelector('select');
    var option = select && select.options[select.selectedIndex];
    var input = row.querySelector('input');
    if (option && input && option.getAttribute('data-placeholder')) input.placeholder = option.getAttribute('data-placeholder');
  }
  rows.addEventListener('change', function (event) {
    var row = event.target.closest('.indicator-row');
    if (row && event.target.tagName === 'SELECT') updatePlaceholder(row);
  });

  addBtn.addEventListener('click', function () {
    var all = rows.querySelectorAll('.indicator-row');
    if (all.length >= max) return;
    var clone = all[0].cloneNode(true);
    clone.querySelector('input').value = '';
    clone.querySelector('select').selectedIndex = 0;
    updatePlaceholder(clone);
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
