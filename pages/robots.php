<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/seo.php';

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: public, max-age=3600');
echo compile_robots_txt($store);
