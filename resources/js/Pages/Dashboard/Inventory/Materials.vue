<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Package, Plus, X, Search, ChevronDown, AlertTriangle,
    ShoppingCart, Eye, Info, History, TrendingUp, Save,
    CheckCircle, AlertCircle, Trash2, BadgeCheck
} from 'lucide-vue-next';

const props = defineProps({
    materials: { type: Array, default: () => [] },
    auth: Object,
});

// UI State
const searchQuery = ref('');
const showAddModal = ref(false);
const showViewModal = ref(false);
const showProcurementModal = ref(false);
const showConfirmModal = ref(false);
const selectedMaterial = ref(null);

// Configuration for the Global Confirmation Modal
const confirmConfig = ref({
    title: '',
    message: '',
    type: 'confirm',
    action: null
});

// Forms
const addForm = useForm({
    name: '',
    category: 'Yarn',
    unit: 'Kg',
    reorder_point: 0,
});

const editForm = useForm({
    name: '',
    reorder_point: 0,
});

const procurementForm = useForm({
    required_qty: 0,
    urgency: 'Medium',
    notes: '',
});

// Helpers
const formatCurrency = (val) => '₱' + Number(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });

const filteredMaterials = computed(() => {
    if (!searchQuery.value) return props.materials;
    const q = searchQuery.value.toLowerCase();
    return props.materials.filter(mat =>
        mat.name.toLowerCase().includes(q) || mat.mat_id.toLowerCase().includes(q)
    );
});

// Modal Control Helpers
const triggerConfirm = (title, message, type, action) => {
    confirmConfig.value = { title, message, type, action };
    showConfirmModal.value = true;
};

const closeConfirm = () => {
    showConfirmModal.value = false;
};

// --- Actions ---

const addMaterial = () => {
    addForm.clearErrors();
    if (!addForm.name) {
        addForm.setError('name', 'Material name is required.');
        return;
    }

    triggerConfirm(
        'Confirm Registration',
        `Are you sure you want to register ${addForm.name} into the system?`,
        'confirm',
        () => {
            addForm.post(route('inv.materials.store'), {
                onSuccess: () => {
                    showAddModal.value = false;
                    addForm.reset();
                    closeConfirm();
                },
            });
        }
    );
};

const openViewModal = (material) => {
    selectedMaterial.value = material;
    editForm.name = material.name;
    editForm.reorder_point = material.reorder_point;
    showViewModal.value = true;
};

const submitUpdate = () => {
    triggerConfirm(
        'Save Changes',
        'Do you want to apply these updates to the material profile?',
        'confirm',
        () => {
            editForm.patch(route('inv.materials.update', selectedMaterial.value.id), {
                onSuccess: () => {
                    showViewModal.value = false;
                    closeConfirm();
                },
            });
        }
    );
};

const submitProcurement = () => {
    if (procurementForm.required_qty <= 0) return;

    triggerConfirm(
        'Send Request',
        `Send procurement request for ${selectedMaterial.value.name} (${procurementForm.required_qty} ${selectedMaterial.value.unit}) to SCM?`,
        'confirm',
        () => {
            procurementForm.post(route('inv.checker.procurement', selectedMaterial.value.id), {
                onSuccess: () => {
                    showProcurementModal.value = false;
                    procurementForm.reset();
                    closeConfirm();
                },
            });
        }
    );
};

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
</script>

