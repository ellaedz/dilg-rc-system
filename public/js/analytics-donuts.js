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

    function withAlpha(hex, alpha) {
        const value = hex.replace('#', '');
        const red = parseInt(value.substring(0, 2), 16);
        const green = parseInt(value.substring(2, 4), 16);
        const blue = parseInt(value.substring(4, 6), 16);
        return `rgba(${red}, ${green}, ${blue}, ${alpha})`;
    }

    function normalizeValues(values) {
        return values.map(value => Number(value) || 0);
    }

    function totalOf(values) {
        return values.reduce((total, value) => total + value, 0);
    }

    const centerTextPlugin = {
        id: 'dilgCenterText',
        afterDraw(chart, args, pluginOptions) {
            const innerArc = chart.getDatasetMeta(1)?.data?.[0] ?? chart.getDatasetMeta(0)?.data?.[0];
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

            const values = normalizeValues(chart.data.datasets[1]?.data ?? chart.data.datasets[0]?.data ?? []);
            const total = totalOf(values);
            const { ctx } = chart;
            const label = pluginOptions?.label || 'TOTAL REPORTS';
            const unit = pluginOptions?.unit || 'reports';
            const valueSize = Math.max(22, Math.min(36, chart.width * 0.075));

            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillStyle = '#718096';
            ctx.font = '700 10px Inter, system-ui, sans-serif';
            ctx.fillText(label, innerArc.x, innerArc.y - 24);
            ctx.fillStyle = '#102b4c';
            ctx.font = `800 ${valueSize}px Inter, system-ui, sans-serif`;
            ctx.fillText(total.toLocaleString(), innerArc.x, innerArc.y + 2);
            ctx.fillStyle = '#64748b';
            ctx.font = '600 11px Inter, system-ui, sans-serif';
            ctx.fillText(unit, innerArc.x, innerArc.y + 27);
            ctx.restore();
        }
    };

    function legendLabels(chart) {
        const labels = chart.data.labels || [];
        const dataset = chart.data.datasets[1] || chart.data.datasets[0];
        const values = normalizeValues(dataset.data || []);
        const total = totalOf(values);

        return labels.map((label, index) => {
            const value = values[index];
            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
            return {
                text: `${label}  ${value.toLocaleString()} · ${percentage}%`,
                fillStyle: dataset.backgroundColor[index],
                strokeStyle: dataset.backgroundColor[index],
                lineWidth: 0,
                hidden: !chart.getDataVisibility(index),
                index,
                pointStyle: 'circle'
            };
        });
    }

    function createConfig({ labels, values, centerLabel, unit = 'reports', colors = DEFAULT_COLORS }) {
        const cleanLabels = Array.from(labels || []);
        const cleanValues = normalizeValues(Array.from(values || []));
        const assignedColors = cleanLabels.map((label, index) => colors[index % colors.length]);

        return {
            type: 'doughnut',
            data: {
                labels: cleanLabels,
                datasets: [
                    {
                        data: cleanValues,
                        backgroundColor: assignedColors.map(color => withAlpha(color, 0.35)),
                        borderColor: 'transparent',
                        borderWidth: 0,
                        borderRadius: 18,
                        spacing: 1,
                        weight: 0.55,
                        hoverOffset: 0
                    },
                    {
                        data: cleanValues,
                        backgroundColor: assignedColors,
                        borderColor: 'transparent',
                        borderWidth: 0,
                        borderRadius: 18,
                        spacing: 3,
                        weight: 1,
                        hoverOffset: 7
                    }
                ]
            },
            plugins: [centerTextPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '56%',
                radius: '88%',
                rotation: -90,
                animation: {
                    duration: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 500,
                    easing: 'easeOutQuart'
                },
                interaction: { mode: 'nearest', intersect: true },
                plugins: {
                    dilgCenterText: { label: centerLabel, unit },
                    legend: {
                        position: 'bottom',
                        onClick(event, legendItem, legend) {
                            legend.chart.toggleDataVisibility(legendItem.index);
                            legend.chart.update();
                        },
                        labels: {
                            generateLabels: legendLabels,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            boxHeight: 8,
                            padding: 14,
                            color: '#46566b',
                            font: { size: 11, weight: '600', family: 'Inter, system-ui, sans-serif' }
                        }
                    },
                    tooltip: {
                        filter(context) { return context.datasetIndex === 1; },
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

    window.DilgAnalyticsDonut = { createConfig, statusColors };
})();
