<?php
header('Content-Type: application/json');
$db_file = 'nodes.db';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $node_id = $data['node_id'] ?? '';
    $ip = $data['ip'] ?? '';
    $port = $data['port'] ?? 0;
    
    if ($node_id && $ip && $port) {
        $db = new SQLite3($db_file);
        $db->exec('CREATE TABLE IF NOT EXISTS nodes (id TEXT PRIMARY KEY, ip TEXT, port INTEGER, last_seen INTEGER)');
        
        $stmt = $db->prepare('INSERT OR REPLACE INTO nodes (id, ip, port, last_seen) VALUES (:id, :ip, :port, :time)');
        $stmt->bindValue(':id', $node_id, SQLITE3_TEXT);
        $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
        $stmt->bindValue(':port', $port, SQLITE3_INTEGER);
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->execute();
        
        $db->close();
    }
    
    $db = new SQLite3($db_file);
    $result = $db->query('SELECT id, ip, port FROM nodes WHERE last_seen > ' . (time() - 3600) . ' LIMIT 50');
    $nodes = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $nodes[] = $row;
    }
    $db->close();
    
    echo json_encode(['nodes' => $nodes]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
