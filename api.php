<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$type = $_GET['type'] ?? 'movie';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$api_key = 'Ak_JJt0dxVPSxsDbNNCHob5pv8HkDGkZMzt'; // Tu clave

$base_url = 'https://vimeus.com/api/listing/' . ($type === 'anime' ? 'animes' : ($type === 'serie' ? 'series' : 'movies'));

$opts = [
    "http" => [
        "method" => "GET",
        "header" => "X-API-Key: $api_key\r\n"
    ]
];
$context = stream_context_create($opts);

if ($search !== '') {
    $found = [];
    $max_pages = 20; // Render es tan rápido que puede escanear 20 páginas al instante
    
    for ($p = 1; $p <= $max_pages; $p++) {
        $url = $base_url . "?page=" . $p;
        $response = @file_get_contents($url, false, $context);
        if (!$response) continue;
        
        $json = json_decode($response, true);
        $items = $json['data']['movies'] ?? $json['data']['series'] ?? $json['data']['animes'] ?? [];
        
        if (empty($items)) break; // Si ya no hay más películas, terminamos de buscar
        
        foreach ($items as $item) {
            if (isset($item['title']) && stripos($item['title'], $search) !== false) {
                $found[] = $item;
            }
        }
    }
    
    // Evitar resultados duplicados
    $unique_found = [];
    $ids = [];
    foreach ($found as $item) {
        if (!in_array($item['tmdb_id'], $ids)) {
            $unique_found[] = $item;
            $ids[] = $item['tmdb_id'];
        }
    }
    
    echo json_encode(['data' => ['result' => array_values($unique_found)]]);
} else {
    // Carga normal de la página de inicio
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $url = $base_url . "?page=" . $page;
    echo @file_get_contents($url, false, $context);
}
?>
