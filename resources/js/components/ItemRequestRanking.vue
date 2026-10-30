<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'

const requests = ref([])
const inventoryItems = ref([])
const loading = ref(false)
const errorMessage = ref('')
const today = new Date()
const dateToInput = (date) => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}
const startDate = ref(`${today.getFullYear()}-01-01`)
const endDate = ref(dateToInput(today))
const selectedItem = ref('')

function normalize(value) {
    return String(value ?? '').trim().toLocaleLowerCase()
}

const itemOptions = computed(() => {
    const names = new Map()
    for (const item of inventoryItems.value) {
        const name = String(item.name ?? item.item_name ?? '').trim()
        if (name && !names.has(normalize(name))) names.set(normalize(name), name)
    }
    for (const request of requests.value) {
        const name = String(request.item_name ?? request.name ?? '').trim()
        if (name && !names.has(normalize(name))) names.set(normalize(name), name)
    }
    return [...names.values()].sort((a, b) => a.localeCompare(b))
})

const invalidDates = computed(() => Boolean(startDate.value && endDate.value && startDate.value > endDate.value))

const matchingRequests = computed(() => {
    if (invalidDates.value || !startDate.value || !endDate.value || !selectedItem.value) return []
    const wantedItem = normalize(selectedItem.value)
    return requests.value.filter((request) => {
        const itemName = normalize(request.item_name ?? request.name)
        const requestDate = String(request.request_date ?? request.requested_at ?? request.created_at ?? '').slice(0, 10)
        return itemName === wantedItem && requestDate >= startDate.value && requestDate <= endDate.value
    })
})

const officeRanking = computed(() => {
    const offices = new Map()
    for (const request of matchingRequests.value) {
        const office = String(request.department_office ?? '').trim() || 'Office not specified'
        const key = normalize(office)
        if (!offices.has(key)) offices.set(key, { office, requestCount: 0, totalQuantity: 0 })
        const row = offices.get(key)
        row.requestCount += 1
        row.totalQuantity += Math.max(0, Number(request.requested_quantity ?? 0) || 0)
    }
    return [...offices.values()].sort((a, b) => b.requestCount - a.requestCount || b.totalQuantity - a.totalQuantity || a.office.localeCompare(b.office))
})

const totalRequestCount = computed(() => matchingRequests.value.length)
const totalQuantityRequested = computed(() => matchingRequests.value.reduce((sum, request) => sum + Math.max(0, Number(request.requested_quantity ?? 0) || 0), 0))

function printReport() {
    if (invalidDates.value || !selectedItem.value) return
    window.print()
}

function displayDate(value) {
    if (!value) return '—'
    const [year, month, day] = value.split('-').map(Number)
    return new Date(year, month - 1, day).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' })
}

async function loadData() {
    loading.value = true
    errorMessage.value = ''
    try {
        const [requestResponse, inventoryResponse] = await Promise.all([
            axios.get('/api/inventory/request-history'),
            axios.get('/api/inventory'),
        ])
        requests.value = Array.isArray(requestResponse.data) ? requestResponse.data : (requestResponse.data?.data ?? [])
        inventoryItems.value = Array.isArray(inventoryResponse.data) ? inventoryResponse.data : (inventoryResponse.data?.data ?? [])
        if (!selectedItem.value && itemOptions.value.length) selectedItem.value = itemOptions.value[0]
    } catch (error) {
        console.error('Failed to load item request ranking data:', error)
        errorMessage.value = error.response?.data?.message || 'Unable to load request records. Please refresh and try again.'
    } finally {
        loading.value = false
    }
}

onMounted(loadData)
</script>

