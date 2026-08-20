<?php
header('Content-Type: application/json');

$type = $_GET['type'] ?? 'movie';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$api_key = 'Ak_JJt0dxVPSxsDbNNCHob5pv8HkDGkZMzt'; // Tu clave

$endpoints = [
    'movie' => 'https://vimeus.com/api/listing/movies',
    'serie' => 'https://vimeus.com/api/listing/series',
    'anime' => 'https://vimeus.com/api/listing/animes'
];

$base_url = $endpoints[$type] ?? $endpoints['movie'];
$context = stream_context_create(['http' => ['header' => "X-API-Key: $api_key\r\n"]]);

if ($search !== '') {
    $found = [];
    // Buscamos a través de las primeras 5 páginas de Vimeus para asegurar encontrar contenido como Rambo
    for ($p = 1; $p <= 5; $p++) {
        $url = $base_url . "?page=" . $p;
        $response = @file_get_contents($url, false, $context);
        
        if ($response === FALSE) continue;
        
        $json = json_decode($response, true);
        $data = $json['data'] ?? [];
        $list = $data['movies'] ?? $data['series'] ?? $data['animes'] ?? [];
        
        if (empty($list)) break;

        foreach ($list as $item) {
            if (isset($item['title']) && stripos($item['title'], $search) !== false) {
                // Evitar duplicados
                $exists = false;
                foreach($found as $f) {
                    if($f['tmdb_id'] == $item['tmdb_id']) $exists = true;
                }
                if(!$exists) {
                    $found[] = $item;
                }
            }
        }
    }
    echo json_encode(['data' => ['result' => $found]]);
} else {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $url = $base_url . "?page=" . $page;
    echo @file_get_contents($url, false, $context);
}
?>
