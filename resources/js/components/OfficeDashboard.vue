<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
})

const inventory = ref([])
const requests = ref([])
const loading = ref(false)
const submitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const activePage = ref('inventory')

const form = ref({
    requestor_name: props.user.name || '',
    reference_no_source: '',
    inventory_id: '',
    quantity: 1,
    request_date: new Date().toLocaleDateString('en-CA'),
})

const availableItems = computed(() =>
    inventory.value.filter(item => Number(item.quantity) > 0)
)

const selectedItem = computed(() =>
    inventory.value.find(
        item => String(item.id) === String(form.value.inventory_id)
    )
)

async function loadInventory() {
    loading.value = true
    errorMessage.value = ''

    try {
        const response = await axios.get('/api/inventory')
        inventory.value = response.data || []
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message || 'Unable to load inventory.'
    } finally {
        loading.value = false
    }
}

async function loadRequests() {
    try {
        const response = await axios.get('/api/inventory/request-history')
        requests.value = response.data || []
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message || 'Unable to load request history.'
    }
}

async function submitRequest() {
    if (submitting.value) return

    errorMessage.value = ''
    successMessage.value = ''

    if (!form.value.inventory_id) {
        errorMessage.value = 'Please select an inventory item.'
        return
    }

    if (!Number.isInteger(Number(form.value.quantity)) || Number(form.value.quantity) < 1) {
        errorMessage.value = 'Please enter a valid quantity.'
        return
    }

    if (!selectedItem.value) {
        errorMessage.value = 'Selected item was not found.'
        return
    }

    if (Number(form.value.quantity) > Number(selectedItem.value.quantity)) {
        errorMessage.value = `Only ${selectedItem.value.quantity} ${selectedItem.value.unit} available.`
        return
    }

    submitting.value = true

    try {
        const response = await axios.post('/api/inventory/requests', {
            requestor_name: form.value.requestor_name,
            reference_no_source: form.value.reference_no_source || null,
            inventory_id: Number(form.value.inventory_id),
            quantity: Number(form.value.quantity),
            request_date: form.value.request_date,
        })

        successMessage.value =
            response.data.message || 'Request submitted successfully.'

        // Refresh stock and own-office request history after submission.
        await loadInventory()
        await loadRequests()

        form.value.reference_no_source = ''
        form.value.inventory_id = ''
        form.value.quantity = 1
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message ||
            Object.values(error.response?.data?.errors || {}).flat().join(' ') ||
            'Unable to submit request.'
    } finally {
        submitting.value = false
    }
}

async function logout() {
    try {
        await axios.get('/sanctum/csrf-cookie', {
            headers: { Accept: 'application/json' },
            withCredentials: true,
            withXSRFToken: true,
        })

        await axios.post('/logout', {}, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            withCredentials: true,
            withXSRFToken: true,
        })

        window.location.href = '/login'
    } catch (error) {
        alert(error.response?.data?.message || 'Logout failed. Please try again.')
    }
}

