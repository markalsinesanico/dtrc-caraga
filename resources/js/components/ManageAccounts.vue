<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const departments = [
    'Office of the TRC Chief',
    'Office of the Chief Health Program Officer (RITD)',
    'Office of the Chief Health Program Officer (OAD)',
    'Office of the Chief Administrative Officer',
    'Office of the Supervising Administrative Officer',
    'Legal Unit',
    'Information and Communication Technology Unit',
    'Residential Program Services',
    'Medical Section',
    'Nursing Section',
    'Dental Section',
    'Clinical Laboratory Section',
    'Psychology Section',
    'Medical Social Work Section',
    'Nutritionist and Dietetics Section',
    'Dormitory Management Section',
    'Livelihood Training Section',
    'Radiology Section',
    'Outpatient Section',
    'Aftercare Section',
    'Health Information Management Section',
    'Accounting Section',
    'Budget Section',
    'Billing and Claims Section',
    'Cash Operations Section',
    'General Services Section',
    'Materials Management Section',
    'Procurement Section',
    'Human Resource Development Section',
    'Quality Management System Section',
]

const accounts = ref([])
const loading = ref(false)
const saving = ref(false)
const message = ref('')
const errorMessage = ref('')

const form = ref({
    name: '',
    email: '',
    password: '',
    department_office: '',
})

async function loadAccounts() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await axios.get('/api/accounts')
        accounts.value = response.data
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message || 'Unable to load office accounts.'
    } finally {
        loading.value = false
    }
}

async function createAccount() {
    if (saving.value) return

    message.value = ''
    errorMessage.value = ''

    saving.value = true

    try {
        const response = await axios.post('/api/accounts', {
            name: form.value.name,
            email: form.value.email,
            password: form.value.password,
            department_office: form.value.department_office,
        })

        message.value = response.data.message || 'Office account created.'

        form.value = {
            name: '',
            email: '',
            password: '',
            department_office: '',
        }

        await loadAccounts()
    } catch (error) {
        const errors = error.response?.data?.errors

        errorMessage.value = errors
            ? Object.values(errors).flat().join(' ')
            : error.response?.data?.message || 'Failed to create account.'
    } finally {
        saving.value = false
    }
}

onMounted(loadAccounts)
</script>

<template>
    <div class="space-y-6">
        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 sm:p-6 border-b border-slate-100 bg-purple-50/50">
                <h2 class="text-lg font-bold text-purple-900">
                    <i class="fa-solid fa-user-plus mr-2"></i>
                    Create Office Account
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Create an account and assign it to one Department / Office.
                </p>
            </div>

            <form @submit.prevent="createAccount" class="p-5 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">
                        Full Name
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        maxlength="255"
                        placeholder="Enter account holder's name"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-purple-300"
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">
                        Email Address
                    </label>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        placeholder="office@example.com"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-purple-300"
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">
                        Password
                    </label>
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        minlength="8"
                        placeholder="At least 8 characters"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-purple-300"
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">
                        Assigned Department / Office
                    </label>
                    <select
                        v-model="form.department_office"
                        required
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-purple-300 bg-white"
                    >
                        <option value="" disabled>Select Department / Office</option>
                        <option v-for="department in departments" :key="department" :value="department">
                            {{ department }}
                        </option>
                    </select>
                </div>

                <div class="md:col-span-2 flex justify-end">
                    <button
                        type="submit"
                        :disabled="saving"
                        class="px-6 py-3 rounded-xl bg-purple-800 hover:bg-purple-900 text-white text-sm font-bold disabled:opacity-50"
                    >
                        <i class="fa-solid fa-user-plus mr-2"></i>
                        {{ saving ? 'Creating...' : 'Create Office Account' }}
                    </button>
                </div>
            </form>

            <div v-if="message" class="mx-5 mb-5 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
                {{ message }}
            </div>

            <div v-if="errorMessage" class="mx-5 mb-5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                {{ errorMessage }}
            </div>
        </section>

        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-800">Office Accounts</h2>
                    <p class="text-xs text-slate-500 mt-1">
                        List of registered office users.
                    </p>
                </div>

                <button
                    type="button"
                    @click="loadAccounts"
                    class="px-4 py-2 rounded-lg bg-purple-50 text-purple-800 border border-purple-200 text-xs font-bold"
                >
                    <i class="fa-solid fa-rotate mr-1"></i>
                    Refresh
                </button>
            </div>

            <div v-if="loading" class="p-8 text-center text-sm text-slate-500">
                Loading accounts...
            </div>

            <div v-else-if="accounts.length === 0" class="p-8 text-center text-sm text-slate-500">
                No office accounts created yet.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="p-4">Name</th>
                            <th class="p-4">Email</th>
                            <th class="p-4">Assigned Office</th>
                            <th class="p-4">Role</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="account in accounts" :key="account.id">
                            <td class="p-4 font-semibold text-slate-800">
                                {{ account.name }}
                            </td>
                            <td class="p-4 text-slate-600">
                                {{ account.email }}
                            </td>
                            <td class="p-4 text-slate-600">
                                {{ account.department_office }}
                            </td>
                            <td class="p-4">
                                <span class="px-3 py-1 rounded-full bg-purple-100 text-purple-800 text-xs font-bold">
                                    Office
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>