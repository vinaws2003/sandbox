<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    alert: Object,
    nodes: Array,
    conditions: Object,
    channels: Object,
    metricTypes: Object,
});

const form = useForm({
    node_id: props.alert.node_id || '',
    metric_type: props.alert.metric_type,
    condition: props.alert.condition,
    threshold: props.alert.threshold,
    notification_channel: props.alert.notification_channel,
    notification_target: props.alert.notification_target || '',
    is_active: props.alert.is_active,
    cooldown_minutes: props.alert.cooldown_minutes,
});

function submit() {
    form.put(route('monitor.alerts.update', props.alert.id));
}
</script>

<template>
    <Head title="Edit Alert Rule" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link
                    :href="route('monitor.alerts.index')"
                    class="mr-4 text-gray-400 hover:text-gray-600"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Edit Alert Rule
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="overflow-hidden bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Node Selection -->
                            <div>
                                <InputLabel for="node_id" value="Node" />
                                <select
                                    id="node_id"
                                    v-model="form.node_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All Nodes (Global Alert)</option>
                                    <option
                                        v-for="node in nodes"
                                        :key="node.id"
                                        :value="node.id"
                                    >
                                        {{ node.name }} ({{ node.type }})
                                    </option>
                                </select>
                                <InputError :message="form.errors.node_id" class="mt-2" />
                            </div>

                            <!-- Metric Type -->
                            <div>
                                <InputLabel for="metric_type" value="Metric Type" />
                                <select
                                    id="metric_type"
                                    v-model="form.metric_type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <optgroup
                                        v-for="(types, group) in metricTypes"
                                        :key="group"
                                        :label="group"
                                    >
                                        <option
                                            v-for="(label, value) in types"
                                            :key="value"
                                            :value="value"
                                        >
                                            {{ label }}
                                        </option>
                                    </optgroup>
                                </select>
                                <InputError :message="form.errors.metric_type" class="mt-2" />
                            </div>

                            <!-- Condition & Threshold -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="condition" value="Condition" />
                                    <select
                                        id="condition"
                                        v-model="form.condition"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option
                                            v-for="(label, value) in conditions"
                                            :key="value"
                                            :value="value"
                                        >
                                            {{ label }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.condition" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="threshold" value="Threshold" />
                                    <TextInput
                                        id="threshold"
                                        v-model="form.threshold"
                                        type="number"
                                        step="0.01"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError :message="form.errors.threshold" class="mt-2" />
                                </div>
                            </div>

                            <!-- Notification -->
                            <div class="border-t pt-6">
                                <h3 class="text-sm font-medium text-gray-900">Notification</h3>
                                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel for="notification_channel" value="Channel" />
                                        <select
                                            id="notification_channel"
                                            v-model="form.notification_channel"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option
                                                v-for="(label, value) in channels"
                                                :key="value"
                                                :value="value"
                                            >
                                                {{ label }}
                                            </option>
                                        </select>
                                        <InputError :message="form.errors.notification_channel" class="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel for="notification_target" value="Target (email, channel)" />
                                        <TextInput
                                            id="notification_target"
                                            v-model="form.notification_target"
                                            type="text"
                                            class="mt-1 block w-full"
                                        />
                                        <InputError :message="form.errors.notification_target" class="mt-2" />
                                    </div>
                                </div>
                            </div>

                            <!-- Cooldown -->
                            <div>
                                <InputLabel for="cooldown_minutes" value="Cooldown (minutes)" />
                                <TextInput
                                    id="cooldown_minutes"
                                    v-model="form.cooldown_minutes"
                                    type="number"
                                    min="1"
                                    max="1440"
                                    class="mt-1 block w-full"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Minimum time between repeated alerts for the same condition.
                                </p>
                                <InputError :message="form.errors.cooldown_minutes" class="mt-2" />
                            </div>

                            <!-- Active -->
                            <div class="flex items-center">
                                <input
                                    id="is_active"
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                    Active (enable alert checking)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 text-right sm:px-6">
                        <Link
                            :href="route('monitor.alerts.index')"
                            class="mr-3 text-sm text-gray-600 hover:text-gray-900"
                        >
                            Cancel
                        </Link>
                        <PrimaryButton :disabled="form.processing">
                            Update Alert
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
