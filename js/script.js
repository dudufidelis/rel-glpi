let ticketsCache = []; //
let totalsCache = { totalOpen: 0, totalResolved: 0 }; //

async function loadDashboard() {
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
    new Chart(ctx2, { //
      type: 'bar', //
      data: { //
        labels: data.mensal.map(m => m.mes), //
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

  } catch (error) {
    console.error('Erro ao carregar o dashboard:', error);
    alert('Ocorreu um erro ao carregar os dados. Tente novamente mais tarde.');
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

async function generatePDF() {
  const { jsPDF } = window.jspdf; //
  const doc = new jsPDF(); //

  // Usa ticketsCache e totalsCache diretamente, sem uma nova requisição fetch
  const ticketsToExport = ticketsCache; //
  const totalOpen = totalsCache.totalOpen; //
  const totalResolved = totalsCache.totalResolved; //

  if (!ticketsToExport || ticketsToExport.length === 0) {
      alert('Não há dados para gerar o PDF. Carregue o dashboard primeiro.');
      return;
  }
  
  doc.setFontSize(16); //
  doc.text("Relatório de Chamados GLPI", 105, 20, null, null, "center"); //
  doc.setFontSize(10); //
  // Pega os valores dos inputs para o período no PDF
  const start = document.getElementById('startDate').value; //
  const end = document.getElementById('endDate').value; //
  doc.text(`Período: ${new Date(start).toLocaleDateString('pt-BR')} a ${new Date(end).toLocaleDateString('pt-BR')}`, 105, 28, null, null, "center"); //
  doc.setFontSize(12); //
  doc.text(`Total Aberto: ${totalOpen} | Total Resolvido: ${totalResolved}`, 105, 36, null, null, "center"); //
  
  const tableColumn = ["Título", "Status", "Requerente", "Data"]; //
  const tableRows = []; //

  ticketsToExport.forEach(t => { //
    const dateParts = t.date.split(' ')[0].split('-'); //
    const formattedDate = `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`; //
    const requesterName = t.full_requester_name && t.full_requester_name.trim() !== '' ? t.full_requester_name : 'N/A'; //
    
    tableRows.push([ //
      t.name, //
      t.status_label, //
      requesterName, //
      formattedDate //
    ]);
  });

  doc.autoTable({ //
    head: [tableColumn], //
    body: tableRows, //
    startY: 45, //
    theme: 'striped', //
    styles: { fontSize: 8, cellPadding: 2, overflow: 'linebreak' }, //
    headStyles: { fillColor: [40, 40, 40], textColor: [255, 255, 255] }, //
    margin: { top: 10 } //
  });

  doc.save('relatorio_glpi.pdf'); //
}

// Inicializa o dashboard e configura a atualização a cada 1 minuto
window.onload = () => { //
  loadDashboard(); // Carrega o dashboard na primeira vez
  setInterval(loadDashboard, 60000); // Atualiza a cada 60 segundos (1 minuto)
};