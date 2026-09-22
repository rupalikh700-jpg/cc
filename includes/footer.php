</div><!-- /container -->

<footer class="site-footer">
  <div class="footer-inner">
    <span class="footer-logo">🐸 PEPE CC</span>
    <nav class="footer-links">
      <a href="/index.php">Home</a>
      <a href="/shop.php">Shop</a>
      <a href="/packs.php">Packs</a>
      <a href="/memes.php">Memes</a>
      <a href="/merch.php">Merch</a>
      <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
        <a href="/orders.php">Orders</a>
        <a href="/user/balance.php">Wallet</a>
        <a href="/user/checker.php">Checker</a>
        <a href="/user/tickets.php">Support</a>
      <?php endif; ?>
    </nav>
    <span class="footer-copy">&copy; <?= date('Y') ?> Pepe CC Shop</span>
  </div>
</footer>

<script>
(function () {
  // ── Hamburger / mobile menu ──────────────────
  var hamburger    = document.getElementById('hamburger');
  var mobileMenu   = document.getElementById('mobileMenu');
  var mobileOverlay = document.getElementById('mobileOverlay');
  var mobileClose  = document.getElementById('mobileClose');

  function openMenu() {
    if (!mobileMenu) return;
    hamburger && hamburger.classList.add('active');
    mobileMenu.classList.add('active');
    mobileOverlay && mobileOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeMenu() {
    if (!mobileMenu) return;
    hamburger && hamburger.classList.remove('active');
    mobileMenu.classList.remove('active');
    mobileOverlay && mobileOverlay.classList.remove('active');
    document.body.style.overflow = '';
  }

  hamburger    && hamburger.addEventListener('click', openMenu);
  mobileClose  && mobileClose.addEventListener('click', closeMenu);
  mobileOverlay && mobileOverlay.addEventListener('click', closeMenu);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closeMenu(); closeDropdown(); }
  });

  // ── User dropdown (click/tap toggle on mobile) ──
  var userPill    = document.querySelector('.user-pill');
  var userDropdown = document.querySelector('.user-dropdown');

  function closeDropdown() {
    userDropdown && userDropdown.classList.remove('open');
  }

  if (userPill && userDropdown) {
    userPill.addEventListener('click', function (e) {
      if (window.innerWidth <= 900) {
        if (!userDropdown.contains(e.target)) {
          var isOpen = userDropdown.classList.toggle('open');
          e.stopPropagation();
          // Prevent body-scroll when dropdown is open
          document.body.style.overflow = isOpen ? 'hidden' : '';
        }
      }
    });
    // Close on any outside tap
    document.addEventListener('click', function () {
      if (window.innerWidth <= 900 && userDropdown.classList.contains('open')) {
        closeDropdown();
        document.body.style.overflow = '';
      }
    });
    // Close after navigating from a dropdown link
    userDropdown.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        closeDropdown();
        document.body.style.overflow = '';
      });
    });
  }

  // ── Shop sidebar toggle (mobile) ─────────────
  var filterBtn     = document.getElementById('filterToggleBtn');
  var shopSidebar   = document.getElementById('shopSidebar');
  var sidebarOverlay = document.getElementById('sidebarOverlay');

  function openSidebar() {
    if (!shopSidebar) return;
    shopSidebar.classList.add('active');
    sidebarOverlay && sidebarOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    if (!shopSidebar) return;
    shopSidebar.classList.remove('active');
    sidebarOverlay && sidebarOverlay.classList.remove('active');
    document.body.style.overflow = '';
  }

  filterBtn      && filterBtn.addEventListener('click', openSidebar);
  sidebarOverlay && sidebarOverlay.addEventListener('click', closeSidebar);

  // ── Copy-to-clipboard buttons ─────────────────
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.copy-btn');
    if (!btn) return;
    var text = btn.dataset.copy || btn.previousElementSibling && btn.previousElementSibling.textContent;
    if (!text) return;
    navigator.clipboard && navigator.clipboard.writeText(text.trim()).then(function () {
      var orig = btn.textContent;
      btn.textContent = '✓';
      setTimeout(function () { btn.textContent = orig; }, 1200);
    });
  });

  // ── Compare bar toggle ────────────────────────
  var compareChecks = document.querySelectorAll('.compare-check');
  var compareBar    = document.getElementById('compareBar');
  var compareList   = document.getElementById('compareList');

  if (compareChecks.length && compareBar) {
    compareChecks.forEach(function (chk) {
      chk.addEventListener('change', function () {
        var selected = Array.from(compareChecks).filter(function (c) { return c.checked; });
        if (selected.length > 0) {
          compareBar.classList.add('visible');
          if (compareList) compareList.textContent = selected.length + ' card(s) selected';
        } else {
          compareBar.classList.remove('visible');
        }
      });
    });
  }
})();
</script>
</body>
</html>
