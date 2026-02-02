<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    alerts: Array,
});

function deleteAlert(alert) {
    if (confirm('Are you sure you want to delete this alert rule?')) {
        router.delete(route('monitor.alerts.destroy', alert.id));
    }
}

function toggleAlert(alert) {
    router.put(route('monitor.alerts.update', alert.id), {
        ...alert,
        is_active: !alert.is_active,
    }, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Alert Rules" />

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
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Alert Rules
                    </h2>
                </div>
                <div class="flex gap-3">
                    <Link
                        :href="route('monitor.alerts.history')"
                        class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50"
                    >
                        View History
                    </Link>
                    <Link
                        :href="route('monitor.alerts.create')"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        Add Alert
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Active
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Node
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Metric
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Condition
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Channel
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    Last Triggered
                                </th>
                                <th class="relative px-6 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="alert in alerts" :key="alert.id">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <button
                                        type="button"
                                        :class="[
                                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
                                            alert.is_active ? 'bg-indigo-600' : 'bg-gray-200',
                                        ]"
                                        @click="toggleAlert(alert)"
                                    >
                                        <span
                                            :class="[
                                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                                alert.is_active ? 'translate-x-5' : 'translate-x-0',
                                            ]"
                                        />
                                    </button>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ alert.node_name }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ alert.metric_type }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ alert.condition_label }} {{ alert.threshold }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    <span class="inline-flex items-center gap-1">
                                        {{ alert.channel_label }}
                                        <span v-if="alert.notification_target" class="text-xs text-gray-400">
                                            ({{ alert.notification_target }})
                                        </span>
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ alert.last_triggered_at || 'Never' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                    <Link
                                        :href="route('monitor.alerts.edit', alert.id)"
                                        class="text-indigo-600 hover:text-indigo-900"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        type="button"
                                        class="ml-4 text-red-600 hover:text-red-900"
                                        @click="deleteAlert(alert)"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="alerts.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    No alert rules configured yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
