// Toggle menu mobile
document.querySelectorAll('[data-toggle]').forEach(function (b) {
  b.addEventListener('click', function () {
    var el = document.querySelector(b.dataset.toggle);
    if (el) el.classList.toggle('open');
  });
});
// Konfirmasi aksi berbahaya: <form data-confirm="Yakin?">
document.querySelectorAll('form[data-confirm]').forEach(function (f) {
  f.addEventListener('submit', function (e) { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
});
// Auto-hilangkan notifikasi
setTimeout(function () { document.querySelectorAll('.flash').forEach(function (f) { f.classList.add('hide'); }); }, 5000);
// Slider produk
document.querySelectorAll('[data-slide]').forEach(function (b) {
  b.addEventListener('click', function () {
    var s = document.getElementById('productSlider');
    if (s) s.scrollBy({ left: Number(b.dataset.slide), behavior: 'smooth' });
  });
});
// Pratinjau foto sebelum upload
document.querySelectorAll('input[type=file][data-preview]').forEach(function (inp) {
  inp.addEventListener('change', function () {
    var img = document.querySelector(inp.dataset.preview);
    if (img && inp.files[0]) { img.src = URL.createObjectURL(inp.files[0]); img.hidden = false; }
  });
});

// Header: solid saat scroll / buka menu; fokus ke kolom cari
(function () {
  var hd = document.getElementById('siteHeader');
  if (!hd) return;
  function sync() { hd.classList.toggle('solid', window.scrollY > 10); }
  window.addEventListener('scroll', sync, { passive: true }); sync();
  document.querySelectorAll('[data-toggle]').forEach(function (b) {
    b.addEventListener('click', function () {
      hd.classList.toggle('menu-open', !!document.querySelector('#siteNav.open'));
      hd.classList.toggle('search-open', !!document.querySelector('#searchBar.open'));
      if (b.dataset.focus) { var i = document.querySelector(b.dataset.focus); if (i) i.focus(); }
    });
  });
})();
// Slider produk unggulan
document.querySelectorAll('[data-feat]').forEach(function (b) {
  b.addEventListener('click', function () {
    var t = document.getElementById('featTrack');
    if (t) t.scrollBy({ left: Number(b.dataset.feat) * t.clientWidth * 0.5, behavior: 'smooth' });
  });
});
