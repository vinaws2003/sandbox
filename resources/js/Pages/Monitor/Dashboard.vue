<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import NodeCard from '@/Components/Monitor/NodeCard.vue';
import StatCard from '@/Components/Monitor/StatCard.vue';

const props = defineProps({
    nodes: Array,
    recentAlerts: Array,
    stats: Object,
});

let refreshInterval = null;

onMounted(() => {
    refreshInterval = setInterval(() => {
        router.reload({ only: ['nodes', 'recentAlerts', 'stats'] });
    }, 30000);
});

onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});

function getNodesByType(type) {
    return props.nodes.filter(n => n.type === type);
}
</script>

<template>
    <Head title="Monitor Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Monitor Dashboard
                </h2>
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('monitor.nodes.create')"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        Add Node
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Stats -->
                <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        title="Total Nodes"
                        :value="stats.total_nodes"
                        icon="🖥️"
                    />
                    <StatCard
                        title="Active Nodes"
                        :value="stats.active_nodes"
                        icon="✅"
                    />
                    <StatCard
                        title="Active Alerts"
                        :value="stats.active_alerts"
                        icon="🔔"
                    />
                    <StatCard
                        title="Triggered (24h)"
                        :value="stats.triggered_24h"
                        icon="⚠️"
                    />
                </div>

                <!-- Quick Links -->
                <div class="mb-8 flex flex-wrap gap-4">
                    <Link
                        :href="route('monitor.galera')"
                        class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow hover:bg-gray-50"
                    >
                        🗄️ Galera Cluster
                    </Link>
                    <Link
                        :href="route('monitor.alerts.index')"
                        class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow hover:bg-gray-50"
                    >
                        🔔 Alert Rules
                    </Link>
                    <Link
                        :href="route('monitor.alerts.history')"
                        class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow hover:bg-gray-50"
                    >
                        📜 Alert History
                    </Link>
                    <Link
                        :href="route('monitor.settings')"
                        class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow hover:bg-gray-50"
                    >
                        ⚙️ Settings
                    </Link>
                </div>

                <!-- Synology Nodes -->
                <div v-if="getNodesByType('synology').length > 0" class="mb-8">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Synology NAS</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <NodeCard
                            v-for="node in getNodesByType('synology')"
                            :key="node.id"
                            :node="node"
                        />
                    </div>
                </div>

                <!-- Laravel App Nodes -->
                <div v-if="getNodesByType('laravel_app').length > 0" class="mb-8">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Laravel Applications</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <NodeCard
                            v-for="node in getNodesByType('laravel_app')"
                            :key="node.id"
                            :node="node"
                        />
                    </div>
                </div>

                <!-- Galera Nodes -->
                <div v-if="getNodesByType('galera').length > 0" class="mb-8">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Galera Cluster</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <NodeCard
                            v-for="node in getNodesByType('galera')"
                            :key="node.id"
                            :node="node"
                        />
                    </div>
                </div>

                <!-- Docker Nodes -->
                <div v-if="getNodesByType('docker').length > 0" class="mb-8">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Docker Hosts</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <NodeCard
                            v-for="node in getNodesByType('docker')"
                            :key="node.id"
                            :node="node"
                        />
                    </div>
                </div>

                <!-- Recent Alerts -->
                <div v-if="recentAlerts.length > 0" class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Recent Alerts</h3>
                            <Link
                                :href="route('monitor.alerts.history')"
                                class="text-sm text-indigo-600 hover:text-indigo-500"
                            >
                                View all
                            </Link>
                        </div>
                    </div>
                    <ul class="divide-y divide-gray-200">
                        <li
                            v-for="alert in recentAlerts"
                            :key="alert.id"
                            class="px-4 py-4 sm:px-6"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ alert.node_name }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ alert.message }}
                                    </p>
                                </div>
                                <div class="text-right text-sm text-gray-500">
                                    {{ new Date(alert.created_at).toLocaleString() }}
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Empty State -->
                <div
                    v-if="nodes.length === 0"
                    class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center"
                >
                    <svg
                        class="mx-auto h-12 w-12 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 12h14M12 5l7 7-7 7"
                        />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No nodes configured</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Get started by adding your first node to monitor.
                    </p>
                    <div class="mt-6">
                        <Link
                            :href="route('monitor.nodes.create')"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            Add Node
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