<template>
    <Head title="Materials | Inventory" />
    <AuthenticatedLayout>
        <div class="py-6 font-sans">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Materials Management</h1>
                        <p class="text-slate-500 text-sm mt-1 uppercase tracking-widest text-[10px] font-bold">Inventory Control Center</p>
                    </div>
                    <button @click="showAddModal = true" class="inline-flex items-center gap-2 px-8 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-black uppercase rounded-2xl hover:bg-blue-600 hover:text-white transition-all shadow-xl shadow-slate-200">
                        <Plus class="w-4 h-4" /> Add Material
                    </button>
                </div>

                <div class="mb-8">
                    <div class="relative max-w-sm">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input v-model="searchQuery" type="text" placeholder="Filter by name or ID..." class="w-full pl-11 pr-4 py-4 bg-white dark:bg-slate-900 border-none rounded-2xl focus:ring-2 focus:ring-blue-500/20 shadow-sm font-bold text-sm" />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Material ID</th>
                                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Name</th>
                                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Category</th>
                                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-800 font-bold uppercase">
                                <tr v-for="mat in filteredMaterials" :key="mat.id" class="hover:bg-slate-50/60 transition cursor-default">
                                    <td class="px-8 py-6 font-mono text-xs text-blue-600">{{ mat.mat_id }}</td>
                                    <td class="px-8 py-6 text-slate-700 dark:text-slate-200">{{ mat.name }}</td>
                                    <td class="px-8 py-6 text-[10px] text-slate-400 tracking-wider">{{ mat.category }}</td>
                                    <td class="px-8 py-6 text-center">
                                        <span :class="['px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-tighter', statusColor(stockStatus(mat))]">
                                            {{ stockStatus(mat) }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <div class="flex items-center justify-center gap-3">
                                            <button @click="openViewModal(mat)" class="p-3 rounded-xl bg-slate-100 text-slate-400 hover:text-slate-900 transition border border-slate-200 shadow-sm"><Eye class="w-4 h-4" /></button>
                                            <button @click="selectedMaterial = mat; procurementForm.required_qty = mat.reorder_point; showProcurementModal = true;" class="p-3 rounded-xl bg-blue-50 text-blue-400 hover:text-blue-600 transition border border-blue-100 shadow-sm"><ShoppingCart class="w-4 h-4" /></button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Material Modal -->
        <Teleport to="body">
            <div v-if="showAddModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-md" @click.self="showAddModal = false">
                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden">
                    <div class="p-8 border-b bg-slate-50 flex items-center justify-between">
                        <h3 class="text-xl font-black uppercase tracking-tight">Create Material</h3>
                        <button @click="showAddModal = false" class="p-2 rounded-full hover:bg-white transition"><X class="w-5 h-5" /></button>
                    </div>
                    <div class="p-8 space-y-6">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Material Name</label>
                            <input v-model="addForm.name" type="text" class="w-full px-5 py-4 bg-slate-100 border-none rounded-2xl focus:ring-2 focus:ring-blue-600/20 font-bold text-sm" placeholder="e.g. Cotton Yarn" />
                            <p v-if="addForm.errors.name" class="text-red-500 text-[10px] font-black uppercase tracking-tight">{{ addForm.errors.name }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Category</label>
                                <select v-model="addForm.category" class="w-full px-5 py-4 bg-slate-100 border-none rounded-2xl font-bold text-sm">
                                    <option v-for="cat in ['Yarn', 'Dye', 'Supplies', 'Packaging']" :value="cat">{{ cat }}</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Unit</label>
                                <select v-model="addForm.unit" class="w-full px-5 py-4 bg-slate-100 border-none rounded-2xl font-bold text-sm">
                                    <option v-for="u in ['Rolls', 'Kg', 'Pcs']" :value="u">{{ u }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Reorder Point</label>
                            <input v-model="addForm.reorder_point" type="number" class="w-full px-5 py-4 bg-slate-100 border-none rounded-2xl font-bold text-sm" />
                        </div>
                    </div>
                    <div class="p-8 pt-0">
                        <button @click="addMaterial" class="w-full py-5 bg-blue-600 text-white font-black uppercase text-xs rounded-2xl hover:bg-blue-700 transition shadow-lg shadow-blue-200">Register Material</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- View/Edit Material Modal -->
        <Teleport to="body">
            <div v-if="showViewModal && selectedMaterial" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-lg" @click.self="showViewModal = false">
                <div class="bg-white dark:bg-gray-900 rounded-[3rem] shadow-2xl border border-slate-200 w-full max-w-5xl overflow-hidden flex flex-col max-h-[90vh]">
                    <div class="p-8 bg-slate-50 border-b flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-blue-600 rounded-2xl text-white shadow-lg"><Info class="w-6 h-6" /></div>
                            <h3 class="font-black uppercase tracking-tight text-xl">Material Insight & Edit</h3>
                        </div>
                        <button @click="showViewModal = false" class="p-3 rounded-full hover:bg-white shadow-sm border border-slate-200 transition"><X class="w-5 h-5" /></button>
                    </div>

                    <div class="p-10 overflow-y-auto space-y-10">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 font-black uppercase">
                            <div class="p-6 bg-blue-50 border-2 border-blue-200 rounded-[2rem]">
                                <label class="text-[10px] text-blue-600 block mb-2 tracking-widest">Update Name</label>
                                <input v-model="editForm.name" type="text" class="w-full bg-transparent border-none p-0 text-base font-black focus:ring-0 text-slate-800" />
                            </div>
                            <div class="p-6 bg-slate-50 border border-slate-200 rounded-[2rem] opacity-50">
                                <p class="text-[10px] text-slate-400 mb-2 tracking-widest">Material ID (Locked)</p>
                                <p class="font-mono text-sm text-blue-600">{{ selectedMaterial.mat_id }}</p>
                            </div>
                            <div class="p-6 bg-slate-50 border border-slate-200 rounded-[2rem]">
                                <p class="text-[10px] text-slate-400 mb-2 tracking-widest">Live Stock</p>
                                <p class="text-2xl text-slate-900">{{ selectedMaterial.total_stock }} <span class="text-xs">{{ selectedMaterial.unit }}</span></p>
                            </div>
                            <div class="p-6 bg-blue-50 border-2 border-blue-200 rounded-[2rem]">
                                <label class="text-[10px] text-blue-600 block mb-2 tracking-widest">Reorder Threshold</label>
                                <div class="flex items-center gap-2">
                                    <input v-model.number="editForm.reorder_point" type="number" class="w-full bg-transparent border-none p-0 text-2xl font-black focus:ring-0 text-slate-900" />
                                    <span class="text-xs text-slate-400">{{ selectedMaterial.unit }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <h4 class="text-sm font-black uppercase tracking-widest flex items-center gap-3 ml-2"><History class="w-5 h-5 text-blue-600" /> Lot Management Records</h4>
                            <div class="rounded-[2rem] border border-slate-100 overflow-hidden shadow-inner bg-slate-50">
                                <table class="w-full text-left text-xs uppercase font-black">
                                    <thead class="bg-white/50 text-slate-400 border-b tracking-widest text-[9px]">
                                        <tr>
                                            <th class="px-8 py-5">Lot ID</th>
                                            <th class="px-8 py-5">PO Ref</th>
                                            <th class="px-8 py-5">Location</th>
                                            <th class="px-8 py-5 text-right">Received Qty</th>
                                            <th class="px-8 py-5 text-right">Total Value</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-slate-700">
                                        <tr v-for="log in selectedMaterial.delivery_history" :key="log.lot_number" class="hover:bg-white/80 transition">
                                            <td class="px-8 py-5 text-indigo-600">{{ log.lot_number }}</td>
                                            <td class="px-8 py-5 font-mono">{{ log.po_number }}</td>
                                            <td class="px-8 py-5 tracking-tight">{{ log.warehouse_name }}</td>
                                            <td class="px-8 py-5 text-right text-slate-900 font-black">{{ log.kg }} {{ selectedMaterial.unit }}</td>
                                            <td class="px-8 py-5 text-right text-slate-900 font-black">{{ formatCurrency(log.total_amount) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="p-8 bg-slate-50 border-t flex justify-between items-center px-12">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2"><CheckCircle class="w-4 h-4" /> Changes persist in master database</span>
                        <div class="flex gap-4">
                            <button @click="showViewModal = false" class="px-8 py-4 font-black uppercase text-[10px] text-slate-500 hover:text-slate-900 transition">Dismiss</button>
                            <button @click="submitUpdate" :disabled="editForm.processing" class="px-10 py-4 bg-blue-600 text-white font-black uppercase text-[10px] rounded-[1.25rem] shadow-xl shadow-blue-200 hover:bg-blue-700 transition flex items-center gap-3">
                                <Save class="w-4 h-4" /> {{ editForm.processing ? 'Updating...' : 'Sync Master Profile' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Procurement Request Modal -->
        <Teleport to="body">
            <div v-if="showProcurementModal && selectedMaterial" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-lg" @click.self="showProcurementModal = false">
                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden">
                    <div class="p-8 border-b bg-slate-50 flex items-center justify-between">
                        <h3 class="text-xl font-black uppercase tracking-tight">Request Procurement</h3>
                        <button @click="showProcurementModal = false" class="p-2 rounded-full hover:bg-white transition"><X class="w-5 h-5" /></button>
                    </div>
                    <div class="p-8 space-y-6">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Material</label>
                            <p class="text-lg font-black text-slate-800">{{ selectedMaterial.name }}</p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Quantity  ({{ selectedMaterial.unit }})</label>
                            <input v-model.number="procurementForm.required_qty" type="number" step="0.01" min="0.01" class="w-full px-5 py-4 bg-slate-100 border-none rounded-2xl focus:ring-2 focus:ring-blue-600/20 font-bold text-sm" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Urgency</label>
                            <select v-model="procurementForm.urgency" class="w-full px-5 py-4 bg-slate-100 border-none rounded-2xl font-bold text-sm">
                                <option value="High">High</option>
                                <option value="Medium">Medium</option>
                                <option value="Low">Low</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Notes (Optional)</label>
                            <textarea v-model="procurementForm.notes" rows="3" class="w-full px-5 py-4 bg-slate-100 border-none rounded-2xl focus:ring-2 focus:ring-blue-600/20 font-bold text-sm" placeholder="Additional details..."></textarea>
                        </div>
                    </div>
                    <div class="p-8 pt-0 flex gap-4">
                        <button @click="showProcurementModal = false" class="flex-1 py-5 bg-slate-100 text-slate-500 font-black uppercase text-xs rounded-2xl hover:bg-slate-200 transition">Cancel</button>
                        <button @click="submitProcurement" :disabled="procurementForm.processing" class="flex-1 py-5 bg-blue-600 text-white font-black uppercase text-xs rounded-2xl hover:bg-blue-700 transition shadow-lg shadow-blue-200">
                            {{ procurementForm.processing ? 'Sending...' : 'Send to SCM' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Global Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-xl">
                <div class="bg-white rounded-[2.5rem] p-10 max-w-md w-full shadow-2xl border border-slate-100 text-center animate-in zoom-in duration-300">
                    <div class="mx-auto w-20 h-20 rounded-full flex items-center justify-center mb-6" :class="confirmConfig.type === 'danger' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600'">
                        <AlertTriangle v-if="confirmConfig.type === 'danger'" class="w-10 h-10" />
                        <BadgeCheck v-else class="w-10 h-10" />
                    </div>
                    <h3 class="text-xl font-black uppercase tracking-tight text-slate-900 mb-3">{{ confirmConfig.title }}</h3>
                    <p class="text-sm font-bold text-slate-500 uppercase tracking-wide leading-relaxed px-4 mb-8">{{ confirmConfig.message }}</p>
                    <div class="flex gap-4">
                        <button @click="closeConfirm" class="flex-1 py-5 rounded-2xl bg-slate-100 text-slate-500 text-xs font-black uppercase hover:bg-slate-200 transition">Wait, Go Back</button>
                        <button @click="confirmConfig.action" class="flex-1 py-5 rounded-2xl text-white text-xs font-black uppercase shadow-lg transition" :class="confirmConfig.type === 'danger' ? 'bg-red-600 hover:bg-red-700 shadow-red-200' : 'bg-blue-600 hover:bg-blue-700 shadow-blue-200'">Process Action</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
* { font-style: normal !important; }
</style>