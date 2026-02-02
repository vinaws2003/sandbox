<script setup>
import { computed } from 'vue';

const props = defineProps({
    count: {
        type: Number,
        default: 0,
    },
    severity: {
        type: String,
        default: 'info',
        validator: (v) => ['info', 'warning', 'critical'].includes(v),
    },
});

const colorClasses = computed(() => {
    return {
        info: 'bg-blue-100 text-blue-800',
        warning: 'bg-yellow-100 text-yellow-800',
        critical: 'bg-red-100 text-red-800',
    }[props.severity];
});
</script>

<template>
    <span
        v-if="count > 0"
        :class="colorClasses"
        class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
    >
        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
            <path
                fill-rule="evenodd"
                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                clip-rule="evenodd"
            />
        </svg>
        {{ count }}
    </span>
</template>
