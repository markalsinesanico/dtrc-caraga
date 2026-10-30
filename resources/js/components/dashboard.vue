Dashboard.vue
Dashboard.vue

<script setup>
import { computed, onMounted, onBeforeUnmount, reactive, ref, watch } from 'vue'
import InventoryRegistrationHistory from './InventoryRegistrationHistory.vue'
import InventoryRequestHistory from './InventoryRequestHistory.vue'
import ItemRequestRanking from './ItemRequestRanking.vue'
import axios from 'axios'
import logo from '../assets/logo.jpg'

const activePage = ref('dashboard')

function navigateTo(page) {
    const allowedPages = ['dashboard', 'registration-history', 'request-history', 'item-request-ranking']
    if (!allowedPages.includes(page)) return
    activePage.value = page
    const nextHash = `#${page}`
    if (window.location.hash !== nextHash) window.location.hash = nextHash
}

function syncPageFromHash() {
    const page = window.location.hash.replace(/^#/, '')
    const allowedPages = ['dashboard', 'registration-history', 'request-history', 'item-request-ranking']
    activePage.value = allowedPages.includes(page) ? page : 'dashboard'
}

onMounted(() => {
    syncPageFromHash()
    window.addEventListener('hashchange', syncPageFromHash)
})
onBeforeUnmount(() => window.removeEventListener('hashchange', syncPageFromHash))

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

/*
|--------------------------------------------------------------------------
| Inventory
|--------------------------------------------------------------------------
*/
const inventory = ref([])
const isSaving = ref(false)

// Items selected for printing multiple Stock Cards in one PDF.
const selectedStockCardIds = ref([])

const selectedStockCardItems = computed(() =>
    selectedStockCardIds.value
        .map((id) => inventory.value.find((item) => String(item.id) === String(id)))
        .filter(Boolean),
)

function toggleStockCardSelection(item, checked) {
    const id = String(item.id)
    if (checked) {
        if (!selectedStockCardIds.value.some((selectedId) => String(selectedId) === id)) {
            selectedStockCardIds.value.push(item.id)
        }
    } else {
        selectedStockCardIds.value = selectedStockCardIds.value.filter(
            (selectedId) => String(selectedId) !== id,
        )
    }
}

function clearStockCardSelection() {
    selectedStockCardIds.value = []
}

// Track kung aling description ang nakabukas
const expandedDescriptions = ref({})

function descriptionKey(item) {
    return String(item.id ?? item.propertyNo ?? item.name)
}

function toggleDescription(item) {
    const key = descriptionKey(item)

    expandedDescriptions.value[key] =
        !expandedDescriptions.value[key]
}

function isDescriptionExpanded(item) {
    return Boolean(
        expandedDescriptions.value[descriptionKey(item)]
    )
}


async function logout() {
    try {
        await axios.get('/sanctum/csrf-cookie', {
            headers: {
                Accept: 'application/json',
            },
            withCredentials: true,
            withXSRFToken: true,
        });

        await axios.post(
            '/logout',
            {},
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                withCredentials: true,
                withXSRFToken: true,
            },
        );

        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');

        window.location.href = '/login';
    } catch (error) {
        console.error(
            'Logout failed:',
            error.response?.status,
            error.response?.data || error.message,
        );

        if (error.response?.status === 419) {
            alert(
                'Your session or CSRF token has expired. Refresh the page, log in again, and retry.',
            );
        } else {
            alert(
                error.response?.data?.message ||
                'Logout failed. Please try again.',
            );
        }
    }
}

/*
|--------------------------------------------------------------------------
| Inventory Registration History
|--------------------------------------------------------------------------
*/
function getLocalDateString(date = new Date()) {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')

    return `${year}-${month}-${day}`
}

const selectedRegistrationDate = ref(getLocalDateString())
const selectedRequestDate = ref(getLocalDateString())
const registeredItems = ref([])
const registrationSearch = ref('')
const registrationLoading = ref(false)
const registrationError = ref('')

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

const showRequestModal = ref(false)
const isSubmittingRequest = ref(false)

// State para sa Item Requests Detail Modal
const showItemRequestsModal = ref(false)
const selectedItemForRequests = ref(null)
const allItemRequests = ref([])

async function openItemRequestsModal(item) {
    selectedItemForRequests.value = item
    showItemRequestsModal.value = true
    
    try {
        const { data } = await axios.get('/api/inventory/request-history')
        allItemRequests.value = (data || []).map(fromRequestApi)
    } catch (error) {
        console.error('Failed to load all item requests:', error)
        allItemRequests.value = requestItems.value
    }
}

function closeItemRequestsModal() {
    showItemRequestsModal.value = false
    selectedItemForRequests.value = null
    allItemRequests.value = []
}

const itemRequestsList = computed(() => {
    if (!selectedItemForRequests.value) return []
    
    const sourceList = allItemRequests.value.length > 0 ? allItemRequests.value : requestItems.value

    return sourceList.filter(
        (req) => req.inventoryId === selectedItemForRequests.value.id || 
                 String(req.propertyNo).trim().toLowerCase() === String(selectedItemForRequests.value.propertyNo).trim().toLowerCase() ||
                 String(req.itemName).trim().toLowerCase() === String(selectedItemForRequests.value.name).trim().toLowerCase()
    )
})

/*
|--------------------------------------------------------------------------
| Print Stock Card Function (Katulad ng ibinigay na template)
|--------------------------------------------------------------------------
*/
function printItemStockCard() {
    const item = selectedItemForRequests.value
    if (!item) return

    const requests = itemRequestsList.value

    const rows = requests.length > 0 ? requests.map(req => {
        const qtyStr = Number(req.requestedQuantity || 0).toFixed(2)
        return `
            <tr>
                <td>${escapePrintHtml(req.requestDate || '-')}</td>
                <td>${escapePrintHtml(req.referenceNoSource || '-')}</td>
                <td class="text-center">${escapePrintHtml(qtyStr)}</td>
                <td class="text-center">${escapePrintHtml(qtyStr)}</td>
                <td>${escapePrintHtml(req.departmentOffice || '-')}</td>
                <td class="text-center">${escapePrintHtml(Number(item.quantity).toFixed(2))}</td>
                <td class="text-center">Procured</td>
            </tr>
        `
    }).join('') : `
        <tr>
            <td colspan="7" class="text-center" style="color: #64748b; padding: 12px;">No requests recorded for this item yet.</td>
        </tr>
    `

    const printWindow = window.open('', '_blank', 'width=1100,height=850')
    if (!printWindow) {
        window.alert('Please allow pop-ups for this site to print the stock card.')
        return
    }

    printWindow.document.open()
    printWindow.document.write(`<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Stock Card - ${escapePrintHtml(item.name)}</title>
<style>
@page { size: A4 landscape; margin: 8mm; }
body { font-family: Arial, Helvetica, sans-serif; color: #000; font-size: 10px; margin: 0; background: #fff; }
.card-container { width: 100%; border: 1.5px solid #000; box-sizing: border-box; }
.header-table, .info-table, .data-table { width: 100%; border-collapse: collapse; }
.header-table td, .info-table td, .data-table th, .data-table td { border: 1px solid #000; padding: 4px 6px; vertical-align: middle; }
.logo-cell { width: 100px; text-align: center; background: #fff; }
.logo-cell img { width: 55px; height: 55px; object-fit: contain; }
.title-cell { text-align: center; font-size: 15px; font-weight: bold; background: #fff; letter-spacing: 0.5px; }
.stock-card-title { text-align: center; font-size: 13px; font-weight: bold; background: #2563eb; color: #fff; text-transform: uppercase; padding: 4px; }
.doc-info-cell { width: 220px; font-size: 9px; padding: 0 !important; }
.doc-info-subtable { width: 100%; border-collapse: collapse; }
.doc-info-subtable td { border-bottom: 1px solid #000; padding: 2px 4px; }
.doc-info-subtable tr:last-child td { border-bottom: none; }
.appendix { text-align: right; font-style: italic; font-size: 11px; font-weight: bold; padding: 4px 0; }
.data-table th { background: #f8fafc; font-size: 9px; text-align: center; font-weight: bold; text-transform: uppercase; }
.text-center { text-align: center; }
.text-right { text-align: right; }
.blue-text { color: #1d4ed8; font-weight: bold; }
.green-text { color: #15803d; font-weight: bold; }
</style>
</head>
<body>
<div class="card-container">
    <table class="header-table">
        <tr>
            <td class="logo-cell" rowspan="2">
                <img src="${logo}" alt="Logo" />
            </td>
            <td class="title-cell">PROPERTY MANAGEMENT SECTION</td>
            <td class="doc-info-cell" rowspan="2">
                <table class="doc-info-subtable">
                    <tr><td>Doc. Code : PMS-15</td></tr>
                    <tr><td>Effectivity Date : August 9, 2022</td></tr>
                    <tr><td>Revision No.: 0</td></tr>
                    <tr><td>Page No. : 1</td></tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="stock-card-title">STOCK CARD</td>
        </tr>
    </table>

    <div class="appendix">Appendix 58</div>

    <table class="info-table" style="border-top: 1.5px solid #000; border-bottom: 1.5px solid #000;">
        <tr>
            <td style="width: 13%; font-weight: bold;">Entity Name:</td>
            <td style="width: 65%;">Drug Treatment and Rehabilitation Center Caraga</td>
            <td style="width: 10%; font-weight: bold;">Fund Cluster:</td>
            <td style="width: 12%;"></td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td style="width: 15%; font-weight: bold;">Item / Product Name:</td>
            <td style="width: 60%;" class="blue-text">${escapePrintHtml(item.name)}</td>
            <td style="width: 12%; font-weight: bold;">Stock No.:</td>
            <td style="width: 13%; background: #fef08a;" class="blue-text text-center">${escapePrintHtml(item.propertyNo)}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Description / Location:</td>
            <td class="green-text">${escapePrintHtml(item.name)} | Location: Warehouse</td>
            <td style="font-weight: bold;">Re-order Point:</td>
            <td class="text-center">${escapePrintHtml(item.reorderLevel)}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Unit of Measurement:</td>
            <td class="green-text">${escapePrintHtml(item.unit)}</td>
            <td style="font-weight: bold;">Opening Balance</td>
            <td class="text-center font-weight-bold">0</td>
        </tr>
    </table>

    <table class="data-table" style="margin-top: -1px;">
        <thead>
            <tr>
                <th rowspan="2" style="width: 11%;">Date</th>
                <th rowspan="2" style="width: 32%;">Reference No. / Source</th>
                <th colspan="2" style="width: 22%;">Procured</th>
                <th rowspan="2" style="width: 18%;">Section</th>
                <th style="width: 9%;">Balance</th>
                <th rowspan="2" style="width: 8%;">Transaction Type</th>
            </tr>
            <tr>
                <th style="width: 11%;">Qty.</th>
                <th style="width: 11%;">Qty.</th>
                <th style="width: 9%;">Qty.</th>
            </tr>
        </thead>
        <tbody>
            ${rows}
        </tbody>
    </table>
</div>
</body>
</html>`)
    printWindow.document.close()
    printWindow.focus()
    setTimeout(() => printWindow.print(), 300)
}

