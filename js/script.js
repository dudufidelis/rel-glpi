let ticketsCache = []; //
let totalsCache = { totalOpen: 0, totalResolved: 0 }; //

// Mostrar/Esconder loading
function showLoading() {
  document.getElementById('loadingOverlay').classList.remove('hidden');
}

function hideLoading() {
  document.getElementById('loadingOverlay').classList.add('hidden');
}

async function loadDashboard() {
  showLoading();
  const cat = document.getElementById('category').value; //
  const start = document.getElementById('startDate').value; //
  const end = document.getElementById('endDate').value; //

  try {
    const res = await fetch(`get_data.php?category=${cat}&start=${start}&end=${end}`); //
    if (!res.ok) { // Verifica se a resposta HTTP foi bem-sucedida (status 200-299)
        throw new Error(`Erro HTTP! Status: ${res.status}`);
    }
    const data = await res.json(); //

    // Se o PHP retornar um erro JSON (ex: erro de conexão no get_data.php)
    if (data.error) {
        alert('Erro ao carregar dados: ' + data.error);
        console.error('Erro na API:', data.error);
        hideLoading();
        return;
    }

    ticketsCache = data.tickets; //
    totalsCache = { totalOpen: data.totalOpen, totalResolved: data.totalResolved }; //

    // Otimização de Ícones e Cores nos Cards
    document.getElementById('cards').innerHTML = data.status.map(s => { //
      const cardConfig = {
        'Total': { icon: 'fas fa-folder-open', color: 'text-gray-700' },
        'Novo': { icon: 'fas fa-plus-circle', color: 'text-blue-500' },
        'Em andamento': { icon: 'fas fa-spinner', color: 'text-yellow-500' },
        'Planejado': { icon: 'fas fa-clipboard-list', color: 'text-purple-500' },
        'Pendente': { icon: 'fas fa-hourglass-half', color: 'text-orange-500' },
        'Resolvido': { icon: 'fas fa-check-circle', color: 'text-green-500' }
      };

      const config = cardConfig[s.label] || { icon: 'fas fa-question-circle', color: 'text-gray-500' };

      return `
        <div class="bg-white p-6 rounded-xl shadow-md text-center transform hover:scale-105 transition duration-300 flex flex-col items-center justify-center">
          <i class="${config.icon} text-4xl ${config.color} mb-3"></i>
          <h3 class="text-3xl font-extrabold text-gray-800">${s.total}</h3>
          <p class="text-lg text-gray-600">${s.label}</p>
        </div>
      `;
    }).join('');

    const existingChartStatus = Chart.getChart('graficoStatus'); //
    if (existingChartStatus) { //
      existingChartStatus.destroy(); //
    }
    const ctx1 = document.getElementById('graficoStatus').getContext('2d'); //

    const filteredStatusData = data.status.filter(s => s.label !== 'Total' && s.label !== 'Resolvido'); //
    const statusLabels = filteredStatusData.map(s => s.label); //
    const statusTotals = filteredStatusData.map(s => s.total); //

    const statusColors = statusLabels.map(label => { //
      switch (label) {
        case 'Novo':
          return '#3B82F6';
        case 'Em andamento':
          return '#F59E0B';
        case 'Planejado':
          return '#8B5CF6';
        case 'Pendente':
          return '#F97316';
        default:
          return '#6B7280';
      }
    });

    new Chart(ctx1, { //
      type: 'bar', //
      data: { //
        labels: statusLabels, //
        datasets: [{ //
          label: 'Total de Chamados', //
          data: statusTotals, //
          backgroundColor: statusColors, //
          borderColor: statusColors, //
          borderWidth: 1 //
        }]
      },
      options: { //
        responsive: true, //
        maintainAspectRatio: false, //
        plugins: { //
          legend: { //
            display: false //
          }
        },
        scales: { //
          y: { //
            beginAtZero: true, //
            ticks: { //
              precision: 0 //
            }
          }
        }
      }
    });

    const existingChartMensal = Chart.getChart('graficoMensal'); //
    if (existingChartMensal) { //
      existingChartMensal.destroy(); //
    }
    const ctx2 = document.getElementById('graficoMensal').getContext('2d'); //
    
    // Formatar labels para mês/ano em português
    const mesesNomes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    const labelsFormatados = data.mensal.map(m => {
      const [ano, mes] = m.mes.split('-');
      return `${mesesNomes[parseInt(mes) - 1]}/${ano.slice(2)}`;
    });
    
    new Chart(ctx2, { //
      type: 'bar', //
      data: { //
        labels: labelsFormatados, //
        datasets: [{ //
          label: 'Total de Chamados', //
          data: data.mensal.map(m => m.total), //
          backgroundColor: '#10B981', //
          borderColor: '#059669', //
          borderWidth: 1 //
        }]
      },
      options: { //
        responsive: true, //
        maintainAspectRatio: false, //
        plugins: { //
          legend: { //
            display: false //
          }
        },
        scales: { //
          y: { //
            beginAtZero: true, //
            ticks: { //
              precision: 0 //
            }
          }
        }
      }
    });

    // Novo gráfico: Top 10 Requerentes
    const existingChartRequerentes = Chart.getChart('graficoRequerentes'); //
    if (existingChartRequerentes) { //
      existingChartRequerentes.destroy(); //
    }
    const ctx3 = document.getElementById('graficoRequerentes').getContext('2d'); //
    new Chart(ctx3, { //
      type: 'bar', // Pode ser 'bar' ou 'horizontalBar' (se preferir barras horizontais)
      data: { //
        labels: data.topRequerentes.map(r => r.requester_name), //
        datasets: [{ //
          label: 'Chamados Abertos', //
          data: data.topRequerentes.map(r => r.total_tickets), //
          backgroundColor: '#6366F1', // Indigo-500
          borderColor: '#4F46E5', //
          borderWidth: 1 //
        }]
      },
      options: { //
        responsive: true, //
        maintainAspectRatio: false, //
        indexAxis: 'y', // Para barras horizontais
        plugins: { //
          legend: { //
            display: false //
          }
        },
        scales: { //
          x: { // Eixo X para barras horizontais
            beginAtZero: true, //
            ticks: { //
              precision: 0 //
            }
          }
        }
      }
    });

    const select = document.getElementById('category'); //
    if (select.options.length === 1) { // Garante que as categorias só são carregadas uma vez
      data.categoriasSelect.forEach(c => { //
        const opt = document.createElement('option'); //
        opt.value = c.id; //
        opt.textContent = c.name; //
        select.appendChild(opt); //
      });
    }

    hideLoading();

  } catch (error) {
    console.error('Erro ao carregar o dashboard:', error);
    alert('Ocorreu um erro ao carregar os dados. Tente novamente mais tarde.');
    hideLoading();
  }
}

