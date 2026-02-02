<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import StatusBadge from '@/Components/Monitor/StatusBadge.vue';
import GaleraNodeVisual from '@/Components/Monitor/GaleraNodeVisual.vue';

const props = defineProps({
    nodes: Array,
    cluster: Object,
});

const selectedNode = ref(null);

function selectNode(node) {
    selectedNode.value = selectedNode.value?.id === node.id ? null : node;
}
</script>

<template>
    <Head title="Galera Cluster" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link
                    :href="route('monitor.dashboard')"
                    class="mr-4 text-gray-400 hover:text-gray-600"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Galera Cluster
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Cluster Status -->
                <div class="mb-8 overflow-hidden rounded-lg bg-white shadow">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Cluster Health</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    {{ cluster.healthy_nodes }} of {{ cluster.expected_size }} nodes healthy
                                </p>
                            </div>
                            <StatusBadge :status="cluster.status" />
                        </div>

                        <div class="mt-6">
                            <div class="flex items-center gap-2">
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-200">
                                    <div
                                        class="h-full transition-all"
                                        :class="{
                                            'bg-green-500': cluster.status === 'healthy',
                                            'bg-yellow-500': cluster.status === 'warning',
                                            'bg-red-500': cluster.status === 'critical',
                                        }"
                                        :style="{ width: `${(cluster.healthy_nodes / cluster.expected_size) * 100}%` }"
                                    />
                                </div>
                                <span class="text-sm font-medium text-gray-700">
                                    {{ Math.round((cluster.healthy_nodes / cluster.expected_size) * 100) }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visual Cluster Representation -->
                <div class="mb-8">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Cluster Nodes</h3>

                    <div v-if="nodes.length === 0" class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center">
                        <p class="text-gray-500">No Galera nodes configured.</p>
                        <Link
                            :href="route('monitor.nodes.create')"
                            class="mt-4 inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            Add Galera Node
                        </Link>
                    </div>

                    <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <GaleraNodeVisual
                            v-for="node in nodes"
                            :key="node.id"
                            :node="node"
                            :is-selected="selectedNode?.id === node.id"
                            @select="selectNode"
                        />
                    </div>
                </div>

                <!-- Cluster Topology Visualization -->
                <div class="mb-8 overflow-hidden rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Replication Topology</h3>

                    <div class="flex items-center justify-center gap-4 py-8">
                        <template v-for="(node, index) in nodes" :key="node.id">
                            <div
                                class="flex flex-col items-center"
                                :class="{ 'opacity-50': !node.connected }"
                            >
                                <div
                                    class="flex h-16 w-16 items-center justify-center rounded-full text-2xl"
                                    :class="{
                                        'bg-green-100': node.status === 'healthy',
                                        'bg-yellow-100': node.status === 'warning',
                                        'bg-red-100': node.status === 'critical',
                                    }"
                                >
                                    🗄️
                                </div>
                                <span class="mt-2 text-sm font-medium text-gray-900">{{ node.name }}</span>
                                <span class="text-xs text-gray-500">{{ node.state_comment }}</span>
                            </div>

                            <div
                                v-if="index < nodes.length - 1"
                                class="flex items-center gap-1"
                            >
                                <div class="h-0.5 w-8 bg-gray-300" :class="{ 'bg-green-500': node.connected && nodes[index + 1]?.connected }"></div>
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                                <div class="h-0.5 w-8 bg-gray-300" :class="{ 'bg-green-500': node.connected && nodes[index + 1]?.connected }"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Selected Node Details -->
                <div v-if="selectedNode" class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">{{ selectedNode.name }} Details</h3>
                            <Link
                                :href="route('monitor.nodes.show', selectedNode.id)"
                                class="text-sm text-indigo-600 hover:text-indigo-500"
                            >
                                View full details
                            </Link>
                        </div>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Host</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ selectedNode.host }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Local State</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ selectedNode.state_comment }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Cluster Size</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ selectedNode.cluster_size }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Flow Control</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ (selectedNode.flow_control_paused * 100).toFixed(2) }}%</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