/*
|--------------------------------------------------------------------------
| Print Multiple Stock Cards
| Each selected item gets its own A4 portrait page in the same print/PDF job.
|--------------------------------------------------------------------------
*/
async function printSelectedStockCards() {
    const items = selectedStockCardItems.value
    if (items.length < 2) {
        window.alert('Please select at least two items to print multiple Stock Cards.')
        return
    }

    let allRequests = []
    try {
        const { data } = await axios.get('/api/inventory/request-history')
        allRequests = (data || []).map(fromRequestApi)
    } catch (error) {
        console.error('Could not load request history for Stock Cards:', error)
        window.alert(apiErrorMessage(error, 'Unable to load request history for the selected Stock Cards.'))
        return
    }

    const printWindow = window.open('', '_blank', 'width=1100,height=850')
    if (!printWindow) {
        window.alert('Please allow pop-ups for this site to print the selected Stock Cards.')
        return
    }

    const pages = items.map((item) => {
        const requests = allRequests.filter((req) =>
            String(req.inventoryId) === String(item.id) ||
            String(req.propertyNo || '').trim().toLowerCase() === String(item.propertyNo || '').trim().toLowerCase() ||
            String(req.itemName || '').trim().toLowerCase() === String(item.name || '').trim().toLowerCase()
        )

        const rows = requests.length
            ? requests.map((req) => {
                const qty = Number(req.requestedQuantity || 0).toFixed(2)
                return `
                    <tr>
                        <td>${escapePrintHtml(req.requestDate || '-')}</td>
                        <td>${escapePrintHtml(req.referenceNoSource || '-')}</td>
                        <td class="center">${escapePrintHtml(qty)}</td>
                        <td class="center">${escapePrintHtml(qty)}</td>
                        <td>${escapePrintHtml(req.departmentOffice || '-')}</td>
                        <td class="center">${escapePrintHtml(Number(item.quantity || 0).toFixed(2))}</td>
                        <td class="center">Procured</td>
                    </tr>`
            }).join('')
            : `<tr><td colspan="7" class="center no-records">No requests recorded for this item yet.</td></tr>`

        return `
        <section class="card-page">
            <table class="header-table">
                <tr>
                    <td class="logo-cell" rowspan="2"><img src="${logo}" alt="Logo"></td>
                    <td class="title-cell">PROPERTY MANAGEMENT SECTION</td>
                    <td class="doc-info-cell" rowspan="2">
                        <table class="doc-info-subtable">
                            <tr><td>Doc. Code : PMS-15</td></tr>
                            <tr><td>Effectivity Date : August 9, 2022</td></tr>
                            <tr><td>Revision No.: 0</td></tr>
                            <tr><td>Page No. : 1</td></tr>
                        </table>
                    </td>
                </tr>
                <tr><td class="stock-card-title">STOCK CARD</td></tr>
            </table>
            <div class="appendix">Appendix 58</div>
            <table class="info-table">
                <tr>
                    <td class="label">Entity Name:</td><td colspan="1">Drug Treatment and Rehabilitation Center Caraga</td>
                    <td class="label">Fund Cluster:</td><td></td>
                </tr>
            </table>
            <table class="info-table">
                <tr>
                    <td class="label">Item / Product Name:</td><td class="blue">${escapePrintHtml(item.name)}</td>
                    <td class="label">Stock No.:</td><td class="yellow center">${escapePrintHtml(item.propertyNo)}</td>
                </tr>
                <tr>
                    <td class="label">Description / Location:</td><td class="green">${escapePrintHtml(item.description || item.name)} | Location: Warehouse</td>
                    <td class="label">Re-order Point:</td><td class="center">${escapePrintHtml(item.reorderLevel ?? 1)}</td>
                </tr>
                <tr>
                    <td class="label">Unit of Measurement:</td><td class="green">${escapePrintHtml(item.unit || '-')}</td>
                    <td class="label">Opening Balance</td><td class="center"><strong>0</strong></td>
                </tr>
            </table>
            <table class="data-table">
                <thead>
                    <tr><th rowspan="2">Date</th><th rowspan="2">Reference No. / Source</th><th colspan="2">Procured</th><th rowspan="2">Section</th><th>Balance</th><th rowspan="2">Transaction Type</th></tr>
                    <tr><th>Qty.</th><th>Qty.</th><th>Qty.</th></tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </section>`
    }).join('')

    printWindow.document.open()
    printWindow.document.write(`<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Selected Inventory Stock Cards</title>
<style>
    /* A4 portrait bond paper: 210mm x 297mm */
    @page { size: A4 portrait; margin: 8mm; }
    * { box-sizing: border-box; }
    html, body { width: 100%; margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; color: #000; font-size: 9px; }
    @media print {
        html, body { width: auto; height: auto; }
        .card-page { width: 100%; }
    }
    .card-page { width: 100%; border: 1.5px solid #000; page-break-after: always; break-after: page; }
    .card-page:last-child { page-break-after: auto; break-after: auto; }
    table { width: 100%; border-collapse: collapse; }
    .header-table td, .info-table td, .data-table th, .data-table td { border: 1px solid #000; padding: 4px 6px; vertical-align: middle; }
    .logo-cell { width: 100px; text-align: center; }
    .logo-cell img { width: 55px; height: 55px; object-fit: contain; }
    .title-cell { text-align: center; font-size: 15px; font-weight: bold; letter-spacing: .5px; }
    .stock-card-title { text-align: center; font-size: 13px; font-weight: bold; background: #2563eb; color: #000; }
    .doc-info-cell { width: 220px; font-size: 9px; padding: 0 !important; }
    .doc-info-subtable td { padding: 2px 4px; border-bottom: 1px solid #000; }
    .doc-info-subtable tr:last-child td { border-bottom: none; }
    .appendix { text-align: right; font-style: italic; font-size: 11px; font-weight: bold; padding: 4px 0; }
    .label { width: 15%; font-weight: bold; }
    .blue { color: #1d4ed8; font-weight: bold; }
    .green { color: #15803d; font-weight: bold; }
    .yellow { background: #fef08a; color: #1d4ed8; font-weight: bold; }
    .data-table { margin-top: -1px; }
    .data-table th { background: #f8fafc; font-size: 9px; text-align: center; text-transform: uppercase; }
    .center { text-align: center; }
    .no-records { color: #64748b; padding: 12px; }
    @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
</style>
</head>
<body>${pages}</body>
</html>`)
    printWindow.document.close()
    printWindow.focus()
    setTimeout(() => printWindow.print(), 400)
}

const requestForm = ref({
    requestorName: '',
    departmentOffice: '',
    referenceNoSource: '',
    items: [],
    requestDate: getLocalDateString(),
})

// Item to add into the current multi-item request form.
const selectedRequestItemId = ref('')

const requestableItems = computed(() =>
    inventory.value.filter((item) => Number(item.quantity) > 0)
)

const selectedRequestItem = computed(() =>
    inventory.value.find((item) => String(item.id) === String(selectedRequestItemId.value)) || null
)

function addRequestItem() {
    const item = selectedRequestItem.value

    if (!item) {
        showToast('Select an Item', 'Please choose an item to add to this request.', 'warning')
        return
    }

    if (Number(item.quantity) < 1) {
        showToast('Out of Stock', `"${item.name}" is currently out of stock.`, 'danger')
        return
    }

    const alreadyAdded = requestForm.value.items.some(
        (row) => String(row.itemId) === String(item.id),
    )

    if (alreadyAdded) {
        showToast('Item Already Added', `"${item.name}" is already in the request list. Change its quantity in the list below.`, 'warning')
        return
    }

    requestForm.value.items.push({
        itemId: item.id,
        itemName: item.name,
        propertyNo: item.propertyNo,
        unit: item.unit,
        quantity: 1,
    })

    selectedRequestItemId.value = ''
}