function exportCSV() {
  let csv = 'Titulo,Status,Requerente,Data\n';

  // Usa ticketsCache diretamente, sem uma nova requisição fetch
  const ticketsToExport = ticketsCache; //

  if (!ticketsToExport || ticketsToExport.length === 0) {
      alert('Não há dados para exportar. Carregue o dashboard primeiro.');
      return;
  }

  ticketsToExport.forEach(t => { //
    const dateParts = t.date.split(' ')[0].split('-'); //
    const formattedDate = `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`; //

    const safeName = `"${t.name.replace(/"/g, '""')}"`; //
    const safeStatus = `"${t.status_label.replace(/"/g, '""')}"`; //
    const requesterName = t.full_requester_name && t.full_requester_name.trim() !== '' ? t.full_requester_name : 'N/A'; //
    const safeRequesterName = `"${requesterName.replace(/"/g, '""')}"`; //
        
    const linha = `${safeName},${safeStatus},${safeRequesterName},${formattedDate}`; //
    csv += linha + '\n'; //
  });

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' }); //
  const link = document.createElement('a'); //
  link.href = URL.createObjectURL(blob); //
  link.download = 'relatorio_glpi.csv'; //
  link.click(); //
}

// Funções do Modal PDF
function openPDFModal() {
  if (!ticketsCache || ticketsCache.length === 0) {
    alert('Não há dados para gerar o PDF. Carregue o dashboard primeiro.');
    return;
  }
  document.getElementById('pdfModal').classList.remove('hidden');
}

function closePDFModal() {
  document.getElementById('pdfModal').classList.add('hidden');
}