onMounted(async () => {
    await loadInventory()
    await loadRequests()
})
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-800">
        <header class="bg-purple-900 text-white shadow-xl">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 py-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl font-black">DTRC-Caraga Inventory System</h1>
                    <p class="text-xs text-purple-200 mt-1">Office Dashboard</p>
                </div>

                <button
                    type="button"
                    @click="logout"
                    class="px-4 py-2 rounded-xl bg-rose-700 hover:bg-rose-800 text-white text-sm font-bold"
                >
                    <i class="fa-solid fa-right-from-bracket mr-2"></i>
                    Logout
                </button>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
            <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6">
                <p class="text-xs font-bold text-purple-700 uppercase tracking-wide">
                    Logged-in Office Account
                </p>
                <h2 class="text-xl font-black text-slate-800 mt-1">
                    {{ user.name }}
                </h2>
                <p class="text-sm text-slate-500 mt-1">{{ user.email }}</p>
                <div class="mt-3 inline-flex px-3 py-1.5 rounded-full bg-purple-100 text-purple-800 text-xs font-bold">
                    {{ user.department_office }}
                </div>
            </section>

            <nav class="flex flex-wrap gap-2">
                <button
                    @click="activePage = 'inventory'"
                    class="px-4 py-2 rounded-xl text-sm font-bold"
                    :class="activePage === 'inventory' ? 'bg-purple-900 text-white' : 'bg-white border border-slate-200 text-slate-600'"
                >
                    <i class="fa-solid fa-boxes-stacked mr-2"></i>
                    Available Inventory
                </button>

                <button
                    @click="activePage = 'request'"
                    class="px-4 py-2 rounded-xl text-sm font-bold"
                    :class="activePage === 'request' ? 'bg-purple-900 text-white' : 'bg-white border border-slate-200 text-slate-600'"
                >
                    <i class="fa-solid fa-file-circle-plus mr-2"></i>
                    Request Item
                </button>

                <button
                    @click="activePage = 'history'; loadRequests()"
                    class="px-4 py-2 rounded-xl text-sm font-bold"
                    :class="activePage === 'history' ? 'bg-purple-900 text-white' : 'bg-white border border-slate-200 text-slate-600'"
                >
                    <i class="fa-solid fa-clock-rotate-left mr-2"></i>
                    My Request History
                </button>
            </nav>

            <div v-if="errorMessage" class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                {{ errorMessage }}
            </div>

            <div v-if="successMessage" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
                {{ successMessage }}
            </div>

            <section v-if="activePage === 'inventory'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-lg">Available Inventory</h2>
                        <p class="text-xs text-slate-500 mt-1">Shared inventory stock available for office requests.</p>
                    </div>
                    <button @click="loadInventory" class="px-3 py-2 rounded-lg bg-purple-50 border border-purple-200 text-purple-800 text-xs font-bold">
                        <i class="fa-solid fa-rotate mr-1"></i> Refresh
                    </button>
                </div>

                <div v-if="loading" class="p-8 text-center text-slate-500 text-sm">
                    Loading inventory...
                </div>

                <div v-else-if="inventory.length === 0" class="p-8 text-center text-slate-500 text-sm">
                    No inventory items found.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="p-4">Property No.</th>
                                <th class="p-4">Item</th>
                                <th class="p-4">IAR No.</th>
                                <th class="p-4">Category</th>
                                <th class="p-4 text-right">Available Stock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in inventory" :key="item.id">
                                <td class="p-4 font-mono text-xs">{{ item.property_no }}</td>
                                <td class="p-4 font-semibold">{{ item.name }}</td>
                                <td class="p-4 text-purple-700 text-xs font-semibold">{{ item.iar_number || 'Not set' }}</td>
                                <td class="p-4">{{ item.category }}</td>
                                <td class="p-4 text-right font-bold" :class="Number(item.quantity) > 0 ? 'text-emerald-700' : 'text-rose-700'">
                                    {{ item.quantity }} {{ item.unit }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-else-if="activePage === 'request'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-5 sm:p-6 border-b border-slate-100 bg-purple-50/50">
                    <h2 class="text-lg font-bold text-purple-900">Request Inventory Item</h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Your assigned office will be attached automatically to this request.
                    </p>
                </div>

                <form @submit.prevent="submitRequest" class="p-5 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Requestor Name</label>
                        <input
                            v-model="form.requestor_name"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Request Date</label>
                        <input
                            v-model="form.request_date"
                            type="date"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Reference No. / Source</label>
                        <input
                            v-model="form.reference_no_source"
                            type="text"
                            maxlength="255"
                            placeholder="Enter reference number or source"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Select Inventory Item</label>
                        <select
                            v-model="form.inventory_id"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm bg-white"
                        >
                            <option value="" disabled>Select an item</option>
                            <option v-for="item in availableItems" :key="item.id" :value="String(item.id)">
                                {{ item.name }} — {{ item.quantity }} {{ item.unit }} available
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Quantity</label>
                        <input
                            v-model.number="form.quantity"
                            type="number"
                            min="1"
                            :max="selectedItem?.quantity || undefined"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm"
                        />
                        <p v-if="selectedItem" class="text-xs text-slate-500 mt-1">
                            Available: {{ selectedItem.quantity }} {{ selectedItem.unit }}
                        </p>
                    </div>

                    <div class="md:col-span-2 rounded-xl bg-purple-50 border border-purple-100 p-3 text-xs text-purple-800">
                        <strong>Assigned Office:</strong> {{ user.department_office }}
                        <span class="block mt-1">This value comes from your account and cannot be changed in this form.</span>
                    </div>

                    <div class="md:col-span-2 flex justify-end">
                        <button
                            type="submit"
                            :disabled="submitting"
                            class="px-6 py-3 rounded-xl bg-purple-800 hover:bg-purple-900 text-white text-sm font-bold disabled:opacity-50"
                        >
                            <i class="fa-solid fa-paper-plane mr-2"></i>
                            {{ submitting ? 'Submitting...' : 'Submit Item Request' }}
                        </button>
                    </div>
                </form>
            </section>

            <section v-else-if="activePage === 'history'" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100">
                    <h2 class="text-lg font-bold">My Request History</h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Only requests under {{ user.department_office }} are shown.
                    </p>
                </div>

                <div v-if="requests.length === 0" class="p-8 text-center text-sm text-slate-500">
                    No request history found for your office.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="p-4">Date</th>
                                <th class="p-4">Requestor</th>
                                <th class="p-4">Item</th>
                                <th class="p-4">Reference No. / Source</th>
                                <th class="p-4 text-right">Quantity</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="request in requests" :key="request.id">
                                <td class="p-4">{{ request.request_date }}</td>
                                <td class="p-4">{{ request.requestor_name }}</td>
                                <td class="p-4 font-semibold">{{ request.item_name }}</td>
                                <td class="p-4">{{ request.reference_no_source || '-' }}</td>
                                <td class="p-4 text-right font-bold">
                                    {{ request.requested_quantity }} {{ request.unit }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</template>