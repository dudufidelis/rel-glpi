<?php
header('Content-Type: application/json');

// Fill all the variables above
$host = '';
$db   = '';
$user = '';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'utf8'"); // Garante UTF-8
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro interno do servidor. Por favor, tente novamente mais tarde.']);
    error_log('Erro na conexão PDO: ' . $e->getMessage());
    exit;
}

$category = $_GET['category'] ?? 'all';
$start = $_GET['start'] ?? '2020-01-01';
$end = $_GET['end'] ?? date('Y-m-d');

// Validação de datas
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $start) || !strtotime($start)) {
    echo json_encode(['error' => 'Formato de data inicial inválido. Use AAAA-MM-DD.']);
    exit;
}
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $end) || !strtotime($end)) {
    echo json_encode(['error' => 'Formato de data final inválido. Use AAAA-MM-DD.']);
    exit;
}

$where = "WHERE t.is_deleted = 0 AND t.date >= :start AND t.date <= :end";
$params = [':start' => $start . ' 00:00:00', ':end' => $end . ' 23:59:59'];

if ($category !== 'all') {
    // Validação de categoria: deve ser um número inteiro positivo
    if (!filter_var($category, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
        echo json_encode(['error' => 'ID de categoria inválido.']);
        exit;
    }
    $where .= " AND t.itilcategories_id = :catid";
    $params[':catid'] = (int)$category; // Garante que é um inteiro
}

// Lógica para Cards de status
// Chamados abertos (status 1,2,3,4) são contados INDEPENDENTE da data
// Chamados resolvidos/fechados (status 5,6) são filtrados pela data
$statusSQL = "
    SELECT t.status, COUNT(*) as total 
    FROM glpi_tickets t 
    WHERE t.is_deleted = 0 
    AND (
        (t.status NOT IN (5, 6)) 
        OR 
        (t.status IN (5, 6) AND t.date >= :start AND t.date <= :end)
    )
";
$statusParams = [':start' => $start . ' 00:00:00', ':end' => $end . ' 23:59:59'];

if ($category !== 'all') {
    $statusSQL .= " AND t.itilcategories_id = :catid";
    $statusParams[':catid'] = (int)$category;
}

$statusSQL .= " GROUP BY t.status";
$stmt = $pdo->prepare($statusSQL);
$stmt->execute($statusParams);
$statusRawData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusDataAggregated = [
    'Novo' => 0,
    'Em andamento' => 0,
    'Planejado' => 0,
    'Pendente' => 0,
    'Resolvido' => 0
];

$statusMap = [
    1 => 'Novo',
    2 => 'Em andamento',
    3 => 'Planejado',
    4 => 'Pendente',
    5 => 'Resolvido',
    6 => 'Resolvido'
];

$totalTickets = 0;
$totalOpen = 0;
$totalResolved = 0;

foreach ($statusRawData as $row) {
    $statusId = (int)$row['status'];
    $count = (int)$row['total'];

    $label = $statusMap[$statusId] ?? 'Desconhecido';

    if (array_key_exists($label, $statusDataAggregated)) {
        $statusDataAggregated[$label] += $count;
    }

    // Lógica para totalOpen e totalResolved
    if ($statusId === 5 || $statusId === 6) {
        $totalResolved += $count;
    } else {
        $totalOpen += $count;
    }
    $totalTickets += $count;
}

$formattedStatusData = [];
// Garante a ordem dos cards
$order = ['Total', 'Novo', 'Em andamento', 'Planejado', 'Pendente', 'Resolvido'];

foreach ($order as $label) {
    if ($label === 'Total') {
        $formattedStatusData[] = [
            'label' => 'Total',
            'total' => $totalTickets
        ];
    } else {
        $formattedStatusData[] = [
            'label' => $label,
            'total' => $statusDataAggregated[$label]
        ];
    }
}

// Mapeamento de status para rótulos para uso na exportação
$exportStatusMap = [
    1 => 'Novo',
    2 => 'Em andamento',
    3 => 'Planejado',
    4 => 'Pendente',
    5 => 'Resolvido',
    6 => 'Resolvido'
];

// Dados para exportação
// Chamados abertos (status 1,2,3,4) aparecem INDEPENDENTE da data
// Chamados resolvidos/fechados (status 5,6) são filtrados pela data
$ticketsSQL = "
    SELECT t.id, t.date, t.name, t.content, t.status, CONCAT(u.firstname, ' ', u.realname) as full_requester_name 
    FROM glpi_tickets t 
    LEFT JOIN glpi_users u ON t.users_id_recipient = u.id 
    WHERE t.is_deleted = 0 
    AND (
        (t.status NOT IN (5, 6)) 
        OR 
        (t.status IN (5, 6) AND t.date >= :start AND t.date <= :end)
    )
";
$ticketsParams = [':start' => $start . ' 00:00:00', ':end' => $end . ' 23:59:59'];

if ($category !== 'all') {
    $ticketsSQL .= " AND t.itilcategories_id = :catid";
    $ticketsParams[':catid'] = (int)$category;
}

$ticketsSQL .= " ORDER BY t.status ASC, t.date DESC";
$stmt = $pdo->prepare($ticketsSQL);
$stmt->execute($ticketsParams);
$ticketsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tickets = array_map(function($ticket) use ($exportStatusMap) {
    $ticket['status_label'] = $exportStatusMap[$ticket['status']] ?? 'Desconhecido';
    return $ticket;
}, $ticketsRaw);

// Gráfico por mês (ÚLTIMOS 12 MESES, com filtro de categoria)
$mensalSQL = "
    SELECT DATE_FORMAT(t.date, '%Y-%m') as mes, COUNT(*) as total 
    FROM glpi_tickets t 
    WHERE t.is_deleted = 0 
    AND t.date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
";
$mensalParams = [];

if ($category !== 'all') {
    $mensalSQL .= " AND t.itilcategories_id = :catid";
    $mensalParams[':catid'] = (int)$category;
}

$mensalSQL .= " GROUP BY mes ORDER BY mes ASC";
$stmt = $pdo->prepare($mensalSQL);
$stmt->execute($mensalParams);
$mensalRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gerar array com todos os 12 últimos meses (mesmo sem dados)
$mensal = [];
for ($i = 11; $i >= 0; $i--) {
    $mesKey = date('Y-m', strtotime("-$i months"));
    $mensal[$mesKey] = ['mes' => $mesKey, 'total' => 0];
}

// Preencher com os dados reais
foreach ($mensalRaw as $row) {
    if (isset($mensal[$row['mes']])) {
        $mensal[$row['mes']]['total'] = (int)$row['total'];
    }
}

$mensal = array_values($mensal);

// Top 10 requerentes (AGORA AFETADO PELOS FILTROS DE DATA E CATEGORIA)
$topRequerentesSQL = "
    SELECT
        CONCAT(u.firstname, ' ', u.realname) as requester_name,
        COUNT(t.id) as total_tickets
    FROM glpi_tickets t
    JOIN glpi_users u ON t.users_id_recipient = u.id
    $where
    AND u.realname IS NOT NULL
    AND u.realname != ''
    AND u.name NOT LIKE '%admin%'
    AND u.is_active = 1
    GROUP BY requester_name
    ORDER BY total_tickets DESC
    LIMIT 10
";
$stmt = $pdo->prepare($topRequerentesSQL);
$stmt->execute($params); // EXECUTA COM OS MESMOS PARÂMETROS DE FILTRO
$topRequerentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lista de categorias (para o <select>)
$cats = $pdo->query("SELECT id, name FROM glpi_itilcategories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'status' => $formattedStatusData,
    'mensal' => $mensal,
    'tickets' => $tickets,
    'categoriasSelect' => $cats,
    'totalOpen' => $totalOpen,
    'totalResolved' => $totalResolved,
    'topRequerentes' => $topRequerentes
]);