function removeRequestItem(index) {
    requestForm.value.items.splice(index, 1)
}

const activeEditingId = ref(null)
const currentModalImageData = ref('')

/*
|--------------------------------------------------------------------------
| Form Data
|--------------------------------------------------------------------------
*/
const addForm = ref({
    name: '',
    description: '',
    iarNumber: '',
    propertyNo: '',
    category: 'Office Supplies',
    unit: 'ream',
    quantity: 10,
    reorderLevel: 5,
    image: '',
})

const editForm = ref({
    id: '',
    name: '',
    description: '',
    propertyNo: '',
    category: 'Office Supplies',
    unit: 'ream',
    quantity: 0,
    reorderLevel: 1,
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
        const itemName = String(item.name || '').toLowerCase()
        const propertyNo = String(item.propertyNo || '').toLowerCase()

        const matchesSearch =
            itemName.includes(searchValue) ||
            propertyNo.includes(searchValue)

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

const lowStockCount = computed(() =>
    inventory.value.filter(
        (item) =>
            item.quantity > 0 &&
            item.quantity <= item.reorderLevel,
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
function itemImage(item) {
    return item?.image || DEFAULT_IMAGE
}

function printAvailableInventory() {
    // Use the complete inventory array, not availableItems, so zero-stock items are included.
    const items = [...inventory.value].sort((a, b) => a.name.localeCompare(b.name))
    if (!items.length) {
        window.alert('There are no inventory items to print.')
        return
    }

    const rows = items.map(item => `
        <tr>
            <td>${escapePrintHtml(item.name || '-')}</td>
            <td>${escapePrintHtml(item.description || '-')}</td>
            <td>${escapePrintHtml(item.propertyNo || '-')}</td>
            <td>${escapePrintHtml(item.unit || '-')}</td>
            <td class="number">${Math.max(0, Number(item.quantity) || 0)}</td>
        </tr>
    `).join('')

    const printWindow = window.open('', '_blank', 'width=1100,height=800')
    if (!printWindow) {
        window.alert('Please allow pop-ups for this site to print the inventory report.')
        return
    }

    printWindow.document.open()
    printWindow.document.write(`<!DOCTYPE html>
    <html lang="en"><head><meta charset="UTF-8"><title>Complete Inventory Stock Report</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #1e293b; font-size: 10px; }
        h1 { margin: 0 0 5px; font-size: 20px; }
        .subtitle { margin: 0 0 16px; color: #64748b; }
        .summary { margin-bottom: 12px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #cbd5e1; padding: 7px; text-align: left; vertical-align: top; overflow-wrap: anywhere; }
        th { background: #f1f5f9; text-transform: uppercase; font-size: 9px; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        th:nth-child(1) { width: 20%; } th:nth-child(2) { width: 40%; }
        th:nth-child(3) { width: 16%; } th:nth-child(4) { width: 10%; }
        th:nth-child(5) { width: 14%; }
        .number { text-align: center; font-weight: bold; }
        @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
    </style></head><body>
    <h1>Complete Inventory Stock Report</h1>
    <p class="subtitle">All registered inventory items, including out-of-stock items</p>
    <p class="summary"><strong>Total items:</strong> ${items.length} &nbsp; | &nbsp; <strong>Generated:</strong> ${escapePrintHtml(new Date().toLocaleString('en-PH'))}</p>
    <table><thead><tr><th>Name of Item</th><th>Description of Item</th><th>Stock Number</th><th>Unit of Measure</th><th>Current Available Stock</th></tr></thead>
    <tbody>${rows}</tbody></table></body></html>`)
    printWindow.document.close()
    printWindow.focus()
    setTimeout(() => printWindow.print(), 300)
}

/*
|--------------------------------------------------------------------------
| Current Inventory API Mapper
|--------------------------------------------------------------------------
*/
function fromApi(item) {
    return {
        id: item.id,
        propertyNo: item.property_no,
        iarNumber: item.iar_number || '',
        name: item.name,
        description: item.description || '',
        category: item.category,
        unit: item.unit,
        quantity: Number(item.quantity) || 0,
        reorderLevel: Number(item.reorder_level) || 1,
        unitCost: Number(item.unit_cost) || 0,
        image: item.image || DEFAULT_IMAGE,
        createdAt: item.created_at || null,
    }
}

function toApi(item) {
    return {
        property_no: item.propertyNo,
        iar_number: item.iarNumber || null,
        name: item.name,
        description: item.description || '',
        category: item.category,
        unit: item.unit,
        quantity: Number(item.quantity) || 0,
        reorder_level: Number(item.reorderLevel) || 1,
        unit_cost: Number(item.unitCost) || 0,
        image: item.image || null,
    }
}

/*
|--------------------------------------------------------------------------
| Historical Registration API Mapper
|--------------------------------------------------------------------------
*/
function fromHistoryApi(item) {
    return {
        id: item.id,
        propertyNo: item.property_no,
        iarNumber: item.iar_number || '',
        name: item.name,
        description: item.description || '',
        category: item.category,
        unit: item.unit,
        quantity: Number(item.registered_qty) || 0,
        registeredQty: Number(item.registered_qty) || 0,
        reorderLevel: Number(item.reorder_level) || 1,
        unitCost: Number(item.unit_cost) || 0,
        image: item.image || DEFAULT_IMAGE,
        registeredDate: item.registered_date || null,
        registeredAt: item.registered_at || item.created_at || null,
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

/*
|--------------------------------------------------------------------------
| Load Current Inventory
|--------------------------------------------------------------------------
*/
async function loadInventory() {
    try {
        const { data } = await axios.get('/api/inventory')

        inventory.value = (data || []).map(fromApi)

        await loadRegisteredItemsByDate(
            selectedRegistrationDate.value,
        )

        await loadRequestItemsByDate(
            selectedRequestDate.value,
        )
    } catch (error) {
        showToast(
            'Load Failed',
            apiErrorMessage(
                error,
                'Could not load inventory from the database.',
            ),
            'danger',
        )
    }
}

async function loadRegisteredItemsByDate(
    date = selectedRegistrationDate.value,
) {
    if (date !== null && !date) {
        registeredItems.value = []
        return
    }

    registrationLoading.value = true
    registrationError.value = ''

    try {
        const { data } = await axios.get(
            '/api/inventory/registration-history',
            {
                params: date ? { date } : {},
            },
        )

        registeredItems.value = (data || [])
            .map(fromHistoryApi)
            .sort((a, b) => {
                const first =
                    new Date(a.registeredAt || 0).getTime()

                const second =
                    new Date(b.registeredAt || 0).getTime()

                return first - second
            })
    } catch (error) {
        console.error(
            'Failed to load registration history:',
            error,
        )

        registeredItems.value = []

        registrationError.value = apiErrorMessage(
            error,
            'Unable to load registration history.',
        )
    } finally {
        registrationLoading.value = false
    }
}

const formattedRegistrationDate = computed(() => {
    if (!selectedRegistrationDate.value) return ''

    const [year, month, day] =
        selectedRegistrationDate.value.split('-')

    const date = new Date(
        Number(year),
        Number(month) - 1,
        Number(day),
    )

    return date.toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    })
})

const filteredRegisteredItems = computed(() => {
    const term = registrationSearch.value.trim().toLowerCase()

    if (!term) return registeredItems.value

    return registeredItems.value.filter((item) =>
        [item.name, item.propertyNo, item.category, item.unit]
            .some((value) => String(value ?? '').toLowerCase().includes(term)),
    )
})

const registrationCount = computed(
    () => filteredRegisteredItems.value.length,
)

const formattedRequestDate = computed(() => {
    if (!selectedRequestDate.value) return ''

    const [year, month, day] = selectedRequestDate.value.split('-')

    const date = new Date(
        Number(year),
        Number(month) - 1,
        Number(day),
    )

    return date.toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    })
})

/*
|--------------------------------------------------------------------------
| Inventory Request History
|--------------------------------------------------------------------------
*/
const requestItems = ref([])
const requestLoading = ref(false)
const requestError = ref('')

function fromRequestApi(item) {
    return {
        id: item.id,
        requestorName: item.requestor_name,
        departmentOffice: item.department_office,
        referenceNoSource: item.reference_no_source || '',
        inventoryId: item.inventory_id,
        propertyNo: item.property_no || '-',
        itemName: item.item_name,
        unit: item.unit,
        requestedQuantity: Number(item.requested_quantity) || 0,
        requestDate: item.request_date || null,
        requestedAt: item.requested_at || item.created_at || null,
    }
}

async function loadRequestItemsByDate(
    date = selectedRequestDate.value,
) {
    if (!date) {
        requestItems.value = []
        return
    }

    requestLoading.value = true
    requestError.value = ''

    try {
        const { data } = await axios.get(
            '/api/inventory/request-history',
            {
                params: {
                    date,
                },
            },
        )

        requestItems.value = (data || [])
            .map(fromRequestApi)
            .sort((a, b) => {
                const first =
                    new Date(a.requestedAt || 0).getTime()

                const second =
                    new Date(b.requestedAt || 0).getTime()

                return second - first
            })
    } catch (error) {
        console.error(
            'Failed to load inventory request history:',
            error,
        )

        requestItems.value = []

        requestError.value = apiErrorMessage(
            error,
            'Unable to load inventory request history.',
        )
    } finally {
        requestLoading.value = false
    }
}

const requestCount = computed(
    () => requestItems.value.length,
)

function formatRequestTime(value) {
    if (!value) return '-'

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) return '-'

    return date.toLocaleTimeString('en-PH', {
        hour: 'numeric',
        minute: '2-digit',
    })
}

function escapePrintHtml(value) {
    return String(value ?? '-')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#039;')
}

async function printRequestHistory() {
    requestError.value = ''

    try {
        const { data } = await axios.get(
            '/api/inventory/request-history',
        )

        const allRequests = (data || [])
            .map(fromRequestApi)
            .sort((a, b) => {
                const first =
                    new Date(a.requestedAt || 0).getTime()

                const second =
                    new Date(b.requestedAt || 0).getTime()

                return second - first
            })

        if (allRequests.length === 0) {
            window.alert('There are no inventory request records to print.')
            return
        }

        const rows = allRequests
            .map((request, index) => {
                return `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${escapePrintHtml(request.id)}</td>
                        <td>${escapePrintHtml(request.inventoryId ?? '-')}</td>
                        <td>${escapePrintHtml(formatRequestTime(request.requestedAt))}</td>
                        <td>${escapePrintHtml(request.requestorName)}</td>
                        <td>${escapePrintHtml(request.departmentOffice)}</td>
                        <td>${escapePrintHtml(request.referenceNoSource || '-')}</td>
                        <td>${escapePrintHtml(request.propertyNo)}</td>
                        <td>${escapePrintHtml(request.itemName)}</td>
                        <td>${escapePrintHtml(request.unit)}</td>
                        <td>${escapePrintHtml(request.requestedQuantity)}</td>
                        <td>${escapePrintHtml(request.requestDate || '-')}</td>
                    </tr>
                `
            })
            .join('')

        const generatedAt = new Date().toLocaleString('en-PH', {
            dateStyle: 'long',
            timeStyle: 'short',
        })

        const printWindow = window.open(
            '',
            '_blank',
            'width=1400,height=900',
        )

        if (!printWindow) {
            window.alert(
                'Please allow pop-ups for this site to print the request history.',
            )
            return
        }

        printWindow.document.open()
        printWindow.document.write(`<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Complete Inventory Request History</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #1e293b;
            background: #fff;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e11d48;
        }

        .title {
            margin: 0;
            font-size: 20px;
            color: #0f172a;
        }

        .subtitle {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 11px;
        }

        .meta {
            text-align: right;
            color: #475569;
            font-size: 9px;
            line-height: 1.6;
        }

        .summary {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }

        .summary-card {
            min-width: 150px;
            padding: 8px 11px;
            border: 1px solid #fecdd3;
            border-radius: 7px;
            background: #fff1f2;
        }

        .summary-label {
            display: block;
            margin-bottom: 2px;
            color: #be123c;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .summary-value {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid #cbd5e1;
            padding: 5px 4px;
            vertical-align: top;
            word-break: break-word;
        }

        th {
            background: #f1f5f9;
            color: #334155;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        td {
            font-size: 8px;
        }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .footer {
            margin-top: 14px;
            padding-top: 7px;
            border-top: 1px solid #cbd5e1;
            color: #64748b;
            font-size: 8px;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1 class="title">Complete Inventory Request History</h1>
            <p class="subtitle">All inventory request records from the system</p>
        </div>
        <div class="meta">
            <div><strong>Total Records:</strong> ${allRequests.length}</div>
            <div><strong>Generated:</strong> ${escapePrintHtml(generatedAt)}</div>
        </div>
    </div>

    <div class="summary">
        <div class="summary-card">
            <span class="summary-label">Total Requests</span>
            <span class="summary-value">${allRequests.length}</span>
        </div>
        <div class="summary-card">
            <span class="summary-label">Report Scope</span>
            <span class="summary-value">All Records</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 3%;">#</th>
                <th style="width: 5%;">Request ID</th>
                <th style="width: 6%;">Inventory ID</th>
                <th style="width: 7%;">Time</th>
                <th style="width: 11%;">Requestor / Name</th>
                <th style="width: 12%;">Department / Office</th>
                <th style="width: 12%;">Reference No. / Source</th>
                <th style="width: 9%;">Property Number</th>
                <th style="width: 17%;">Item Name</th>
                <th style="width: 6%;">Unit</th>
                <th style="width: 7%;">Requested Qty.</th>
                <th style="width: 14%;">Request Date</th>
            </tr>
        </thead>
        <tbody>${rows}</tbody>
    </table>

    <div class="footer">
        Inventory Request History • This document contains every inventory request record currently stored in the system.
    </div>
</body>
</html>`)
        printWindow.document.close()
        printWindow.focus()

        setTimeout(() => {
            printWindow.print()
        }, 300)
    } catch (error) {
        console.error(
            'Failed to load complete inventory request history for printing:',
            error,
        )

        window.alert(
            apiErrorMessage(
                error,
                'Unable to load all inventory request records for printing.',
            ),
        )
    }
}


function csvCell(value) {
    const text = String(value ?? '')
    return `"${text.replace(/"/g, '""')}"`
}

async function fetchAllRegistrationHistory() {
    const { data } = await axios.get(
        '/api/inventory/registration-history',
    )

    return (data || [])
        .map(fromHistoryApi)
        .sort((a, b) => {
            const first = new Date(a.registeredAt || 0).getTime()
            const second = new Date(b.registeredAt || 0).getTime()
            return first - second
        })
}


async function exportRegistrationHistoryToExcel() {
    try {
        const allRegisteredItems = await fetchAllRegistrationHistory()

        if (!allRegisteredItems.length) {
            window.alert('There are no inventory registration records to export.')
            return
        }

        const headers = [
            'Registration ID',
            'Registration Date',
            'Registered At',
            'Property Number',
            'Item Name',
            'Category',
            'Unit',
            'Registered Quantity',
            'Reorder Level',
        ]

        const rows = allRegisteredItems.map((item) => [
            item.id,
            item.registeredDate || '',
            item.registeredAt || '',
            item.propertyNo,
            item.name,
            item.category,
            item.unit,
            item.registeredQty,
            item.reorderLevel,
        ])

        const csv = '\uFEFF' + [headers, ...rows]
            .map((row) => row.map(csvCell).join(','))
            .join('\r\n')
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
        const url = URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = 'inventory-registration-history-all-dates.csv'
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        URL.revokeObjectURL(url)
    } catch (error) {
        console.error('Failed to export all registration history records:', error)
        window.alert(apiErrorMessage(error, 'Unable to export all inventory registration records.'))
    }
}

async function printRegistrationHistory() {
    let allRegisteredItems

    try {
        allRegisteredItems = await fetchAllRegistrationHistory()
    } catch (error) {
        console.error('Failed to load all registration history records for printing:', error)
        window.alert(apiErrorMessage(error, 'Unable to load all inventory registration records for printing.'))
        return
    }

    if (!allRegisteredItems.length) {
        window.alert('There are no inventory registration records to print.')
        return
    }

    const rows = allRegisteredItems.map((item, index) => `
        <tr>
            <td>${index + 1}</td>
            <td>${escapePrintHtml(item.id)}</td>
            <td>${escapePrintHtml(item.registeredDate || '-')}</td>
            <td>${escapePrintHtml(item.registeredAt || '-')}</td>
            <td>${escapePrintHtml(item.propertyNo)}</td>
            <td>${escapePrintHtml(item.name)}</td>
            <td>${escapePrintHtml(item.category)}</td>
            <td>${escapePrintHtml(item.unit)}</td>
            <td>${escapePrintHtml(item.registeredQty)}</td>
        </tr>
    `).join('')

    const printWindow = window.open('', '_blank', 'width=1400,height=900')
    if (!printWindow) {
        window.alert('Please allow pop-ups for this site to print registration history.')
        return
    }

    printWindow.document.open()
    printWindow.document.write(`<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Inventory Registration History — All Dates</title>
<style>
@page { size: A4 landscape; margin: 10mm; }
body { font-family: Arial, Helvetica, sans-serif; color: #1e293b; font-size: 10px; }
h1 { font-size: 20px; margin-bottom: 4px; }
p { color: #64748b; margin-top: 0; }
table { width: 100%; border-collapse: collapse; table-layout: auto; }
th, td { border: 1px solid #cbd5e1; padding: 6px; text-align: left; overflow-wrap: anywhere; }
th { background: #f1f5f9; text-transform: uppercase; font-size: 8px; }
tr:nth-child(even) { background: #f8fafc; }
</style>
</head>
<body>
<h1>Inventory Registration History — All Dates</h1>
<p>Total records across all registration dates: ${allRegisteredItems.length}</p>
<table>
<thead><tr><th>#</th><th>Registration ID</th><th>Registration Date</th><th>Registered At</th><th>Property Number</th><th>Item Name</th><th>Category</th><th>Unit</th><th>Registered Qty</th></tr></thead>
<tbody>${rows}</tbody>
</table>
</body>
</html>`)
    printWindow.document.close()
    printWindow.focus()
    setTimeout(() => printWindow.print(), 300)
}

watch(selectedRegistrationDate, (newDate) => {
    if (registrationSearch.value.trim()) {
        loadRegisteredItemsByDate(null)
    } else {
        loadRegisteredItemsByDate(newDate)
    }
})

watch(registrationSearch, (value) => {
    if (value.trim()) {
        loadRegisteredItemsByDate(null)
    } else {
        loadRegisteredItemsByDate(selectedRegistrationDate.value)
    }
})

watch(selectedRequestDate, (newDate) => {
    loadRequestItemsByDate(newDate)
})

onMounted(() => {
    loadInventory()
    loadRegisteredItemsByDate(selectedRegistrationDate.value)
})

function isLowStock(item) {
    return (
        item.quantity > 0 &&
        item.quantity <= item.reorderLevel
    )
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
        description: '',
        iarNumber: '',
        propertyNo: '',
        category: 'Office Supplies',
        unit: 'ream',
        quantity: 10,
        reorderLevel: 5,
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

    const quantity = Number(addForm.value.quantity)

    if (Number.isNaN(quantity) || quantity < 0) {
        showToast(
            'Invalid Quantity',
            'Please enter a valid quantity.',
            'danger',
        )
        return
    }

    const payload = toApi({
        name: addForm.value.name.trim(),
        description: addForm.value.description.trim(),
        iarNumber: addForm.value.iarNumber.trim(),
        propertyNo: addForm.value.propertyNo.trim(),
        category: addForm.value.category,
        unit: addForm.value.unit,
        quantity,
        reorderLevel:
            Number(addForm.value.reorderLevel) || 5,
        image: addForm.value.image || '',
    })

    isSaving.value = true

    try {
        const { data } = await axios.post(
            '/api/inventory',
            payload,
        )

        const newItem = fromApi(data.data)

        inventory.value.push(newItem)

        selectedRegistrationDate.value = getLocalDateString()
        await loadRegisteredItemsByDate(null)

        closeAddItemModal()

        showToast(
            'Item Registered',
            `Successfully added "${data.data.name}" to inventory with an original quantity of ${quantity}.`,
            'success',
        )
    } catch (error) {
        showToast(
            'Save Failed',
            apiErrorMessage(
                error,
                'Could not save the item to the database.',
            ),
            'danger',
        )
    } finally {
        isSaving.value = false
    }
}

async function persistItem(item) {
    const { data } = await axios.put(
        `/api/inventory/${item.id}`,
        toApi(item),
    )

    const mapped = fromApi(data.data)

    const index = inventory.value.findIndex(
        (row) => row.id === item.id,
    )

    if (index !== -1) {
        inventory.value[index] = mapped
    }

    return mapped
}

async function quickAdjustQty(id, change) {
    const item = inventory.value.find(
        (item) => item.id === id,
    )

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
            apiErrorMessage(
                error,
                'Could not update quantity in the database.',
            ),
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
            `Updated current stock for "${item.name}" to ${newQty}.`,
            'info',
        )
    }
}

