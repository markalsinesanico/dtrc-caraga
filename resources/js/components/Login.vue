
<script setup>
import { ref } from 'vue';
import axios from 'axios';

const emit = defineEmits(['login-success']);

const email = ref('');
const password = ref('');
const errorMessage = ref('');
const loading = ref(false);

async function login() {
    errorMessage.value = '';
    loading.value = true;

    try {
        await axios.get('/sanctum/csrf-cookie');

        const response = await axios.post('/login', {
            email: email.value,
            password: password.value,
        });

        emit('login-success', response.data.user);
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ||
            'Hindi makapag-login. Tingnan ang email, password, at connection.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-purple-50 p-4">
        <form
            @submit.prevent="login"
            class="w-full max-w-md space-y-5 rounded-2xl bg-white p-8 shadow-xl"
        >
            <div class="text-center">
                <h1 class="text-2xl font-bold text-purple-800">
                    Material Management Section
                </h1>

                <p class="mt-2 text-sm text-gray-500">
                    Sign in to your authorized account
                </p>
            </div>

            <div
                v-if="errorMessage"
                class="rounded-lg bg-red-50 p-3 text-sm text-red-700"
            >
                {{ errorMessage }}
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">
                    Email
                </label>

                <input
                    v-model="email"
                    type="email"
                    autocomplete="username"
                    required
                    class="w-full rounded-lg border p-3 outline-none focus:border-purple-600"
                    placeholder="Enter your email"
                />
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">
                    Password
                </label>

                <input
                    v-model="password"
                    type="password"
                    autocomplete="current-password"
                    required
                    class="w-full rounded-lg border p-3 outline-none focus:border-purple-600"
                    placeholder="Enter your password"
                />
            </div>

            <button
                type="submit"
                :disabled="loading"
                class="w-full rounded-lg bg-purple-700 p-3 font-semibold text-white hover:bg-purple-800 disabled:opacity-50"
            >
                {{ loading ? 'Signing in...' : 'Login' }}
            </button>
        </form>
    </div>
</template>
