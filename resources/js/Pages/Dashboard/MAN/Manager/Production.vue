<script setup>
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Package, ClipboardList } from 'lucide-vue-next';

const props = defineProps({
    orders: Array,
});

const forwardToChecker = (order) => {
    if (confirm(`Forward ${order.type === 'sales_order' ? 'Job Order' : 'Purchase Order'} ${order.order_number} to Checker Quality?`)) {
        router.post(route('man.manager.forward-to-checker', order.id), {
            type: order.type,
        });
    }
};

const formatDate = (date) => new Date(date).toLocaleDateString();
</script>

<template>
    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto">
            <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">
                <ClipboardList class="w-6 h-6 text-blue-600" />
                Received Orders (Awaiting Production)
            </h1>

            <div v-if="orders.length === 0" class="bg-white rounded-2xl shadow-sm border p-12 text-center text-gray-500">
                <Package class="w-12 h-12 mx-auto mb-3 opacity-30" />
                <p class="font-bold">No orders waiting for production.</p>
            </div>

            <div v-else class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Qty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Received</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="order in orders" :key="order.id + order.type">
                            <td class="px-6 py-4">
                                <span :class="[
                                    'inline-flex items-center px-2 py-1 rounded-full text-xs font-bold',
                                    order.type === 'sales_order' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700'
                                ]">
                                    {{ order.type === 'sales_order' ? 'Job Order' : 'Purchase Order' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-mono font-bold">{{ order.order_number }}</td>
                            <td class="px-6 py-4">{{ order.client_name }}</td>
                            <td class="px-6 py-4">
                                <ul class="text-sm list-disc list-inside">
                                    <li v-for="(item, idx) in order.items" :key="idx">
                                        {{ item.product_name }} ({{ item.quantity }})
                                    </li>
                                </ul>
                            </td>
                            <td class="px-6 py-4 font-bold">{{ order.total_quantity }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ formatDate(order.created_at) }}</td>
                            <td class="px-6 py-4">
                                <button @click="forwardToChecker(order)"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition shadow-sm">
                                    Start Production
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>