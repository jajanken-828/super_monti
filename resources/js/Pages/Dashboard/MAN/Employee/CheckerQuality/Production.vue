<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Package, Shirt, Droplet, Factory, Box,
    CheckCircle, XCircle, ArrowRight,
    Wind, Flame, Archive, ClipboardList,
    AlertTriangle, X, ChevronRight, Layers,
} from 'lucide-vue-next';

const props = defineProps({
    orders:       Array,
    fabrics:      Array,
    dyeJobs:      Array,
    softenerJobs: Array,
    squeezerJobs: Array,
    ironJobs:     Array,
    formJobs:     Array,
    packages:     Array,
});

// ─── Active Tab ───────────────────────────────────────────────────────────────
const activeTab = ref('orders');

const tabs = computed(() => [
    { key: 'orders',   label: 'Orders',    icon: ClipboardList, count: props.orders?.length       ?? 0 },
    { key: 'fabrics',  label: 'Fabrics',   icon: Shirt,         count: props.fabrics?.length      ?? 0 },
    { key: 'dye',      label: 'Dye',       icon: Droplet,       count: props.dyeJobs?.length      ?? 0 },
    { key: 'softener', label: 'Softener',  icon: Wind,          count: props.softenerJobs?.length ?? 0 },
    { key: 'iron',     label: 'Iron',      icon: Flame,         count: props.ironJobs?.length     ?? 0 },
    { key: 'form',     label: 'Form',      icon: Factory,       count: props.formJobs?.length     ?? 0 },
    { key: 'packed',   label: 'Packed',    icon: Package,       count: props.packages?.length     ?? 0 },
]);

// ─── Confirm Modal ────────────────────────────────────────────────────────────
const confirmModal   = ref(false);
const confirmLabel   = ref('');
const confirmSub     = ref('');
const confirmPending = ref(null);

const askConfirm = (label, sub, action) => {
    confirmLabel.value   = label;
    confirmSub.value     = sub;
    confirmPending.value = action;
    confirmModal.value   = true;
};
const runConfirm = () => {
    if (confirmPending.value) confirmPending.value();
    confirmModal.value   = false;
    confirmPending.value = null;
};
const cancelConfirm = () => {
    confirmModal.value   = false;
    confirmPending.value = null;
};

// ─── Actions ──────────────────────────────────────────────────────────────────
const passFabric = (fabricId, destination) =>
    router.post(route('man.staff.checker-quality.pass-fabric', fabricId), { destination });

const passDye = (dyeId, action) =>
    router.post(route('man.staff.checker-quality.pass-dye', dyeId), { action });

// Note: passSoftener, passIron, packForm, rejectForm, assignToOrder are used below —
// define them in your controller and wire routes accordingly.
</script>

