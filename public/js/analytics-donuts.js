(function () {
    'use strict';

    const DEFAULT_COLORS = [
        '#1769a8', '#13a89e', '#6c55d9', '#e55f6d', '#d89916',
        '#2f855a', '#9b51a0', '#d16a24', '#4b7bec', '#66758a'
    ];

    const STATUS_COLORS = {
        'submitted': '#2f72b7',
        'for verification': '#d89916',
        'verified': '#16858c',
        'assigned': '#4d57bb',
        'in progress': '#7d48ad',
        'action taken': '#128060',
        'resolved': '#2f855a',
        'rejected': '#c33d49',
        'closed': '#66758a'
    };

    function normalizeValues(values) {
        return values.map(value => Number(value) || 0);
    }

    function totalOf(values) {
        return values.reduce((total, value) => total + value, 0);
    }

    const centerTextPlugin = {
        id: 'dilgCenterText',
        afterDraw(chart, args, pluginOptions) {
            const innerArc = chart.getDatasetMeta(0)?.data?.[0];
            if (!innerArc) {
                const { ctx, chartArea } = chart;
                if (!chartArea) return;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillStyle = '#718096';
                ctx.font = '700 12px Inter, system-ui, sans-serif';
                ctx.fillText('NO DATA AVAILABLE', (chartArea.left + chartArea.right) / 2, (chartArea.top + chartArea.bottom) / 2);
                ctx.restore();
                return;
            }

            const values = normalizeValues(chart.data.datasets[0]?.data ?? []);
            const total = totalOf(values);
            const { ctx } = chart;
            const label = pluginOptions?.label || 'TOTAL REPORTS';
            const unit = pluginOptions?.unit || 'reports';
            const valueSize = Math.max(21, Math.min(30, chart.width * 0.085));

            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = '#718096';
            ctx.font = '700 10px Inter, system-ui, sans-serif';
            ctx.fillText(label, innerArc.x, innerArc.y - 20);
            ctx.fillStyle = '#102b4c';
            ctx.font = `800 ${valueSize}px Inter, system-ui, sans-serif`;
            ctx.fillText(total.toLocaleString(), innerArc.x, innerArc.y + 2);
            ctx.fillStyle = '#64748b';
            ctx.font = '600 11px Inter, system-ui, sans-serif';
            ctx.fillText(unit, innerArc.x, innerArc.y + 23);
            ctx.restore();
        }
    };

    function createConfig({ labels, values, centerLabel, unit = 'reports', colors = DEFAULT_COLORS }) {
        const cleanLabels = Array.from(labels || []);
        const cleanValues = normalizeValues(Array.from(values || []));
        const assignedColors = cleanLabels.map((label, index) => colors[index % colors.length]);

        return {
            type: 'doughnut',
            data: {
                labels: cleanLabels,
                datasets: [{
                    data: cleanValues,
                    backgroundColor: assignedColors,
                    borderColor: '#fff',
                    borderWidth: 2,
                    borderRadius: 3,
                    hoverOffset: 4
                }]
            },
            plugins: [centerTextPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '73%',
                radius: '77%',
                rotation: -90,
                animation: {
                    duration: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 500,
                    easing: 'easeOutQuart'
                },
                interaction: { mode: 'nearest', intersect: true },
                plugins: {
                    dilgCenterText: { label: centerLabel, unit },
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                const total = totalOf(normalizeValues(context.dataset.data));
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : '0.0';
                                return ` ${context.label}: ${context.parsed.toLocaleString()} ${unit} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        };
    }

    function statusColors(labels) {
        return Array.from(labels || []).map((label, index) =>
            STATUS_COLORS[String(label).trim().toLowerCase()] || DEFAULT_COLORS[index % DEFAULT_COLORS.length]
        );
    }

    function renderLegend(container, chart) {
        if (!container) return;

        const labels = chart.data.labels || [];
        const dataset = chart.data.datasets[0];
        const values = normalizeValues(dataset.data || []);
        const total = totalOf(values);
        container.replaceChildren();

        if (total === 0) {
            const empty = document.createElement('span');
            empty.className = 'donut-legend-empty';
            empty.textContent = 'No reports in this period';
            container.appendChild(empty);
            return;
        }

        labels.forEach((label, index) => {
            const item = document.createElement('div');
            item.className = 'donut-legend-item';
            const dot = document.createElement('span');
            dot.className = 'donut-legend-dot';
            dot.style.backgroundColor = dataset.backgroundColor[index];
            const name = document.createElement('span');
            name.className = 'donut-legend-name';
            name.textContent = label;
            const value = document.createElement('strong');
            value.textContent = `${Math.round((values[index] / total) * 100)}%`;
            item.append(dot, name, value);
            container.appendChild(item);
        });
    }

    window.DilgAnalyticsDonut = { createConfig, renderLegend, statusColors };
})();
