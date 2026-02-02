<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    nodeTypes: Array,
});

const form = useForm({
    name: '',
    type: 'synology',
    host: '',
    port: '',
    is_active: true,
    credentials: {
        username: '',
        password: '',
        database: '',
        health_endpoint: '/health',
    },
});

function submit() {
    form.post(route('monitor.nodes.store'));
}

function getDefaultPort(type) {
    return {
        synology: 5000,
        docker: 2375,
        galera: 3306,
        laravel_app: 443,
    }[type] || '';
}
</script>

<template>
    <Head title="Add Node" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link
                    :href="route('monitor.nodes.index')"
                    class="mr-4 text-gray-400 hover:text-gray-600"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Add Node
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="overflow-hidden bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Name -->
                            <div>
                                <InputLabel for="name" value="Name" />
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    placeholder="e.g., NAS Primary"
                                />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>

                            <!-- Type -->
                            <div>
                                <InputLabel for="type" value="Type" />
                                <select
                                    id="type"
                                    v-model="form.type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @change="form.port = getDefaultPort(form.type)"
                                >
                                    <option
                                        v-for="type in nodeTypes"
                                        :key="type.value"
                                        :value="type.value"
                                    >
                                        {{ type.label }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.type" class="mt-2" />
                            </div>

                            <!-- Host -->
                            <div>
                                <InputLabel for="host" value="Host" />
                                <TextInput
                                    id="host"
                                    v-model="form.host"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    placeholder="e.g., 192.168.1.100 or nas.example.com"
                                />
                                <InputError :message="form.errors.host" class="mt-2" />
                            </div>

                            <!-- Port -->
                            <div>
                                <InputLabel for="port" value="Port (optional)" />
                                <TextInput
                                    id="port"
                                    v-model="form.port"
                                    type="number"
                                    class="mt-1 block w-full"
                                    :placeholder="`Default: ${getDefaultPort(form.type)}`"
                                />
                                <InputError :message="form.errors.port" class="mt-2" />
                            </div>

                            <!-- Credentials based on type -->
                            <template v-if="form.type === 'synology'">
                                <div class="border-t pt-6">
                                    <h3 class="text-sm font-medium text-gray-900">Synology Credentials</h3>
                                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <InputLabel for="username" value="Username" />
                                            <TextInput
                                                id="username"
                                                v-model="form.credentials.username"
                                                type="text"
                                                class="mt-1 block w-full"
                                            />
                                        </div>
                                        <div>
                                            <InputLabel for="password" value="Password" />
                                            <TextInput
                                                id="password"
                                                v-model="form.credentials.password"
                                                type="password"
                                                class="mt-1 block w-full"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template v-else-if="form.type === 'galera'">
                                <div class="border-t pt-6">
                                    <h3 class="text-sm font-medium text-gray-900">MySQL Credentials</h3>
                                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <InputLabel for="username" value="Username" />
                                            <TextInput
                                                id="username"
                                                v-model="form.credentials.username"
                                                type="text"
                                                class="mt-1 block w-full"
                                                placeholder="monitor"
                                            />
                                        </div>
                                        <div>
                                            <InputLabel for="password" value="Password" />
                                            <TextInput
                                                id="password"
                                                v-model="form.credentials.password"
                                                type="password"
                                                class="mt-1 block w-full"
                                            />
                                        </div>
                                        <div class="sm:col-span-2">
                                            <InputLabel for="database" value="Database" />
                                            <TextInput
                                                id="database"
                                                v-model="form.credentials.database"
                                                type="text"
                                                class="mt-1 block w-full"
                                                placeholder="mysql"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template v-else-if="form.type === 'laravel_app'">
                                <div class="border-t pt-6">
                                    <h3 class="text-sm font-medium text-gray-900">Health Check Configuration</h3>
                                    <div class="mt-4">
                                        <InputLabel for="health_endpoint" value="Health Endpoint" />
                                        <TextInput
                                            id="health_endpoint"
                                            v-model="form.credentials.health_endpoint"
                                            type="text"
                                            class="mt-1 block w-full"
                                            placeholder="/health"
                                        />
                                    </div>
                                </div>
                            </template>

                            <!-- Active -->
                            <div class="flex items-center">
                                <input
                                    id="is_active"
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                    Active (include in metric collection)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 text-right sm:px-6">
                        <Link
                            :href="route('monitor.nodes.index')"
                            class="mr-3 text-sm text-gray-600 hover:text-gray-900"
                        >
                            Cancel
                        </Link>
                        <PrimaryButton :disabled="form.processing">
                            Create Node
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
