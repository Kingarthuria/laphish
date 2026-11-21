// LaPhish, bedah alamat di beranda.
// Hanya MEMBACA struktur sebuah alamat di peramban: bagian mana yang menentukan pemilik situs.
// Tidak mengirim apa pun ke server dan tidak menilai aman atau tidaknya sebuah situs.
// Untuk mengetahui apakah sebuah tautan pernah dilaporkan, gunakan halaman Cek.
(function () {
  var form = document.getElementById('bedahForm');
  if (!form) return;

  var input = document.getElementById('bedahInput');
  var out = document.getElementById('bedahUrl');
  var verdict = document.getElementById('bedahVonis');
  var chips = Array.prototype.slice.call(document.querySelectorAll('.lp-chip'));

  // Akhiran domain Indonesia bertingkat: domain = tiga label terakhir (contoh: kampus.ac.id).
  var TLD2 = ['ac.id', 'co.id', 'go.id', 'or.id', 'sch.id', 'web.id', 'my.id', 'net.id', 'mil.id'];

  // Tampilan "defanged" seperti di halaman Cek: titik menjadi [.] dan http menjadi hxxp.
  function defang(text) {
    return text.replace(/^http/i, 'hxxp').replace(/\./g, '[.]');
  }

  function parse(raw) {
    var s = raw.trim().replace(/\[\.\]/g, '.').replace(/^hxxp/i, 'http');
    if (!s) return null;
    var scheme = '';
    var m = s.match(/^([a-z][a-z0-9+.-]*):\/\//i);
    if (m) { scheme = m[0]; s = s.slice(m[0].length); }
    var cut = s.search(/[\/?#]/);
    var host = cut === -1 ? s : s.slice(0, cut);
    var rest = cut === -1 ? '' : s.slice(cut);

    // Bagian sebelum @ adalah nama pengguna, bukan alamat situs. Peramban mengabaikannya saat memilih situs.
    var at = host.lastIndexOf('@');
    var userinfo = at === -1 ? '' : host.slice(0, at + 1);
    host = host.slice(at + 1);
    var pm = host.match(/:\d+$/);
    var port = pm ? pm[0] : '';
    host = host.replace(/:\d+$/, '');

    if (!/^[a-z0-9.-]+$/i.test(host) || host.indexOf('.') === -1) return null;
    var labels = host.split('.');
    if (labels.some(function (l) { return l === ''; })) return null;

    var isIp = /^\d+(\.\d+){3}$/.test(host);
    var n = isIp ? labels.length : (TLD2.indexOf(labels.slice(-2).join('.').toLowerCase()) !== -1 ? 3 : 2);
    var domain = labels.slice(-n).join('.');
    var sub = labels.slice(0, -n).join('.');
    return { scheme: scheme, userinfo: userinfo, sub: sub ? sub + '.' : '', domain: domain, port: port, rest: rest, isIp: isIp };
  }

  function segment(text, label, cls) {
    var d = document.createElement('div');
    d.className = 'lp-seg ' + cls;
    var c = document.createElement('code');
    c.textContent = defang(text);
    var l = document.createElement('small');
    l.textContent = label;
    d.appendChild(c);
    d.appendChild(l);
    return d;
  }

  function render(raw) {
    out.textContent = '';
    var p = parse(raw);
    // Satu-satunya teks di bawah alamat: pesan bila masukan belum berupa alamat. Selain itu cukup bagian-bagian alamatnya.
    verdict.textContent = '';
    if (!p) {
      verdict.textContent = 'Tautan belum lengkap. Contoh: https://nama-situs.com/halaman';
      return;
    }
    if (p.scheme) out.appendChild(segment(p.scheme, 'protokol', 'dim'));
    if (p.userinfo) out.appendChild(segment(p.userinfo, 'sebelum @: diabaikan', 'dim'));
    if (p.sub) out.appendChild(segment(p.sub, 'subdomain: bebas dibuat', 'dim'));
    out.appendChild(segment(p.domain, 'domain: pemilik situs', 'key'));
    if (p.port) out.appendChild(segment(p.port, 'port', 'dim'));
    if (p.rest) out.appendChild(segment(p.rest, 'jalur', 'dim'));
    // Layar sempit: alamat bisa digeser, jadi geser otomatis agar bagian domain langsung terlihat.
    var key = out.querySelector('.lp-seg.key');
    out.scrollLeft = 0;
    if (key && out.scrollWidth > out.clientWidth) {
      out.scrollLeft = Math.max(0, key.offsetLeft - (out.clientWidth - key.offsetWidth) / 2);
    }
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    chips.forEach(function (c) { c.setAttribute('aria-pressed', 'false'); });
    render(input.value);
  });

  chips.forEach(function (chip) {
    chip.addEventListener('click', function () {
      chips.forEach(function (c) { c.setAttribute('aria-pressed', String(c === chip)); });
      input.value = chip.getAttribute('data-url');
      render(input.value);
    });
  });

  render(input.value);
})();
