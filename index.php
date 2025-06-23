<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Relatórios GLPI</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="shortcut icon" href="assets/favicon.ico" type="image/x-icon">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
  <style>
    @media print {
      body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .no-print {
        display: none !important;
      }
    }
  </style>
</head>
<body class="bg-gray-100 text-gray-800 font-sans">
  <div class="container mx-auto p-6 lg:p-10">
    <h1 class="text-4xl font-extrabold text-center mb-10 text-blue-700 flex items-center justify-center gap-3">
      <i class="fas fa-chart-bar"></i> Relatórios GLPI
    </h1>

    <div class="no-print bg-white rounded-xl shadow-lg p-6 mb-8 flex flex-wrap justify-center items-center gap-5">
      <div class="flex items-center gap-3">
        <label for="category" class="text-gray-600 font-semibold">Categoria:</label>
        <select id="category" class="p-3 border border-gray-300 rounded-lg w-60 focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="all">Todas as Categorias</option>
        </select>
      </div>
      <div class="flex items-center gap-3">
        <label for="startDate" class="text-gray-600 font-semibold">De:</label>
        <input type="date" id="startDate" class="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= date('Y-m-01') ?>">
      </div>
      <div class="flex items-center gap-3">
        <label for="endDate" class="text-gray-600 font-semibold">Até:</label>
        <input type="date" id="endDate" class="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?= date('Y-m-d') ?>">
      </div>
      <button onclick="loadDashboard()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition duration-300 ease-in-out flex items-center gap-2">
        <i class="fas fa-filter"></i> Filtrar
      </button>
      <button onclick="exportCSV()" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition duration-300 ease-in-out flex items-center gap-2">
        <i class="fas fa-file-csv"></i> Exportar CSV
      </button>
      <button onclick="generatePDF()" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition duration-300 ease-in-out flex items-center gap-2">
        <i class="fas fa-file-pdf"></i> Gerar PDF
      </button>
    </div>

    <div id="cards" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8"></div>

    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
      <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold mb-4 text-gray-700 flex items-center gap-2">
          <i class="fas fa-info-circle"></i> Chamados por Status
        </h2>
        <canvas id="graficoStatus" class="max-h-80"></canvas>
      </div>
      <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold mb-4 text-gray-700 flex items-center gap-2">
          <i class="fas fa-calendar-alt"></i> Chamados por Mês (Últimos 24 meses)
        </h2>
        <canvas id="graficoMensal" class="max-h-80"></canvas>
      </div>
      <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold mb-4 text-gray-700 flex items-center gap-2">
          <i class="fas fa-users"></i> Top 10 Requerentes
        </h2>
        <canvas id="graficoRequerentes" class="max-h-80"></canvas>
      </div>
    </div>
  </div>

  <script src="js/script.js"></script>
</body>
</html>