function openRequestModal(id) {
    const item = inventory.value.find((row) => row.id === id)

    if (!item) return
    if (Number(item.quantity) < 1) {
        showToast('Out of Stock', `"${item.name}" is currently out of stock.`, 'warning')
        return
    }

    requestForm.value = {
        requestorName: '',
        departmentOffice: '',
        referenceNoSource: '',
        items: [{
            itemId: item.id,
            itemName: item.name,
            propertyNo: item.propertyNo,
            unit: item.unit,
            quantity: 1,
        }],
        requestDate: getLocalDateString(),
    }
    selectedRequestItemId.value = ''
    showRequestModal.value = true
}

function closeRequestModal() {
    if (isSubmittingRequest.value) return
    showRequestModal.value = false
}

async function submitInventoryRequest() {
    if (isSubmittingRequest.value) return

    const requestorName = requestForm.value.requestorName.trim()
    const departmentOffice = requestForm.value.departmentOffice
    const referenceNoSource = requestForm.value.referenceNoSource.trim() || null
    const requestDate = requestForm.value.requestDate
    const requestedItems = requestForm.value.items.map((row) => ({
        ...row,
        quantity: Number(row.quantity),
    }))

    if (!requestorName) {
        showToast('Requestor Required', 'Please enter the requestor/name.', 'danger')
        return
    }
    if (!departmentOffice) {
        showToast('Department Required', 'Please select the department/office.', 'danger')
        return
    }
    if (!requestDate) {
        showToast('Date Required', 'Please select the request date.', 'danger')
        return
    }
    if (!requestedItems.length) {
        showToast('No Items Selected', 'Add at least one inventory item to the request.', 'danger')
        return
    }

    // Validate every item and quantity before sending any request.
    for (const requested of requestedItems) {
        const item = inventory.value.find((row) => String(row.id) === String(requested.itemId))

        if (!item) {
            showToast('Item Not Found', 'One of the selected items no longer exists in inventory. Please reopen the form.', 'danger')
            return
        }
        if (!Number.isInteger(requested.quantity) || requested.quantity < 1) {
            showToast('Invalid Quantity', `Enter a whole quantity greater than zero for "${item.name}".`, 'danger')
            return
        }
        if (requested.quantity > Number(item.quantity)) {
            showToast('Insufficient Stock', `Only ${item.quantity} ${item.unit}${item.quantity === 1 ? '' : 's'} of "${item.name}" are available.`, 'danger')
            return
        }
    }

    isSubmittingRequest.value = true
    let savedCount = 0

    try {
        // Keep one database request row per item. This preserves the existing
        // item-specific request history and requestor-name filtering.
        for (const requested of requestedItems) {
            const item = inventory.value.find((row) => String(row.id) === String(requested.itemId))
            const { data } = await axios.post('/api/inventory/requests', {
                requestor_name: requestorName,
                department_office: departmentOffice,
                reference_no_source: referenceNoSource,
                inventory_id: item.id,
                quantity: requested.quantity,
                request_date: requestDate,
            })

            const updatedInventory = fromApi(data.data.inventory)
            const index = inventory.value.findIndex((row) => String(row.id) === String(updatedInventory.id))
            if (index !== -1) inventory.value[index] = updatedInventory
            savedCount += 1
        }

        await loadRequestItemsByDate(selectedRequestDate.value)
        showRequestModal.value = false
        requestForm.value.items = []

        showToast(
            'Request Recorded',
            `Successfully recorded ${savedCount} item${savedCount === 1 ? '' : 's'} for ${departmentOffice}. Each item has its own request history under ${requestorName}.`,
            'success',
        )
    } catch (error) {
        await loadInventory()
        await loadRequestItemsByDate(selectedRequestDate.value)

        if (savedCount > 0) {
            showToast(
                'Partially Recorded',
                `${savedCount} item(s) were saved before an error occurred. Check Inventory Request History before retrying to avoid duplicate requests. ${apiErrorMessage(error, 'Please check the remaining items.')}`,
                'danger',
            )
        } else {
            showToast('Request Failed', apiErrorMessage(error, 'Could not record the inventory request.'), 'danger')
        }
    } finally {
        isSubmittingRequest.value = false
    }
}

