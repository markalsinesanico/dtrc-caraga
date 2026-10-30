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
                                    class="w-9 h-9 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-file-circle-minus"></i>
                                </div>

                                <div>
                                    <h2 class="text-lg font-bold text-slate-800">
                                        Inventory Request History
                                    </h2>

                                    <p class="text-xs text-slate-500 mt-0.5">
                                        View all inventory requests recorded on the selected date.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="inline-flex items-center self-start lg:self-auto gap-2 bg-white border border-rose-200 text-rose-800 px-3 py-2 rounded-xl shadow-sm"
                        >
                            <i class="fa-solid fa-clipboard-list text-rose-600"></i>

                            <span class="text-sm font-bold">
                                {{ requestCount }}
                                {{ requestCount === 1 ? 'Request' : 'Requests' }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-rose-50/60 border border-rose-100 rounded-xl px-4 py-3"
                    >
                        <div class="flex items-center gap-2 min-w-0">
                            <i class="fa-solid fa-calendar-day text-rose-600"></i>
                            <span class="text-xs font-bold uppercase tracking-wider text-rose-600">
                                Request Date
                            </span>
                            <span class="text-sm font-black text-slate-800 truncate">
                                {{ formattedRequestDate }}
                            </span>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <label
                                for="request-date"
                                class="text-xs font-bold uppercase tracking-wider text-slate-500"
                            >
                                Select Date
                            </label>

                            <div class="relative">
                                <i
                                    class="fa-solid fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-rose-500 text-sm pointer-events-none"
                                ></i>

                                <input
                                    id="request-date"
                                    v-model="selectedRequestDate"
                                    type="date"
                                    class="pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent shadow-sm"
                                />
                            </div>

                            <button
                                type="button"
                                @click="printRequestHistory"
                                :disabled="requestLoading || requestItems.length === 0"
                                title="Print or save request history as PDF"
                                aria-label="Print or save request history as PDF"
                                class="inline-flex items-center justify-center w-11 h-11 rounded-xl bg-rose-600 text-white shadow-sm transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-rose-600"
                            >
                                <i class="fa-solid fa-print text-base"></i>
                            </button>

                            <span
                                v-if="requestCount > 3"
                                class="hidden lg:inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-500"
                            >
                                <i class="fa-solid fa-arrows-up-down text-rose-500"></i>
                                Scroll to view all requests
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    v-if="requestLoading"
                    class="px-6 py-12 text-center"
                >
                    <div
                        class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-rose-100 text-rose-600 mb-3"
                    >
                        <i class="fa-solid fa-spinner fa-spin text-xl"></i>
                    </div>

                    <p class="text-sm font-semibold text-slate-600">
                        Loading inventory requests...
                    </p>
                </div>

                <div
                    v-else-if="requestError"
                    class="px-6 py-10 text-center"
                >
                    <div
                        class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-rose-100 text-rose-600 mb-3"
                    >
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>

                    <p class="text-sm font-semibold text-rose-700">
                        {{ requestError }}
                    </p>
                </div>

                <div
                    v-else-if="requestItems.length === 0"
                    class="px-6 py-12 text-center"
                >
                    <div
                        class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 mb-4"
                    >
                        <i class="fa-solid fa-clipboard-list text-2xl"></i>
                    </div>

                    <h3 class="text-base font-bold text-slate-700">
                        No Inventory Requests
                    </h3>

                    <p class="text-sm text-slate-400 mt-1 max-w-md mx-auto">
                        No inventory requests were recorded on {{ formattedRequestDate }}.
                    </p>
                </div>

                <div
                    v-else
                    class="max-h-[230px] min-h-[205px] overflow-y-auto overflow-x-auto scrollbar-thin scrollbar-thumb-slate-300 scrollbar-track-slate-100"
                >
                    <table class="w-full min-w-[900px] text-left border-collapse">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-slate-50 border-b border-slate-200 shadow-sm">
                                <th class="px-5 sm:px-6 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Time
                                </th>

                                <th class="px-5 sm:px-6 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Requestor / Name
                                </th>

                                <th class="px-5 sm:px-6 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Department / Office
                                </th>

                                <th class="px-5 sm:px-6 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Reference No. / Source
                                </th>

                                <th class="px-5 sm:px-6 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Property Number
                                </th>

                                <th class="px-5 sm:px-6 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Item Name
                                </th>

                                <th class="px-5 sm:px-6 py-3 text-xs font-bold uppercase tracking-wider text-slate-500 text-right">
                                    Requested
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="request in requestItems"
                                :key="request.id"
                                class="hover:bg-rose-50/40 transition-colors"
                            >
                                <td class="px-5 sm:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-100 text-rose-700 border border-rose-200"
                                        >
                                            <i class="fa-solid fa-clock text-xs"></i>
                                        </span>
                                        <span class="text-xs font-bold text-slate-600">
                                            {{ formatRequestTime(request.requestedAt) }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-5 sm:px-6 py-4">
                                    <p class="text-sm font-bold text-slate-800">
                                        {{ request.requestorName }}
                                    </p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        {{ request.requestDate }}
                                    </p>
                                </td>

                                <td class="px-5 sm:px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold"
                                    >
                                        {{ request.departmentOffice }}
                                    </span>
                                </td>

                                <td class="px-5 sm:px-6 py-4">
                                    <span class="text-xs text-slate-600">
                                        {{ request.referenceNoSource || '-' }}
                                    </span>
                                </td>

                                <td class="px-5 sm:px-6 py-4 whitespace-nowrap">
                                    <span class="font-mono text-xs font-bold text-slate-700">
                                        {{ request.propertyNo }}
                                    </span>
                                </td>

                                <td class="px-5 sm:px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 flex items-center justify-center flex-shrink-0"
                                        >
                                            <i class="fa-solid fa-box-open text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-800">
                                                {{ request.itemName }}
                                            </p>
                                            <p class="text-xs text-slate-400 mt-0.5">
                                                {{ request.unit }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 sm:px-6 py-4 text-right whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center justify-center min-w-[48px] px-2.5 py-1 rounded-lg bg-rose-100 text-rose-800 text-sm font-black border border-rose-200"
                                    >
                                        {{ request.requestedQuantity }}
                                    </span>
                                    <span class="ml-1 text-xs text-slate-400">
                                        {{ request.unit }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
</template>
