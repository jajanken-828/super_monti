<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Package,
    Plus,
    X,
    Search,
    ChevronDown,
    AlertTriangle,
    ShoppingCart,
    Eye,
    Info,
    History,
    TrendingUp
} from 'lucide-vue-next';

const props = defineProps({
    materials: {
        type: Array,
        default: () => [],
    },
    auth: Object,
});

// UI State
const searchQuery = ref('');
const showAddModal = ref(false);
const showViewModal = ref(false);
const showProcurementModal = ref(false);
const selectedMaterial = ref(null);
const processing = ref(false);

// Helpers
const formatCurrency = (val) => '₱' + Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });

// Form for procurement request
const procurementForm = useForm({
    required_qty: 0,
    urgency: 'Medium',
    notes: '',
});

// Form for adding material
const addForm = useForm({
    name: '',
    category: 'Yarn',
    unit: 'Kg',
    reorder_point: 0,
});

// Filtered materials
const filteredMaterials = computed(() => {
    if (!searchQuery.value) return props.materials;
    const q = searchQuery.value.toLowerCase();
    return props.materials.filter(mat =>
        mat.name.toLowerCase().includes(q) ||
        mat.mat_id.toLowerCase().includes(q)
    );
});

// Reset add material form
const resetAddForm = () => {
    addForm.name = '';
    addForm.category = 'Yarn';
    addForm.unit = 'Kg';
    addForm.reorder_point = 0;
    addForm.clearErrors();
};

// Add material
const addMaterial = () => {
    addForm.post(route('inv.materials.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false;
            resetAddForm();
        },
        onFinish: () => (processing.value = false),
    });
};

// Open View Modal
const openViewModal = (material) => {
    selectedMaterial.value = material;
    showViewModal.value = true;
};

// Open procurement modal
const openProcurementModal = (material) => {
    selectedMaterial.value = material;
    procurementForm.required_qty = Math.max(0, material.reorder_point - material.total_stock);
    procurementForm.urgency = 'Medium';
    procurementForm.notes = '';
    showProcurementModal.value = true;
};

// Submit procurement request
const submitProcurement = () => {
    if (!procurementForm.required_qty || procurementForm.required_qty <= 0) {
        alert('Please enter a valid quantity.');
        return;
    }
    
    procurementForm.post(route('inv.checker.procurement', selectedMaterial.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showProcurementModal.value = false;
            selectedMaterial.value = null;
            procurementForm.reset();
        },
        onError: (errors) => {
            alert('Failed to send procurement request: ' + (errors.message || 'Unknown error'));
        },
    });
};

// Stock helpers
const stockStatus = (mat) => {
    if (mat.total_stock <= 0) return 'Out of Stock';
    if (mat.total_stock <= mat.reorder_point) return 'Low Stock';
    return 'In Stock';
};

const statusColor = (status) => {
    if (status === 'In Stock') return 'bg-emerald-100 text-emerald-700';
    if (status === 'Low Stock') return 'bg-amber-100 text-amber-700';
    return 'bg-red-100 text-red-600';
};

const isBelowReorder = (mat) => {
    return mat.total_stock <= mat.reorder_point && mat.total_stock > 0;
};
</script>

