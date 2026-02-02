<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    nodeId: {
        type: Number,
        required: true,
    },
});

const loading = ref(false);
const status = ref(null); // 'success' | 'error' | null

function testConnection() {
    loading.value = true;
    status.value = null;

    router.post(
        route('monitor.nodes.test', props.nodeId),
        {},
        {
            preserveScroll: true,
            onSuccess: (page) => {
                if (page.props.flash?.success) {
                    status.value = 'success';
                } else if (page.props.flash?.error) {
                    status.value = 'error';
                }
                loading.value = false;

                setTimeout(() => {
                    status.value = null;
                }, 3000);
            },
            onError: () => {
                status.value = 'error';
                loading.value = false;
            },
        }
    );
}
</script>

<template>
    <button
        type="button"
        :disabled="loading"
        :class="[
            'inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition',
            status === 'success'
                ? 'bg-green-100 text-green-700'
                : status === 'error'
                ? 'bg-red-100 text-red-700'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
            loading ? 'cursor-not-allowed opacity-75' : '',
        ]"
        @click="testConnection"
    >
        <svg
            v-if="loading"
            class="h-4 w-4 animate-spin"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            />
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            />
        </svg>
        <svg
            v-else-if="status === 'success'"
            class="h-4 w-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
            />
        </svg>
        <svg
            v-else-if="status === 'error'"
            class="h-4 w-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
            />
        </svg>
        <svg
            v-else
            class="h-4 w-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"
            />
        </svg>
        {{ loading ? 'Testing...' : status === 'success' ? 'Connected!' : status === 'error' ? 'Failed' : 'Test Connection' }}
    </button>
</template>
