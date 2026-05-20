<?php
// Serve a simple SVG placeholder avatar
header('Content-Type: image/svg+xml');
echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80">
  <circle cx="40" cy="40" r="40" fill="#e2e8f0"/>
  <circle cx="40" cy="30" r="14" fill="#94a3b8"/>
  <ellipse cx="40" cy="72" rx="22" ry="16" fill="#94a3b8"/>
</svg>';
