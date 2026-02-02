<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const uuids = ref([]);
const count = ref(5);
const uppercase = ref(false);
const withHyphens = ref(true);

const generateUUID = () => {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
};

const formatUUID = (uuid) => {
    let result = withHyphens.value ? uuid : uuid.replace(/-/g, '');
    return uppercase.value ? result.toUpperCase() : result;
};

const generate = () => {
    uuids.value = Array.from({ length: count.value }, () => formatUUID(generateUUID()));
};

const copyToClipboard = async (text) => {
    await navigator.clipboard.writeText(text);
};

const copyAll = async () => {
    await navigator.clipboard.writeText(uuids.value.join('\n'));
};

// Generate initial UUIDs
generate();
</script>

<template>
    <Head title="UUID Generator" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link :href="route('dashboard')" class="mr-4 text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    UUID Generator
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex flex-wrap items-center gap-4 mb-6">
                            <div class="flex items-center gap-2">
                                <label class="text-sm font-medium text-gray-700">Count:</label>
                                <select v-model="count" class="rounded-md border-gray-300 text-sm">
                                    <option :value="1">1</option>
                                    <option :value="5">5</option>
                                    <option :value="10">10</option>
                                    <option :value="20">20</option>
                                </select>
                            </div>

                            <label class="flex items-center gap-2">
                                <input type="checkbox" v-model="uppercase" class="rounded border-gray-300 text-indigo-600" />
                                <span class="text-sm text-gray-700">Uppercase</span>
                            </label>

                            <label class="flex items-center gap-2">
                                <input type="checkbox" v-model="withHyphens" class="rounded border-gray-300 text-indigo-600" />
                                <span class="text-sm text-gray-700">With hyphens</span>
                            </label>

                            <button
                                @click="generate"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                            >
                                Generate
                            </button>
                        </div>

                        <div class="flex justify-end mb-3">
                            <button
                                @click="copyAll"
                                class="text-sm text-indigo-600 hover:text-indigo-800"
                            >
                                Copy All
                            </button>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="(uuid, index) in uuids"
                                :key="index"
                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg font-mono text-sm"
                            >
                                <span class="text-gray-900">{{ uuid }}</span>
                                <button
                                    @click="copyToClipboard(uuid)"
                                    class="px-3 py-1 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700 transition-colors"
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
