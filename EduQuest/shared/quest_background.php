<?php
/**
 * quest_background.php
 * EduQuest — Quest-World Student Frontend
 *
 * Output the five animated background layers.
 * Usage: require_once '/path/to/shared/quest_background.php';
 * Place immediately after the opening <body class="quest-theme"> tag.
 *
 * For HTML pages, these layers are injected automatically
 * by quest_background.js on DOMContentLoaded.
 */
?>
<div class="quest-bg-container" aria-hidden="true">

  <!-- LAYER 1: SKY (z-index -5) -->
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

  <!-- LAYER 2: DISTANT MOUNTAINS (z-index -4) -->
  <div class="quest-bg-layer quest-bg-mountains">
    <svg class="quest-mountains-svg"
         xmlns="http://www.w3.org/2000/svg"
         viewBox="0 0 1600 500"
         preserveAspectRatio="xMidYMax meet">
      <polygon class="quest-peaks-far" fill="#4a5568"
        points="0,500 0,338 82,238 162,312 264,198 366,278 464,178 566,240 666,148
                768,208 866,118 968,198 1066,138 1168,210 1268,168 1368,222 1468,158
                1544,198 1600,178 1600,500"/>
      <polygon class="quest-peaks-mid" fill="#3a5c2a"
        points="0,500 0,388 122,308 224,368 342,284 484,354 604,274 724,340 844,264
                984,330 1104,258 1224,320 1364,268 1484,310 1600,278 1600,500"/>
    </svg>
  </div>

  <!-- LAYER 3: PINE FOREST (z-index -3) -->
  <!-- Trees are generated dynamically by quest_background.js -->
  <!-- For PHP pages without JS, a static fallback SVG is provided below -->
  <div class="quest-bg-layer quest-bg-forest" id="questForestLayer">
    <svg class="quest-forest-svg" xmlns="http://www.w3.org/2000/svg"
         viewBox="0 0 1600 200" preserveAspectRatio="xMidYMax meet">
      <?php
      $pineData = [
        [30,.67,0],[88,.89,1],[144,.56,0],[202,.78,1],[258,1.0,0],[318,.61,1],
        [378,.83,0],[440,.72,1],[500,.92,0],[564,.53,1],[622,.78,0],[682,.94,1],
        [746,.64,0],[812,.86,1],[872,.72,0],[938,.97,1],[1002,.58,0],[1062,.81,1],
        [1132,.92,0],[1202,.67,1],[1268,.83,0],[1344,1.0,1],[1414,.69,0],[1482,.86,1],[1548,.75,0],
      ];
      $colors   = ['#1e3a14','#2d5016'];
      $durations = [3.2,4.1,3.7,4.8,3.4,5.0,3.9,4.3,3.6,4.6,3.8,4.0,3.5,4.9,3.3,4.7,4.2,3.6,4.4,3.1,4.5,3.8,4.1,3.9,4.3];
      $vbH = 200;
      foreach ($pineData as $i => $t) {
          list($bx, $h, $ci) = $t;
          $col  = $colors[$ci];
          $col2 = $colors[$ci === 0 ? 1 : 0];
          $dur  = $durations[$i];
          $sh   = $h * $vbH;
          $tw   = max(4, $sh * 0.05);
          $trh  = $sh * 0.22;
          printf(
              '<g class="quest-pine" style="animation-duration:%ss;transform-origin:%spx 100%%;" transform="translate(%s,%s)">'
              . '<rect x="%s" y="%s" width="%s" height="%s" fill="#2e1a0e"/>'
              . '<polygon points="0,%s %s,%s %s,%s" fill="%s"/>'
              . '<polygon points="0,%s %s,%s %s,%s" fill="%s"/>'
              . '<polygon points="0,%s %s,%s %s,%s" fill="%s"/>'
              . '</g>',
              $dur, $bx, $bx, $vbH,
              round(-$tw/2,1), round(-$trh,1), round($tw,1), round($trh,1),
              round(-$sh,1), round($sh*.28,1), round(-$sh*.54,1), round(-$sh*.28,1), round(-$sh*.54,1), $col,
              round(-$sh*.70,1), round($sh*.35,1), round(-$sh*.30,1), round(-$sh*.35,1), round(-$sh*.30,1), $col2,
              round(-$sh*.50,1), round($sh*.42,1), round(-$sh*.12,1), round(-$sh*.42,1), round(-$sh*.12,1), $col
          );
      }
      ?>
    </svg>
  </div>

  <!-- LAYER 4: FOREGROUND TERRAIN & OAK TREES (z-index -2) -->
  <div class="quest-bg-layer quest-bg-foreground" id="questForegroundLayer">
    <svg class="quest-foreground-svg" xmlns="http://www.w3.org/2000/svg"
         viewBox="0 0 1600 200" preserveAspectRatio="xMidYMax meet">
      <!-- Rolling hill -->
      <path d="M0,200 C100,150 200,165 350,158 C500,152 650,135 800,142
               C950,150 1100,128 1250,138 C1400,148 1520,132 1600,140 L1600,200 Z"
            fill="#5c3d1a"/>
      <?php
      $oakData = [
          [-35, 1.4, 6.5], [185, 1.0, 7.2], [485, 1.2, 6.8],
          [825, 1.1, 7.5], [1205, 1.3, 6.9], [1585, 1.0, 7.8],
      ];
      foreach ($oakData as $t) {
          list($bx, $s, $dur) = $t;
          $tw = 22 * $s; $th = 90 * $s;
          $r1 = 65*$s; $r2 = 50*$s; $r3 = 50*$s; $r4 = 55*$s;
          printf(
              '<g class="quest-oak" style="animation-duration:%ss;transform-origin:%spx 200%%;" transform="translate(%s,170)">'
              . '<rect x="%s" y="%s" width="%s" height="%s" fill="#2e1a0e" rx="%s"/>'
              . '<circle cx="0"    cy="%s" r="%s" fill="#4a7c2f"/>'
              . '<circle cx="%s"   cy="%s" r="%s" fill="#2d5016"/>'
              . '<circle cx="%s"   cy="%s" r="%s" fill="#4a7c2f"/>'
              . '<circle cx="0"    cy="%s" r="%s" fill="#2d5016" opacity="0.9"/>'
              . '</g>',
              $dur, $bx, $bx,
              round(-$tw/2,1), round(-$th,1), round($tw,1), round($th,1), round($tw*.35,1),
              round(-($th+$r1*.8),1), round($r1,1),
              round(-$r2*.7,1), round(-($th+$r2*.5),1), round($r2,1),
              round($r3*.7,1),  round(-($th+$r3*.5),1), round($r3,1),
              round(-($th+$r4*1.2),1), round($r4,1)
          );
      }
      ?>
      <!-- Fireflies are injected by quest_background.js -->
    </svg>
  </div>

  <!-- LAYER 5: GROUND FOG (z-index -1) -->
  <div class="quest-bg-layer quest-bg-fog">
    <div class="quest-fog quest-fog--1"></div>
    <div class="quest-fog quest-fog--2"></div>
  </div>

</div><!-- /.quest-bg-container -->
