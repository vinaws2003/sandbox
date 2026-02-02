<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const input = ref('');
const indentSize = ref(2);
const error = ref('');

const formatted = computed(() => {
    if (!input.value.trim()) {
        error.value = '';
        return '';
    }
    try {
        const parsed = JSON.parse(input.value);
        error.value = '';
        return JSON.stringify(parsed, null, indentSize.value);
    } catch (e) {
        error.value = e.message;
        return '';
    }
});

const minified = computed(() => {
    if (!input.value.trim()) return '';
    try {
        return JSON.stringify(JSON.parse(input.value));
    } catch {
        return '';
    }
});

const copyToClipboard = async (text) => {
    await navigator.clipboard.writeText(text);
};

const loadSample = () => {
    input.value = JSON.stringify({
        name: "John Doe",
        age: 30,
        email: "john@example.com",
        address: {
            street: "123 Main St",
            city: "New York",
            country: "USA"
        },
        hobbies: ["reading", "coding", "gaming"]
    });
};
</script>

<template>
    <Head title="JSON Formatter" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link :href="route('dashboard')" class="mr-4 text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    JSON Formatter
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-4">
                                <label class="text-sm font-medium text-gray-700">Indent:</label>
                                <select v-model="indentSize" class="rounded-md border-gray-300 text-sm">
                                    <option :value="2">2 spaces</option>
                                    <option :value="4">4 spaces</option>
                                    <option :value="1">1 tab</option>
                                </select>
                            </div>
                            <button
                                @click="loadSample"
                                class="text-sm text-indigo-600 hover:text-indigo-800"
                            >
                                Load Sample
                            </button>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Input JSON</label>
                                <textarea
                                    v-model="input"
                                    rows="16"
                                    class="w-full font-mono text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Paste your JSON here..."
                                ></textarea>
                                <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-sm font-medium text-gray-700">Formatted Output</label>
                                    <button
                                        @click="copyToClipboard(formatted)"
                                        class="text-sm text-indigo-600 hover:text-indigo-800"
                                        :disabled="!formatted"
                                    >
                                        Copy
                                    </button>
                                </div>
                                <pre class="w-full h-[384px] p-4 font-mono text-sm bg-gray-800 text-green-400 rounded-lg overflow-auto">{{ formatted || 'Output will appear here...' }}</pre>
                            </div>
                        </div>

                        <div v-if="minified" class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-700">Minified</label>
                                <button
                                    @click="copyToClipboard(minified)"
                                    class="text-sm text-indigo-600 hover:text-indigo-800"
                                >
                                    Copy
                                </button>
                            </div>
                            <code class="text-sm text-gray-600 break-all">{{ minified }}</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