<template>
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">

                <!-- ── Page Header ─────────────────────────────────────────── -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">
                            Production Pipeline
                        </h1>
                        <p class="text-sm text-gray-500 mt-0.5">Track and advance items through each stage</p>
                    </div>
                    <!-- accent bar -->
                    <div class="hidden sm:flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-yellow-400"></span>
                        <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                        <span class="w-2 h-2 rounded-full bg-gray-900"></span>
                    </div>
                </div>

                <!-- ── Tab Navigation ──────────────────────────────────────── -->
                <div class="relative">
                    <!-- scroll container -->
                    <div class="overflow-x-auto pb-1 -mx-4 px-4 sm:mx-0 sm:px-0">
                        <div class="flex gap-1 bg-white border border-gray-200 rounded-2xl p-1.5 w-max sm:w-full min-w-full">
                            <button
                                v-for="tab in tabs"
                                :key="tab.key"
                                @click="activeTab = tab.key"
                                type="button"
                                class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all duration-200 flex-1 justify-center"
                                :class="activeTab === tab.key
                                    ? 'bg-gray-900 text-white shadow-sm'
                                    : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50'"
                            >
                                <component :is="tab.icon" class="w-3.5 h-3.5 flex-shrink-0" />
                                <span class="hidden sm:inline">{{ tab.label }}</span>
                                <span
                                    class="rounded-md px-1.5 py-0.5 text-[10px] font-bold leading-none"
                                    :class="activeTab === tab.key
                                        ? 'bg-white/20 text-white'
                                        : 'bg-gray-100 text-gray-500'"
                                >
                                    {{ tab.count }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ════════════════════════════════════════════════════════════
                     ORDERS TAB
                ════════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'orders'">
                    <!-- empty state -->
                    <div v-if="!orders?.length"
                         class="bg-white rounded-2xl border border-dashed border-gray-200 py-14 flex flex-col items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center">
                            <ClipboardList class="w-6 h-6 text-gray-400" />
                        </div>
                        <p class="text-sm font-medium text-gray-400">No orders in queue</p>
                    </div>

                    <!-- cards -->
                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div
                            v-for="order in orders"
                            :key="order.id"
                            class="bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
                        >
                            <!-- card header -->
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
                                <span class="text-xs font-bold text-blue-600 font-mono tracking-wide">
                                    {{ order.po_number }}
                                </span>
                                <span class="inline-flex items-center gap-1 bg-yellow-100 text-yellow-800 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                    Queued
                                </span>
                            </div>
                            <!-- card body -->
                            <div class="px-4 py-3 space-y-1">
                                <p class="text-sm font-bold text-gray-900">{{ order.product_name }}</p>
                                <p class="text-xs text-gray-500">{{ order.quantity }} pcs</p>
                            </div>
                            <!-- card footer -->
                            <div class="px-4 pb-4 flex flex-col sm:flex-row gap-2">
                                <button
                                    @click="router.post(route('man.staff.checker-quality.check-inventory', order.id))"
                                    class="flex-1 flex items-center justify-center gap-1.5 border border-gray-200 text-gray-600 hover:bg-gray-50 text-xs font-semibold px-3 py-2.5 rounded-xl transition"
                                >
                                    <CheckCircle class="w-3.5 h-3.5" /> Check Inventory
                                </button>
                                <button
                                    @click="router.post(route('man.staff.checker-quality.start-production', order.id))"
                                    class="flex-1 flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-2.5 rounded-xl transition"
                                >
                                    <ArrowRight class="w-3.5 h-3.5" /> Start Production
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════════════════════════════════════════════════════════════
                     FABRICS TAB
                ════════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'fabrics'">
                    <div v-if="!fabrics?.length"
                         class="bg-white rounded-2xl border border-dashed border-gray-200 py-14 flex flex-col items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center">
                            <Shirt class="w-6 h-6 text-gray-400" />
                        </div>
                        <p class="text-sm font-medium text-gray-400">No fabrics awaiting quality check</p>
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div
                            v-for="fabric in fabrics"
                            :key="fabric.id"
                            class="bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
                        >
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
                                <span class="text-xs font-bold text-gray-900 font-mono tracking-wide">{{ fabric.code }}</span>
                                <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                    Fabric
                                </span>
                            </div>
                            <div class="px-4 py-3">
                                <p class="text-sm text-gray-500">
                                    Weight: <span class="font-bold text-gray-900">{{ fabric.weight }} kg</span>
                                </p>
                            </div>
                            <div class="px-4 pb-4 flex flex-col sm:flex-row gap-2">
                                <button
                                    @click="passFabric(fabric.id, 'dyeing')"
                                    class="flex-1 flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-2.5 rounded-xl transition"
                                >
                                    <Droplet class="w-3.5 h-3.5" /> Pass to Dyeing
                                </button>
                                <!-- <button
                                    @click="passFabric(fabric.id, 'softener')"
                                    class="flex-1 flex items-center justify-center gap-1.5 bg-yellow-400 hover:bg-yellow-500 text-gray-900 text-xs font-bold px-3 py-2.5 rounded-xl transition"
                                >
                                    <Wind class="w-3.5 h-3.5" /> Pass to Softener
                                </button> -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════════════════════════════════════════════════════════════
                     DYE TAB
                ════════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'dye'">
                    <div v-if="!dyeJobs?.length"
                         class="bg-white rounded-2xl border border-dashed border-gray-200 py-14 flex flex-col items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center">
                            <Droplet class="w-6 h-6 text-gray-400" />
                        </div>
                        <p class="text-sm font-medium text-gray-400">No dye jobs in progress</p>
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div
                            v-for="dye in dyeJobs"
                            :key="dye.id"
                            class="bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
                        >
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
                                <span class="text-xs font-bold text-gray-900 font-mono tracking-wide">{{ dye.code }}</span>
                                <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                    Dyeing
                                </span>
                            </div>
                            <div class="px-4 py-3">
                                <p class="text-xs text-gray-500">
                                    Fabric: <span class="font-semibold text-gray-800 font-mono">{{ dye.fabric.code }}</span>
                                </p>
                            </div>
                            <div class="px-4 pb-4 flex flex-col sm:flex-row gap-2">
                                <button
                                    @click="passDye(dye.id, 'quality')"
                                    class="flex-1 flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-2.5 rounded-xl transition"
                                >
                                    <CheckCircle class="w-3.5 h-3.5" /> Quality Pass
                                </button>
                                <button
                                    @click="askConfirm(
                                        'Reject Dye Job',
                                        `Are you sure you want to reject ${dye.code}? This cannot be undone.`,
                                        () => passDye(dye.id, 'reject')
                                    )"
                                    class="flex-1 flex items-center justify-center gap-1.5 border border-red-200 text-red-600 hover:bg-red-50 text-xs font-semibold px-3 py-2.5 rounded-xl transition"
                                >
                                    <XCircle class="w-3.5 h-3.5" /> Reject
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════════════════════════════════════════════════════════════
                     SOFTENER & SQUEEZER TAB
                ════════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'softener'">
                    <div v-if="!softenerJobs?.length"
                         class="bg-white rounded-2xl border border-dashed border-gray-200 py-14 flex flex-col items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center">
                            <Wind class="w-6 h-6 text-gray-400" />
                        </div>
                        <p class="text-sm font-medium text-gray-400">No softener jobs in progress</p>
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div
                            v-for="job in softenerJobs"
                            :key="job.id"
                            class="bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
                        >
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
                                <span class="text-xs font-bold text-gray-900 font-mono tracking-wide">{{ job.code }}</span>
                                <span
                                    class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                    :class="job.status === 'softened'
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-yellow-100 text-yellow-800'"
                                >
                                    {{ job.status }}
                                </span>
                            </div>
                            <div class="px-4 py-3">
                                <p class="text-xs text-gray-500">
                                    Fabric: <span class="font-semibold text-gray-800 font-mono">{{ job.fabric.code }}</span>
                                </p>
                            </div>
                            <div v-if="job.status === 'softened'" class="px-4 pb-4 flex flex-col sm:flex-row gap-2">
                                <button
                                    @click="passSoftener(job.id, 'quality')"
                                    class="flex-1 flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-2.5 rounded-xl transition"
                                >
                                    <CheckCircle class="w-3.5 h-3.5" /> Pass to Iron
                                </button>
                                <button
                                    @click="passSoftener(job.id, 'resoften')"
                                    class="flex-1 flex items-center justify-center gap-1.5 bg-yellow-400 hover:bg-yellow-500 text-gray-900 text-xs font-bold px-3 py-2.5 rounded-xl transition"
                                >
                                    <Wind class="w-3.5 h-3.5" /> Resoften
                                </button>
                            </div>
                            <div v-else class="px-4 pb-4">
                                <p class="text-xs text-gray-400 italic text-center py-1">Awaiting softening…</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════════════════════════════════════════════════════════════
                     IRON TAB
                ════════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'iron'">
                    <div v-if="!ironJobs?.length"
                         class="bg-white rounded-2xl border border-dashed border-gray-200 py-14 flex flex-col items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center">
                            <Flame class="w-6 h-6 text-gray-400" />
                        </div>
                        <p class="text-sm font-medium text-gray-400">No iron jobs in progress</p>
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div
                            v-for="iron in ironJobs"
                            :key="iron.id"
                            class="bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
                        >
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
                                <span class="text-xs font-bold text-gray-900 font-mono tracking-wide">{{ iron.code }}</span>
                                <span class="inline-flex items-center gap-1 bg-orange-50 text-orange-700 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                    Ironing
                                </span>
                            </div>
                            <div class="px-4 py-3">
                                <p class="text-xs text-gray-500">
                                    Fabric: <span class="font-semibold text-gray-800 font-mono">{{ iron.fabric.code }}</span>
                                </p>
                            </div>
                            <div class="px-4 pb-4 flex flex-col sm:flex-row gap-2">
                                <button
                                    @click="passIron(iron.id, 'form')"
                                    class="flex-1 flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-2.5 rounded-xl transition"
                                >
                                    <Factory class="w-3.5 h-3.5" /> Send to Form
                                </button>
                                <button
                                    @click="passIron(iron.id, 'pack')"
                                    class="flex-1 flex items-center justify-center gap-1.5 bg-yellow-400 hover:bg-yellow-500 text-gray-900 text-xs font-bold px-3 py-2.5 rounded-xl transition"
                                >
                                    <Package class="w-3.5 h-3.5" /> Pack
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════════════════════════════════════════════════════════════
                     FORM TAB
                ════════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'form'">
                    <div v-if="!formJobs?.length"
                         class="bg-white rounded-2xl border border-dashed border-gray-200 py-14 flex flex-col items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center">
                            <Factory class="w-6 h-6 text-gray-400" />
                        </div>
                        <p class="text-sm font-medium text-gray-400">No form jobs in progress</p>
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div
                            v-for="form in formJobs"
                            :key="form.id"
                            class="bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
                        >
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
                                <span class="text-xs font-bold text-gray-900 font-mono tracking-wide">{{ form.code }}</span>
                                <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                    Forming
                                </span>
                            </div>
                            <div class="px-4 py-3 space-y-1">
                                <p class="text-sm font-semibold text-gray-900">{{ form.product.name }}</p>
                                <p class="text-xs text-gray-500">Qty: <span class="font-bold text-gray-800">{{ form.quantity }} pcs</span></p>
                            </div>
                            <div class="px-4 pb-4 flex flex-col sm:flex-row gap-2">
                                <button
                                    @click="packForm(form.id)"
                                    class="flex-1 flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-2.5 rounded-xl transition"
                                >
                                    <Package class="w-3.5 h-3.5" /> Pack
                                </button>
                                <button
                                    @click="askConfirm(
                                        'Reject Form Job',
                                        `Are you sure you want to reject ${form.code}? This action cannot be undone.`,
                                        () => rejectForm(form.id)
                                    )"
                                    class="flex-1 flex items-center justify-center gap-1.5 border border-red-200 text-red-600 hover:bg-red-50 text-xs font-semibold px-3 py-2.5 rounded-xl transition"
                                >
                                    <XCircle class="w-3.5 h-3.5" /> Reject
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════════════════════════════════════════════════════════════
                     PACKED TAB
                ════════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'packed'">
                    <div v-if="!packages?.length"
                         class="bg-white rounded-2xl border border-dashed border-gray-200 py-14 flex flex-col items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center">
                            <Package class="w-6 h-6 text-gray-400" />
                        </div>
                        <p class="text-sm font-medium text-gray-400">No packages ready for assignment</p>
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div
                            v-for="pkg in packages"
                            :key="pkg.id"
                            class="bg-white rounded-2xl border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
                        >
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
                                <span class="text-xs font-bold text-gray-900 font-mono tracking-wide">{{ pkg.code }}</span>
                                <span class="inline-flex items-center gap-1 bg-green-100 text-green-700 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                    Packed
                                </span>
                            </div>
                            <div class="px-4 py-3">
                                <p class="text-xs text-gray-500">
                                    Items: <span class="font-bold text-gray-900">{{ pkg.items.length }} product{{ pkg.items.length !== 1 ? 's' : '' }}</span>
                                </p>
                            </div>
                            <div class="px-4 pb-4">
                                <button
                                    @click="assignToOrder(pkg.id)"
                                    class="w-full flex items-center justify-center gap-1.5 bg-gray-900 hover:bg-gray-700 text-white text-xs font-bold px-3 py-2.5 rounded-xl transition"
                                >
                                    <ArrowRight class="w-3.5 h-3.5" /> Assign to Order
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════
             CONFIRM MODAL — used for all destructive actions
        ═══════════════════════════════════════════════════════════════════ -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="confirmModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="cancelConfirm"
            >
                <!-- backdrop -->
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

                <!-- panel -->
                <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden">

                    <!-- top accent -->
                    <div class="h-1 w-full bg-gradient-to-r from-yellow-400 via-red-400 to-red-500"></div>

                    <!-- content -->
                    <div class="px-6 pt-5 pb-4 text-center">
                        <div class="w-12 h-12 mx-auto mb-4 rounded-2xl bg-red-50 flex items-center justify-center">
                            <AlertTriangle class="w-6 h-6 text-red-500" />
                        </div>
                        <h3 class="text-base font-bold text-gray-900">{{ confirmLabel }}</h3>
                        <p class="text-sm text-gray-500 mt-1.5 leading-relaxed">{{ confirmSub }}</p>
                    </div>

                    <!-- actions -->
                    <div class="flex gap-2 px-6 pb-6">
                        <button
                            @click="cancelConfirm"
                            type="button"
                            class="flex-1 py-2.5 border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-xl transition"
                        >
                            Cancel
                        </button>
                        <button
                            @click="runConfirm"
                            type="button"
                            class="flex-1 py-2.5 bg-red-500 hover:bg-red-600 text-white text-sm font-bold rounded-xl transition"
                        >
                            Yes, Reject
                        </button>
                    </div>

                </div>
            </div>
        </transition>

    </AuthenticatedLayout>
</template>