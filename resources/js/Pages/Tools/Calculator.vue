<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const display = ref('0');
const previousValue = ref(null);
const operator = ref(null);
const waitingForOperand = ref(false);

const inputDigit = (digit) => {
    if (waitingForOperand.value) {
        display.value = digit;
        waitingForOperand.value = false;
    } else {
        display.value = display.value === '0' ? digit : display.value + digit;
    }
};

const inputDecimal = () => {
    if (waitingForOperand.value) {
        display.value = '0.';
        waitingForOperand.value = false;
        return;
    }
    if (!display.value.includes('.')) {
        display.value += '.';
    }
};

const clear = () => {
    display.value = '0';
    previousValue.value = null;
    operator.value = null;
    waitingForOperand.value = false;
};

const performOperation = (nextOperator) => {
    const inputValue = parseFloat(display.value);

    if (previousValue.value === null) {
        previousValue.value = inputValue;
    } else if (operator.value) {
        const currentValue = previousValue.value;
        let result;

        switch (operator.value) {
            case '+':
                result = currentValue + inputValue;
                break;
            case '-':
                result = currentValue - inputValue;
                break;
            case '*':
                result = currentValue * inputValue;
                break;
            case '/':
                result = inputValue !== 0 ? currentValue / inputValue : 'Error';
                break;
            default:
                result = inputValue;
        }

        display.value = String(result);
        previousValue.value = result;
    }

    waitingForOperand.value = true;
    operator.value = nextOperator;
};

const calculate = () => {
    if (operator.value && previousValue.value !== null) {
        performOperation(null);
        operator.value = null;
        previousValue.value = null;
        waitingForOperand.value = true;
    }
};

const toggleSign = () => {
    const value = parseFloat(display.value);
    display.value = String(value * -1);
};

const percentage = () => {
    const value = parseFloat(display.value);
    display.value = String(value / 100);
};

const buttons = [
    { label: 'C', action: clear, class: 'bg-gray-300 hover:bg-gray-400' },
    { label: '+/-', action: toggleSign, class: 'bg-gray-300 hover:bg-gray-400' },
    { label: '%', action: percentage, class: 'bg-gray-300 hover:bg-gray-400' },
    { label: '/', action: () => performOperation('/'), class: 'bg-orange-400 hover:bg-orange-500 text-white' },
    { label: '7', action: () => inputDigit('7'), class: 'bg-gray-100 hover:bg-gray-200' },
    { label: '8', action: () => inputDigit('8'), class: 'bg-gray-100 hover:bg-gray-200' },
    { label: '9', action: () => inputDigit('9'), class: 'bg-gray-100 hover:bg-gray-200' },
    { label: '*', action: () => performOperation('*'), class: 'bg-orange-400 hover:bg-orange-500 text-white' },
    { label: '4', action: () => inputDigit('4'), class: 'bg-gray-100 hover:bg-gray-200' },
    { label: '5', action: () => inputDigit('5'), class: 'bg-gray-100 hover:bg-gray-200' },
    { label: '6', action: () => inputDigit('6'), class: 'bg-gray-100 hover:bg-gray-200' },
    { label: '-', action: () => performOperation('-'), class: 'bg-orange-400 hover:bg-orange-500 text-white' },
    { label: '1', action: () => inputDigit('1'), class: 'bg-gray-100 hover:bg-gray-200' },
    { label: '2', action: () => inputDigit('2'), class: 'bg-gray-100 hover:bg-gray-200' },
    { label: '3', action: () => inputDigit('3'), class: 'bg-gray-100 hover:bg-gray-200' },
    { label: '+', action: () => performOperation('+'), class: 'bg-orange-400 hover:bg-orange-500 text-white' },
    { label: '0', action: () => inputDigit('0'), class: 'bg-gray-100 hover:bg-gray-200 col-span-2' },
    { label: '.', action: inputDecimal, class: 'bg-gray-100 hover:bg-gray-200' },
    { label: '=', action: calculate, class: 'bg-orange-400 hover:bg-orange-500 text-white' },
];
</script>

<template>
    <Head title="Calculator" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center">
                <Link
                    :href="route('dashboard')"
                    class="mr-4 text-gray-500 hover:text-gray-700"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Calculator
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-sm sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-4 p-4 bg-gray-800 rounded-lg text-right">
                            <span class="text-3xl font-light text-white">{{ display }}</span>
                        </div>

                        <div class="grid grid-cols-4 gap-2">
                            <button
                                v-for="(btn, index) in buttons"
                                :key="index"
                                @click="btn.action"
                                :class="[
                                    'p-4 text-xl font-medium rounded-lg transition-colors duration-150',
                                    btn.class,
                                ]"
                            >
                                {{ btn.label }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
