
<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

import Login from './components/Login.vue';
import Dashboard from './components/dashboard.vue';

const user = ref(null);
const checkingLogin = ref(true);

async function checkLogin() {
    try {
        const response = await axios.get('/api/user');
        user.value = response.data;
    } catch (error) {
        user.value = null;
    } finally {
        checkingLogin.value = false;
    }
}

function handleLoginSuccess(loggedInUser) {
    user.value = loggedInUser;
}

onMounted(checkLogin);
</script>

<template>
    <div v-if="checkingLogin" class="flex min-h-screen items-center justify-center">
        <p class="text-purple-800">Checking login...</p>
    </div>

    <Login
        v-else-if="!user"
        @login-success="handleLoginSuccess"
    />

    <div v-else>
        <Dashboard />
    </div>
</template>
