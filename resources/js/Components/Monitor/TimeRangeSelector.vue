<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '1h',
    },
});

const emit = defineEmits(['update:modelValue']);

const options = [
    { value: '1h', label: '1 Hour' },
    { value: '6h', label: '6 Hours' },
    { value: '24h', label: '24 Hours' },
    { value: '7d', label: '7 Days' },
];

const selected = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});
</script>

<template>
    <div class="inline-flex rounded-md shadow-sm">
        <button
            v-for="(option, index) in options"
            :key="option.value"
            type="button"
            :class="[
                'relative inline-flex items-center px-3 py-2 text-sm font-medium',
                selected === option.value
                    ? 'bg-indigo-600 text-white'
                    : 'bg-white text-gray-700 hover:bg-gray-50',
                index === 0 ? 'rounded-l-md' : '',
                index === options.length - 1 ? 'rounded-r-md' : '',
                index !== 0 ? '-ml-px' : '',
                'border border-gray-300 focus:z-10 focus:outline-none focus:ring-1 focus:ring-indigo-500',
            ]"
            @click="selected = option.value"
        >
            {{ option.label }}
        </button>
    </div>
</template>
