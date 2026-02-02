<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
    size: {
        type: String,
        default: 'md',
    },
});

const statusConfig = {
    healthy: { bg: 'bg-green-100', text: 'text-green-800', dot: 'bg-green-500', label: 'Healthy' },
    warning: { bg: 'bg-yellow-100', text: 'text-yellow-800', dot: 'bg-yellow-500', label: 'Warning' },
    critical: { bg: 'bg-red-100', text: 'text-red-800', dot: 'bg-red-500', label: 'Critical' },
    unknown: { bg: 'bg-gray-100', text: 'text-gray-800', dot: 'bg-gray-500', label: 'Unknown' },
    inactive: { bg: 'bg-gray-100', text: 'text-gray-500', dot: 'bg-gray-400', label: 'Inactive' },
};

const config = computed(() => statusConfig[props.status] || statusConfig.unknown);

const sizeClasses = computed(() => {
    return {
        sm: 'px-2 py-0.5 text-xs',
        md: 'px-2.5 py-1 text-sm',
        lg: 'px-3 py-1.5 text-base',
    }[props.size];
});

const dotSize = computed(() => {
    return {
        sm: 'h-1.5 w-1.5',
        md: 'h-2 w-2',
        lg: 'h-2.5 w-2.5',
    }[props.size];
});
</script>

<template>
    <span
        :class="[config.bg, config.text, sizeClasses]"
        class="inline-flex items-center gap-1.5 rounded-full font-medium"
    >
        <span :class="[config.dot, dotSize]" class="rounded-full animate-pulse"></span>
        {{ config.label }}
    </span>
</template>
