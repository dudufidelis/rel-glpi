<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Indicadores GLPI</title>
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
      <i class="fas fa-chart-bar"></i> Indicadores GLPI
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
      <button onclick="openPDFModal()" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition duration-300 ease-in-out flex items-center gap-2">
        <i class="fas fa-file-pdf"></i> Gerar PDF
      </button>
    </div>

    <!-- Modal de Seleção de Colunas para PDF -->
    <div id="pdfModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden p-4 overflow-y-auto">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-auto my-auto">
        <!-- Header do Modal -->
        <div class="flex justify-between items-center p-4 sm:p-6 border-b border-gray-200">
          <h3 class="text-lg sm:text-xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-file-pdf text-red-600"></i> Exportar PDF
          </h3>
          <button onclick="closePDFModal()" class="text-gray-500 hover:text-gray-700 text-xl sm:text-2xl p-1">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <!-- Corpo do Modal com Scroll -->
        <div class="p-4 sm:p-6 max-h-[60vh] overflow-y-auto">
          <p class="text-gray-600 mb-3 text-sm sm:text-base font-medium">Selecione as colunas:</p>
          
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mb-5">
            <label class="flex items-center gap-2 p-2 sm:p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
              <input type="checkbox" id="col_codigo" checked class="w-4 h-4 sm:w-5 sm:h-5 text-blue-600 rounded focus:ring-blue-500">
              <span class="text-gray-700 text-sm sm:text-base">Código</span>
            </label>
            <label class="flex items-center gap-2 p-2 sm:p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
              <input type="checkbox" id="col_titulo" checked class="w-4 h-4 sm:w-5 sm:h-5 text-blue-600 rounded focus:ring-blue-500">
              <span class="text-gray-700 text-sm sm:text-base">Título</span>
            </label>
            <label class="flex items-center gap-2 p-2 sm:p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
              <input type="checkbox" id="col_status" checked class="w-4 h-4 sm:w-5 sm:h-5 text-blue-600 rounded focus:ring-blue-500">
              <span class="text-gray-700 text-sm sm:text-base">Status</span>
            </label>
            <label class="flex items-center gap-2 p-2 sm:p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
              <input type="checkbox" id="col_requerente" checked class="w-4 h-4 sm:w-5 sm:h-5 text-blue-600 rounded focus:ring-blue-500">
              <span class="text-gray-700 text-sm sm:text-base">Requerente</span>
            </label>
            <label class="flex items-center gap-2 p-2 sm:p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
              <input type="checkbox" id="col_data" checked class="w-4 h-4 sm:w-5 sm:h-5 text-blue-600 rounded focus:ring-blue-500">
              <span class="text-gray-700 text-sm sm:text-base">Data</span>
            </label>
          </div>

          <p class="text-gray-600 mb-3 text-sm sm:text-base font-medium">Filtrar por status:</p>
          
          <div class="space-y-2">
            <label class="flex items-center gap-2 sm:gap-3 p-2 sm:p-3 bg-blue-50 rounded-lg hover:bg-blue-100 cursor-pointer transition">
              <input type="radio" name="filtro_status" value="todos" checked class="w-4 h-4 sm:w-5 sm:h-5 text-blue-600 focus:ring-blue-500">
              <span class="text-gray-700 text-sm sm:text-base">Todos os chamados</span>
            </label>
            <label class="flex items-center gap-2 sm:gap-3 p-2 sm:p-3 bg-orange-50 rounded-lg hover:bg-orange-100 cursor-pointer transition">
              <input type="radio" name="filtro_status" value="abertos" class="w-4 h-4 sm:w-5 sm:h-5 text-orange-600 focus:ring-orange-500">
              <span class="text-gray-700 text-sm sm:text-base">Apenas não resolvidos</span>
            </label>
            <label class="flex items-center gap-2 sm:gap-3 p-2 sm:p-3 bg-green-50 rounded-lg hover:bg-green-100 cursor-pointer transition">
              <input type="radio" name="filtro_status" value="resolvidos" class="w-4 h-4 sm:w-5 sm:h-5 text-green-600 focus:ring-green-500">
              <span class="text-gray-700 text-sm sm:text-base">Apenas resolvidos/fechados</span>
            </label>
          </div>
        </div>
        
        <!-- Footer do Modal -->
        <div class="flex gap-3 p-4 sm:p-6 border-t border-gray-200">
          <button onclick="closePDFModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-2.5 sm:py-3 px-4 rounded-lg transition text-sm sm:text-base">
            Cancelar
          </button>
          <button onclick="generatePDF()" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-2.5 sm:py-3 px-4 rounded-lg transition flex items-center justify-center gap-2 text-sm sm:text-base">
            <i class="fas fa-download"></i> Gerar PDF
          </button>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden">
      <div class="bg-white p-6 rounded-xl shadow-lg flex flex-col items-center gap-4">
        <i class="fas fa-spinner fa-spin text-4xl text-blue-600"></i>
        <p class="text-gray-700 font-semibold">Carregando dados...</p>
      </div>
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
          <i class="fas fa-calendar-alt"></i> Chamados por Mês
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