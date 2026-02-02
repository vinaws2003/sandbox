<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    icon: {
        type: String,
        default: null,
    },
    trend: {
        type: String,
        default: null,
        validator: (v) => ['up', 'down', null].includes(v),
    },
    trendValue: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">{{ title }}</dt>
        <dd class="mt-1 flex items-baseline justify-between">
            <div class="flex items-baseline text-2xl font-semibold text-gray-900">
                {{ value }}
                <span v-if="trendValue" class="ml-2 text-sm font-medium" :class="{
                    'text-green-600': trend === 'up',
                    'text-red-600': trend === 'down',
                    'text-gray-500': !trend,
                }">
                    <span v-if="trend === 'up'" class="mr-0.5">↑</span>
                    <span v-else-if="trend === 'down'" class="mr-0.5">↓</span>
                    {{ trendValue }}
                </span>
            </div>
            <div v-if="icon" class="rounded-full bg-gray-100 p-3">
                <span class="text-xl">{{ icon }}</span>
            </div>
        </dd>
    </div>
</template>