function openUpdateQtyModal(id) {
    const item = inventory.value.find(
        (item) => item.id === id,
    )

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

    if (
        Number.isNaN(newQty) ||
        newQty < 0
    ) {
        return
    }

    const previousQty = item.quantity

    item.quantity = newQty
    isSaving.value = true

    try {
        const saved = await persistItem(item)

        closeUpdateQtyModal()

        if (
            previousQty === 0 &&
            saved.quantity > 0
        ) {
            showToast(
                'Item Restocked!',
                `"${saved.name}" has been replenished and moved back to Available Stock.`,
                'success',
            )
        } else if (
            saved.quantity === 0
        ) {
            showToast(
                'Item Depleted',
                `"${saved.name}" quantity set to 0 and moved to Out of Stock.`,
                'warning',
            )
        } else {
            showToast(
                'Stock Saved',
                `Updated current quantity for "${saved.name}" to ${saved.quantity}. Registration history remains unchanged.`,
                'info',
            )
        }
    } catch (error) {
        item.quantity = previousQty

        showToast(
            'Update Failed',
            apiErrorMessage(
                error,
                'Could not update the quantity in the database.',
            ),
            'danger',
        )
    } finally {
        isSaving.value = false
    }
}

function openEditItemModal(id) {
    const item = inventory.value.find(
        (item) => item.id === id,
    )

    if (!item) return

    activeEditingId.value = id

    editForm.value = {
        id: item.id,
        name: item.name,
        description: item.description || '',
        propertyNo: item.propertyNo,
        category: item.category,
        unit: item.unit,
        quantity: item.quantity,
        reorderLevel: item.reorderLevel,
        image: item.image || '',
    }

    currentModalImageData.value =
        item.image || ''

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
    item.description = editForm.value.description.trim()
    item.propertyNo =
        editForm.value.propertyNo.trim()
    item.category = editForm.value.category
    item.unit = editForm.value.unit
    item.quantity =
        Number(editForm.value.quantity) || 0
    item.reorderLevel =
        Number(editForm.value.reorderLevel) || 1

    if (editForm.value.image) {
        item.image = editForm.value.image
    }

    isSaving.value = true

    try {
        const saved = await persistItem(item)

        closeEditItemModal()

        showToast(
            'Changes Saved',
            `Updated current details for "${saved.name}". Original registration history remains unchanged.`,
            'success',
        )
    } catch (error) {
        Object.assign(item, snapshot)

        showToast(
            'Update Failed',
            apiErrorMessage(
                error,
                'Could not update the item in the database.',
            ),
            'danger',
        )
    } finally {
        isSaving.value = false
    }
}

