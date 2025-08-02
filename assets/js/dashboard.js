if (typeof Chart !== 'undefined') {
  document.addEventListener('DOMContentLoaded', () => {
    if (!window.bdpDashboardData) return;

    const canvas = document.getElementById('bdp-messages-chart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const { labels, completed, pending, canceled } = bdpDashboardData;

    const getCSSVar = (name) =>
      getComputedStyle(document.documentElement).getPropertyValue(name).trim();

    const chartColors = {
      completed: getCSSVar('--status-completed') || '#4caf50',
      pending: getCSSVar('--status-pending') || '#ff9800',
      canceled: getCSSVar('--status-canceled') || '#f44336'
    };

    const chart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'completed',
            data: completed,
            backgroundColor: chartColors.completed,
            borderRadius: 4
          },
          {
            label: 'pending',
            data: pending,
            backgroundColor: chartColors.pending,
            borderRadius: 4
          },
          {
            label: 'canceled',
            data: canceled,
            backgroundColor: chartColors.canceled,
            borderRadius: 4
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (tooltipItem) => `${tooltipItem.dataset.label}: ${tooltipItem.raw}`
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { stepSize: 1 }
          }
        }
      }
    });

    const labelsButtons = document.querySelectorAll('.bdp-status-label');
    let active = '';

    labelsButtons.forEach(el => {
      el.addEventListener('click', () => {
        const status = el.dataset.status;

        if (active === status) {
          active = '';
          labelsButtons.forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-pressed', 'false');
          });
          chart.data.datasets.forEach(ds => ds.hidden = false);
        } else {
          active = status;
          labelsButtons.forEach(btn => {
            const isActive = btn === el;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-pressed', String(isActive));
          });
          chart.data.datasets.forEach(ds => {
            ds.hidden = ds.label !== status;
          });
        }

        chart.update();
      });
    });
  });
}