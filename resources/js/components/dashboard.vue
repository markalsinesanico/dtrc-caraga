<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import logo from '../assets/logo.jpg'


/*
|--------------------------------------------------------------------------
| Default Image
|--------------------------------------------------------------------------
*/
const DEFAULT_IMAGE =
    "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100' fill='%237c3aed'><rect width='100' height='100' fill='%23f5f3ff'/><path d='M30 35 L50 25 L70 35 L70 65 L50 75 L30 65 Z' fill='%23ddd6fe' stroke='%237c3aed' stroke-width='3'/><path d='M50 25 L50 75 M30 35 L50 45 L70 35' stroke='%237c3aed' stroke-width='3'/></svg>"

/*
|--------------------------------------------------------------------------
| Categories / Units
|--------------------------------------------------------------------------
*/
const categories = [
    'Office Supplies',
    'IT Equipment',
    'Janitorial',
    'Furniture',
    'Maintenance',
    'Medical',
]

const units = [
    'ream',
    'piece',
    'box',
    'pack',
    'unit',
    'roll',
    'bottle',
    'set',
]

/*
|--------------------------------------------------------------------------
| Inventory
|--------------------------------------------------------------------------
*/
const inventory = ref([])
const isSaving = ref(false)

/*
|--------------------------------------------------------------------------
| UI State
|--------------------------------------------------------------------------
*/
const search = ref('')
const categoryFilter = ref('ALL')
const viewMode = ref('table')

const showAddModal = ref(false)
const showUpdateModal = ref(false)
const showEditModal = ref(false)

const activeEditingId = ref(null)
const currentModalImageData = ref('')

/*
|--------------------------------------------------------------------------
| Form Data
|--------------------------------------------------------------------------
*/
const addForm = ref({
    name: '',
    propertyNo: '',
    category: 'Office Supplies',
    unit: 'ream',
    quantity: 10,
    reorderLevel: 5,
    unitCost: 150.0,
    image: '',
})

const editForm = ref({
    id: '',
    name: '',
    propertyNo: '',
    category: 'Office Supplies',
    unit: 'ream',
    quantity: 0,
    reorderLevel: 1,
    unitCost: 0,
    image: '',
})

const updateQuantity = ref(0)

/*
|--------------------------------------------------------------------------
| Toasts
|--------------------------------------------------------------------------
*/
const toasts = ref([])

let toastId = 0

function showToast(title, message, type = 'info') {
    const id = ++toastId

    toasts.value.push({
        id,
        title,
        message,
        type,
    })

    setTimeout(() => {
        removeToast(id)
    }, 3500)
}

function removeToast(id) {
    toasts.value = toasts.value.filter((toast) => toast.id !== id)
}

/*
|--------------------------------------------------------------------------
| Computed Inventory
|--------------------------------------------------------------------------
*/
const sortedInventory = computed(() => {
    return [...inventory.value].sort((a, b) =>
        a.name.localeCompare(b.name),
    )
})

const filteredInventory = computed(() => {
    const searchValue = search.value.toLowerCase().trim()

    return sortedInventory.value.filter((item) => {
        const matchesSearch =
            item.name.toLowerCase().includes(searchValue) ||
            item.propertyNo.toLowerCase().includes(searchValue)

        const matchesCategory =
            categoryFilter.value === 'ALL' ||
            item.category === categoryFilter.value

        return matchesSearch && matchesCategory
    })
})

const availableItems = computed(() =>
    filteredInventory.value.filter((item) => item.quantity > 0),
)

const outOfStockItems = computed(() =>
    filteredInventory.value.filter((item) => item.quantity === 0),
)

/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/
const totalItems = computed(() => inventory.value.length)

const totalValue = computed(() =>
    inventory.value
        .filter((item) => item.quantity > 0)
        .reduce((total, item) => total + item.quantity * item.unitCost, 0),
)

const lowStockCount = computed(() =>
    inventory.value.filter(
        (item) =>
            item.quantity > 0 && item.quantity <= item.reorderLevel,
    ).length,
)

const outOfStockCount = computed(() =>
    inventory.value.filter((item) => item.quantity === 0).length,
)

const availableCount = computed(() =>
    inventory.value.filter((item) => item.quantity > 0).length,
)

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/
function formatPeso(amount) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(amount)
}

function itemImage(item) {
    return item.image || DEFAULT_IMAGE
}

function fromApi(item) {
    return {
        id: item.id,
        propertyNo: item.property_no,
        name: item.name,
        category: item.category,
        unit: item.unit,
        quantity: Number(item.quantity) || 0,
        reorderLevel: Number(item.reorder_level) || 1,
        unitCost: Number(item.unit_cost) || 0,
        image: item.image || DEFAULT_IMAGE,
    }
}

function toApi(item) {
    return {
        property_no: item.propertyNo,
        name: item.name,
        category: item.category,
        unit: item.unit,
        quantity: Number(item.quantity) || 0,
        reorder_level: Number(item.reorderLevel) || 1,
        unit_cost: Number(item.unitCost) || 0,
        image: item.image || null,
    }
}

function apiErrorMessage(error, fallback) {
    const errors = error.response?.data?.errors

    if (errors) {
        const firstError = Object.values(errors).flat()[0]

        if (firstError) {
            return firstError
        }
    }

    return error.response?.data?.message || fallback
}

async function loadInventory() {
    try {
        const { data } = await axios.get('/inventory')
        inventory.value = (data || []).map(fromApi)
    } catch (error) {
        showToast(
            'Load Failed',
            apiErrorMessage(error, 'Could not load inventory from the database.'),
            'danger',
        )
    }
}

onMounted(() => {
    loadInventory()
})

function isLowStock(item) {
    return item.quantity > 0 && item.quantity <= item.reorderLevel
}

/*
|--------------------------------------------------------------------------
| View Mode
|--------------------------------------------------------------------------
*/
function toggleViewMode(mode) {
    viewMode.value = mode
}

/*
|--------------------------------------------------------------------------
| Image Upload
|--------------------------------------------------------------------------
*/
function handleImageFileUpload(event, target = 'add') {
    const file = event.target.files?.[0]

    if (!file) return

    const reader = new FileReader()

    reader.onload = (e) => {
        const result = e.target.result

        if (target === 'add') {
            addForm.value.image = result
            currentModalImageData.value = result
        } else {
            editForm.value.image = result
            currentModalImageData.value = result
        }
    }

    reader.readAsDataURL(file)
}

function handleImageUrlInput(url, target = 'add') {
    const value = url.trim()

    if (target === 'add') {
        addForm.value.image = value
        currentModalImageData.value = value
    } else {
        editForm.value.image = value
        currentModalImageData.value = value
    }
}

