<template>
    <Head title="Order Receiving" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-10 p-4 lg:p-10">

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Truck class="h-3.5 w-3.5" />
                        Logistics Portal
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Order <span class="text-indigo-600">Receiving</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Confirm delivery of your shipments.
                    </p>
                </div>
                <button @click="refreshData" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <RefreshCw class="h-5 w-5 text-gray-500" />
                </button>
            </div>

            <!-- Stats Summary -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">In Transit</p>
                    <p class="text-3xl font-black text-blue-600 mt-1">{{ inTransitCount }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 p-7 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Delivered</p>
                    <p class="text-3xl font-black text-emerald-600 mt-1">{{ deliveredCount }}</p>
                </div>
            </div>

            <!-- Deliveries Table -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-gray-50 dark:border-gray-800 flex justify-between items-center">
                    <div class="relative flex-1 lg:w-96 group">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input v-model="searchTerm" type="text" placeholder="Search by delivery number..."
                            class="w-full pl-11 pr-4 py-3.5 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase tracking-widest">
                    </div>
                    <div class="flex gap-2 ml-4">
                        <select v-model="statusFilter"
                            class="px-4 py-3 rounded-2xl border-gray-100 dark:border-gray-800 dark:bg-gray-950 text-[10px] font-black uppercase">
                            <option value="all">All Shipments</option>
                            <option value="dispatched">Dispatched</option>
                            <option value="in_transit">In Transit</option>
                            <option value="delivered">Delivered</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-8 py-5">Delivery #</th>
                                <th class="px-8 py-5">Driver / Truck</th>
                                <th class="px-8 py-5">Route</th>
                                <th class="px-8 py-5">Scheduled</th>
                                <th class="px-8 py-5 text-center">Status</th>
                                <th class="px-8 py-5 text-center">Packages</th>
                                <th class="px-8 py-5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="delivery in filteredDeliveries" :key="delivery.id" class="group hover:bg-gray-50/50 transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                            <Package class="h-5 w-5" />
                                        </div>
                                        <span class="font-mono text-sm font-black text-gray-900 dark:text-white">{{ delivery.delivery_number }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="text-sm font-medium">{{ delivery.driver_name || '—' }}</div>
                                    <div class="text-xs text-gray-500">{{ delivery.truck_number || '—' }}</div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="text-sm">{{ delivery.route_name || '—' }}</div>
                                    <div class="text-xs text-gray-500 truncate max-w-[200px]">
                                        {{ delivery.origin }} → {{ delivery.destination }}
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-sm text-gray-600">{{ formatDateTime(delivery.scheduled) }}</td>
                                <td class="px-8 py-6 text-center">
                                    <span :class="statusBadge(delivery.status)" class="px-3 py-1 rounded-full text-[9px] font-black uppercase">
                                        {{ formatStatus(delivery.status) }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <button @click="openPackagesModal(delivery)" class="text-indigo-600 text-xs font-bold hover:underline">
                                        {{ delivery.packages.length }} package(s)
                                    </button>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <button @click="markAsReceived(delivery)"
                                        :disabled="delivery.status === 'delivered' || processing[delivery.id]"
                                        class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                        <Loader2 v-if="processing[delivery.id]" class="h-4 w-4 animate-spin inline mr-1" />
                                        {{ delivery.status === 'delivered' ? 'Received' : 'Confirm Receipt' }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredDeliveries.length === 0">
                                <td colspan="7" class="px-8 py-20 text-center text-gray-400 uppercase font-black italic">
                                    No shipments found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Packages Modal -->
        <Teleport to="body">
            <div v-if="showPackagesModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closePackagesModal">
                <div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 bg-indigo-600 text-white flex justify-between items-center">
                        <h3 class="font-black text-lg">Packages in Delivery</h3>
                        <button @click="closePackagesModal" class="p-1 hover:bg-white/20 rounded-lg"><X class="h-5 w-5" /></button>
                    </div>
                    <div class="p-6 space-y-3">
                        <p class="text-sm font-mono">{{ selectedDelivery?.delivery_number }}</p>
                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                            <div v-for="(pkg, idx) in selectedDelivery?.packages" :key="idx" class="py-3 flex justify-between">
                                <div>
                                    <p class="font-medium">{{ pkg.product_name || '—' }}</p>
                                    <p class="text-xs text-gray-500">{{ pkg.package_number }}</p>
                                </div>
                                <p class="font-bold">{{ pkg.quantity }} pcs</p>
                            </div>
                        </div>
                        <button @click="closePackagesModal" class="w-full py-3 bg-gray-100 dark:bg-gray-800 rounded-xl text-[10px] font-black uppercase">Close</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Toast Notification -->
        <Transition name="toast">
            <div v-if="toast.show" class="fixed bottom-8 right-8 z-50 px-6 py-3 rounded-xl shadow-lg text-white font-bold text-sm"
                :class="toast.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'">
                {{ toast.message }}
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Truck, RefreshCw, Search, Package, Loader2, X } from 'lucide-vue-next';

const props = defineProps({
    deliveries: {
        type: Array,
        default: () => []
    }
});

const searchTerm = ref('');
const statusFilter = ref('all');
const processing = ref({});
const toast = ref({ show: false, type: 'success', message: '' });
const showPackagesModal = ref(false);
const selectedDelivery = ref(null);

const filteredDeliveries = computed(() => {
    let list = props.deliveries;
    if (searchTerm.value) {
        const term = searchTerm.value.toLowerCase();
        list = list.filter(d => d.delivery_number.toLowerCase().includes(term));
    }
    if (statusFilter.value !== 'all') {
        list = list.filter(d => d.status === statusFilter.value);
    }
    return list;
});

const inTransitCount = computed(() => props.deliveries.filter(d => d.status === 'in_transit').length);
const deliveredCount = computed(() => props.deliveries.filter(d => d.status === 'delivered').length);

const statusBadge = (status) => {
    const map = {
        dispatched: 'bg-amber-100 text-amber-700',
        in_transit: 'bg-blue-100 text-blue-700',
        delivered: 'bg-emerald-100 text-emerald-700'
    };
    return map[status] || 'bg-gray-100 text-gray-600';
};

const formatStatus = (status) => {
    const map = { dispatched: 'Dispatched', in_transit: 'In Transit', delivered: 'Delivered' };
    return map[status] || status;
};

const formatDateTime = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3000);
};

const refreshData = () => {
    router.reload({ only: ['deliveries'] });
};

const openPackagesModal = (delivery) => {
    selectedDelivery.value = delivery;
    showPackagesModal.value = true;
};

const closePackagesModal = () => {
    showPackagesModal.value = false;
    selectedDelivery.value = null;
};

const markAsReceived = async (delivery) => {
    if (delivery.status === 'delivered') return;
    processing.value[delivery.id] = true;
    try {
        await router.post(route('client.receiving.mark', delivery.id));
        showToast('success', `Delivery ${delivery.delivery_number} confirmed. Thank you!`);
        refreshData();
    } catch (error) {
        showToast('error', 'Failed to confirm delivery.');
    } finally {
        processing.value[delivery.id] = false;
    }
};
</script>

<style scoped>
.toast-enter-active, .toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from, .toast-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>