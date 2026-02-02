<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const hexColor = ref('#6366f1');

const hexToRgb = (hex) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : null;
};

const rgbToHsl = (r, g, b) => {
    r /= 255; g /= 255; b /= 255;
    const max = Math.max(r, g, b), min = Math.min(r, g, b);
    let h, s, l = (max + min) / 2;

    if (max === min) {
        h = s = 0;
    } else {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        switch (max) {
            case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
            case g: h = ((b - r) / d + 2) / 6; break;
            case b: h = ((r - g) / d + 4) / 6; break;
        }
    }
    return {
        h: Math.round(h * 360),
        s: Math.round(s * 100),
        l: Math.round(l * 100)
    };
};

const rgb = computed(() => hexToRgb(hexColor.value));
const hsl = computed(() => rgb.value ? rgbToHsl(rgb.value.r, rgb.value.g, rgb.value.b) : null);

const formats = computed(() => {
    if (!rgb.value || !hsl.value) return [];
    return [
        { label: 'HEX', value: hexColor.value.toUpperCase() },
        { label: 'RGB', value: `rgb(${rgb.value.r}, ${rgb.value.g}, ${rgb.value.b})` },
        { label: 'RGBA', value: `rgba(${rgb.value.r}, ${rgb.value.g}, ${rgb.value.b}, 1)` },
        { label: 'HSL', value: `hsl(${hsl.value.h}, ${hsl.value.s}%, ${hsl.value.l}%)` },
        { label: 'CSS Variable', value: `--color: ${hexColor.value};` },
    ];
});

const presetColors = [
    '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16',
    '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9',
    '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
    '#ec4899', '#f43f5e', '#78716c', '#737373', '#1f2937',
];

const copyToClipboard = async (text) => {
    await navigator.clipboard.writeText(text);
};
</script>

<template>
    <Head title="Color Picker" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link :href="route('dashboard')" class="mr-4 text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Color Picker
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex flex-col items-center mb-8">
                            <div
                                class="w-48 h-48 rounded-xl shadow-lg mb-4 border-4 border-white"
                                :style="{ backgroundColor: hexColor }"
                            ></div>
                            <input
                                type="color"
                                v-model="hexColor"
                                class="w-48 h-12 cursor-pointer rounded-lg"
                            />
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Preset Colors</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="color in presetColors"
                                    :key="color"
                                    @click="hexColor = color"
                                    class="w-8 h-8 rounded-lg shadow-sm border-2 transition-transform hover:scale-110"
                                    :class="hexColor === color ? 'border-gray-800' : 'border-transparent'"
                                    :style="{ backgroundColor: color }"
                                ></button>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="format in formats"
                                :key="format.label"
                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                            >
                                <div>
                                    <span class="text-sm font-medium text-gray-500">{{ format.label }}</span>
                                    <code class="ml-3 text-gray-900">{{ format.value }}</code>
                                </div>
                                <button
                                    @click="copyToClipboard(format.value)"
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
