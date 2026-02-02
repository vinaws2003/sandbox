<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const mode = ref('encode');
const input = ref('');
const error = ref('');

const output = computed(() => {
    if (!input.value) {
        error.value = '';
        return '';
    }

    try {
        if (mode.value === 'encode') {
            error.value = '';
            return btoa(unescape(encodeURIComponent(input.value)));
        } else {
            error.value = '';
            return decodeURIComponent(escape(atob(input.value)));
        }
    } catch (e) {
        error.value = mode.value === 'decode' ? 'Invalid Base64 string' : 'Encoding error';
        return '';
    }
});

const copyToClipboard = async () => {
    if (output.value) {
        await navigator.clipboard.writeText(output.value);
    }
};

const swapInputOutput = () => {
    if (output.value && !error.value) {
        input.value = output.value;
        mode.value = mode.value === 'encode' ? 'decode' : 'encode';
    }
};

const clear = () => {
    input.value = '';
    error.value = '';
};
</script>

<template>
    <Head title="Base64 Encoder/Decoder" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link :href="route('dashboard')" class="mr-4 text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Base64 Encoder/Decoder
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-center gap-4 mb-6">
                            <button
                                @click="mode = 'encode'"
                                :class="[
                                    'px-6 py-2 rounded-lg font-medium transition-colors',
                                    mode === 'encode'
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                ]"
                            >
                                Encode
                            </button>
                            <button
                                @click="mode = 'decode'"
                                :class="[
                                    'px-6 py-2 rounded-lg font-medium transition-colors',
                                    mode === 'decode'
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                ]"
                            >
                                Decode
                            </button>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-sm font-medium text-gray-700">
                                        {{ mode === 'encode' ? 'Plain Text' : 'Base64 String' }}
                                    </label>
                                    <button @click="clear" class="text-sm text-gray-500 hover:text-gray-700">
                                        Clear
                                    </button>
                                </div>
                                <textarea
                                    v-model="input"
                                    rows="12"
                                    class="w-full font-mono text-sm rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    :placeholder="mode === 'encode' ? 'Enter text to encode...' : 'Enter Base64 to decode...'"
                                ></textarea>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-sm font-medium text-gray-700">
                                        {{ mode === 'encode' ? 'Base64 Output' : 'Decoded Text' }}
                                    </label>
                                    <div class="flex gap-3">
                                        <button
                                            @click="swapInputOutput"
                                            class="text-sm text-indigo-600 hover:text-indigo-800"
                                            :disabled="!output || error"
                                        >
                                            Swap
                                        </button>
                                        <button
                                            @click="copyToClipboard"
                                            class="text-sm text-indigo-600 hover:text-indigo-800"
                                            :disabled="!output"
                                        >
                                            Copy
                                        </button>
                                    </div>
                                </div>
                                <div class="w-full h-[288px] p-4 font-mono text-sm bg-gray-800 text-green-400 rounded-lg overflow-auto">
                                    <template v-if="error">
                                        <span class="text-red-400">{{ error }}</span>
                                    </template>
                                    <template v-else>
                                        {{ output || 'Output will appear here...' }}
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