/*
|--------------------------------------------------------------------------
| Add Item Modal
|--------------------------------------------------------------------------
*/
function openAddItemModal() {
    addForm.value = {
        name: '',
        propertyNo: '',
        category: 'Office Supplies',
        unit: 'ream',
        quantity: 10,
        reorderLevel: 5,
        unitCost: 150.0,
        image: '',
    }

    currentModalImageData.value = ''

    showAddModal.value = true
}

function closeAddItemModal() {
    showAddModal.value = false
}

async function handleAddItem() {
    if (isSaving.value) return

    const payload = toApi({
        name: addForm.value.name.trim(),
        propertyNo: addForm.value.propertyNo.trim(),
        category: addForm.value.category,
        unit: addForm.value.unit,
        quantity: Number(addForm.value.quantity) || 0,
        reorderLevel: Number(addForm.value.reorderLevel) || 5,
        unitCost: Number(addForm.value.unitCost) || 0,
        image: addForm.value.image || '',
    })

    isSaving.value = true

    try {
        const { data } = await axios.post('/inventory', payload)
        inventory.value.push(fromApi(data.data))

        closeAddItemModal()

        showToast(
            'Item Registered',
            `Successfully added "${data.data.name}" to inventory.`,
            'success',
        )
    } catch (error) {
        showToast(
            'Save Failed',
            apiErrorMessage(error, 'Could not save the item to the database.'),
            'danger',
        )
    } finally {
        isSaving.value = false
    }
}

/*
|--------------------------------------------------------------------------
| Quick Quantity Adjustment
|--------------------------------------------------------------------------
*/
async function persistItem(item) {
    const { data } = await axios.put(`/inventory/${item.id}`, toApi(item))
    const mapped = fromApi(data.data)
    const index = inventory.value.findIndex((row) => row.id === item.id)

    if (index !== -1) {
        inventory.value[index] = mapped
    }

    return mapped
}

async function quickAdjustQty(id, change) {
    const item = inventory.value.find((item) => item.id === id)

    if (!item) return

    const previousQty = item.quantity
    const newQty = previousQty + change

    if (newQty < 0) return

    item.quantity = newQty

    try {
        await persistItem(item)
    } catch (error) {
        item.quantity = previousQty
        showToast(
            'Update Failed',
            apiErrorMessage(error, 'Could not update quantity in the database.'),
            'danger',
        )
        return
    }

    if (newQty === 0) {
        showToast(
            'Stock Depleted',
            `"${item.name}" is now out of stock and moved to the lower section.`,
            'warning',
        )
    } else {
        showToast(
            'Quantity Updated',
            `Updated stock for "${item.name}" to ${newQty}.`,
            'info',
        )
    }
}

/*
|--------------------------------------------------------------------------
| Update Quantity Modal
|--------------------------------------------------------------------------
*/
function openUpdateQtyModal(id) {
    const item = inventory.value.find((item) => item.id === id)

    if (!item) return

    activeEditingId.value = id
    updateQuantity.value = item.quantity
    showUpdateModal.value = true
}

function closeUpdateQtyModal() {
    showUpdateModal.value = false
    activeEditingId.value = null
}

function adjustModalQty(delta) {
    let quantity = Number(updateQuantity.value) || 0

    quantity += delta

    if (quantity < 0) {
        quantity = 0
    }

    updateQuantity.value = quantity
}

const activeUpdateItem = computed(() => {
    return inventory.value.find(
        (item) => item.id === activeEditingId.value,
    )
})

async function saveQtyUpdate() {
    const item = activeUpdateItem.value

    if (!item || isSaving.value) return

    const newQty = Number(updateQuantity.value)

    if (Number.isNaN(newQty) || newQty < 0) return

    const previousQty = item.quantity
    item.quantity = newQty
    isSaving.value = true

    try {
        const saved = await persistItem(item)
        closeUpdateQtyModal()

        if (previousQty === 0 && saved.quantity > 0) {
            showToast(
                'Item Restocked!',
                `"${saved.name}" has been replenished and moved back to Available Stock.`,
                'success',
            )
        } else if (saved.quantity === 0) {
            showToast(
                'Item Depleted',
                `"${saved.name}" quantity set to 0 and moved to Out of Stock.`,
                'warning',
            )
        } else {
            showToast(
                'Stock Saved',
                `Updated quantity for "${saved.name}" to ${saved.quantity}.`,
                'info',
            )
        }
    } catch (error) {
        item.quantity = previousQty
        showToast(
            'Update Failed',
            apiErrorMessage(error, 'Could not update quantity in the database.'),
            'danger',
        )
    } finally {
        isSaving.value = false
    }
}

/*
|--------------------------------------------------------------------------
| Edit Item Modal
|--------------------------------------------------------------------------
*/
function openEditItemModal(id) {
    const item = inventory.value.find((item) => item.id === id)

    if (!item) return

    activeEditingId.value = id

    editForm.value = {
        id: item.id,
        name: item.name,
        propertyNo: item.propertyNo,
        category: item.category,
        unit: item.unit,
        quantity: item.quantity,
        reorderLevel: item.reorderLevel,
        unitCost: item.unitCost,
        image: item.image || '',
    }

    currentModalImageData.value = item.image || ''

    showEditModal.value = true
}

function closeEditItemModal() {
    showEditModal.value = false
    activeEditingId.value = null
}

async function handleSaveEditItem() {
    const item = inventory.value.find(
        (item) => item.id === editForm.value.id,
    )

    if (!item || isSaving.value) return

    const snapshot = { ...item }

    item.name = editForm.value.name.trim()
    item.propertyNo = editForm.value.propertyNo.trim()
    item.category = editForm.value.category
    item.unit = editForm.value.unit
    item.quantity = Number(editForm.value.quantity) || 0
    item.reorderLevel = Number(editForm.value.reorderLevel) || 1
    item.unitCost = Number(editForm.value.unitCost) || 0

    if (editForm.value.image) {
        item.image = editForm.value.image
    }

    isSaving.value = true

    try {
        const saved = await persistItem(item)
        closeEditItemModal()
        showToast(
            'Changes Saved',
            `Updated details for "${saved.name}".`,
            'success',
        )
    } catch (error) {
        Object.assign(item, snapshot)
        showToast(
            'Update Failed',
            apiErrorMessage(error, 'Could not update the item in the database.'),
            'danger',
        )
    } finally {
        isSaving.value = false
    }
}

async function deleteCurrentItem() {
    const id = activeEditingId.value

    if (!id || isSaving.value) return

    const item = inventory.value.find((item) => item.id === id)

    if (!item) return

    const itemName = item.name
    isSaving.value = true

    try {
        await axios.delete(`/inventory/${id}`)
        inventory.value = inventory.value.filter(
            (row) => row.id !== id,
        )
        closeEditItemModal()
        showToast(
            'Item Deleted',
            `Removed "${itemName}" from government database.`,
            'danger',
        )
    } catch (error) {
        showToast(
            'Delete Failed',
            apiErrorMessage(error, 'Could not delete the item from the database.'),
            'danger',
        )
    } finally {
        isSaving.value = false
    }
}

