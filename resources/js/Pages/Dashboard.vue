<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const tools = [
    {
        name: 'Calculator',
        description: 'Basic arithmetic operations',
        route: 'tools.calculator',
        icon: '🧮',
    },
    {
        name: 'Text Case',
        description: 'Convert text between cases',
        route: 'tools.text-case',
        icon: '🔤',
    },
    {
        name: 'JSON Formatter',
        description: 'Format and validate JSON',
        route: 'tools.json-formatter',
        icon: '📋',
    },
    {
        name: 'Color Picker',
        description: 'Pick colors and convert formats',
        route: 'tools.color-picker',
        icon: '🎨',
    },
    {
        name: 'UUID Generator',
        description: 'Generate random UUIDs',
        route: 'tools.uuid-generator',
        icon: '🔑',
    },
    {
        name: 'Base64',
        description: 'Encode and decode Base64',
        route: 'tools.base64',
        icon: '🔐',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Tool Hub
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Available Tools</h3>
                    <p class="mt-1 text-sm text-gray-600">Select a tool to get started</p>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="tool in tools"
                        :key="tool.name"
                        :href="route(tool.route)"
                        class="group block overflow-hidden bg-white shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200"
                    >
                        <div class="p-6">
                            <div class="flex items-center">
                                <span class="text-3xl">{{ tool.icon }}</span>
                                <div class="ml-4">
                                    <h4 class="text-lg font-semibold text-gray-900 group-hover:text-indigo-600">
                                        {{ tool.name }}
                                    </h4>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ tool.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