async function generatePDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();

  if (!ticketsCache || ticketsCache.length === 0) {
      alert('Não há dados para gerar o PDF. Carregue o dashboard primeiro.');
      return;
  }

  // Verificar colunas selecionadas
  const colCodigo = document.getElementById('col_codigo').checked;
  const colTitulo = document.getElementById('col_titulo').checked;
  const colStatus = document.getElementById('col_status').checked;
  const colRequerente = document.getElementById('col_requerente').checked;
  const colData = document.getElementById('col_data').checked;

  // Verificar se pelo menos uma coluna foi selecionada
  if (!colCodigo && !colTitulo && !colStatus && !colRequerente && !colData) {
    alert('Selecione pelo menos uma coluna para o relatório.');
    return;
  }

  // Obter filtro de status selecionado
  const filtroStatus = document.querySelector('input[name="filtro_status"]:checked').value;
  
  // Filtrar tickets com base na seleção
  let ticketsToExport = [];
  if (filtroStatus === 'todos') {
    ticketsToExport = ticketsCache;
  } else if (filtroStatus === 'abertos') {
    ticketsToExport = ticketsCache.filter(t => {
      const status = parseInt(t.status);
      return status !== 5 && status !== 6;
    });
  } else if (filtroStatus === 'resolvidos') {
    ticketsToExport = ticketsCache.filter(t => {
      const status = parseInt(t.status);
      return status === 5 || status === 6;
    });
  }

  if (ticketsToExport.length === 0) {
    alert('Não há chamados com o filtro selecionado.');
    return;
  }
  
  // Calcular abertos e resolvidos com base nos tickets filtrados para exportação
  let abertos = 0;
  let resolvidos = 0;
  ticketsToExport.forEach(t => {
    const status = parseInt(t.status);
    if (status === 5 || status === 6) {
      resolvidos++;
    } else {
      abertos++;
    }
  });

  // Texto do filtro de status para o cabeçalho
  let filtroStatusTexto = 'Todos';
  if (filtroStatus === 'abertos') filtroStatusTexto = 'Não Resolvidos';
  if (filtroStatus === 'resolvidos') filtroStatusTexto = 'Resolvidos/Fechados';
  
  // Obter informações dos filtros
  const start = document.getElementById('startDate').value;
  const end = document.getElementById('endDate').value;
  const categorySelect = document.getElementById('category');
  const categoryName = categorySelect.options[categorySelect.selectedIndex].text;
  
  // Cabeçalho do PDF - Preto/Branco/Cinza
  doc.setFontSize(18);
  doc.setTextColor(0, 0, 0);
  doc.text("Relatório de Chamados GLPI", 105, 18, null, null, "center");
  
  // Linha separadora
  doc.setDrawColor(150, 150, 150);
  doc.line(15, 23, 195, 23);
  
  // Informações do filtro
  doc.setFontSize(10);
  doc.setTextColor(60, 60, 60);
  
  const startFormatted = new Date(start + 'T00:00:00').toLocaleDateString('pt-BR');
  const endFormatted = new Date(end + 'T00:00:00').toLocaleDateString('pt-BR');
  
  doc.text(`Período: ${startFormatted} a ${endFormatted}`, 15, 30);
  doc.text(`Categoria: ${categoryName}`, 15, 36);
  doc.text(`Filtro: ${filtroStatusTexto}`, 15, 42);
  
  // Resumo no lado direito
  doc.text(`Total: ${ticketsToExport.length}`, 150, 30);
  doc.text(`Abertos: ${abertos}`, 150, 36);
  doc.text(`Resolvidos: ${resolvidos}`, 150, 42);
  
  // Montar colunas dinamicamente
  const tableColumn = [];
  if (colCodigo) tableColumn.push("Cód.");
  if (colTitulo) tableColumn.push("Título");
  if (colStatus) tableColumn.push("Status");
  if (colRequerente) tableColumn.push("Requerente");
  if (colData) tableColumn.push("Data");

  const tableRows = [];

  ticketsToExport.forEach(t => {
    const dateParts = t.date.split(' ')[0].split('-');
    const formattedDate = `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
    const requesterName = t.full_requester_name && t.full_requester_name.trim() !== '' ? t.full_requester_name : 'N/A';
    
    // Montar linha dinamicamente
    const row = [];
    if (colCodigo) row.push(t.id);
    if (colTitulo) row.push(t.name);
    if (colStatus) row.push(t.status_label);
    if (colRequerente) row.push(requesterName);
    if (colData) row.push(formattedDate);
    
    tableRows.push(row);
  });

  doc.autoTable({
    head: [tableColumn],
    body: tableRows,
    startY: 50,
    theme: 'striped',
    styles: { fontSize: 8, cellPadding: 2, overflow: 'linebreak', textColor: [0, 0, 0] },
    headStyles: { fillColor: [60, 60, 60], textColor: [255, 255, 255], fontStyle: 'bold' },
    alternateRowStyles: { fillColor: [240, 240, 240] },
    margin: { top: 10, left: 15, right: 15 }
  });

  doc.save('relatorio_glpi.pdf');
  closePDFModal();
}

// Inicializa o dashboard e configura a atualização a cada 1 minuto
window.onload = () => { //
  loadDashboard(); // Carrega o dashboard na primeira vez
  setInterval(loadDashboard, 60000); // Atualiza a cada 60 segundos (1 minuto)
};