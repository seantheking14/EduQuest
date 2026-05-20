/**
 * quest_background.js
 * EduQuest — Quest-World Student Frontend
 *
 * Responsibilities:
 *  1. Inject the five animated background layers into the DOM
 *  2. Generate pine forest and foreground SVGs dynamically
 *  3. Create fireflies with unique randomised paths (injected @keyframes)
 *  4. Inject the animated torch SVG into the nav brand
 *  5. Parallax on mousemove (desktop) and deviceorientation (mobile)
 *  6. Page transition fade overlay
 *  7. Leaf particle micro-interaction on .quest-card mouseenter
 */

(function () {
  'use strict';

  /* ─── 1. BACKGROUND HTML ─────────────────────────────── */

  function buildBackgroundHTML() {
    return `
<div class="quest-bg-container" aria-hidden="true">

  <!-- LAYER 1: SKY -->
  <div class="quest-bg-layer quest-bg-sky">
    <div class="quest-cloud quest-cloud--1">
      <svg xmlns="http://www.w3.org/2000/svg" width="240" height="90" viewBox="0 0 240 90">
        <ellipse cx="120" cy="62" rx="100" ry="28" fill="#f0f4f8"/>
        <ellipse cx="78"  cy="50" rx="66"  ry="24" fill="#f0f4f8"/>
        <ellipse cx="162" cy="50" rx="66"  ry="24" fill="#f0f4f8"/>
        <ellipse cx="120" cy="44" rx="56"  ry="22" fill="#f0f4f8"/>
      </svg>
    </div>
    <div class="quest-cloud quest-cloud--2">
      <svg xmlns="http://www.w3.org/2000/svg" width="178" height="70" viewBox="0 0 178 70">
        <ellipse cx="89"  cy="48" rx="76"  ry="22" fill="#f0f4f8"/>
        <ellipse cx="54"  cy="38" rx="48"  ry="20" fill="#f0f4f8"/>
        <ellipse cx="124" cy="38" rx="48"  ry="20" fill="#f0f4f8"/>
        <ellipse cx="89"  cy="34" rx="42"  ry="18" fill="#f0f4f8"/>
      </svg>
    </div>
    <div class="quest-cloud quest-cloud--3">
      <svg xmlns="http://www.w3.org/2000/svg" width="300" height="112" viewBox="0 0 300 112">
        <ellipse cx="150" cy="76" rx="130" ry="36" fill="#f0f4f8"/>
        <ellipse cx="88"  cy="60" rx="86"  ry="30" fill="#f0f4f8"/>
        <ellipse cx="212" cy="60" rx="86"  ry="30" fill="#f0f4f8"/>
        <ellipse cx="150" cy="52" rx="70"  ry="28" fill="#f0f4f8"/>
      </svg>
    </div>
    <div class="quest-cloud quest-cloud--4">
      <svg xmlns="http://www.w3.org/2000/svg" width="160" height="66" viewBox="0 0 160 66">
        <ellipse cx="80"  cy="45" rx="68"  ry="21" fill="#f0f4f8"/>
        <ellipse cx="44"  cy="36" rx="44"  ry="18" fill="#f0f4f8"/>
        <ellipse cx="116" cy="36" rx="44"  ry="18" fill="#f0f4f8"/>
        <ellipse cx="80"  cy="30" rx="38"  ry="16" fill="#f0f4f8"/>
      </svg>
    </div>
    <div class="quest-cloud quest-cloud--5">
      <svg xmlns="http://www.w3.org/2000/svg" width="210" height="82" viewBox="0 0 210 82">
        <ellipse cx="105" cy="56" rx="88"  ry="26" fill="#f0f4f8"/>
        <ellipse cx="64"  cy="44" rx="58"  ry="21" fill="#f0f4f8"/>
        <ellipse cx="146" cy="44" rx="58"  ry="21" fill="#f0f4f8"/>
        <ellipse cx="105" cy="38" rx="50"  ry="19" fill="#f0f4f8"/>
      </svg>
    </div>
  </div>

  <!-- LAYER 2: MOUNTAINS -->
  <div class="quest-bg-layer quest-bg-mountains">
    <svg class="quest-mountains-svg"
         xmlns="http://www.w3.org/2000/svg"
         viewBox="0 0 1600 500"
         preserveAspectRatio="xMidYMax meet">
      <!-- Back row — tall jagged peaks -->
      <polygon class="quest-peaks-far" fill="#4a5568"
        points="0,500 0,338 82,238 162,312 264,198 366,278 464,178 566,240 666,148 768,208 866,118 968,198 1066,138 1168,210 1268,168 1368,222 1468,158 1544,198 1600,178 1600,500"/>
      <!-- Front row — shorter warmer peaks -->
      <polygon class="quest-peaks-mid" fill="#3a5c2a"
        points="0,500 0,388 122,308 224,368 342,284 484,354 604,274 724,340 844,264 984,330 1104,258 1224,320 1364,268 1484,310 1600,278 1600,500"/>
    </svg>
  </div>

  <!-- LAYER 3: PINE FOREST (SVG injected by initForest) -->
  <div class="quest-bg-layer quest-bg-forest" id="questForestLayer"></div>

  <!-- LAYER 4: FOREGROUND (SVG injected by initForeground; fireflies appended) -->
  <div class="quest-bg-layer quest-bg-foreground" id="questForegroundLayer"></div>

  <!-- LAYER 5: FOG -->
  <div class="quest-bg-layer quest-bg-fog">
    <div class="quest-fog quest-fog--1"></div>
    <div class="quest-fog quest-fog--2"></div>
  </div>

</div>`.trim();
  }

  /* ─── 2. PINE FOREST SVG ─────────────────────────────── */

  // Each entry: { x: centre-x in viewBox, h: relative height 0-1, c: colour index }
  var PINE_DATA = [
    {x:30,  h:.67, c:0}, {x:88,  h:.89, c:1}, {x:144, h:.56, c:0}, {x:202, h:.78, c:1},
    {x:258, h:1.0, c:0}, {x:318, h:.61, c:1}, {x:378, h:.83, c:0}, {x:440, h:.72, c:1},
    {x:500, h:.92, c:0}, {x:564, h:.53, c:1}, {x:622, h:.78, c:0}, {x:682, h:.94, c:1},
    {x:746, h:.64, c:0}, {x:812, h:.86, c:1}, {x:872, h:.72, c:0}, {x:938, h:.97, c:1},
    {x:1002,h:.58, c:0}, {x:1062,h:.81, c:1}, {x:1132,h:.92, c:0}, {x:1202,h:.67, c:1},
    {x:1268,h:.83, c:0}, {x:1344,h:1.0, c:1}, {x:1414,h:.69, c:0}, {x:1482,h:.86, c:1},
    {x:1548,h:.75, c:0},
  ];

  var PINE_DURATIONS = [3.2,4.1,3.7,4.8,3.4,5.0,3.9,4.3,3.6,4.6,3.8,4.0,3.5,4.9,3.3,4.7,4.2,3.6,4.4,3.1,4.5,3.8,4.1,3.9,4.3];
  var PINE_COLORS   = ['#1e3a14', '#2d5016'];
  var VB_H = 200; // viewBox height for forest

  function buildForestSVG() {
    var trees = '';
    PINE_DATA.forEach(function (t, i) {
      var col  = PINE_COLORS[t.c];
      var col2 = PINE_COLORS[t.c === 0 ? 1 : 0];
      var dur  = PINE_DURATIONS[i];
      var sh   = t.h * VB_H;          // scaled height in viewBox units
      var tw   = Math.max(4, sh * 0.05);
      var trh  = sh * 0.22;
      var bx   = t.x;

      trees += '<g transform="translate(' + bx + ',' + VB_H + ')">'  
             + '<g class="quest-pine" style="animation-duration:' + dur + 's;">'  
             + '<rect x="' + (-tw/2).toFixed(1) + '" y="' + (-trh).toFixed(1) + '" width="' + tw.toFixed(1) + '" height="' + trh.toFixed(1) + '" fill="#2e1a0e"/>'
             + '<polygon points="0,' + (-sh).toFixed(1) + ' ' + (sh*.28).toFixed(1) + ',' + (-sh*.54).toFixed(1) + ' ' + (-sh*.28).toFixed(1) + ',' + (-sh*.54).toFixed(1) + '" fill="' + col + '"/>'
             + '<polygon points="0,' + (-sh*.70).toFixed(1) + ' ' + (sh*.35).toFixed(1) + ',' + (-sh*.30).toFixed(1) + ' ' + (-sh*.35).toFixed(1) + ',' + (-sh*.30).toFixed(1) + '" fill="' + col2 + '"/>'
             + '<polygon points="0,' + (-sh*.50).toFixed(1) + ' ' + (sh*.42).toFixed(1) + ',' + (-sh*.12).toFixed(1) + ' ' + (-sh*.42).toFixed(1) + ',' + (-sh*.12).toFixed(1) + '" fill="' + col + '"/>'
             + '</g></g>';
    });

    return '<svg class="quest-forest-svg" xmlns="http://www.w3.org/2000/svg"'
         + ' viewBox="0 0 1600 ' + VB_H + '" preserveAspectRatio="xMidYMax meet">'
         + trees
         + '</svg>';
  }

  /* ─── 3. FOREGROUND SVG ──────────────────────────────── */

  var OAK_DATA = [
    {x: -35,  s:1.4, dur:6.5},
    {x: 185,  s:1.0, dur:7.2},
    {x: 485,  s:1.2, dur:6.8},
    {x: 825,  s:1.1, dur:7.5},
    {x:1205,  s:1.3, dur:6.9},
    {x:1585,  s:1.0, dur:7.8},
  ];

  function buildForegroundSVG() {
    var hillPath = 'M0,200 C100,150 200,165 350,158 C500,152 650,135 800,142 '
                 + 'C950,150 1100,128 1250,138 C1400,148 1520,132 1600,140 L1600,200 Z';

    var oaks = '';
    OAK_DATA.forEach(function (t) {
      var s   = t.s;
      var tw  = 22 * s;
      var th  = 90 * s;
      var r1  = 65 * s, r2 = 50 * s, r3 = 50 * s, r4 = 55 * s;
      var bx  = t.x;

      oaks += '<g transform="translate(' + bx + ',170)">'  
            + '<g class="quest-oak" style="animation-duration:' + t.dur + 's;">'  
            + '<rect x="' + (-tw/2).toFixed(1) + '" y="' + (-th).toFixed(1) + '" width="' + tw.toFixed(1) + '" height="' + th.toFixed(1) + '" fill="#2e1a0e" rx="' + (tw*.35).toFixed(1) + '"/>'
            + '<circle cx="0"               cy="' + (-(th + r1*.8)).toFixed(1) + '" r="' + r1.toFixed(1) + '" fill="#4a7c2f"/>'
            + '<circle cx="' + (-r2*.7).toFixed(1) + '" cy="' + (-(th + r2*.5)).toFixed(1) + '" r="' + r2.toFixed(1) + '" fill="#2d5016"/>'
            + '<circle cx="' + ( r3*.7).toFixed(1) + '" cy="' + (-(th + r3*.5)).toFixed(1) + '" r="' + r3.toFixed(1) + '" fill="#4a7c2f"/>'
            + '<circle cx="0"               cy="' + (-(th + r4*1.2)).toFixed(1) + '" r="' + r4.toFixed(1) + '" fill="#2d5016" opacity="0.9"/>'
            + '</g></g>';
    });

    return '<svg class="quest-foreground-svg" xmlns="http://www.w3.org/2000/svg"'
         + ' viewBox="0 0 1600 200" preserveAspectRatio="xMidYMax meet">'
         + '<path d="' + hillPath + '" fill="#5c3d1a"/>'
         + oaks
         + '</svg>';
  }

  /* ─── 4. FIREFLIES ───────────────────────────────────── */

  function rand(min, max) { return min + Math.random() * (max - min); }

  function buildFireflies(count) {
    var flies = [];
    for (var i = 0; i < count; i++) {
      flies.push({
        left:     rand(3, 92),
        bottom:   rand(4, 48),
        floatDur: rand(6, 14),
        glowDur:  rand(1.2, 2.8),
        delay:    rand(0, 10),
      });
    }
    return flies;
  }

  function injectFireflyKeyframes(flies) {
    var css = '';
    flies.forEach(function (f, i) {
      var dx1 = (Math.random() - 0.5) * 70;
      var dy1 = rand(18, 38);
      var dx2 = (Math.random() - 0.5) * 90;
      var dy2 = rand(38, 65);
      var dx3 = (Math.random() - 0.5) * 60;
      var dy3 = rand(55, 90);
      var dx4 = (Math.random() - 0.5) * 50;
      css += '@keyframes firefly-float-' + i + '{'
           + '0%{transform:translate(0,0)}'
           + '25%{transform:translate(' + dx1.toFixed(1) + 'px,' + (-dy1).toFixed(1) + 'px)}'
           + '50%{transform:translate(' + dx2.toFixed(1) + 'px,' + (-dy2).toFixed(1) + 'px)}'
           + '75%{transform:translate(' + dx3.toFixed(1) + 'px,' + (-dy3).toFixed(1) + 'px)}'
           + '100%{transform:translate(' + dx4.toFixed(1) + 'px,0)}}\n';
    });
    var style = document.createElement('style');
    style.id = 'quest-firefly-keyframes';
    style.textContent = css;
    document.head.appendChild(style);
  }

  function appendFireflies(container, flies) {
    flies.forEach(function (f, i) {
      var div = document.createElement('div');
      div.className = 'quest-firefly';
      div.style.cssText = 'left:' + f.left.toFixed(1) + '%;'
        + 'bottom:' + f.bottom.toFixed(1) + '%;'
        + 'animation:firefly-float-' + i + ' ' + f.floatDur.toFixed(1) + 's ease-in-out infinite,'
        + 'glow-pulse ' + f.glowDur.toFixed(1) + 's ease-in-out infinite alternate;'
        + 'animation-delay:' + f.delay.toFixed(1) + 's,' + (f.delay * 0.6).toFixed(1) + 's;';
      container.appendChild(div);
    });
  }

  /* ─── 5. LOGO + TORCH ICON ──────────────────────────── */

  var BOOK_SWORD_SVG =
    '<svg xmlns="http://www.w3.org/2000/svg" width="26" height="28" viewBox="0 0 26 28" aria-hidden="true">'
    /* sword blade (behind book) */
    + '<polygon points="13,1 14.6,11 11.4,11" fill="#c9a227"/>'
    /* crossguard */
    + '<rect x="7.5" y="11" width="11" height="2.5" rx="1.2" fill="#f0c040"/>'
    /* grip */
    + '<rect x="11.8" y="13.5" width="2.4" height="5" rx="1.2" fill="#6b3a1f"/>'
    /* pommel */
    + '<circle cx="13" cy="20" r="2.2" fill="#c9a227" stroke="#f0c040" stroke-width="0.5"/>'
    /* left page */
    + '<path d="M1,9 C1,7.5 3.5,7 7,7 L13,8.5 L13,22.5 C9,21.5 5,22 3,22.8 C1.5,23.2 1,22.2 1,21 Z" fill="#ede0c4"/>'
    /* right page */
    + '<path d="M25,9 C25,7.5 22.5,7 19,7 L13,8.5 L13,22.5 C17,21.5 21,22 23,22.8 C24.5,23.2 25,22.2 25,21 Z" fill="#f5e6c8"/>'
    /* page outlines */
    + '<path d="M1,9 C1,7.5 3.5,7 7,7 L13,8.5 L13,22.5 C9,21.5 5,22 3,22.8 C1.5,23.2 1,22.2 1,21 Z" fill="none" stroke="#c9a227" stroke-width="0.8"/>'
    + '<path d="M25,9 C25,7.5 22.5,7 19,7 L13,8.5 L13,22.5 C17,21.5 21,22 23,22.8 C24.5,23.2 25,22.2 25,21 Z" fill="none" stroke="#c9a227" stroke-width="0.8"/>'
    /* spine */
    + '<line x1="13" y1="8.5" x2="13" y2="22.5" stroke="#c9a227" stroke-width="1.6"/>'
    /* page lines left */
    + '<line x1="4" y1="12" x2="11" y2="11.5" stroke="#6b3a1f" stroke-width="0.65" opacity="0.5"/>'
    + '<line x1="4" y1="14.5" x2="11" y2="14" stroke="#6b3a1f" stroke-width="0.65" opacity="0.5"/>'
    + '<line x1="4" y1="17" x2="11" y2="16.5" stroke="#6b3a1f" stroke-width="0.65" opacity="0.5"/>'
    /* page lines right */
    + '<line x1="15" y1="11.5" x2="22" y2="12" stroke="#6b3a1f" stroke-width="0.65" opacity="0.5"/>'
    + '<line x1="15" y1="14" x2="22" y2="14.5" stroke="#6b3a1f" stroke-width="0.65" opacity="0.5"/>'
    + '<line x1="15" y1="16.5" x2="22" y2="17" stroke="#6b3a1f" stroke-width="0.65" opacity="0.5"/>'
    + '</svg>';

  function injectBookSwordLogo() {
    var logo = document.querySelector('.quest-theme .nav-logo');
    if (!logo) return;
    logo.innerHTML = '<span class="quest-logo-icon">' + BOOK_SWORD_SVG + '</span> EduQuest';
  }

  function injectTorch() {
    var brand = document.querySelector('.quest-theme .nav-brand');
    if (!brand) return;
    var torch = document.createElement('span');
    torch.className = 'quest-torch';
    torch.setAttribute('aria-hidden', 'true');
    torch.innerHTML =
      '<svg xmlns="http://www.w3.org/2000/svg" width="26" height="34" viewBox="0 0 26 34">'
      + '<rect x="10" y="21" width="6" height="13" rx="2" fill="#6b3a1f"/>'
      + '<rect x="11" y="19" width="4" height="5"  rx="1" fill="#8b5e3c"/>'
      + '<ellipse cx="13" cy="21" rx="7" ry="3.5" fill="#8b5e3c"/>'
      + '<ellipse cx="13" cy="19" rx="5.5" ry="3" fill="#a07040"/>'
      + '<g class="quest-torch-flame">'
      + '<ellipse cx="13" cy="15" rx="4.5" ry="6.5" fill="#f39c12" opacity="0.9"/>'
      + '<ellipse cx="11" cy="13" rx="3"   ry="4.5" fill="#e74c3c" opacity="0.7"/>'
      + '<ellipse cx="14" cy="12" rx="2.8" ry="3.8" fill="#f0c040" opacity="0.85"/>'
      + '<ellipse cx="13" cy="10" rx="1.8" ry="3.5" fill="#fff3b0" opacity="0.6"/>'
      + '</g></svg>';
    brand.insertBefore(torch, brand.firstChild);
  }

  /* ─── 6. PARALLAX ─────────────────────────────────────── */

  function initParallax() {
    var forest = document.getElementById('questForestLayer');
    if (!forest) return;

    document.addEventListener('mousemove', function (e) {
      if (window.innerWidth <= 768) return;
      var cx = window.innerWidth / 2;
      var dx = (e.clientX - cx) / cx;
      forest.style.transform = 'translateX(' + (dx * -12).toFixed(2) + 'px)';
    });

    window.addEventListener('deviceorientation', function (e) {
      if (!e.gamma) return;
      var tilt = Math.max(-90, Math.min(90, e.gamma));
      var dx   = (tilt / 90) * 12;
      forest.style.transform = 'translateX(' + dx.toFixed(2) + 'px)';
    });
  }

  /* ─── 7. PAGE TRANSITION ─────────────────────────────── */

  function initPageTransition() {
    var overlay = document.createElement('div');
    overlay.className = 'quest-page-transition';
    overlay.style.cssText = 'opacity:1;transition:opacity 0.3s ease-out;';
    document.body.appendChild(overlay);

    // Fade out on load
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        overlay.style.opacity = '0';
        setTimeout(function () { overlay.style.display = 'none'; }, 340);
      });
    });

    // Fade in on internal link click
    document.addEventListener('click', function (e) {
      var link = e.target.closest('a[href]');
      if (!link) return;
      var href = link.getAttribute('href');
      if (!href || href.charAt(0) === '#'
          || href.indexOf('javascript') === 0
          || href.indexOf('mailto') === 0
          || href.indexOf('tel') === 0
          || link.getAttribute('target') === '_blank') return;

      e.preventDefault();
      overlay.style.display = 'block';
      overlay.style.transition = 'opacity 0.2s ease-in';

      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          overlay.style.opacity = '1';
          setTimeout(function () { window.location.href = href; }, 225);
        });
      });
    }, true);
  }

  /* ─── 8. LEAF PARTICLES ─────────────────────────────── */

  var LEAF_SVG =
    '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="8" viewBox="0 0 12 8">'
    + '<path d="M1,7 Q3,1 6,1 Q9,1 11,4 Q8,7 5,7 Z" fill="#4a7c2f" opacity="0.82"/>'
    + '<line x1="1" y1="7" x2="11" y2="4" stroke="#2d5016" stroke-width="0.6"/>'
    + '</svg>';

  function spawnLeaves(card) {
    for (var i = 0; i < 3; i++) {
      (function (idx) {
        var leaf = document.createElement('div');
        leaf.className = 'leaf-particle';
        leaf.innerHTML = LEAF_SVG;
        leaf.style.left  = (8 + Math.random() * 84) + '%';
        leaf.style.bottom = '0';
        leaf.style.animationDelay = (idx * 0.15) + 's';
        card.appendChild(leaf);
        leaf.addEventListener('animationend', function () { leaf.remove(); });
      })(i);
    }
  }

  function initLeafParticles() {
    document.querySelectorAll('.quest-card').forEach(function (card) {
      card.addEventListener('mouseenter', function () {
        card.classList.add('is-hovered');
        spawnLeaves(card);
      });
      card.addEventListener('mouseleave', function () {
        card.classList.remove('is-hovered');
      });
    });
  }

  /* ─── 9. MOBILE SIMPLIFICATION ──────────────────────── */

  function applyMobileSimplifications() {
    if (window.innerWidth > 767) return;
    // Extra clouds hidden by CSS media query; fireflies already capped at 4
  }

  /* ─── 10. NAV BRAND HOME LINK ───────────────────────── */

  function initBrandLink() {
    var brand = document.querySelector('.quest-theme .nav-brand');
    if (!brand) return;
    brand.style.cursor = 'pointer';
    brand.addEventListener('click', function (e) {
      if (e.target.closest('a')) return;
      window.location.href = '../dashboard/dashboard.html';
    });
  }

  /* ─── 11. INIT ───────────────────────────────────────── */

  document.addEventListener('DOMContentLoaded', function () {
    var body = document.body;
    if (!body.classList.contains('quest-theme')) return;

    /* ── Inject background layers ── */
    var wrap = document.createElement('div');
    wrap.innerHTML = buildBackgroundHTML();
    body.insertBefore(wrap.firstElementChild, body.firstChild);

    /* ── Forest ── */
    var forestLayer = document.getElementById('questForestLayer');
    if (forestLayer) forestLayer.innerHTML = buildForestSVG();

    /* ── Foreground ── */
    var fgLayer = document.getElementById('questForegroundLayer');
    if (fgLayer) fgLayer.innerHTML = buildForegroundSVG();

    /* ── Fireflies ── */
    var mobileFF = window.innerWidth <= 767;
    var flies    = buildFireflies(mobileFF ? 4 : 10);
    injectFireflyKeyframes(flies);
    if (fgLayer) appendFireflies(fgLayer, flies);

    /* ── Torch ── */
    injectTorch();

    /* ── Book-sword logo ── */
    injectBookSwordLogo();

    /* ── Nav brand home link ── */
    initBrandLink();

    /* ── Parallax (desktop only) ── */
    if (window.innerWidth > 768) initParallax();

    /* ── Page transition ── */
    initPageTransition();

    /* ── Leaf particles (slight delay for dynamic cards) ── */
    setTimeout(initLeafParticles, 400);

    applyMobileSimplifications();
  });

})();
