<?php
// assets/images/no-image.png — redirect sang SVG placeholder
header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=86400');
readfile(__DIR__ . '/no-image.svg');