async function deleteCurrentItem() {
    const id = activeEditingId.value

    if (!id || isSaving.value) return

    const item = inventory.value.find(
        (item) => item.id === id,
    )

    if (!item) return

    const itemName = item.name

    isSaving.value = true

    try {
        await axios.delete(`/api/inventory/${id}`)

        inventory.value = inventory.value.filter(
            (row) => row.id !== id,
        )

        await loadRegisteredItemsByDate(
            selectedRegistrationDate.value,
        )

        closeEditItemModal()

        showToast(
            'Item Deleted',
            `Removed "${itemName}" from current inventory. Registration history was preserved.`,
            'danger',
        )
    } catch (error) {
        showToast(
            'Delete Failed',
            apiErrorMessage(
                error,
                'Could not delete the item from the database.',
            ),
            'danger',
        )
    } finally {
        isSaving.value = false
    }
}

function handleImageError(event) {
    event.target.src = DEFAULT_IMAGE
}


// Shared state/functions for the two separate history components.
const pageState = reactive({
    navigateTo,
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
    handleImageError,
})

</script>

<template>
    <div class="h-screen overflow-hidden bg-slate-50 font-sans text-slate-800 antialiased flex flex-col">

        <!-- =========================================================
             HEADER
        ========================================================== -->
        <header
            class="text-white shadow-xl shrink-0 z-30 border-b border-purple-800"
            style="
                background: rgba(76, 29, 149, 0.95);
                backdrop-filter: blur(8px);
            "
        >
            <div class="w-full px-4 sm:px-6 lg:px-8">
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
                                Material Management Section
                            </h1>

                            <p class="text-xs text-purple-200 hidden sm:block">
                                Official Supplies, Equipment & Logistics Inventory Control System
                            </p>
                        </div>
                    </div>


                </div>
            </div>

            <div
                class="h-1 bg-gradient-to-r from-purple-400 via-purple-300 to-purple-600"
            ></div>
            <div class="md:hidden bg-purple-950 px-3 py-2 flex gap-2 overflow-x-auto">
                <button type="button" @click="navigateTo('dashboard')" class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold text-white" :class="activePage === 'dashboard' ? 'bg-purple-700' : 'bg-purple-900'">Dashboard</button>
                <button type="button" @click="navigateTo('registration-history')" class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold text-white" :class="activePage === 'registration-history' ? 'bg-purple-700' : 'bg-purple-900'">Registration History</button>
                <button type="button" @click="navigateTo('request-history')" class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold text-white" :class="activePage === 'request-history' ? 'bg-purple-700' : 'bg-purple-900'">Request History</button>
                <button type="button" @click="navigateTo('item-request-ranking')" class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold text-white" :class="activePage === 'item-request-ranking' ? 'bg-purple-700' : 'bg-purple-900'">Item Request Ranking</button>
                <button type="button" @click="logout" class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold text-white bg-rose-700 hover:bg-rose-800"><i class="fa-solid fa-right-from-bracket mr-1"></i>Logout</button>
            </div>
        </header>

        <!-- =========================================================
             MAIN
        ========================================================== -->
        <div class="flex flex-1 min-w-0 min-h-0 overflow-hidden">
            <aside class="w-64 h-full shrink-0 bg-white border-r border-slate-200 shadow-sm hidden md:flex md:flex-col">
                <div class="px-5 py-6 border-b border-slate-100 flex items-center gap-3">
                    <img :src="logo" alt="DTRC Caraga Logo" class="w-12 h-12 rounded-full object-cover border border-purple-200" />
                    <div class="min-w-0"><p class="font-black text-sm text-purple-900">DTRC-Caraga</p><p class="text-xs text-slate-500">Inventory System</p></div>
                </div>
                <nav class="p-3 space-y-2 flex-1">
                    <button type="button" @click="navigateTo('dashboard')" :class="activePage === 'dashboard' ? 'bg-purple-100 text-purple-900 border-purple-200' : 'text-slate-600 border-transparent hover:bg-slate-50'" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border text-left font-semibold text-sm transition-colors"><i class="fa-solid fa-boxes-stacked w-5 text-center"></i><span>Dashboard</span></button>
                    <button type="button" @click="navigateTo('registration-history')" :class="activePage === 'registration-history' ? 'bg-purple-100 text-purple-900 border-purple-200' : 'text-slate-600 border-transparent hover:bg-slate-50'" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border text-left font-semibold text-sm transition-colors"><i class="fa-solid fa-calendar-days w-5 text-center"></i><span>Inventory Registration History</span></button>
                    <button type="button" @click="navigateTo('request-history')" :class="activePage === 'request-history' ? 'bg-purple-100 text-purple-900 border-purple-200' : 'text-slate-600 border-transparent hover:bg-slate-50'" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border text-left font-semibold text-sm transition-colors"><i class="fa-solid fa-file-circle-minus w-5 text-center"></i><span>Inventory Request History</span></button>
                    <button type="button" @click="navigateTo('item-request-ranking')" :class="activePage === 'item-request-ranking' ? 'bg-purple-100 text-purple-900 border-purple-200' : 'text-slate-600 border-transparent hover:bg-slate-50'" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border text-left font-semibold text-sm transition-colors"><i class="fa-solid fa-chart-column w-5 text-center"></i><span>Item Request Ranking</span></button>
                </nav>
                <div class="mt-auto p-3 border-t border-slate-100">
                    <button type="button" @click="logout" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-left font-semibold text-sm text-rose-700 hover:bg-rose-50 border border-transparent hover:border-rose-100 transition-colors"><i class="fa-solid fa-right-from-bracket w-5 text-center"></i><span>Logout</span></button>
                </div>
            </aside>
            <main class="min-w-0 min-h-0 h-full flex-1 overflow-y-auto overscroll-contain max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

            <template v-if="activePage === 'dashboard'">
<!-- =====================================================
                 AVAILABLE INVENTORY
            ====================================================== -->
            <section
                class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden"
            >
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

                    <div
                        class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3"
                    >
                       
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

                        <button
                            v-if="selectedStockCardIds.length >= 2"
                            type="button"
                            @click="printSelectedStockCards"
                            class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-xl bg-purple-700 hover:bg-purple-800 text-white text-xs font-bold shadow-sm transition-colors"
                            title="Print selected Stock Cards as a multi-page PDF"
                        >
                            <i class="fa-solid fa-print"></i>
                            <span>Print {{ selectedStockCardIds.length }} Stock Cards</span>
                        </button>

                        <button
                            type="button"
                            @click="printAvailableInventory"
                            class="inline-flex items-center justify-center gap-2 px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold shadow-sm transition-colors"
                            title="Print or save all inventory as PDF"
                        >
                            <i class="fa-solid fa-print"></i>
                            <span>Print / PDF</span>
                        </button>
 <button
                            type="button"
                            @click="openAddItemModal"
                            class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-purple-600 to-purple-500 hover:from-purple-500 hover:to-purple-400 text-white font-semibold px-4 py-2.5 rounded-xl transition-all duration-200 shadow-lg shadow-purple-900/20 focus:ring-2 focus:ring-purple-300 active:scale-95"
                        >
                            <i class="fa-solid fa-plus-circle text-lg"></i>
                            <span>Add New Item</span>
                        </button>
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

                <!-- TABLE VIEW -->
                <div
                    v-if="viewMode === 'table'"
                    class="max-h-[310px] overflow-auto scrollbar-thin scrollbar-thumb-slate-300 scrollbar-track-slate-100"
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
                                <td class="py-3 px-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <input
                                            type="checkbox"
                                            :checked="selectedStockCardIds.some(id => String(id) === String(item.id))"
                                            @change="toggleStockCardSelection(item, $event.target.checked)"
                                            @click.stop
                                            class="w-4 h-4 accent-purple-700 cursor-pointer"
                                            :aria-label="`Select Stock Card for ${item.name}`"
                                            :title="`Select ${item.name} for multi-page Stock Card PDF`"
                                        />
                                        <img
                                            :src="itemImage(item)"
                                            @error="handleImageError"
                                            @click="openItemRequestsModal(item)"
                                            class="w-12 h-12 object-cover rounded-xl border border-slate-200 shadow-sm bg-white cursor-pointer hover:opacity-85 transition-opacity"
                                            alt="Thumbnail"
                                            title="I-click para makita ang mga nag-request"
                                        />
                                    </div>
                                </td>

                                <td
                                    class="py-3 px-4 font-mono text-xs font-black text-purple-900"
                                >
                                    {{ item.propertyNo }}
                                </td>

                                <td class="py-3 px-4">
                                    <div
                                        @click="openItemRequestsModal(item)"
                                        class="font-bold text-slate-800 text-sm cursor-pointer hover:text-purple-700 transition-colors inline-block"
                                        title="I-click para makita ang mga nag-request"
                                    >
                                        {{ item.name }}
                                    </div>
                                    <div
    v-if="item.description"
    class="mt-1 max-w-sm flex items-start gap-2"
