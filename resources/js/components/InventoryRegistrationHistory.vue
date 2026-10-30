<script setup>
import { toRefs } from 'vue'
const props = defineProps({ state: { type: Object, required: true } })
const {
    activePage,
    DEFAULT_IMAGE,
    categories,
    units,
    departments,
    inventory,
    isSaving,
    expandedDescriptions,
    descriptionKey,
    toggleDescription,
    isDescriptionExpanded,
    logout,
    getLocalDateString,
    selectedRegistrationDate,
    selectedRequestDate,
    registeredItems,
    registrationSearch,
    registrationLoading,
    registrationError,
    search,
    categoryFilter,
    viewMode,
    showAddModal,
    showUpdateModal,
    showEditModal,
    showRequestModal,
    isSubmittingRequest,
    showItemRequestsModal,
    selectedItemForRequests,
    allItemRequests,
    openItemRequestsModal,
    closeItemRequestsModal,
    itemRequestsList,
    printItemStockCard,
    requestForm,
    selectedRequestItemId,
    requestableItems,
    selectedRequestItem,
    addRequestItem,
    removeRequestItem,
    activeEditingId,
    currentModalImageData,
    addForm,
    editForm,
    updateQuantity,
    toasts,
    toastId,
    showToast,
    removeToast,
    sortedInventory,
    filteredInventory,
    availableItems,
    outOfStockItems,
    totalItems,
    lowStockCount,
    outOfStockCount,
    availableCount,
    itemImage,
    printAvailableInventory,
    fromApi,
    toApi,
    fromHistoryApi,
    apiErrorMessage,
    loadInventory,
    loadRegisteredItemsByDate,
    formattedRegistrationDate,
    filteredRegisteredItems,
    registrationCount,
    formattedRequestDate,
    requestItems,
    requestLoading,
    requestError,
    fromRequestApi,
    loadRequestItemsByDate,
    requestCount,
    formatRequestTime,
    escapePrintHtml,
    printRequestHistory,
    csvCell,
    fetchAllRegistrationHistory,
    exportRegistrationHistoryToExcel,
    printRegistrationHistory,
    isLowStock,
    toggleViewMode,
    handleImageFileUpload,
    handleImageUrlInput,
    openAddItemModal,
    closeAddItemModal,
    handleAddItem,
    persistItem,
    quickAdjustQty,
    openRequestModal,
    closeRequestModal,
    submitInventoryRequest,
    openUpdateQtyModal,
    closeUpdateQtyModal,
    adjustModalQty,
    activeUpdateItem,
    saveQtyUpdate,
    openEditItemModal,
    closeEditItemModal,
    handleSaveEditItem,
    deleteCurrentItem,
    handleImageError
} = toRefs(props.state)
</script>

