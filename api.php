<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$type = $_GET['type'] ?? 'movie';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$api_key = 'Ak_JJt0dxVPSxsDbNNCHob5pv8HkDGkZMzt'; // Tu clave de Vimeus

$base_url = 'https://vimeus.com/api/listing/' . ($type === 'anime' ? 'animes' : ($type === 'serie' ? 'series' : 'movies'));

if ($search !== '') {
    $max_pages = 15; // Escanea 15 páginas de Vimeus al mismo tiempo
    $multi = curl_multi_init();
    $channels = [];

    // Preparamos todas las conexiones simultáneas
    for ($p = 1; $p <= $max_pages; $p++) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $base_url . "?page=" . $p);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-Key: $api_key"]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Evita el bloqueo SSL de Docker
        curl_multi_add_handle($multi, $ch);
        $channels[$p] = $ch;
    }

    // Ejecutamos todas las peticiones a la vez (tarda menos de 1 segundo)
    $active = null;
    do {
        $mrc = curl_multi_exec($multi, $active);
    } while ($mrc == CURLM_CALL_MULTI_PERFORM || $active);

    $found = [];
    $ids = [];

    // Recolectamos los resultados de las 15 páginas
    foreach ($channels as $ch) {
        $response = curl_multi_getcontent($ch);
        curl_multi_remove_handle($multi, $ch);
        
        if ($response) {
            $json = json_decode($response, true);
            $items = $json['data']['movies'] ?? $json['data']['series'] ?? $json['data']['animes'] ?? [];
            
            foreach ($items as $item) {
                if (isset($item['title']) && stripos($item['title'], $search) !== false) {
                    // Evitar películas duplicadas
                    if (!in_array($item['tmdb_id'], $ids)) {
                        $found[] = $item;
                        $ids[] = $item['tmdb_id'];
                    }
                }
            }
        }
    }
    curl_multi_close($multi);
    
    echo json_encode(['data' => ['result' => array_values($found)]]);
} else {
    // Carga normal del catálogo usando cURL seguro
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $base_url . "?page=" . $page);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-Key: $api_key"]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    echo $response;
}
?>