<template>
    <section id="item-request-ranking" class="space-y-6">
        <div class="print-only mb-5 hidden">
            <h1 class="text-center text-xl font-black">DTRC-CARAGA</h1>
            <h2 class="mt-1 text-center text-lg font-bold">Item Request Ranking by Office</h2>
            <p class="mt-2 text-center text-sm">Item: <strong>{{ selectedItem || 'No item selected' }}</strong></p>
            <p class="text-center text-sm">Period: {{ displayDate(startDate) }} – {{ displayDate(endDate) }}</p>
        </div>
        <div class="no-print rounded-2xl bg-gradient-to-r from-purple-950 via-purple-900 to-purple-800 p-6 text-white shadow-lg">
            <div class="flex items-start gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/10 text-2xl"><i class="fa-solid fa-chart-column"></i></div>
                <div>
                    <h2 class="text-xl font-black sm:text-2xl">Item Request Ranking by Office</h2>
                    <p class="mt-1 text-sm text-purple-100">Choose an item and date range to see which offices requested it most often.</p>
                </div>
            </div>
        </div>

        <div class="no-print rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label for="ranking-item" class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-600">Inventory Item</label>
                    <select id="ranking-item" v-model="selectedItem" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm font-semibold text-slate-800 focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-200">
                        <option value="">Select an item</option>
                        <option v-for="item in itemOptions" :key="item" :value="item">{{ item }}</option>
                    </select>
                </div>
                <div>
                    <label for="ranking-start-date" class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-600">Start Date</label>
                    <input id="ranking-start-date" v-model="startDate" type="date" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm font-semibold text-slate-800 focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-200" />
                </div>
                <div>
                    <label for="ranking-end-date" class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-600">End Date</label>
                    <input id="ranking-end-date" v-model="endDate" type="date" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm font-semibold text-slate-800 focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-200" />
                </div>
            </div>
            <p v-if="invalidDates" class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700">The start date must be before or equal to the end date.</p>
            <p class="mt-3 text-xs text-slate-500">The date range includes both the start date and the end date. “Number of Requests” counts request records; “Total Quantity” adds the requested item quantities.</p>
        </div>

        <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-sm font-semibold text-slate-500"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading request records...</div>
        <div v-else-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 p-5 text-sm font-semibold text-rose-700">
            {{ errorMessage }}
            <button type="button" @click="loadData" class="ml-3 rounded-lg bg-rose-700 px-3 py-2 text-xs font-bold text-white hover:bg-rose-800">Retry</button>
        </div>
        <template v-else>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-purple-100 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Selected Item</p><p class="mt-2 text-lg font-black text-purple-900">{{ selectedItem || 'No item selected' }}</p></div>
                <div class="rounded-2xl border border-purple-100 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Number of Requests</p><p class="mt-2 text-3xl font-black text-purple-900">{{ totalRequestCount }}</p></div>
                <div class="rounded-2xl border border-purple-100 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Total Quantity Requested</p><p class="mt-2 text-3xl font-black text-purple-900">{{ totalQuantityRequested }}</p></div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="no-print flex flex-col gap-3 border-b border-slate-100 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                    <h3 class="font-black text-slate-800">Offices Ranked by Number of Requests</h3>
                    <p class="mt-1 text-xs text-slate-500">Highest requester first for the selected item and date range.</p>
                    </div>
                    <button type="button" @click="printReport" :disabled="!selectedItem || invalidDates" class="inline-flex items-center justify-center gap-2 rounded-xl bg-purple-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-purple-800 disabled:cursor-not-allowed disabled:opacity-50">
                        <i class="fa-solid fa-file-pdf"></i> Print / Save as PDF
                    </button>
                </div>
                <div class="print-only hidden border-b border-slate-300 px-5 py-3">
                    <p class="font-bold">Offices Ranked by Number of Requests</p>
                    <p class="text-sm">Highest requester first for the selected item and date range.</p>
                    <p class="mt-1 text-sm">Total requests: <strong>{{ totalRequestCount }}</strong> · Total quantity: <strong>{{ totalQuantityRequested }}</strong></p>
                </div>
                <div v-if="officeRanking.length" class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead class="bg-white text-xs uppercase tracking-wide text-slate-500">
                            <tr><th class="px-5 py-4">Rank</th><th class="px-5 py-4">Office / Section</th><th class="px-5 py-4 text-right">Number of Requests</th><th class="px-5 py-4 text-right">Total Quantity</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(office, index) in officeRanking" :key="office.office" :class="index === 0 ? 'bg-emerald-50/70' : 'hover:bg-slate-50'">
                                <td class="px-5 py-4"><span class="inline-flex h-8 w-8 items-center justify-center rounded-full font-black" :class="index === 0 ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700'">{{ index + 1 }}</span></td>
                                <td class="px-5 py-4 font-bold text-slate-800">{{ office.office }}<span v-if="index === 0" class="ml-2 rounded-full bg-emerald-100 px-2 py-1 text-[10px] font-black uppercase text-emerald-800">Top requester</span></td>
                                <td class="px-5 py-4 text-right font-black tabular-nums text-purple-900">{{ office.requestCount }}</td>
                                <td class="px-5 py-4 text-right font-semibold tabular-nums text-slate-700">{{ office.totalQuantity }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="p-10 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400"><i class="fa-solid fa-chart-simple text-xl"></i></div>
                    <p class="font-bold text-slate-700">No requests found</p>
                    <p class="mt-1 text-sm text-slate-500">No records match this item and date range. Try another item or expand the dates.</p>
                </div>
            </div>
        </template>
    </section>
</template>


<style>
@media print {
    @page { size: A4 portrait; margin: 14mm; }
    body * { visibility: hidden !important; }
    #item-request-ranking, #item-request-ranking * { visibility: visible !important; }
    #item-request-ranking {
        position: absolute !important;
        inset: 0 auto auto 0 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        color: #111827 !important;
        background: #fff !important;
        font-size: 10pt !important;
    }
    #item-request-ranking .no-print { display: none !important; }
    #item-request-ranking .print-only { display: block !important; }
    #item-request-ranking .grid { display: none !important; }
    #item-request-ranking .overflow-hidden,
    #item-request-ranking .overflow-x-auto { overflow: visible !important; }
    #item-request-ranking table { width: 100% !important; min-width: 0 !important; border-collapse: collapse !important; }
    #item-request-ranking th, #item-request-ranking td {
        padding: 8px 6px !important;
        border: 1px solid #9ca3af !important;
        color: #111827 !important;
        background: #fff !important;
        font-size: 9pt !important;
    }
    #item-request-ranking .rounded-2xl { border-radius: 0 !important; box-shadow: none !important; }
    #item-request-ranking .text-emerald-800,
    #item-request-ranking .text-purple-900,
    #item-request-ranking .text-slate-800,
    #item-request-ranking .text-slate-700 { color: #111827 !important; }
    #item-request-ranking tr { break-inside: avoid; }
}
</style>
