/** Leadership dashboard charts. Chart.js is imported dynamically so it only loads on /rahbariyat. */
document.addEventListener('DOMContentLoaded', async () => {
	const root = document.querySelector('[data-dashboard]');
	if (!root) {
		return;
	}

	const skeletons = root.querySelectorAll('[data-skeleton]');
	const { Chart, registerables } = await import('chart.js');
	Chart.register(...registerables);

	const payload = JSON.parse(root.dataset.dashboard);
	const months = ['Yan', 'Fev', 'Mar', 'Apr', 'May', 'Iyn', 'Iyl', 'Avg', 'Sen', 'Okt', 'Noy', 'Dek'];

	new Chart(document.querySelector('#chart-faculty'), {
		type: 'bar',
		data: {
			labels: payload.facultyRows.map((r) => r.faculty),
			datasets: [
				{ label: 'Yuklangan', data: payload.facultyRows.map((r) => r.uploaded), backgroundColor: '#1e40af' },
				{ label: 'Tasdiqlangan', data: payload.facultyRows.map((r) => r.approved), backgroundColor: '#0f766e' },
			],
		},
	});

	new Chart(document.querySelector('#chart-dynamics'), {
		type: 'line',
		data: {
			labels: payload.dynamics.map((r) => months[r.month - 1]),
			datasets: [{ label: 'Tasdiqlangan', data: payload.dynamics.map((r) => r.approved), borderColor: '#0f766e', tension: 0.3 }],
		},
	});

	new Chart(document.querySelector('#chart-tiers'), {
		type: 'doughnut',
		data: {
			labels: Object.keys(payload.tierDistribution),
			datasets: [{ data: Object.values(payload.tierDistribution), backgroundColor: ['#1e40af', '#0f766e', '#b45309', '#475569', '#94a3b8', '#b91c1c'] }],
		},
	});

	for (const skeleton of skeletons) {
		skeleton.hidden = true;
	}
});
