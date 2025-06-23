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

$where = "WHERE t.date >= :start AND t.date <= :end";
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

// Lógica para Cards de status (com agregação de 5 e 6 para Resolvido e adição de Total)
$statusSQL = "SELECT t.status, COUNT(*) as total FROM glpi_tickets t $where GROUP BY t.status";
$stmt = $pdo->prepare($statusSQL);
$stmt->execute($params);
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

// Dados para exportação (agora incluindo status e nome completo do requerente)
$ticketsSQL = "SELECT t.date, t.name, t.content, t.status, CONCAT(u.firstname, ' ', u.realname) as full_requester_name FROM glpi_tickets t LEFT JOIN glpi_users u ON t.users_id_recipient = u.id $where ORDER BY t.date DESC LIMIT 100";
$stmt = $pdo->prepare($ticketsSQL);
$stmt->execute($params);
$ticketsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tickets = array_map(function($ticket) use ($exportStatusMap) {
    $ticket['status_label'] = $exportStatusMap[$ticket['status']] ?? 'Desconhecido';
    return $ticket;
}, $ticketsRaw);

// Gráfico por mês (fixo nos últimos 24 meses)
$date24MonthsAgo = date('Y-m-d', strtotime('-24 months'));
$mensalSQL = "SELECT DATE_FORMAT(t.date, '%Y-%m') as mes, COUNT(*) as total FROM glpi_tickets t WHERE t.date >= :date24MonthsAgo GROUP BY mes ORDER BY mes ASC";
$stmt = $pdo->prepare($mensalSQL);
$stmt->execute([':date24MonthsAgo' => $date24MonthsAgo . ' 00:00:00']);
$mensal = $stmt->fetchAll(PDO::FETCH_ASSOC);

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