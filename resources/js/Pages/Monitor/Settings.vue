<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    settings: Object,
});
</script>

<template>
    <Head title="Monitor Settings" />

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
                    Monitor Settings
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                    <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                        <h3 class="text-lg font-medium text-gray-900">Configuration</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            These settings are managed via environment variables in your .env file.
                        </p>
                    </div>

                    <div class="px-4 py-5 sm:p-6">
                        <dl class="divide-y divide-gray-200">
                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Polling Interval</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    {{ settings.polling_interval }} seconds
                                    <span class="ml-2 text-xs text-gray-500">(MONITOR_POLLING_INTERVAL)</span>
                                </dd>
                            </div>

                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Retention Days</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    {{ settings.retention_days }} days
                                    <span class="ml-2 text-xs text-gray-500">(MONITOR_RETENTION_DAYS)</span>
                                </dd>
                            </div>

                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Default Alert Cooldown</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    {{ settings.alert_cooldown_default }} minutes
                                    <span class="ml-2 text-xs text-gray-500">(ALERT_COOLDOWN_DEFAULT)</span>
                                </dd>
                            </div>

                            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Prometheus Endpoint</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    <span
                                        :class="settings.prometheus_enabled ? 'text-green-600' : 'text-red-600'"
                                    >
                                        {{ settings.prometheus_enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                    <span class="ml-2 text-xs text-gray-500">(PROMETHEUS_ENABLED)</span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                        <h4 class="text-sm font-medium text-gray-900">Collector Timeouts</h4>
                        <dl class="mt-4 divide-y divide-gray-200">
                            <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm text-gray-500">Synology</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    {{ settings.synology_timeout }}s
                                </dd>
                            </div>
                            <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm text-gray-500">Docker</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    {{ settings.docker_timeout }}s
                                </dd>
                            </div>
                            <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm text-gray-500">Galera</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    {{ settings.galera_timeout }}s
                                </dd>
                            </div>
                            <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm text-gray-500">Laravel App</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    {{ settings.laravel_app_timeout }}s
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Environment Variables Reference -->
                <div class="mt-8 overflow-hidden bg-white shadow sm:rounded-lg">
                    <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                        <h3 class="text-lg font-medium text-gray-900">Environment Variables</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Add these to your .env file to configure the monitor.
                        </p>
                    </div>
                    <div class="px-4 py-5 sm:p-6">
                        <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-sm text-gray-100"><code># Monitor Configuration
MONITOR_POLLING_INTERVAL=60
MONITOR_RETENTION_DAYS=7
SYNOLOGY_API_VERSION=7
SYNOLOGY_TIMEOUT=30
DOCKER_TIMEOUT=10
GALERA_TIMEOUT=5
LARAVEL_APP_TIMEOUT=10
PROMETHEUS_ENABLED=true
PROMETHEUS_TOKEN=your-optional-token
ALERT_COOLDOWN_DEFAULT=15</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
