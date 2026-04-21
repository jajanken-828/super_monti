<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ClipboardList, Send, X, AlertCircle, CheckCircle } from 'lucide-vue-next';

const props = defineProps({ procurementRequests: Array });

// Modal State
const showConfirmModal = ref(false);
const selectedRequestId = ref(null);
const selectedRequestName = ref('');

// Trigger the modal instead of the alert
const confirmSend = (req) => {
    selectedRequestId.value = req.id;
    selectedRequestName.value = req.material_name;
    showConfirmModal.value = true;
};

// Execute the actual post request
const handleSendAction = () => {
    if (!selectedRequestId.value) return;

    router.post(route('scm.procurement-order.send', selectedRequestId.value), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showConfirmModal.value = false;
            selectedRequestId.value = null;
        }
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="SCM Procurement Orders" />
        
        <div class="max-w-7xl mx-auto p-4 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase italic">Procurement Requests</h1>
                <p class="text-slate-500 text-sm mt-1 font-medium text-uppercase">Direct materials queue for SCM processing.</p>
            </div>

            <div v-if="procurementRequests.length === 0" class="bg-white dark:bg-slate-900 rounded-[2rem] border border-dashed border-slate-300 p-16 text-center">
                <ClipboardList class="w-12 h-12 mx-auto mb-4 text-slate-300" />
                <p class="font-bold uppercase tracking-widest text-xs text-slate-400 italic">No pending procurement requests.</p>
            </div>

            <div class="grid gap-4">
                <div v-for="req in procurementRequests" :key="req.id" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 p-6 flex justify-between items-center shadow-sm hover:shadow-md transition-shadow">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                             <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-blue-50 text-blue-600 uppercase tracking-tighter">{{ req.urgency }}</span>
                             <p class="font-black text-lg text-slate-800 dark:text-slate-100 uppercase tracking-tight">{{ req.material_name }}</p>
                        </div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">
                            Volume Order: <span class="text-slate-900 dark:text-slate-300">{{ req.required_qty }} {{ req.unit }}</span>
                        </p>
                    </div>
                    
                    <button @click="confirmSend(req)" class="px-6 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 hover:opacity-80 transition shadow-lg shadow-slate-200 dark:shadow-none">
                        <Send class="w-4 h-4" /> Send to Procurement
                    </button>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showConfirmModal = false">
                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] w-full max-w-md p-8 shadow-2xl border border-slate-200 dark:border-slate-800 animate-in zoom-in duration-200">
                    <div class="flex flex-col items-center text-center">
                        <div class="h-20 w-20 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center mb-6">
                            <AlertCircle class="w-10 h-10 text-blue-600" />
                        </div>
                        
                        <h3 class="text-2xl font-black uppercase tracking-tighter mb-2 text-slate-900 dark:text-white leading-none">
                            Verify Transfer
                        </h3>
                        <p class="text-sm font-bold text-slate-500 leading-relaxed px-4">
                            Are you sure you want to move <span class="text-blue-600">"{{ selectedRequestName }}"</span> to the Procurement Module for official order creation?
                        </p>
                        
                        <div class="mt-10 flex w-full gap-3">
                            <button 
                                @click="showConfirmModal = false" 
                                class="flex-1 px-6 py-4 bg-slate-100 dark:bg-slate-800 text-slate-500 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:opacity-80 transition"
                            >
                                Cancel
                            </button>
                            <button 
                                @click="handleSendAction" 
                                class="flex-[1.5] px-6 py-4 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all"
                            >
                                Confirm Transfer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes zoom-in {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.animate-in {
    animation-fill-mode: both;
}
.zoom-in { animation: zoom-in 0.2s ease-out; }
</style>