/*
|--------------------------------------------------------------------------
| Image Error
|--------------------------------------------------------------------------
*/
function handleImageError(event) {
    event.target.src = DEFAULT_IMAGE
}
</script>

<template>
    <div class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased flex flex-col">

        <!-- =========================================================
             HEADER
        ========================================================== -->
        <header
            class="text-white shadow-xl sticky top-0 z-30 border-b border-purple-800"
            style="
                background: rgba(76, 29, 149, 0.95);
                backdrop-filter: blur(8px);
            "
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">

                    <div class="flex items-center space-x-4">
                        
                             <img
        :src="logo"
        alt="Logo"
        class="w-20 h-20 rounded-full object-cover border-2 border-white shadow-lg"
    />
                        

                        <div>
                            <div class="flex items-center space-x-2">
                                <span
                                    class="text-[10px] font-bold uppercase tracking-widest text-purple-300 bg-purple-950/60 px-2 py-0.5 rounded-md border border-purple-700/50"
                                >
                                    Republic of the Philippines
                                </span>

                                <span
                                    class="text-[10px] font-semibold uppercase tracking-widest text-purple-200"
                                >
                                    Property Division
                                </span>
                            </div>

                            <h1
                                class="text-lg sm:text-2xl font-black tracking-tight text-white mt-0.5"
                            >
                                Material Management Office
                            </h1>

                            <p class="text-xs text-purple-200 hidden sm:block">
                                Official Supplies, Equipment & Logistics Inventory Control System
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <button
                            @click="openAddItemModal"
                            class="bg-gradient-to-r from-purple-600 to-purple-500 hover:from-purple-500 hover:to-purple-400 text-white font-semibold px-4 py-2.5 rounded-xl transition-all duration-200 flex items-center space-x-2 shadow-lg shadow-purple-900/30 hover:scale-[1.02] focus:ring-2 focus:ring-purple-300 active:scale-95"
                        >
                            <i class="fa-solid fa-plus-circle text-lg"></i>
                            <span class="hidden sm:inline">
                                Add New Item
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <div
                class="h-1 bg-gradient-to-r from-purple-400 via-purple-300 to-purple-600"
            ></div>
        </header>

        <!-- =========================================================
             MAIN
        ========================================================== -->
        <main
            class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-8"
        >

            <!-- =====================================================
                 STATISTICS
            ====================================================== -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

                <!-- TOTAL ITEMS -->
                <div
                    class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-all relative overflow-hidden group"
                >
                    <div
                        class="absolute top-0 right-0 w-24 h-24 -mr-6 -mt-6 bg-purple-100/50 rounded-full group-hover:scale-110 transition-transform pointer-events-none"
                    ></div>

                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-xs font-bold uppercase tracking-wider text-slate-500"
                            >
                                Total Unique Items
                            </p>

                            <h3
                                class="text-3xl font-black text-slate-800 mt-1"
                            >
                                {{ totalItems }}
                            </h3>

                            <p
                                class="text-xs text-slate-400 mt-1 flex items-center"
                            >
                                <i
                                    class="fa-solid fa-database text-[10px] mr-1 text-purple-500"
                                ></i>
                                Registered in catalog
                            </p>
                        </div>

                        <div
                            class="w-12 h-12 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-xl font-bold border border-purple-200/60 shadow-inner"
                        >
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                    </div>
                </div>

                <!-- TOTAL VALUE -->
                <div
                    class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-all relative overflow-hidden group"
                >
                    <div
                        class="absolute top-0 right-0 w-24 h-24 -mr-6 -mt-6 bg-emerald-100/50 rounded-full group-hover:scale-110 transition-transform pointer-events-none"
                    ></div>

                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-xs font-bold uppercase tracking-wider text-slate-500"
                            >
                                Total Inventory Value
                            </p>

                            <h3
                                class="text-2xl font-black text-emerald-700 mt-1"
                            >
                                {{ formatPeso(totalValue) }}
                            </h3>

                            <p
                                class="text-xs text-slate-400 mt-1 flex items-center"
                            >
                                <i
                                    class="fa-solid fa-peso-sign text-[10px] mr-1 text-emerald-500"
                                ></i>
                                Total active stock worth
                            </p>
                        </div>

                        <div
                            class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl font-bold border border-emerald-200/60 shadow-inner"
                        >
                            <i class="fa-solid fa-coins"></i>
                        </div>
                    </div>
                </div>

                <!-- LOW STOCK -->
                <div
                    class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-all relative overflow-hidden group"
                >
                    <div
                        class="absolute top-0 right-0 w-24 h-24 -mr-6 -mt-6 bg-amber-100/50 rounded-full group-hover:scale-110 transition-transform pointer-events-none"
                    ></div>

                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-xs font-bold uppercase tracking-wider text-slate-500"
                            >
                                Low Stock Alert
                            </p>

                            <h3
                                class="text-3xl font-black text-amber-600 mt-1"
                            >
                                {{ lowStockCount }}
                            </h3>

                            <p
                                class="text-xs text-slate-400 mt-1 flex items-center"
                            >
                                <i
                                    class="fa-solid fa-triangle-exclamation text-[10px] mr-1 text-amber-500"
                                ></i>
                                At or below reorder level
                            </p>
                        </div>

                        <div
                            class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl font-bold border border-amber-200/60 shadow-inner"
                        >
                            <i class="fa-solid fa-boxes-packing"></i>
                        </div>
                    </div>
                </div>

                <!-- OUT OF STOCK -->
                <div
                    class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm hover:shadow-md transition-all relative overflow-hidden group"
                >
                    <div
                        class="absolute top-0 right-0 w-24 h-24 -mr-6 -mt-6 bg-rose-100/50 rounded-full group-hover:scale-110 transition-transform pointer-events-none"
                    ></div>

                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-xs font-bold uppercase tracking-wider text-slate-500"
                            >
                                Out of Stock
                            </p>

                            <h3
                                class="text-3xl font-black text-rose-600 mt-1"
                            >
                                {{ outOfStockCount }}
                            </h3>

                            <p
                                class="text-xs text-slate-400 mt-1 flex items-center"
                            >
                                <i
                                    class="fa-solid fa-circle-exclamation text-[10px] mr-1 text-rose-500"
                                ></i>
                                Zero balance remaining
                            </p>
                        </div>

                        <div
                            class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl font-bold border border-rose-200/60 shadow-inner"
                        >
                            <i class="fa-solid fa-ban"></i>
                        </div>
                    </div>
                </div>

            </div>

            <!-- =====================================================
                 AVAILABLE INVENTORY
            ====================================================== -->
            <section
                class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden"
            >

                <!-- TOOLBAR -->
                <div
                    class="p-5 sm:p-6 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-slate-50/50"
                >
                    <div>
                        <div class="flex items-center space-x-2">
                            <span
                                class="w-3 h-3 rounded-full bg-emerald-500 inline-block animate-pulse"
                            ></span>

                            <h2 class="text-lg font-bold text-slate-800">
                                Available Inventory Stock
                            </h2>

                            <span
                                class="bg-emerald-100 text-emerald-800 text-xs font-bold px-2.5 py-0.5 rounded-full border border-emerald-200"
                            >
                                {{ availableCount }} Items
                            </span>
                        </div>

                        <p class="text-xs text-slate-500 mt-0.5">
                            Active equipment and supplies available for disbursement and issue.
                        </p>
                    </div>

                    <!-- CONTROLS -->
                    <div
                        class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3"
                    >

                        <!-- SEARCH -->
                        <div class="relative flex-1 sm:w-64">
                            <i
                                class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"
                            ></i>

                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search item, code, description..."
                                class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all shadow-sm"
                            />
                        </div>

                        <!-- CATEGORY -->
                        <select
                            v-model="categoryFilter"
                            class="py-2 px-3 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all shadow-sm"
                        >
                            <option value="ALL">
                                All Categories
                            </option>

                            <option
                                v-for="category in categories"
                                :key="category"
                                :value="category"
                            >
                                {{
                                    category === 'Janitorial'
                                        ? 'Janitorial Supplies'
                                        : category === 'Maintenance'
                                            ? 'Maintenance & Hardware'
                                            : category === 'Medical'
                                                ? 'Medical Supplies'
                                                : category
                                }}
                            </option>
                        </select>

                        <!-- VIEW TOGGLE -->
                        <div
                            class="flex items-center bg-slate-200/80 p-1 rounded-xl"
                        >
                            <button
                                @click="toggleViewMode('table')"
                                :class="
                                    viewMode === 'table'
                                        ? 'px-3 py-1.5 rounded-lg text-xs font-bold text-purple-900 bg-white shadow-sm transition-all'
                                        : 'px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:text-purple-900 transition-all'
                                "
                                title="Table View"
                            >
                                <i class="fa-solid fa-table-list"></i>
                            </button>

                            <button
                                @click="toggleViewMode('grid')"
                                :class="
                                    viewMode === 'grid'
                                        ? 'px-3 py-1.5 rounded-lg text-xs font-bold text-purple-900 bg-white shadow-sm transition-all'
                                        : 'px-3 py-1.5 rounded-lg text-xs font-bold text-slate-600 hover:text-purple-900 transition-all'
                                "
                                title="Grid View"
                            >
                                <i class="fa-solid fa-border-all"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- =================================================
                     TABLE VIEW
                ================================================== -->
                <div
                    v-if="viewMode === 'table'"
                    class="overflow-x-auto"
                >
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="bg-slate-100/70 border-b border-slate-200 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider"
                            >
                                <th class="py-3.5 px-4 text-center">
                                    Image
                                </th>
                                <th class="py-3.5 px-4">
                                    Property / Article No.
                                </th>
                                <th class="py-3.5 px-4">
                                    Item Description
                                </th>
                                <th class="py-3.5 px-4">
                                    Category
                                </th>
                                <th class="py-3.5 px-4 text-right">
                                    Unit Cost
                                </th>
                                <th class="py-3.5 px-4 text-center">
                                    Available Stock
                                </th>
                                <th class="py-3.5 px-4 text-center">
                                    Status
                                </th>
                                <th class="py-3.5 px-4 text-right">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            v-if="availableItems.length"
                            class="divide-y divide-slate-100 text-sm"
                        >
                            <tr
                                v-for="item in availableItems"
                                :key="item.id"
                                class="hover:bg-slate-50/80 transition-colors border-b border-slate-100"
                            >

                                <!-- IMAGE -->
                                <td class="py-3 px-4 text-center">
                                    <img
                                        :src="itemImage(item)"
                                        @error="handleImageError"
                                        class="w-12 h-12 object-cover rounded-xl border border-slate-200 mx-auto shadow-sm bg-white"
                                        alt="Thumbnail"
                                    />
                                </td>

                                <!-- PROPERTY -->
                                <td
                                    class="py-3 px-4 font-mono text-xs font-black text-purple-900"
                                >
                                    {{ item.propertyNo }}
                                </td>

                                <!-- NAME -->
                                <td class="py-3 px-4">
                                    <div
                                        class="font-bold text-slate-800 text-sm"
                                    >
                                        {{ item.name }}
                                    </div>

                                    <div
                                        class="text-[11px] text-slate-400"
                                    >
                                        Total Value:
                                        {{ formatPeso(item.quantity * item.unitCost) }}
                                    </div>
                                </td>

                                <!-- CATEGORY -->
                                <td class="py-3 px-4">
                                    <span
                                        class="inline-block px-2.5 py-1 text-xs font-semibold bg-slate-100 text-slate-600 rounded-lg border border-slate-200"
                                    >
                                        {{ item.category }}
                                    </span>
                                </td>

                                <!-- COST -->
                                <td
                                    class="py-3 px-4 text-right font-semibold text-slate-700 text-sm"
                                >
                                    {{ formatPeso(item.unitCost) }}
                                </td>

                                <!-- STOCK -->
                                <td class="py-3 px-4 text-center">
                                    <span
                                        :class="
                                            isLowStock(item)
                                                ? 'font-black text-base text-amber-600'
                                                : 'font-black text-base text-slate-800'
                                        "
                                    >
                                        {{ item.quantity }}
                                    </span>

                                    <span
                                        class="text-xs text-slate-400 font-medium"
                                    >
                                        {{ item.unit }}
                                    </span>
                                </td>

                                <!-- STATUS -->
                                <td class="py-3 px-4 text-center">
                                    <span
                                        v-if="isLowStock(item)"
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200"
                                    >
                                        <i
                                            class="fa-solid fa-triangle-exclamation text-xs mr-1 text-amber-600"
                                        ></i>
                                        Low Stock
                                    </span>

                                    <span
                                        v-else
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
                                    >
                                        <i
                                            class="fa-solid fa-circle-check text-xs mr-1 text-emerald-600"
                                        ></i>
                                        In Stock
                                    </span>
                                </td>

                                <!-- ACTIONS -->
                                <td
                                    class="py-3 px-4 text-right whitespace-nowrap"
                                >
                                    <button
                                        @click="quickAdjustQty(item.id, 1)"
                                        title="Quick Add +1"
                                        class="p-1.5 text-slate-600 hover:text-purple-700 hover:bg-purple-100/70 rounded-lg transition-colors"
                                    >
                                        <i class="fa-solid fa-plus text-xs"></i>
                                    </button>

                                    <button
                                        @click="quickAdjustQty(item.id, -1)"
                                        title="Quick Deduct -1"
                                        class="p-1.5 text-slate-600 hover:text-rose-700 hover:bg-rose-100/70 rounded-lg transition-colors"
                                    >
                                        <i class="fa-solid fa-minus text-xs"></i>
                                    </button>

                                    <button
                                        @click="openUpdateQtyModal(item.id)"
                                        class="px-3 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold rounded-lg text-xs transition-colors border border-purple-200 ml-1"
                                    >
                                        Update Qty
                                    </button>

                                    <button
                                        @click="openEditItemModal(item.id)"
                                        title="Edit Item Details"
                                        class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors ml-1"
                                    >
                                        <i
                                            class="fa-solid fa-pen-to-square"
                                        ></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- =================================================
                     GRID VIEW
                ================================================== -->
                <div
                    v-if="viewMode === 'grid' && availableItems.length"
                    class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5"
                >
                    <div
                        v-for="item in availableItems"
                        :key="item.id"
                        class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 hover:shadow-md transition-shadow relative flex flex-col justify-between"
                    >
                        <div>

                            <div
                                class="relative h-36 rounded-xl overflow-hidden bg-slate-100 border border-slate-200/60 mb-3"
                            >
                                <img
                                    :src="itemImage(item)"
                                    @error="handleImageError"
                                    class="w-full h-full object-cover"
                                    alt="Card Image"
                                />

                                <span
                                    class="absolute top-2 left-2 font-mono text-[10px] font-black bg-purple-900/90 text-white px-2 py-0.5 rounded-md backdrop-blur-sm"
                                >
                                    {{ item.propertyNo }}
                                </span>

                                <div class="absolute bottom-2 right-2">
                                    <span
                                        v-if="isLowStock(item)"
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200"
                                    >
                                        <i
                                            class="fa-solid fa-triangle-exclamation text-xs mr-1"
                                        ></i>
                                        Low Stock
                                    </span>

                                    <span
                                        v-else
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
                                    >
                                        <i
                                            class="fa-solid fa-circle-check text-xs mr-1"
                                        ></i>
                                        In Stock
                                    </span>
                                </div>
                            </div>

                            <div
                                class="text-xs font-bold text-slate-400 uppercase tracking-wider"
                            >
                                {{ item.category }}
                            </div>

                            <h4
                                class="font-bold text-slate-800 text-base mt-0.5"
                            >
                                {{ item.name }}
                            </h4>

                            <div
                                class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100 text-xs"
                            >
                                <span class="text-slate-500">
                                    Unit Cost:
                                    <strong class="text-slate-700">
                                        {{ formatPeso(item.unitCost) }}
                                    </strong>
                                </span>

                                <span class="text-slate-500">
                                    Stock:
                                    <strong
                                        class="text-slate-900 text-sm font-black"
                                    >
                                        {{ item.quantity }}
                                        {{ item.unit }}
                                    </strong>
                                </span>
                            </div>
                        </div>

                        <div
                            class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2"
                        >
                            <button
                                @click="openUpdateQtyModal(item.id)"
                                class="flex-1 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 font-bold rounded-xl text-xs transition-colors border border-purple-200 text-center"
                            >
                                Adjust Quantity
                            </button>

                            <button
                                @click="openEditItemModal(item.id)"
                                class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors"
                            >
                                <i
                                    class="fa-solid fa-pen-to-square"
                                ></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- EMPTY AVAILABLE -->
                <div
                    v-if="availableItems.length === 0"
                    class="p-12 text-center"
                >
                    <div
                        class="w-16 h-16 bg-purple-50 text-purple-400 rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl border border-purple-100"
                    >
                        <i class="fa-solid fa-box-open"></i>
                    </div>

                    <h3
                        class="text-base font-bold text-slate-700"
                    >
                        No Available Items Found
                    </h3>

                    <p
                        class="text-xs text-slate-400 mt-1 max-w-sm mx-auto"
                    >
                        There are no available stock items matching your
                        current search criteria or category filter.
                    </p>
                </div>
            </section>

            <!-- =====================================================
                 OUT OF STOCK
            ====================================================== -->
            <section
                class="bg-white rounded-2xl border border-rose-200/80 shadow-sm overflow-hidden"
            >
                <div
                    class="p-5 sm:p-6 bg-rose-50/50 border-b border-rose-100 flex items-center justify-between"
                >
                    <div class="flex items-center space-x-3">

                        <div
                            class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-lg font-bold border border-rose-200/80"
                        >
                            <i
                                class="fa-solid fa-triangle-exclamation"
                            ></i>
                        </div>

                        <div>
                            <div class="flex items-center space-x-2">

                                <h2
                                    class="text-lg font-bold text-slate-800"
                                >
                                    Out of Stock Items
                                </h2>

                                <span
                                    class="bg-rose-100 text-rose-800 text-xs font-bold px-2.5 py-0.5 rounded-full border border-rose-200"
                                >
                                    {{ outOfStockCount }} Items
                                </span>
                            </div>

                            <p class="text-xs text-slate-500 mt-0.5">
                                Critical zero-balance inventory items requiring
                                urgent requisition or purchase order.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="outOfStockItems.length"
                    class="overflow-x-auto"
                >
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="bg-rose-50/30 border-b border-rose-100 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider"
                            >
                                <th class="py-3.5 px-4 text-center">
                                    Image
                                </th>
                                <th class="py-3.5 px-4">
                                    Property / Article No.
                                </th>
                                <th class="py-3.5 px-4">
                                    Item Description
                                </th>
                                <th class="py-3.5 px-4">
                                    Category
                                </th>
                                <th class="py-3.5 px-4 text-right">
                                    Unit Cost
                                </th>
                                <th class="py-3.5 px-4 text-center">
                                    Status
                                </th>
                                <th class="py-3.5 px-4 text-right">
                                    Quick Restock Action
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-slate-100 text-sm"
                        >
                            <tr
                                v-for="item in outOfStockItems"
                                :key="item.id"
                                class="hover:bg-rose-50/30 transition-colors border-b border-slate-100"
                            >

                                <td class="py-3 px-4 text-center">
                                    <img
                                        :src="itemImage(item)"
                                        @error="handleImageError"
                                        class="w-12 h-12 object-cover rounded-xl border border-rose-200 mx-auto shadow-sm bg-white opacity-80"
                                        alt="Out of Stock Thumbnail"
                                    />
                                </td>

                                <td
                                    class="py-3 px-4 font-mono text-xs font-black text-rose-900"
                                >
                                    {{ item.propertyNo }}
                                </td>

                                <td
                                    class="py-3 px-4 font-bold text-slate-800 text-sm"
                                >
                                    {{ item.name }}
                                </td>

                                <td class="py-3 px-4">
                                    <span
                                        class="inline-block px-2.5 py-1 text-xs font-semibold bg-rose-50 text-rose-700 rounded-lg border border-rose-100"
                                    >
                                        {{ item.category }}
                                    </span>
                                </td>

                                <td
                                    class="py-3 px-4 text-right font-semibold text-slate-700 text-sm"
                                >
                                    {{ formatPeso(item.unitCost) }}
                                </td>

                                <td class="py-3 px-4 text-center">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200"
                                    >
                                        <i
                                            class="fa-solid fa-ban text-xs mr-1 text-rose-600"
                                        ></i>
                                        Out of Stock
                                    </span>
                                </td>

                                <td class="py-3 px-4 text-right">
                                    <button
                                        @click="openUpdateQtyModal(item.id)"
                                        class="px-3 py-1.5 bg-gradient-to-r from-rose-600 to-rose-500 hover:from-rose-500 hover:to-rose-400 text-white font-bold rounded-xl text-xs shadow-sm transition-all flex items-center space-x-1.5 ml-auto"
                                    >
                                        <i
                                            class="fa-solid fa-boxes-packing"
                                        ></i>

                                        <span>
                                            Restock Item
                                        </span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- EMPTY OUT OF STOCK -->
                <div
                    v-else
                    class="p-10 text-center"
                >
                    <div
                        class="w-14 h-14 bg-emerald-50 text-emerald-500 rounded-2xl flex items-center justify-center mx-auto mb-3 text-xl border border-emerald-100"
                    >
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                    <h3
                        class="text-base font-bold text-slate-700"
                    >
                        All Supplies are In Stock
                    </h3>

                    <p class="text-xs text-slate-400 mt-1">
                        Excellent! There are currently no critical zero-balance
                        items in the material office.
                    </p>
                </div>
            </section>
        </main>

        <!-- =========================================================
             FOOTER
        ========================================================== -->
        <footer
            class="bg-white border-t border-slate-200 mt-auto py-6 text-center text-xs text-slate-500"
        >
            <div
                class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3"
            >
                <div class="flex items-center space-x-2">
                    <i
                        class="fa-solid fa-building-columns text-purple-700 text-base"
                    ></i>

                    <span
                        class="font-semibold text-slate-700"
                    >
                        Material Management Office (MMO) Portal
                    </span>
                </div>

                <p class="text-slate-400">
                    &copy; Republic of the Philippines. Standard Property &
                    Stock Control System.
                </p>
            </div>
        </footer>

        <!-- =========================================================
             ADD ITEM MODAL
        ========================================================== -->
        <div
            v-if="showAddModal"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center"
            @click.self="closeAddItemModal"
        >
            <div
                class="bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-2xl w-full mx-4 overflow-hidden max-h-[90vh] flex flex-col"
            >

                <!-- HEADER -->
                <div
                    class="bg-gradient-to-r from-purple-900 to-purple-800 px-6 py-4 text-white flex justify-between items-center shadow-md"
                >
                    <div class="flex items-center space-x-3">
                        <div
                            class="w-9 h-9 rounded-xl bg-purple-700/60 flex items-center justify-center text-purple-200 border border-purple-500/40"
                        >
                            <i class="fa-solid fa-box-open text-lg"></i>
                        </div>

                        <div>
                            <h3
                                class="font-bold text-base sm:text-lg text-white"
                            >
                                Add New Inventory Item
                            </h3>

                            <p
                                class="text-[11px] text-purple-200"
                            >
                                Register new office property with item image
                                and specifications.
                            </p>
                        </div>
                    </div>

                    <button
                        @click="closeAddItemModal"
                        class="text-purple-200 hover:text-white bg-purple-800/50 hover:bg-purple-700 p-2 rounded-xl transition-colors"
                    >
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- FORM -->
                <form
                    @submit.prevent="handleAddItem"
                    class="p-6 space-y-5 overflow-y-auto flex-1"
                >

                    <!-- IMAGE -->
                    <div
                        class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80"
                    >
                        <label
                            class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-2"
                        >
                            <i
                                class="fa-solid fa-image text-purple-600 mr-1"
                            ></i>
                            Item Photo / Image
                        </label>

                        <div
                            class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center"
                        >
                            <div
                                class="flex flex-col items-center justify-center"
                            >
                                <div
                                    class="w-28 h-28 rounded-2xl border-2 border-dashed border-purple-300 bg-white p-1 shadow-sm flex items-center justify-center overflow-hidden"
                                >
                                    <img
                                        v-if="addForm.image"
                                        :src="addForm.image"
                                        @error="handleImageError"
                                        class="w-full h-full object-cover rounded-xl"
                                        alt="Preview"
                                    />

                                    <div
                                        v-else
                                        class="text-center p-2"
                                    >
                                        <i
                                            class="fa-solid fa-camera text-2xl text-purple-300"
                                        ></i>

                                        <span
                                            class="block text-[10px] text-slate-400 font-medium mt-1"
                                        >
                                            No Image
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="sm:col-span-2 space-y-3"
                            >
                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-slate-500 mb-1"
                                    >
                                        Option A: Upload File
                                    </label>

                                    <input
                                        type="file"
                                        accept="image/*"
                                        @change="handleImageFileUpload($event, 'add')"
                                        class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-purple-100 file:text-purple-700 hover:file:bg-purple-200 cursor-pointer"
                                    />
                                </div>

                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-slate-500 mb-1"
                                    >
                                        Option B: Image URL
                                    </label>

                                    <input
                                        type="url"
                                        :value="addForm.image.startsWith('data:') ? '' : addForm.image"
                                        @input="handleImageUrlInput($event.target.value, 'add')"
                                        placeholder="https://example.com/item.jpg"
                                        class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- NAME -->
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1"
                        >
                            Item Description / Name *
                        </label>

                        <input
                            v-model="addForm.name"
                            type="text"
                            required
                            placeholder="e.g. Bond Paper A4 80gsm High White"
                            class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                        />
                    </div>

                    <!-- PROPERTY + CATEGORY -->
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                    >
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1"
                            >
                                Property / Article No. *
                            </label>

                            <input
                                v-model="addForm.propertyNo"
                                type="text"
                                required
                                placeholder="e.g. MMO-2026-008"
                                class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm font-mono text-purple-900 font-bold focus:outline-none focus:ring-2 focus:ring-purple-500"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1"
                            >
                                Category *
                            </label>

                            <select
                                v-model="addForm.category"
                                required
                                class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                            >
                                <option
                                    v-for="category in categories"
                                    :key="category"
                                    :value="category"
                                >
                                    {{ category }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- UNIT / QTY / REORDER -->
                    <div
                        class="grid grid-cols-1 sm:grid-cols-3 gap-4"
                    >
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1"
                            >
                                Unit of Measure *
                            </label>

                            <select
                                v-model="addForm.unit"
                                required
                                class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                            >
                                <option
                                    v-for="unit in units"
                                    :key="unit"
                                    :value="unit"
                                >
                                    {{ unit }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1"
                            >
                                Initial Qty *
                            </label>

                            <input
                                v-model.number="addForm.quantity"
                                type="number"
                                min="0"
                                required
                                class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1"
                            >
                                Reorder Threshold *
                            </label>

                            <input
                                v-model.number="addForm.reorderLevel"
                                type="number"
                                min="1"
                                required
                                class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                            />
                        </div>
                    </div>

                    <!-- COST -->
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1"
                        >
                            Estimated Unit Cost (₱) *
                        </label>

                        <input
                            v-model.number="addForm.unitCost"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                        />
                    </div>

                    <!-- BUTTONS -->
                    <div
                        class="pt-4 flex justify-end space-x-3 border-t border-slate-100"
                    >
                        <button
                            type="button"
                            @click="closeAddItemModal"
                            class="px-5 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-xs font-bold hover:bg-slate-50 transition-colors"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            :disabled="isSaving"
                            class="px-6 py-2.5 bg-gradient-to-r from-purple-700 to-purple-600 hover:from-purple-600 hover:to-purple-500 text-white rounded-xl text-xs font-bold shadow-md transition-all disabled:opacity-60"
                        >
                            <i class="fa-solid fa-check mr-1.5"></i>
                            {{ isSaving ? 'Saving...' : 'Save & Register Item' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- =========================================================
             UPDATE QUANTITY MODAL
        ========================================================== -->
        <div
            v-if="showUpdateModal && activeUpdateItem"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center"
            @click.self="closeUpdateQtyModal"
        >
            <div
                class="bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-md w-full mx-4 overflow-hidden"
            >
                <div
                    class="bg-gradient-to-r from-purple-900 to-purple-800 px-6 py-4 text-white flex justify-between items-center"
                >
                    <div class="flex items-center space-x-2">
                        <i
                            class="fa-solid fa-boxes-packing text-purple-300 text-lg"
                        ></i>

                        <h3
                            class="font-bold text-base text-white"
                        >
                            Update Item Stock Quantity
                        </h3>
                    </div>

                    <button
                        @click="closeUpdateQtyModal"
                        class="text-purple-200 hover:text-white bg-purple-800/50 hover:bg-purple-700 p-1.5 rounded-xl transition-colors"
                    >
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="p-6">

                    <!-- ITEM PREVIEW -->
                    <div
                        class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 mb-5 flex items-center space-x-4"
                    >
                        <img
                            :src="itemImage(activeUpdateItem)"
                            @error="handleImageError"
                            class="w-16 h-16 object-cover rounded-xl border border-slate-200 bg-white flex-shrink-0"
                            alt="Item Image"
                        />

                        <div class="overflow-hidden">
                            <span
                                class="text-[10px] font-black text-purple-700 uppercase tracking-wider bg-purple-100 px-2 py-0.5 rounded-md inline-block"
                            >
                                {{ activeUpdateItem.propertyNo }}
                            </span>

                            <h4
                                class="font-bold text-slate-800 text-sm mt-1 truncate"
                            >
                                {{ activeUpdateItem.name }}
                            </h4>

                            <div
                                class="flex items-center space-x-3 text-xs text-slate-500 mt-1"
                            >
                                <span>
                                    Unit:
                                    <strong
                                        class="text-slate-700"
                                    >
                                        {{ activeUpdateItem.unit }}
                                    </strong>
                                </span>

                                <span>•</span>

                                <span>
                                    Reorder limit:
                                    <strong
                                        class="text-slate-700"
                                    >
                                        {{ activeUpdateItem.reorderLevel }}
                                    </strong>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- QUANTITY -->
                    <div class="space-y-4">
                        <label
                            class="block text-xs font-extrabold text-slate-600 uppercase tracking-wider text-center"
                        >
                            Set Available Quantity
                        </label>

                        <div
                            class="flex items-center justify-center space-x-2"
                        >
                            <button
                                type="button"
                                @click="adjustModalQty(-10)"
                                class="w-11 h-11 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition-colors shadow-sm active:scale-95"
                            >
                                -10
                            </button>

                            <button
                                type="button"
                                @click="adjustModalQty(-1)"
                                class="w-11 h-11 bg-purple-100 hover:bg-purple-200 text-purple-800 font-bold rounded-xl text-lg transition-colors shadow-sm active:scale-95"
                            >
                                -1
                            </button>

                            <input
                                v-model.number="updateQuantity"
                                type="number"
                                min="0"
                                class="w-24 h-12 text-center text-2xl font-black text-purple-900 bg-white border-2 border-purple-400 rounded-2xl focus:outline-none focus:ring-2 focus:ring-purple-600"
                            />

                            <button
                                type="button"
                                @click="adjustModalQty(1)"
                                class="w-11 h-11 bg-purple-100 hover:bg-purple-200 text-purple-800 font-bold rounded-xl text-lg transition-colors shadow-sm active:scale-95"
                            >
                                +1
                            </button>

                            <button
                                type="button"
                                @click="adjustModalQty(10)"
                                class="w-11 h-11 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition-colors shadow-sm active:scale-95"
                            >
                                +10
                            </button>
                        </div>

                        <!-- MOVEMENT -->
                        <p
                            v-if="Number(updateQuantity) === 0"
                            class="text-xs text-center text-rose-700 bg-rose-50 p-2.5 rounded-xl border border-rose-200 font-bold"
                        >
                            <i
                                class="fa-solid fa-arrow-down-long mr-1"
                            ></i>
                            Stock zero! Item will move to Out of Stock section.
                        </p>

                        <p
                            v-else
                            class="text-xs text-center text-purple-700 bg-purple-50 p-2.5 rounded-xl border border-purple-200 font-bold"
                        >
                            <i
                                class="fa-solid fa-circle-check mr-1"
                            ></i>
                            Stock available! Item will remain in Available section.
                        </p>
                    </div>

                    <!-- BUTTONS -->
                    <div
                        class="mt-6 pt-4 flex justify-end space-x-3 border-t border-slate-100"
                    >
                        <button
                            type="button"
                            @click="closeUpdateQtyModal"
                            class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl text-xs font-bold hover:bg-slate-50 transition-colors"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            @click="saveQtyUpdate"
                            class="px-5 py-2 bg-gradient-to-r from-purple-700 to-purple-600 hover:from-purple-600 hover:to-purple-500 text-white rounded-xl text-xs font-bold shadow-md transition-all"
                        >
                            Apply Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- =========================================================
             EDIT ITEM MODAL
        ========================================================== -->
        <div
            v-if="showEditModal"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center"
            @click.self="closeEditItemModal"
        >
            <div
                class="bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-2xl w-full mx-4 overflow-hidden max-h-[90vh] flex flex-col"
            >

                <!-- HEADER -->
                <div
                    class="bg-gradient-to-r from-purple-900 to-purple-800 px-6 py-4 text-white flex justify-between items-center"
                >
                    <div class="flex items-center space-x-2">
                        <i
                            class="fa-solid fa-pen-to-square text-purple-300 text-lg"
                        ></i>

                        <h3
                            class="font-bold text-base text-white"
                        >
                            Edit Full Item Details
                        </h3>
                    </div>

                    <button
                        @click="closeEditItemModal"
                        class="text-purple-200 hover:text-white bg-purple-800/50 hover:bg-purple-700 p-1.5 rounded-xl transition-colors"
                    >
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- FORM -->
                <form
                    @submit.prevent="handleSaveEditItem"
                    class="p-6 space-y-4 overflow-y-auto flex-1"
                >

                    <!-- IMAGE -->
                    <div
                        class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80"
                    >
                        <label
                            class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-2"
                        >
                            Item Photo / Image
                        </label>

                        <div
                            class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center"
                        >
                            <div class="flex justify-center">
                                <img
                                    :src="editForm.image || DEFAULT_IMAGE"
                                    @error="handleImageError"
                                    class="w-24 h-24 object-cover rounded-2xl border-2 border-purple-200 bg-white shadow-sm"
                                    alt="Edit Preview"
                                />
                            </div>

                            <div
                                class="sm:col-span-2 space-y-3"
                            >
                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-slate-500 mb-1"
                                    >
                                        Change File Image
                                    </label>

                                    <input
                                        type="file"
                                        accept="image/*"
                                        @change="handleImageFileUpload($event, 'edit')"
                                        class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-purple-100 file:text-purple-700 hover:file:bg-purple-200 cursor-pointer"
                                    />
                                </div>

                                <div>
                                    <label
                                        class="block text-[11px] font-bold text-slate-500 mb-1"
                                    >
                                        Or Change Image URL
                                    </label>

                                    <input
                                        type="url"
                                        :value="editForm.image.startsWith('data:') ? '' : editForm.image"
                                        @input="handleImageUrlInput($event.target.value, 'edit')"
                                        placeholder="https://example.com/item.jpg"
                                        class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- NAME -->
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1"
                        >
                            Item Description / Name *
                        </label>

                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                        />
                    </div>

                    <!-- PROPERTY + CATEGORY -->
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                    >
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1"
                            >
                                Property / Article No. *
                            </label>

                            <input
                                v-model="editForm.propertyNo"
                                type="text"
                                required
                                class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm font-mono text-purple-900 font-bold focus:outline-none focus:ring-2 focus:ring-purple-500"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1"
                            >
                                Category *
                            </label>

                            <select
                                v-model="editForm.category"
                                required
                                class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                            >
                                <option
                                    v-for="category in categories"
                                    :key="category"
                                    :value="category"
                                >
                                    {{ category }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- UNIT / QUANTITY / REORDER -->
                    <div
                        class="grid grid-cols-1 sm:grid-cols-3 gap-4"
                    >
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1"
                            >
                                Unit of Measure *
                            </label>

                            <select
                                v-model="editForm.unit"
                                required
                                class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                            >
                                <option
                                    v-for="unit in units"
                                    :key="unit"
                                    :value="unit"
                                >
                                    {{ unit }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1"
                            >
                                Quantity *
                            </label>

                            <input
                                v-model.number="editForm.quantity"
                                type="number"
                                min="0"
                                required
                                class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1"
                            >
                                Reorder Level *
                            </label>

                            <input
                                v-model.number="editForm.reorderLevel"
                                type="number"
                                min="1"
                                required
                                class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                            />
                        </div>
                    </div>

                    <!-- COST -->
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1"
                        >
                            Unit Cost (₱) *
                        </label>

                        <input
                            v-model.number="editForm.unitCost"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                        />
                    </div>

                    <!-- BUTTONS -->
                    <div
                        class="pt-4 flex justify-between items-center border-t border-slate-100"
                    >
                        <button
                            type="button"
                            @click="deleteCurrentItem"
                            class="px-4 py-2 text-rose-600 hover:bg-rose-50 rounded-xl text-xs font-bold transition-colors flex items-center space-x-1"
                        >
                            <i
                                class="fa-solid fa-trash-can"
                            ></i>

                            <span>
                                Delete Item
                            </span>
                        </button>

                        <div class="flex space-x-3">
                            <button
                                type="button"
                                @click="closeEditItemModal"
                                class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl text-xs font-bold hover:bg-slate-50 transition-colors"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="px-5 py-2 bg-gradient-to-r from-purple-700 to-purple-600 hover:from-purple-600 hover:to-purple-500 text-white rounded-xl text-xs font-bold shadow-md transition-all"
                            >
                                Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- =========================================================
             TOASTS
        ========================================================== -->
        <div
            class="fixed bottom-5 right-5 z-[60] flex flex-col space-y-3 pointer-events-none"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto flex items-start p-4 rounded-2xl shadow-xl border bg-white max-w-sm"
                :class="{
                    'border-emerald-200': toast.type === 'success',
                    'border-amber-200': toast.type === 'warning',
                    'border-rose-200': toast.type === 'danger',
                    'border-purple-200': toast.type === 'info',
                }"
            >

                <!-- ICON -->
                <div
                    v-if="toast.type === 'success'"
                    class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold mr-3 flex-shrink-0"
                >
                    <i class="fa-solid fa-check"></i>
                </div>

                <div
                    v-else-if="toast.type === 'warning'"
                    class="w-8 h-8 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center font-bold mr-3 flex-shrink-0"
                >
                    <i
                        class="fa-solid fa-triangle-exclamation"
                    ></i>
                </div>

                <div
                    v-else-if="toast.type === 'danger'"
                    class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center font-bold mr-3 flex-shrink-0"
                >
                    <i class="fa-solid fa-trash-can"></i>
                </div>

                <div
                    v-else
                    class="w-8 h-8 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold mr-3 flex-shrink-0"
                >
                    <i class="fa-solid fa-info"></i>
                </div>

                <!-- MESSAGE -->
                <div class="flex-1 pr-2">
                    <h5
                        class="font-bold text-xs text-slate-800"
                    >
                        {{ toast.title }}
                    </h5>

                    <p
                        class="text-[11px] text-slate-500 mt-0.5"
                    >
                        {{ toast.message }}
                    </p>
                </div>

                <button
                    @click="removeToast(toast.id)"
                    class="text-slate-400 hover:text-slate-600 text-xs"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

    </div>
</template>

<style scoped>
/*
|--------------------------------------------------------------------------
| Custom Scrollbar
|--------------------------------------------------------------------------
*/
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f5f9;
}

::-webkit-scrollbar-thumb {
    background: #c4b5fd;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #7c3aed;
}

/*
|--------------------------------------------------------------------------
| Font
|--------------------------------------------------------------------------
*/
:global(body) {
    font-family: 'Inter', sans-serif;
}
</style>