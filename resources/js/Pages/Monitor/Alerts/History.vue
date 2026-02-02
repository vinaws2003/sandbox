<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    logs: Object,
    filters: Object,
});

const conditionLabels = {
    gt: 'Greater than',
    gte: 'Greater than or equal',
    lt: 'Less than',
    lte: 'Less than or equal',
    eq: 'Equal to',
    neq: 'Not equal to',
};
</script>

<template>
    <Head title="Alert History" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link
                    :href="route('monitor.alerts.index')"
                    class="mr-4 text-gray-400 hover:text-gray-600"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Alert History
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Time
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Node
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Metric
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Value
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Threshold
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Message
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="log in logs.data" :key="log.id">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    <div>{{ log.created_at }}</div>
                                    <div class="text-xs text-gray-400">{{ log.created_at_human }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <Link
                                        :href="route('monitor.nodes.show', log.node_id)"
                                        class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                                    >
                                        {{ log.node_name }}
                                    </Link>
                                    <div class="text-xs text-gray-400">{{ log.node_type }}</div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ log.metric_type }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-red-600">
                                    {{ parseFloat(log.value).toFixed(2) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ conditionLabels[log.condition] }} {{ parseFloat(log.threshold).toFixed(2) }}
                                </td>
                                <td class="max-w-xs truncate px-6 py-4 text-sm text-gray-500" :title="log.message">
                                    {{ log.message }}
                                </td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    No alert history found.
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="logs.last_page > 1" class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                        <nav class="flex items-center justify-between">
                            <div class="hidden sm:block">
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{ logs.from }}</span>
                                    to
                                    <span class="font-medium">{{ logs.to }}</span>
                                    of
                                    <span class="font-medium">{{ logs.total }}</span>
                                    results
                                </p>
                            </div>
                            <div class="flex flex-1 justify-between gap-2 sm:justify-end">
                                <Link
                                    v-if="logs.prev_page_url"
                                    :href="logs.prev_page_url"
                                    class="relative inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                                >
                                    Previous
                                </Link>
                                <Link
                                    v-if="logs.next_page_url"
                                    :href="logs.next_page_url"
                                    class="relative inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                                >
                                    Next
                                </Link>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
