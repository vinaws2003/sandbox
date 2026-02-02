<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import StatusBadge from './StatusBadge.vue';

const props = defineProps({
    node: {
        type: Object,
        required: true,
    },
});

const cardClasses = computed(() => {
    switch (props.node.status) {
        case 'critical':
            return 'bg-red-50 ring-1 ring-red-200';
        case 'warning':
            return 'bg-yellow-50 ring-1 ring-yellow-200';
        case 'inactive':
        case 'unknown':
            return 'bg-gray-50 ring-1 ring-gray-200';
        default:
            return 'bg-white';
    }
});

const typeLabels = {
    synology: 'Synology NAS',
    docker: 'Docker',
    galera: 'Galera',
    laravel_app: 'Laravel App',
};

const typeIcons = {
    synology: '🖥️',
    docker: '🐳',
    galera: '🗄️',
    laravel_app: '🔷',
};

const recentErrors = computed(() => {
    const errors = props.node.metrics?.status?.metadata?.recent_errors;
    if (!errors?.length) return [];
    const cutoff = Date.now() - 8 * 60 * 60 * 1000;
    return errors.filter(e => !e.timestamp || new Date(e.timestamp).getTime() > cutoff);
});

function formatMetricValue(type, value) {
    if (value === undefined || value === null) return '-';

    const num = parseFloat(value);

    switch (type) {
        case 'cpu':
        case 'memory':
        case 'disk':
            return `${num.toFixed(1)}%`;
        case 'response_time':
            return `${(num * 1000).toFixed(0)}ms`;
        case 'temperature':
            return `${num.toFixed(1)}°C`;
        case 'queue_size':
        case 'failed_jobs':
            return Math.round(num);
        default:
            return num.toFixed(2);
    }
}
</script>

<template>
    <Link
        :href="route('monitor.nodes.show', node.id)"
        class="block overflow-hidden rounded-lg shadow transition hover:shadow-md"
        :class="cardClasses"
    >
        <div class="p-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">{{ typeIcons[node.type] }}</span>
                    <div>
                        <h3 class="text-sm font-medium text-gray-900">{{ node.name }}</h3>
                        <p class="text-xs text-gray-500">{{ typeLabels[node.type] }}</p>
                    </div>
                </div>
                <StatusBadge :status="node.status" size="sm" />
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                <template v-if="node.type === 'synology'">
                    <div>
                        <span class="text-gray-500">CPU</span>
                        <span class="ml-2 font-medium">{{ formatMetricValue('cpu', node.metrics?.cpu?.value) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Memory</span>
                        <span class="ml-2 font-medium">{{ formatMetricValue('memory', node.metrics?.memory?.value) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Disk</span>
                        <span class="ml-2 font-medium">{{ formatMetricValue('disk', node.metrics?.disk?.value) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Temp</span>
                        <span class="ml-2 font-medium">{{ formatMetricValue('temperature', node.metrics?.temperature?.value) }}</span>
                    </div>
                </template>

                <template v-else-if="node.type === 'laravel_app'">
                    <div>
                        <span class="text-gray-500">Response</span>
                        <span class="ml-2 font-medium">{{ formatMetricValue('response_time', node.metrics?.response_time?.value) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Queue</span>
                        <span class="ml-2 font-medium">{{ formatMetricValue('queue_size', node.metrics?.queue_size?.value) }}</span>
                    </div>
                    <div
                        v-if="recentErrors.length"
                        class="col-span-2"
                    >
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                            {{ recentErrors.length }} {{ recentErrors.length === 1 ? 'error' : 'errors' }}
                        </span>
                    </div>
                </template>

                <template v-else-if="node.type === 'galera'">
                    <div>
                        <span class="text-gray-500">Cluster</span>
                        <span class="ml-2 font-medium">{{ parseInt(node.metrics?.wsrep_cluster_size?.value) || '-' }} nodes</span>
                    </div>
                    <div>
                        <span class="text-gray-500">State</span>
                        <span class="ml-2 font-medium">{{ parseInt(node.metrics?.wsrep_local_state?.value) === 4 ? 'Synced' : 'Not Synced' }}</span>
                    </div>
                </template>
            </div>

            <div class="mt-4 flex items-center justify-between text-xs text-gray-400">
                <span>{{ node.host }}</span>
                <span v-if="node.last_updated">Updated {{ new Date(node.last_updated).toLocaleTimeString() }}</span>
            </div>
        </div>
    </Link>
</template>
