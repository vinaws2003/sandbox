<script setup>
import { computed } from 'vue';
import StatusBadge from './StatusBadge.vue';

const props = defineProps({
    node: {
        type: Object,
        required: true,
    },
    isSelected: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['select']);

const stateColors = {
    healthy: 'border-green-500 bg-green-50',
    warning: 'border-yellow-500 bg-yellow-50',
    critical: 'border-red-500 bg-red-50',
};

const borderClass = computed(() => {
    return stateColors[props.node.status] || 'border-gray-300 bg-gray-50';
});
</script>

<template>
    <div
        :class="[
            borderClass,
            isSelected ? 'ring-2 ring-indigo-500' : '',
        ]"
        class="cursor-pointer rounded-lg border-2 p-4 transition-all hover:shadow-md"
        @click="emit('select', node)"
    >
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-2xl">🗄️</span>
                <div>
                    <h4 class="font-medium text-gray-900">{{ node.name }}</h4>
                    <p class="text-xs text-gray-500">{{ node.host }}</p>
                </div>
            </div>
            <StatusBadge :status="node.status" size="sm" />
        </div>

        <div class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">State</span>
                <span class="font-medium" :class="{
                    'text-green-600': node.state_comment === 'Synced',
                    'text-yellow-600': ['Joined', 'Donor/Desynced', 'Joining'].includes(node.state_comment),
                    'text-red-600': !['Synced', 'Joined', 'Donor/Desynced', 'Joining'].includes(node.state_comment),
                }">
                    {{ node.state_comment }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Cluster Size</span>
                <span class="font-medium">{{ node.cluster_size }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Ready</span>
                <span :class="node.ready ? 'text-green-600' : 'text-red-600'">
                    {{ node.ready ? 'Yes' : 'No' }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Connected</span>
                <span :class="node.connected ? 'text-green-600' : 'text-red-600'">
                    {{ node.connected ? 'Yes' : 'No' }}
                </span>
            </div>
            <div v-if="node.flow_control_paused > 0" class="flex justify-between">
                <span class="text-gray-500">Flow Control</span>
                <span class="text-yellow-600">{{ (node.flow_control_paused * 100).toFixed(2) }}%</span>
            </div>
        </div>

        <div class="mt-3 text-xs text-gray-400">
            Last updated: {{ node.last_updated }}
        </div>
    </div>
</template>
