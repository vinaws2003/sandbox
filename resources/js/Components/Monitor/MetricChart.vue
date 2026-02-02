<script setup>
import { ref, onMounted, watch } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    labels: {
        type: Array,
        default: () => [],
    },
    values: {
        type: Array,
        default: () => [],
    },
    unit: {
        type: String,
        default: '',
    },
    color: {
        type: String,
        default: '#3B82F6',
    },
    stats: {
        type: Object,
        default: null,
    },
});

const chartData = ref({
    labels: props.labels,
    datasets: [
        {
            label: props.title,
            data: props.values,
            borderColor: props.color,
            backgroundColor: props.color + '20',
            fill: true,
            tension: 0.4,
            pointRadius: 0,
            pointHoverRadius: 4,
        },
    ],
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            callbacks: {
                label: function (context) {
                    return `${context.parsed.y}${props.unit}`;
                },
            },
        },
    },
    scales: {
        x: {
            grid: {
                display: false,
            },
            ticks: {
                maxTicksLimit: 8,
            },
        },
        y: {
            beginAtZero: true,
            grid: {
                color: '#E5E7EB',
            },
            ticks: {
                callback: function (value) {
                    return value + props.unit;
                },
            },
        },
    },
    interaction: {
        intersect: false,
        mode: 'index',
    },
};

watch(() => [props.labels, props.values], ([newLabels, newValues]) => {
    chartData.value = {
        labels: newLabels,
        datasets: [
            {
                ...chartData.value.datasets[0],
                data: newValues,
            },
        ],
    };
});
</script>

<template>
    <div class="overflow-hidden rounded-lg bg-white p-4 shadow">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-sm font-medium text-gray-900">{{ title }}</h3>
            <div v-if="stats" class="flex gap-4 text-xs text-gray-500">
                <span>Avg: <span class="font-medium text-gray-900">{{ stats.avg }}{{ unit }}</span></span>
                <span>Min: <span class="font-medium text-gray-900">{{ stats.min }}{{ unit }}</span></span>
                <span>Max: <span class="font-medium text-gray-900">{{ stats.max }}{{ unit }}</span></span>
            </div>
        </div>
        <div class="h-48">
            <Line :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>
