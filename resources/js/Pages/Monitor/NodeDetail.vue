<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import StatusBadge from '@/Components/Monitor/StatusBadge.vue';
import MetricChart from '@/Components/Monitor/MetricChart.vue';
import TimeRangeSelector from '@/Components/Monitor/TimeRangeSelector.vue';
import TestConnectionButton from '@/Components/Monitor/TestConnectionButton.vue';

const props = defineProps({
    node: Object,
    metrics: Object,
    charts: Object,
    recentAlerts: Array,
    range: String,
});

const selectedRange = ref(props.range);

const recentErrors = computed(() => {
    const errors = props.metrics?.status?.metadata?.recent_errors;
    if (!errors?.length) return [];
    const cutoff = Date.now() - 8 * 60 * 60 * 1000;
    return errors.filter(e => !e.timestamp || new Date(e.timestamp).getTime() > cutoff);
});

function deleteNode() {
    if (confirm(`Are you sure you want to delete "${props.node.name}"?`)) {
        router.delete(route('monitor.nodes.destroy', props.node.id));
    }
}

watch(selectedRange, (newRange) => {
    router.get(
        route('monitor.nodes.show', props.node.id),
        { range: newRange },
        { preserveState: true, preserveScroll: true }
    );
});

const typeLabels = {
    synology: 'Synology NAS',
    docker: 'Docker Host',
    galera: 'Galera Cluster Node',
    laravel_app: 'Laravel Application',
};

function formatMetricValue(type, value) {
    if (value === undefined || value === null) return '-';

    switch (type) {
        case 'cpu':
        case 'memory':
        case 'disk':
        case 'container_cpu':
        case 'container_memory':
            return `${parseFloat(value).toFixed(1)}%`;
        case 'response_time':
        case 'internal_response_time':
            return `${(parseFloat(value) * 1000).toFixed(0)}ms`;
        case 'temperature':
            return `${parseFloat(value).toFixed(1)}°C`;
        case 'network_in':
        case 'network_out':
            return `${parseFloat(value).toFixed(1)} KB/s`;
        case 'uptime':
            const days = Math.floor(value / 86400);
            return `${days} days`;
        default:
            return parseFloat(value).toFixed(2);
    }
}

function getChartColor(type) {
    const colors = {
        cpu: '#EF4444',
        memory: '#F59E0B',
        disk: '#3B82F6',
        temperature: '#EF4444',
        network_in: '#10B981',
        network_out: '#6366F1',
        response_time: '#8B5CF6',
        queue_size: '#F59E0B',
        failed_jobs: '#EF4444',
    };
    return colors[type] || '#6B7280';
}

function getChartUnit(type) {
    const units = {
        cpu: '%',
        memory: '%',
        disk: '%',
        temperature: '°C',
        response_time: 's',
        queue_size: '',
        failed_jobs: '',
    };
    return units[type] || '';
}
</script>

<template>
    <Head :title="node.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <Link
                        :href="route('monitor.dashboard')"
                        class="mr-4 text-gray-400 hover:text-gray-600"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h2 class="text-xl font-semibold leading-tight text-gray-800">
                            {{ node.name }}
                        </h2>
                        <p class="text-sm text-gray-500">{{ typeLabels[node.type] }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <TestConnectionButton :node-id="node.id" />
                    <Link
                        :href="route('monitor.nodes.edit', node.id)"
                        class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50"
                    >
                        Edit
                    </Link>
                    <button
                        type="button"
                        class="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700"
                        @click="deleteNode"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Node Info -->
                <div class="mb-8 overflow-hidden rounded-lg bg-white shadow">
                    <div class="px-4 py-5 sm:px-6">
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Host</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ node.host }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Port</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ node.port || 'Default' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    <StatusBadge :status="node.is_active ? 'healthy' : 'inactive'" size="sm" />
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Created</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ node.created_at }}</dd>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Metrics -->
                <div class="mb-8">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Current Metrics</h3>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                        <div
                            v-for="(data, type) in metrics"
                            :key="type"
                            class="rounded-lg bg-white p-4 shadow"
                        >
                            <dt class="truncate text-sm font-medium text-gray-500">{{ type }}</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900">
                                {{ formatMetricValue(type, data.value) }}
                            </dd>
                            <dd class="mt-1 text-xs text-gray-400">{{ data.recorded_at }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Recent Errors (Laravel App) -->
                <div
                    v-if="node.type === 'laravel_app' && recentErrors.length"
                    class="mb-8"
                >
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Recent Errors</h3>
                    <div class="overflow-hidden rounded-lg bg-white shadow">
                        <ul class="divide-y divide-gray-200">
                            <li
                                v-for="(error, index) in recentErrors"
                                :key="index"
                                class="px-4 py-3 sm:px-6"
                            >
                                <div class="flex items-start gap-3">
                                    <span
                                        class="mt-0.5 inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="{
                                            'bg-red-100 text-red-800': error.level === 'error' || error.level === 'critical' || error.level === 'emergency' || error.level === 'alert',
                                            'bg-yellow-100 text-yellow-800': error.level === 'warning',
                                            'bg-blue-100 text-blue-800': error.level === 'notice' || error.level === 'info',
                                            'bg-gray-100 text-gray-800': !error.level || error.level === 'debug',
                                        }"
                                    >
                                        {{ error.level || 'error' }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm text-gray-900">{{ error.message }}</p>
                                        <p v-if="error.timestamp" class="mt-0.5 text-xs text-gray-500">
                                            {{ error.timestamp }}
                                        </p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Time Range Selector -->
                <div class="mb-4 flex justify-end">
                    <TimeRangeSelector v-model="selectedRange" />
                </div>

                <!-- Charts -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <MetricChart
                        v-for="(data, type) in charts"
                        :key="type"
                        :title="type"
                        :labels="data.labels"
                        :values="data.values"
                        :stats="data.stats"
                        :color="getChartColor(type)"
                        :unit="getChartUnit(type)"
                    />
                </div>

                <!-- Recent Alerts for this Node -->
                <div v-if="recentAlerts.length > 0" class="mt-8">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Recent Alerts</h3>
                    <div class="overflow-hidden rounded-lg bg-white shadow">
                        <ul class="divide-y divide-gray-200">
                            <li
                                v-for="alert in recentAlerts"
                                :key="alert.id"
                                class="px-4 py-4 sm:px-6"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ alert.metric_type }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ alert.message }}
                                        </p>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ alert.created_at }}
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