>
    <span
        class="text-xs text-slate-500 min-w-0 flex-1"
        :class="
            isDescriptionExpanded(item)
                ? 'whitespace-pre-wrap break-words'
                : 'block truncate'
        "
    >
        {{ item.description }}
    </span>

    <button
        v-if="item.description.length > 50"
        type="button"
        @click.stop="toggleDescription(item)"
        class="text-xs font-semibold text-purple-700 hover:text-purple-900 whitespace-nowrap"
    >
        {{ isDescriptionExpanded(item) ? 'See less' : 'See more' }}
    </button>
</div>
                                    <p class="text-xs text-purple-700 font-semibold mt-1">
                                        IAR No.: {{ item.iarNumber || 'Not set' }}
                                    </p>

                                </td>

                                <td class="py-3 px-4">
                                    <span
                                        class="inline-block px-2.5 py-1 text-xs font-semibold bg-slate-100 text-slate-600 rounded-lg border border-slate-200"
                                    >
                                        {{ item.category }}
                                    </span>
                                </td>

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

                                <td
                                    class="py-3 px-4 text-right whitespace-nowrap"
                                >
                                    <button
                                        @click="quickAdjustQty(item.id, 1)"
                                        title="Quick Add +1"
                                        class="p-1.5 text-slate-600 hover:text-purple-700 hover:bg-purple-100/70 rounded-lg transition-colors"
                                    >
                                        <i
                                            class="fa-solid fa-plus text-xs"
                                        ></i>
                                    </button>

                                    <button
                                        @click="openRequestModal(item.id)"
                                        title="Request Item"
                                        class="p-1.5 text-slate-600 hover:text-rose-700 hover:bg-rose-100/70 rounded-lg transition-colors"
                                    >
                                        <i
                                            class="fa-solid fa-minus text-xs"
                                        ></i>
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

                <!-- GRID VIEW -->
                <div
                    v-if="
                        viewMode === 'grid' &&
                        availableItems.length
                    "
                    class="max-h-[420px] overflow-y-auto p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 scrollbar-thin scrollbar-thumb-slate-300 scrollbar-track-slate-100"
                >
                    <div
                        v-for="item in availableItems"
                        :key="item.id"
                        class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 hover:shadow-md transition-shadow relative flex flex-col justify-between"
                    >
                        <div>
                            <div
                                @click="openItemRequestsModal(item)"
                                class="relative h-36 rounded-xl overflow-hidden bg-slate-100 border border-slate-200/60 mb-3 cursor-pointer group"
                                title="I-click para makita ang mga nag-request"
                            >
                                <img
                                    :src="itemImage(item)"
                                    @error="handleImageError"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                                    alt="Card Image"
                                />

                                <span
                                    class="absolute top-2 left-2 font-mono text-[10px] font-black bg-purple-900/90 text-white px-2 py-0.5 rounded-md backdrop-blur-sm"
                                >
                                    {{ item.propertyNo }}
                                </span>
                                <label
                                    class="absolute top-2 right-2 flex items-center justify-center w-7 h-7 rounded-md bg-white/95 shadow cursor-pointer"
                                    :title="`Select ${item.name} for multi-page Stock Card PDF`"
                                    @click.stop
                                >
                                    <input
                                        type="checkbox"
                                        :checked="selectedStockCardIds.some(id => String(id) === String(item.id))"
                                        @change="toggleStockCardSelection(item, $event.target.checked)"
                                        class="w-4 h-4 accent-purple-700 cursor-pointer"
                                        :aria-label="`Select Stock Card for ${item.name}`"
                                    />
                                </label>

                                <div
                                    class="absolute bottom-2 right-2"
                                >
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
                                @click="openItemRequestsModal(item)"
                                class="font-bold text-slate-800 text-base mt-0.5 cursor-pointer hover:text-purple-700 transition-colors"
                                title="I-click para makita ang mga nag-request"
                            >
                                {{ item.name }}
                            </h4>
                            <p class="text-xs text-purple-700 font-semibold mt-1">
                                IAR No.: {{ item.iarNumber || 'Not set' }}
                            </p>

                            <div
                                class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100 text-xs"
                            >
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
                    class="max-h-[310px] overflow-auto scrollbar-thin scrollbar-thumb-slate-300 scrollbar-track-slate-100"
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
                                        @click="openItemRequestsModal(item)"
                                        class="w-12 h-12 object-cover rounded-xl border border-rose-200 mx-auto shadow-sm bg-white opacity-90 cursor-pointer hover:opacity-100 transition-opacity"
                                        alt="Out of Stock Thumbnail"
                                        title="I-click para makita ang mga nag-request"
                                    />
                                </td>

                                <td
                                    class="py-3 px-4 font-mono text-xs font-black text-rose-900"
                                >
                                    {{ item.propertyNo }}
                                </td>

                                <td class="py-3 px-4">
                                    <div 
                                        @click="openItemRequestsModal(item)"
                                        class="font-bold text-slate-800 text-sm cursor-pointer hover:text-rose-700 transition-colors inline-block"
                                        title="I-click para makita ang mga nag-request"
                                    >
                                        {{ item.name }}
                                    </div>
                                    <p v-if="item.description" class="text-xs text-slate-500 mt-1 max-w-sm whitespace-normal">{{ item.description }}</p>
                                    <p class="text-xs text-purple-700 font-semibold mt-1">
                                        IAR No.: {{ item.iarNumber || 'Not set' }}
                                    </p>
                                </td>

                                <td class="py-3 px-4">
                                    <span
                                        class="inline-block px-2.5 py-1 text-xs font-semibold bg-rose-50 text-rose-700 rounded-lg border border-rose-100"
                                    >
                                        {{ item.category }}
                                    </span>
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
</template>

            <InventoryRegistrationHistory v-else-if="activePage === 'registration-history'" :state="pageState" />
            <InventoryRequestHistory v-else-if="activePage === 'request-history'" :state="pageState" />
            <ItemRequestRanking v-else-if="activePage === 'item-request-ranking'" />




            </main>
        </div>

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
                        Materials Management Section 
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

                <form
                    @submit.prevent="handleAddItem"
                    class="p-6 space-y-5 overflow-y-auto flex-1"
                >
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

                    <div>
                        <label
                            class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1"
                        >
                            Item Name *
                        </label>

                        <input
                            v-model="addForm.name"
                            type="text"
                            required
                            placeholder="e.g. Bond Paper"
                            class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                        />
                        <p class="text-[11px] text-slate-400 mt-1">Short, recognizable name of the inventory item.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Description</label>
                        <textarea
                            v-model="addForm.description"
                            rows="3"
                            maxlength="2000"
                            placeholder="e.g. A4 size, 80gsm, high-white multipurpose bond paper"
                            class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500 resize-y"
                        ></textarea>
                        <p class="text-[11px] text-slate-400 mt-1">Add size, type, specifications, or other details. This will be saved in the database.</p>
                    </div>

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
                                IAR Number
                            </label>

                            <input
                                v-model="addForm.iarNumber"
                                type="text"
                                placeholder="e.g. IAR-2026-001"
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
                            {{
                                isSaving
                                    ? 'Saving...'
                                    : 'Save & Register Item'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- =========================================================
             MULTI-ITEM INVENTORY REQUEST MODAL
        ========================================================== -->
        <div
            v-if="showRequestModal"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-3"
            @click.self="closeRequestModal"
        >
            <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-2xl w-full mx-4 overflow-hidden max-h-[92vh] flex flex-col">
                <div class="bg-gradient-to-r from-rose-700 to-rose-600 px-6 py-4 text-white flex justify-between items-center">
                    <div class="flex items-center space-x-2">
                        <i class="fa-solid fa-file-circle-minus text-rose-200 text-lg"></i>
                        <div>
                            <h3 class="font-bold text-base text-white">Inventory Request</h3>
                            <p class="text-[11px] text-rose-100 mt-0.5">Request several inventory items in one form.</p>
                        </div>
                    </div>
                    <button type="button" @click="closeRequestModal" :disabled="isSubmittingRequest" class="text-rose-100 hover:text-white bg-rose-800/40 hover:bg-rose-800/70 p-1.5 rounded-xl transition-colors disabled:opacity-50">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form @submit.prevent="submitInventoryRequest" class="p-6 space-y-4 overflow-y-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Requestor / Name *</label>
                            <input v-model="requestForm.requestorName" type="text" required autocomplete="name" placeholder="e.g. Juan Dela Cruz" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Department / Office *</label>
                            <select v-model="requestForm.departmentOffice" required class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500">
                                <option value="" disabled>Select department / office</option>
                                <option v-for="department in departments" :key="department" :value="department">{{ department }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Reference No. / Source</label>
                            <input v-model="requestForm.referenceNoSource" type="text" placeholder="e.g. RIS No. / Office Memo / Source" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Request Date *</label>
                            <input v-model="requestForm.requestDate" type="date" required class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-rose-500" />
                        </div>
                    </div>

                    <div class="rounded-2xl border border-rose-100 bg-rose-50 p-4 space-y-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-rose-700">Add another item</p>
                            <p class="text-xs text-slate-600 mt-1">Pumili ng item at idagdag sa listahan. Maaari kang mag-request ng maraming item sa iisang form.</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <select v-model="selectedRequestItemId" class="flex-1 min-w-0 px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500">
                                <option value="">Select an available item...</option>
                                <option v-for="item in requestableItems" :key="item.id" :value="String(item.id)">
                                    {{ item.name }} — {{ item.quantity }} {{ item.unit }} available
                                </option>
                            </select>
                            <button type="button" @click="addRequestItem" :disabled="!selectedRequestItemId || isSubmittingRequest" class="px-4 py-2.5 rounded-xl bg-rose-700 hover:bg-rose-600 text-white text-xs font-bold disabled:opacity-50">
                                <i class="fa-solid fa-plus mr-1"></i> Add Item
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <h4 class="text-sm font-black text-slate-800">Items to Request</h4>
                            <span class="text-xs font-bold text-rose-700 bg-rose-50 border border-rose-100 rounded-full px-3 py-1">{{ requestForm.items.length }} item(s)</span>
                        </div>

                        <div v-if="requestForm.items.length === 0" class="text-center border border-dashed border-slate-300 rounded-xl p-6 text-sm text-slate-500">
                            Wala pang item sa request. Pumili ng item sa itaas at i-click ang Add Item.
                        </div>

                        <div v-for="(requested, index) in requestForm.items" :key="requested.itemId" class="border border-slate-200 rounded-2xl p-3 flex flex-col sm:flex-row sm:items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-800 break-words">{{ requested.itemName }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">Stock No.: {{ requested.propertyNo || '-' }} · Available: {{ inventory.find((row) => String(row.id) === String(requested.itemId))?.quantity ?? 0 }} {{ requested.unit }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Quantity</label>
                                    <input v-model.number="requested.quantity" type="number" min="1" :max="inventory.find((row) => String(row.id) === String(requested.itemId))?.quantity ?? 1" required class="w-24 px-3 py-2 border border-slate-200 rounded-lg text-sm font-black text-slate-800 focus:outline-none focus:ring-2 focus:ring-rose-500" />
                                </div>
                                <button type="button" @click="removeRequestItem(index)" :disabled="isSubmittingRequest" title="Remove item" class="mt-4 p-2 rounded-lg text-rose-600 hover:bg-rose-50 disabled:opacity-50">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-3.5 py-3 text-xs font-semibold">
                        <i class="fa-solid fa-circle-info mr-1"></i>
                        Ang bawat item ay ise-save bilang sariling request record, gamit ang parehong requestor name, department, reference at date. Ibabawas ang bawat quantity sa stock ng katumbas na item.
                    </div>

                    <div class="pt-4 flex justify-end space-x-3 border-t border-slate-100">
                        <button type="button" @click="closeRequestModal" :disabled="isSubmittingRequest" class="px-5 py-2.5 border border-slate-200 text-slate-600 rounded-xl text-xs font-bold hover:bg-slate-50 transition-colors disabled:opacity-50">Cancel</button>
                        <button type="submit" :disabled="isSubmittingRequest || requestForm.items.length === 0" class="px-6 py-2.5 bg-gradient-to-r from-rose-700 to-rose-600 hover:from-rose-600 hover:to-rose-500 text-white rounded-xl text-xs font-bold shadow-md transition-all disabled:opacity-60">
                            <i class="fa-solid fa-paper-plane mr-1.5"></i>
                            {{ isSubmittingRequest ? 'Recording...' : `Submit ${requestForm.items.length} Item(s)` }}
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
                                    <strong class="text-slate-700">
                                        {{ activeUpdateItem.unit }}
                                    </strong>
                                </span>

                                <span>•</span>

                                <span>
                                    Reorder limit:
                                    <strong class="text-slate-700">
                                        {{ activeUpdateItem.reorderLevel }}
                                    </strong>
                                </span>
                            </div>
                        </div>
                    </div>

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

                <form
                    @submit.prevent="handleSaveEditItem"
                    class="p-6 space-y-4 overflow-y-auto flex-1"
                >
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

                    <div>
                        <label
                            class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1"
                        >
                            Item Name *
                        </label>

                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Description</label>
                        <textarea
                            v-model="editForm.description"
                            rows="3"
                            maxlength="2000"
                            placeholder="Size, type, specifications, or other details"
                            class="w-full px-3.5 py-2 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-500 resize-y"
                        ></textarea>
                    </div>

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

                    <div
                        class="pt-4 flex justify-between items-center border-t border-slate-100"
                    >
                        <button
                            type="button"
                            @click="deleteCurrentItem"
                            class="px-4 py-2 text-rose-600 hover:bg-rose-50 rounded-xl text-xs font-bold transition-colors flex items-center space-x-1"
                        >
                            <i class="fa-solid fa-trash-can"></i>

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
             ITEM REQUEST HISTORY DETAILS MODAL (MAY PRINT STOCK CARD BUTTON NA)
        ========================================================== -->
        <div
            v-if="showItemRequestsModal && selectedItemForRequests"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
            @click.self="closeItemRequestsModal"
        >
            <div
                class="bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-2xl w-full overflow-hidden max-h-[90vh] flex flex-col"
            >
                <!-- Modal Header -->
                <div
                    class="bg-gradient-to-r from-purple-900 to-purple-800 px-6 py-4 text-white flex justify-between items-center"
                >
                    <div class="flex items-center space-x-3">
                        <img
                            :src="itemImage(selectedItemForRequests)"
                            @error="handleImageError"
                            class="w-10 h-10 object-cover rounded-xl border border-purple-300 bg-white"
                        />
                        <div>
                            <h3 class="font-bold text-base sm:text-lg text-white">
                                {{ selectedItemForRequests.name }}
                            </h3>
                            <p class="text-[11px] text-purple-200">
                                Listahan ng lahat ng nag-request sa item na ito
                            </p>
                        </div>
                    </div>

                    <button
                        @click="closeItemRequestsModal"
                        class="text-purple-200 hover:text-white bg-purple-800/50 hover:bg-purple-700 p-2 rounded-xl transition-colors"
                    >
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4 overflow-y-auto flex-1">
                    <!-- Item Info Card -->
                    <div class="bg-purple-50 p-4 rounded-2xl border border-purple-100 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-purple-600 tracking-wider">Property Number</span>
                            <p class="font-mono font-black text-slate-800 text-sm">{{ selectedItemForRequests.propertyNo }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-bold uppercase text-purple-600 tracking-wider">Kasalukuyang Balance</span>
                            <p class="font-black text-emerald-700 text-lg">
                                {{ selectedItemForRequests.quantity }} {{ selectedItemForRequests.unit }}
                            </p>
                        </div>
                    </div>

                    <!-- Requests Table -->
                    <div v-if="itemRequestsList.length > 0" class="border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-100 text-[11px] font-extrabold text-slate-600 uppercase">
                                    <th class="py-2.5 px-3">Date</th>
                                    <th class="py-2.5 px-3">Pangalan ng Nag-request / Office</th>
                                    <th class="py-2.5 px-3">Property No.</th>
                                    <th class="py-2.5 px-3 text-center">Qty Requested</th>
                                    <th class="py-2.5 px-3 text-center">Remaining Balance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <tr
                                    v-for="req in itemRequestsList"
                                    :key="req.id"
                                    class="hover:bg-slate-50 transition-colors"
                                >
                                    <td class="py-3 px-3 font-semibold text-slate-700">
                                        {{ req.requestDate || '-' }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="font-bold text-slate-800">{{ req.requestorName }}</div>
                                        <div class="text-[10px] text-slate-500">{{ req.departmentOffice }}</div>
                                    </td>
                                    <td class="py-3 px-3 font-mono font-bold text-purple-900">
                                        {{ req.propertyNo || selectedItemForRequests.propertyNo }}
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="inline-block bg-rose-100 text-rose-800 font-black px-2 py-0.5 rounded-md">
                                            {{ req.requestedQuantity }} {{ selectedItemForRequests.unit }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-center font-bold text-slate-800">
                                        {{ selectedItemForRequests.quantity }} {{ selectedItemForRequests.unit }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-8 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                        <i class="fa-solid fa-clipboard-question text-3xl text-slate-300 mb-2"></i>
                        <p class="text-sm font-bold text-slate-600">Walang naitatalang request</p>
                        <p class="text-xs text-slate-400">Wala pang nagre-request para sa item na ito sa buong kasaysayan.</p>
                    </div>
                </div>

                <!-- Modal Footer with Print Stock Card Button -->
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-between items-center">
                    <button
                        type="button"
                        @click="printItemStockCard"
                        class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-colors flex items-center space-x-1.5 shadow-sm"
                    >
                        <i class="fa-solid fa-print"></i>
                        <span>Print Stock Card (PDF)</span>
                    </button>

                    <button
                        type="button"
                        @click="closeItemRequestsModal"
                        class="px-5 py-2 bg-purple-700 hover:bg-purple-800 text-white rounded-xl text-xs font-bold transition-colors"
                    >
                        Isara
                    </button>
                </div>
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
                    <i class="fa-solid fa-triangle-exclamation"></i>
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

                <div class="flex-1 pr-2">
                    <h5 class="font-bold text-xs text-slate-800">
                        {{ toast.title }}
                    </h5>

                    <p class="text-[11px] text-slate-500 mt-0.5">
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

:global(body) {
    font-family: 'Inter', sans-serif;
}
</style>