<template>
    <Head title="Materials | Inventory" />
    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase italic">Materials Management</h1>
                        <p class="text-slate-500 text-sm mt-0.5">Manage raw materials and track delivery lot history.</p>
                    </div>
                    <button
                        @click="showAddModal = true"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-black uppercase rounded-2xl hover:opacity-80 transition shadow-lg shadow-slate-200"
                    >
                        <Plus class="w-4 h-4" />
                        Add Material
                    </button>
                </div>

                <div class="mb-6">
                    <div class="relative max-w-sm">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search by name or ID..."
                            class="w-full pl-11 pr-4 py-3 bg-white dark:bg-slate-900 border-none rounded-2xl focus:ring-2 focus:ring-blue-500/20 shadow-sm"
                        />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-100 dark:border-slate-800 overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Material ID</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Name</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Category</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                                <tr v-if="filteredMaterials.length === 0">
                                    <td colspan="5" class="px-6 py-16 text-center text-slate-400">
                                        <Package class="w-10 h-10 mx-auto mb-3 opacity-30" />
                                        <p class="font-bold text-slate-500">No materials found.</p>
                                    </td>
                                </tr>
                                <tr v-for="mat in filteredMaterials" :key="mat.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                    <td class="px-6 py-5 font-mono text-xs font-bold text-blue-600">{{ mat.mat_id }}</td>
                                    <td class="px-6 py-5 font-bold text-slate-700 dark:text-slate-200">{{ mat.name }}</td>
                                    <td class="px-6 py-5 text-xs font-bold text-slate-500">{{ mat.category }}</td>
                                    <td class="px-6 py-5 text-center">
                                        <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter', statusColor(stockStatus(mat))]">
                                            {{ stockStatus(mat) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="openViewModal(mat)" class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-slate-900 dark:hover:text-white transition shadow-sm border border-slate-100 dark:border-slate-700" title="View Details">
                                                <Eye class="w-4 h-4" />
                                            </button>
                                            <button @click="openProcurementModal(mat)" class="p-2.5 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-400 hover:text-blue-600 transition shadow-sm border border-blue-100 dark:border-blue-800" title="Request Procurement">
                                                <ShoppingCart class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showAddModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showAddModal = false">
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
                    <div class="flex items-center justify-between mb-5 px-2">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase italic tracking-tighter">Add Material</h3>
                        <button @click="showAddModal = false" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 transition">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="space-y-4 px-2">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Material Name</label>
                            <input v-model="addForm.name" type="text" class="mt-1 w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-800 border-none rounded-xl focus:ring-2 focus:ring-blue-500/20" placeholder="e.g. Cotton Yarn 20s" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Category</label>
                                <select v-model="addForm.category" class="mt-1 w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-800 border-none rounded-xl">
                                    <option value="Yarn">Yarn</option>
                                    <option value="Dye">Dye</option>
                                    <option value="Supplies">Supplies</option>
                                    <option value="Packaging">Packaging</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Unit</label>
                                <select v-model="addForm.unit" class="mt-1 w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-800 border-none rounded-xl">
                                    <option value="Rolls">Rolls</option>
                                    <option value="Kg">Kg</option>
                                    <option value="Pcs">Pcs</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Reorder Point</label>
                            <input v-model="addForm.reorder_point" type="number" class="mt-1 w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-800 border-none rounded-xl" />
                        </div>
                    </div>
                    <div class="mt-8 px-2">
                        <button @click="addMaterial" class="w-full py-4 bg-blue-600 text-white font-black uppercase text-xs rounded-2xl hover:bg-blue-700 transition">Create Material</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="showViewModal && selectedMaterial" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showViewModal = false">
                <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-5xl overflow-hidden flex flex-col max-h-[90vh]">
                    <div class="p-6 bg-slate-50 dark:bg-slate-800 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <Info class="w-6 h-6 text-blue-600" />
                            <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-tight text-xl">Material Insight</h3>
                        </div>
                        <button @click="showViewModal = false" class="p-2 rounded-full hover:bg-white dark:hover:bg-slate-700 transition"><X class="w-5 h-5" /></button>
                    </div>
                    
                    <div class="p-8 overflow-y-auto space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="p-5 bg-gray-50 dark:bg-slate-800/50 rounded-3xl border border-gray-100 dark:border-slate-700">
                                <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Name</p>
                                <p class="font-bold text-slate-800 dark:text-white">{{ selectedMaterial.name }}</p>
                            </div>
                            <div class="p-5 bg-gray-50 dark:bg-slate-800/50 rounded-3xl border border-gray-100 dark:border-slate-700">
                                <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Material ID</p>
                                <p class="font-mono font-bold text-blue-600">{{ selectedMaterial.mat_id }}</p>
                            </div>
                            <div class="p-5 bg-gray-50 dark:bg-slate-800/50 rounded-3xl border border-gray-100 dark:border-slate-700">
                                <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Current Stock</p>
                                <p class="font-black text-xl text-slate-900 dark:text-white">{{ selectedMaterial.total_stock }} <span class="text-xs">{{ selectedMaterial.unit }}</span></p>
                            </div>
                            <div class="p-5 bg-gray-50 dark:bg-slate-800/50 rounded-3xl border border-gray-100 dark:border-slate-700">
                                <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Reorder Point</p>
                                <p class="font-black text-xl text-slate-900 dark:text-white">{{ selectedMaterial.reorder_point }} <span class="text-xs">{{ selectedMaterial.unit }}</span></p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center gap-2 px-2 text-slate-800 dark:text-white font-black uppercase text-sm">
                                <History class="w-4 h-4 text-blue-600" /> Delivery & Lot History
                            </div>
                            <div class="rounded-3xl border border-slate-100 dark:border-slate-800 overflow-hidden shadow-sm">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 dark:bg-slate-800/80 font-black uppercase text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800">
                                        <tr>
                                            <th class="px-5 py-4">Lot Number</th>
                                            <th class="px-5 py-4">PO Number</th>
                                            <th class="px-5 py-4">Warehouse</th>
                                            <th class="px-5 py-4">Received Date</th>
                                            <th class="px-5 py-4 text-right">KG</th>
                                            <th class="px-5 py-4 text-right">Price/KG</th>
                                            <th class="px-5 py-4 text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                                        <tr v-for="log in selectedMaterial.delivery_history" :key="log.lot_number" class="hover:bg-gray-50/50 dark:hover:bg-slate-800/50 transition">
                                            <td class="px-5 py-4 font-black text-indigo-600 uppercase">{{ log.lot_number }}</td>
                                            <td class="px-5 py-4 font-mono font-bold text-slate-600 dark:text-slate-400">{{ log.po_number }}</td>
                                            <td class="px-5 py-4 dark:text-slate-300">{{ log.warehouse_name }}</td>
                                            <td class="px-5 py-4 font-semibold text-slate-500 dark:text-slate-400 text-nowrap">{{ log.received_date }}</td>
                                            <td class="px-5 py-4 text-right font-black text-slate-800 dark:text-slate-200">{{ log.kg }} {{ selectedMaterial.unit }}</td>
                                            <td class="px-5 py-4 text-right font-bold text-slate-600 dark:text-slate-400">{{ formatCurrency(log.price_per_kg) }}</td>
                                            <td class="px-5 py-4 text-right font-black text-slate-900 dark:text-white">{{ formatCurrency(log.total_amount) }}</td>
                                        </tr>
                                        <tr v-if="!selectedMaterial.delivery_history || selectedMaterial.delivery_history.length === 0">
                                            <td colspan="7" class="px-5 py-12 text-center text-slate-400 dark:text-slate-600 italic font-medium uppercase tracking-tighter">
                                                No delivery records found for this material.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 bg-slate-50 dark:bg-slate-800 border-t border-slate-100 dark:border-slate-700 flex justify-end">
                        <button @click="showViewModal = false" class="px-8 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black uppercase text-[10px] rounded-2xl tracking-widest transition">Close Detailed View</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="showProcurementModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showProcurementModal = false">
                <div class="bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6">
                    <div class="flex items-center justify-between mb-5 px-2">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tighter italic">Request Procurement</h3>
                        <button @click="showProcurementModal = false" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 transition">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="space-y-4 px-2">
                        <div class="bg-blue-50 dark:bg-blue-900/10 p-4 rounded-2xl border border-blue-100 dark:border-blue-900/30">
                            <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest">Target Material</p>
                            <p class="font-bold text-slate-800 dark:text-slate-200 mt-1">{{ selectedMaterial?.name }}</p>
                            <p class="text-xs text-slate-400 mt-1">Shortage: {{ Math.max(0, (selectedMaterial?.reorder_point - selectedMaterial?.total_stock)).toFixed(2) }} {{ selectedMaterial?.unit }}</p>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Quantity ({{ selectedMaterial?.unit }})</label>
                            <input v-model="procurementForm.required_qty" type="number" step="0.01" class="mt-1 w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-800 border-none rounded-xl focus:ring-2 focus:ring-blue-500/20" />
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Urgency Level</label>
                            <select v-model="procurementForm.urgency" class="mt-1 w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-800 border-none rounded-xl">
                                <option value="High">High – Immediate Action</option>
                                <option value="Medium">Medium – Standard</option>
                                <option value="Low">Low – Non-Urgent</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Notes</label>
                            <textarea v-model="procurementForm.notes" rows="3" class="mt-1 w-full px-4 py-3 text-sm bg-slate-50 dark:bg-slate-800 border-none rounded-xl resize-none" placeholder="Reason for restock..."></textarea>
                        </div>
                    </div>
                    <div class="mt-8 px-2">
                        <button @click="submitProcurement" :disabled="procurementForm.processing" class="w-full py-4 bg-blue-600 text-white font-black uppercase text-xs rounded-2xl hover:bg-blue-700 transition shadow-lg shadow-blue-200 dark:shadow-none">Send Procurement Request</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>