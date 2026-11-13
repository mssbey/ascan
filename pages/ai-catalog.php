<?php
declare(strict_types=1);

require_once INCLUDES_DIR . '/seo.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
header('Cache-Control: public, max-age=3600');

$aiRoute = $aiCatalogRoute ?? 'ai-catalog.json';

if ($aiRoute === 'mcp-server-card.json') {
    echo compile_mcp_server_card_json($store);
} elseif ($aiRoute === 'agent-card.json') {
    echo compile_agent_card_json($store);
} else {
    echo compile_ai_catalog_json($store);
}