<template>
<section
                class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden"
            >
                <div
                    class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/50"
                >
                    <div
                        class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5"
                    >
                        <div>
                            <div class="flex items-center space-x-2">
                                <div
                                    class="w-9 h-9 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-calendar-days"></i>
                                </div>

                                <div>
                                    <h2 class="text-lg font-bold text-slate-800">
                                        Inventory Registration History
                                    </h2>

                                    <p class="text-xs text-slate-500 mt-0.5">
                                        View all inventory items registered on a specific date.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <label
                                for="registration-date"
                                class="text-xs font-bold uppercase tracking-wider text-slate-500"
                            >
                                Select Date
                            </label>

                            <div class="relative">
                                <i
                                    class="fa-solid fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-purple-500 text-sm pointer-events-none"
                                ></i>

                                <input
                                    id="registration-date"
                                    v-model="selectedRegistrationDate"
                                    type="date"
                                    class="pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-5 sm:px-6 pt-4">
                    <label for="registration-item-search" class="sr-only">
                        Search registration history items
                    </label>
                    <div class="relative max-w-md">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
                        <input
                            id="registration-item-search"
                            v-model="registrationSearch"
                            type="search"
                            placeholder="Search item name, property number, category..."
                            class="w-full pl-9 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        />
                    </div>
                </div>

                <div
                    class="px-5 sm:px-6 py-4 border-b border-slate-100 bg-purple-50/40"
                >
                    <div
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                    >
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-purple-600">
                                Registered Items
                            </p>

                            <h3 class="text-lg sm:text-xl font-black text-slate-800 mt-0.5">
                                Items Registered on {{ formattedRegistrationDate }}
                            </h3>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                            <div
                                class="inline-flex items-center gap-2 bg-white border border-purple-200 text-purple-800 px-3 py-2 rounded-xl shadow-sm"
                            >
                                <i class="fa-solid fa-box-open text-purple-600"></i>
                                <span class="text-sm font-bold">
                                    {{ registrationCount }}
                                    {{ registrationCount === 1 ? 'Item' : 'Items' }}
                                </span>
                            </div>

                            <button
                                type="button"
                                @click="exportRegistrationHistoryToExcel"
                                :disabled="registrationLoading || registeredItems.length === 0"
                                title="Export registration history records from all dates to Excel-compatible CSV"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold shadow-sm hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed"
                            >
                                <i class="fa-solid fa-file-excel"></i>
                                Export Excel
                            </button>

                            <button
                                type="button"
                                @click="printRegistrationHistory"
                                :disabled="registrationLoading || registeredItems.length === 0"
                                title="Print registration history records from all dates"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-purple-600 text-white text-sm font-semibold shadow-sm hover:bg-purple-700 disabled:opacity-40 disabled:cursor-not-allowed"
                            >
                                <i class="fa-solid fa-print"></i>
                                Print
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="registrationLoading"
                    class="px-6 py-12 text-center"
                >
                    <div
                        class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-purple-100 text-purple-600 mb-3"
                    >
                        <i class="fa-solid fa-spinner fa-spin text-xl"></i>
                    </div>

                    <p class="text-sm font-semibold text-slate-600">
                        Loading registered items...
                    </p>
                </div>

                <div
                    v-else-if="registrationError"
                    class="px-6 py-10 text-center"
                >
                    <div
                        class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-rose-100 text-rose-600 mb-3"
                    >
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>

                    <p class="text-sm font-semibold text-rose-700">
                        {{ registrationError }}
                    </p>
                </div>

                <div
                    v-else-if="registeredItems.length === 0"
                    class="px-6 py-12 text-center"
                >
                    <div
                        class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 mb-4"
                    >
                        <i class="fa-solid fa-calendar-xmark text-2xl"></i>
                    </div>

                    <h3 class="text-base font-bold text-slate-700">
                        No Items Registered
                    </h3>

                    <p class="text-sm text-slate-400 mt-1 max-w-md mx-auto">
                        No inventory items were registered on {{ formattedRegistrationDate }}.
                    </p>
                </div>

                <div
                    v-else-if="filteredRegisteredItems.length === 0"
                    class="px-6 py-10 text-center"
                >
                    <i class="fa-solid fa-magnifying-glass text-2xl text-slate-300 mb-3"></i>
                    <p class="text-sm font-semibold text-slate-600">No matching items found</p>
                    <p class="text-xs text-slate-400 mt-1">Try another item name, property number, or category.</p>
                </div>

                <div v-else class="max-h-[230px] overflow-auto scrollbar-thin scrollbar-thumb-slate-300 scrollbar-track-slate-100">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-5 sm:px-6 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Qty
                                </th>

                                <th class="px-5 sm:px-6 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Property Number
                                </th>

                                <th class="px-5 sm:px-6 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Item Name
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="item in filteredRegisteredItems"
                                :key="item.id"
                                class="hover:bg-purple-50/40 transition-colors"
                            >
                                <td class="px-5 sm:px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center justify-center min-w-[42px] px-2.5 py-1 rounded-lg bg-purple-100 text-purple-800 text-sm font-black border border-purple-200"
                                    >
                                        {{ item.registeredQty }}
                                    </span>

                                    <span class="ml-2 text-xs text-slate-400">
                                        {{ item.unit }}
                                    </span>
                                </td>

                                <td class="px-5 sm:px-6 py-4 whitespace-nowrap">
                                    <span class="font-mono text-sm font-bold text-slate-700">
                                        {{ item.propertyNo }}
                                    </span>
                                </td>

                                <td class="px-5 sm:px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img
                                            :src="itemImage(item)"
                                            :alt="item.name"
                                            @error="handleImageError"
                                            class="w-10 h-10 rounded-lg object-cover border border-slate-200 bg-slate-50"
                                        />

                                        <div>
                                            <p class="text-sm font-bold text-slate-800">
                                                {{ item.name }}
                                            </p>

                                            <p class="text-xs text-slate-400 mt-0.5">
                                                {{ item.category }}
                                            </p>
                                            <p class="text-xs text-purple-700 font-semibold mt-1">
                                                IAR No.: {{ item.iarNumber || 'Not set' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
</template>
