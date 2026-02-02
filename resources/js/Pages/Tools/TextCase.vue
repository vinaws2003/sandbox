<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const input = ref('');

const uppercase = computed(() => input.value.toUpperCase());
const lowercase = computed(() => input.value.toLowerCase());
const titleCase = computed(() => {
    return input.value.replace(/\w\S*/g, (txt) => {
        return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
    });
});
const sentenceCase = computed(() => {
    return input.value.toLowerCase().replace(/(^\s*\w|[.!?]\s*\w)/g, (c) => c.toUpperCase());
});
const camelCase = computed(() => {
    return input.value
        .toLowerCase()
        .replace(/[^a-zA-Z0-9]+(.)/g, (_, chr) => chr.toUpperCase());
});
const snakeCase = computed(() => {
    return input.value
        .replace(/\s+/g, '_')
        .replace(/([a-z])([A-Z])/g, '$1_$2')
        .toLowerCase();
});
const kebabCase = computed(() => {
    return input.value
        .replace(/\s+/g, '-')
        .replace(/([a-z])([A-Z])/g, '$1-$2')
        .toLowerCase();
});

const copyToClipboard = async (text) => {
    await navigator.clipboard.writeText(text);
};

const conversions = computed(() => [
    { label: 'UPPERCASE', value: uppercase.value },
    { label: 'lowercase', value: lowercase.value },
    { label: 'Title Case', value: titleCase.value },
    { label: 'Sentence case', value: sentenceCase.value },
    { label: 'camelCase', value: camelCase.value },
    { label: 'snake_case', value: snakeCase.value },
    { label: 'kebab-case', value: kebabCase.value },
]);
</script>

<template>
    <Head title="Text Case Converter" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link :href="route('dashboard')" class="mr-4 text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Text Case Converter
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Input Text</label>
                            <textarea
                                v-model="input"
                                rows="4"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Enter your text here..."
                            ></textarea>
                        </div>

                        <div class="space-y-4">
                            <div
                                v-for="conv in conversions"
                                :key="conv.label"
                                class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg"
                            >
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ conv.label }}</label>
                                    <div class="text-gray-900 break-all">{{ conv.value || '-' }}</div>
                                </div>
                                <button
                                    @click="copyToClipboard(conv.value)"
                                    class="px-3 py-1 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700 transition-colors"
                                    :disabled="!conv.value"
                                >
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
