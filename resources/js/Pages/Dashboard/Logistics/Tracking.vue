<template>
    <Head title="Live Tracking" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-8 p-4 lg:p-10">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <MapPin class="h-3.5 w-3.5" />
                        Fleet Visibility
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Delivery <span class="text-indigo-600">Tracking</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Real‑time map of all active deliveries and complete trip history.
                    </p>
                </div>
                <button @click="refreshData" class="p-2.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 transition">
                    <RefreshCw class="h-4 w-4 text-gray-500" />
                </button>
            </div>

            <!-- Live Map Card -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                            <Globe class="h-4 w-4 text-indigo-600" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Live Map</p>
                            <p class="text-sm font-black text-gray-900 dark:text-white">
                                Active Deliveries & Routes
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-[10px] font-black uppercase tracking-widest text-gray-400">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-blue-500"></span> HQ
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span> Destination
                        </div>
                        <div class="flex items-center gap-1.5">
                            <Truck class="h-3.5 w-3.5 text-amber-500" /> Truck
                        </div>
                    </div>
                </div>
                <div class="relative p-3">
                    <div v-if="activeDeliveries.length === 0" class="absolute inset-3 z-10 flex items-center justify-center bg-gray-50/80 dark:bg-gray-800/50 backdrop-blur-sm rounded-3xl border-2 border-dashed border-gray-200">
                        <div class="text-center">
                            <Truck class="h-8 w-8 text-gray-400 mx-auto mb-2" />
                            <p class="text-sm font-black text-gray-500">No active deliveries on the map</p>
                            <p class="text-xs text-gray-400 mt-1">Dispatched or in‑transit trips will appear here</p>
                        </div>
                    </div>
                    <div id="tracking-map" class="w-full rounded-3xl z-0" style="height: 480px;"></div>
                </div>
                <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-800 flex items-center gap-4 text-xs">
                    <span class="text-gray-500">{{ activeDeliveries.length }} active trip(s)</span>
                    <span class="text-gray-400">•</span>
                    <span class="text-gray-500">{{ deliveredCount }} delivered</span>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 pb-1">
                <button
                    @click="setFilter('all')"
                    :class="[
                        'px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition',
                        currentFilter === 'all'
                            ? 'bg-indigo-600 text-white shadow-sm'
                            : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800'
                    ]"
                >
                    All Deliveries
                </button>
                <button
                    @click="setFilter('scheduled')"
                    :class="[
                        'px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition',
                        currentFilter === 'scheduled'
                            ? 'bg-amber-600 text-white shadow-sm'
                            : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800'
                    ]"
                >
                    Scheduled
                </button>
                <button
                    @click="setFilter('active')"
                    :class="[
                        'px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition',
                        currentFilter === 'active'
                            ? 'bg-blue-600 text-white shadow-sm'
                            : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800'
                    ]"
                >
                    Active (Dispatched / In Transit)
                </button>
                <button
                    @click="setFilter('delivered')"
                    :class="[
                        'px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition',
                        currentFilter === 'delivered'
                            ? 'bg-emerald-600 text-white shadow-sm'
                            : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800'
                    ]"
                >
                    Delivered
                </button>
            </div>

            <!-- Deliveries Table -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-[0.15em]">
                            <tr>
                                <th class="px-6 py-4">Delivery #</th>
                                <th class="px-6 py-4">Driver / Truck</th>
                                <th class="px-6 py-4">Route</th>
                                <th class="px-6 py-4">Scheduled</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Packages</th>
                                <th class="px-6 py-4 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                            <tr v-for="delivery in filteredDeliveries" :key="delivery.id" class="group hover:bg-gray-50/50 transition">
                                <td class="px-6 py-4">
                                    <span class="font-mono text-sm font-black">{{ delivery.delivery_number }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium">{{ delivery.driver?.user?.name || '—' }}</div>
                                    <div class="text-xs text-gray-500">{{ delivery.truck?.truck_number || '—' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm">{{ delivery.route?.name || '—' }}</div>
                                    <div class="text-xs text-gray-500 truncate max-w-[200px]">
                                        {{ delivery.route?.origin }} → {{ delivery.route?.destination }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    {{ formatDateTime(delivery.scheduled_departure) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="statusBadge(delivery.status)" class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase">
                                        {{ formatStatus(delivery.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    {{ delivery.packages?.length || 0 }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        @click="flyToDelivery(delivery)"
                                        class="text-indigo-600 text-xs font-bold hover:underline flex items-center justify-end gap-1"
                                    >
                                        <Eye class="h-3.5 w-3.5" /> View on Map
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredDeliveries.length === 0">
                                <td colspan="7" class="px-6 py-16 text-center text-gray-400 uppercase font-black italic">
                                    <Truck class="h-10 w-10 mx-auto mb-2 opacity-30" />
                                    No deliveries match the selected filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { MapPin, RefreshCw, Globe, Truck, Eye } from 'lucide-vue-next';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    deliveries: { type: Array, default: () => [] },
    filter: { type: String, default: 'all' },
    montiLocation: { type: Object, default: null },
});

const currentFilter = ref(props.filter || 'all');
let map = null;
let markersLayer = null;
let polylinesLayer = null;

// Fallback coordinates if CEO hasn't set any
const defaultLat = 14.3275;
const defaultLng = 120.9404;
const montiLat = props.montiLocation ? Number(props.montiLocation.latitude) : defaultLat;
const montiLng = props.montiLocation ? Number(props.montiLocation.longitude) : defaultLng;

const filteredDeliveries = computed(() => {
    if (currentFilter.value === 'all') return props.deliveries;
    if (currentFilter.value === 'scheduled') return props.deliveries.filter(d => d.status === 'pending');
    if (currentFilter.value === 'active') return props.deliveries.filter(d => ['dispatched', 'in_transit'].includes(d.status));
    if (currentFilter.value === 'delivered') return props.deliveries.filter(d => d.status === 'delivered');
    return props.deliveries;
});

const activeDeliveries = computed(() =>
    props.deliveries.filter(d => ['dispatched', 'in_transit'].includes(d.status))
);

const deliveredCount = computed(() => props.deliveries.filter(d => d.status === 'delivered').length);

const setFilter = (filter) => {
    currentFilter.value = filter;
    router.get(route('logistics.tracking'), { filter }, { preserveState: true, preserveScroll: true });
};

const refreshData = () => {
    router.reload({ only: ['deliveries'] });
};

const statusBadge = (status) => {
    const map = {
        pending: 'bg-gray-100 text-gray-700',
        dispatched: 'bg-amber-100 text-amber-700',
        in_transit: 'bg-blue-100 text-blue-700',
        delivered: 'bg-emerald-100 text-emerald-700',
    };
    return map[status] || 'bg-gray-100 text-gray-600';
};

const formatStatus = (status) => {
    const map = {
        pending: 'Scheduled',
        dispatched: 'Dispatched',
        in_transit: 'In Transit',
        delivered: 'Delivered',
    };
    return map[status] || status;
};

const formatDateTime = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

// ---------- Map ----------
const buildTruckIcon = () =>
    L.divIcon({
        className: '',
        html: `<div style="background:#f59e0b;color:white;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 3px 10px rgba(245,158,11,0.5);font-size:16px;">🚚</div>`,
        iconSize: [32, 32],
        iconAnchor: [16, 16],
    });

const buildDestIcon = () =>
    L.divIcon({
        className: '',
        html: `<div style="background:#10b981;color:white;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;border:2px solid white;box-shadow:0 3px 10px rgba(16,185,129,0.5);font-size:14px;">📍</div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 28],
    });

const initMap = () => {
    if (map) return;
    const street = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 });
    const satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 20 });
    map = L.map('tracking-map', { center: [montiLat, montiLng], zoom: 11, layers: [street] });
    L.control.layers({ Street: street, Satellite: satellite }).addTo(map);

    // HQ marker
    L.marker([montiLat, montiLng], {
        icon: L.divIcon({
            html: `<div style="background:#3b82f6;color:white;border-radius:50%;width:34px;height:34px;display:flex;align-items:center;justify-content:center;border:3px solid white;box-shadow:0 3px 10px rgba(59,130,246,0.5);font-size:16px;">🏭</div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 17],
        }),
    }).bindPopup('<strong>MontiTextiles HQ</strong>').addTo(map);

    markersLayer = L.layerGroup().addTo(map);
    polylinesLayer = L.layerGroup().addTo(map);

    drawActiveDeliveries();
};

const drawActiveDeliveries = () => {
    if (!map || !markersLayer || !polylinesLayer) return;
    markersLayer.clearLayers();
    polylinesLayer.clearLayers();

    activeDeliveries.value.forEach((delivery) => {
        const route = delivery.route;
        if (!route) return;

        let polyline;
        if (route.route_geometry) {
            let geom = route.route_geometry;
            if (typeof geom === 'string') {
                try { geom = JSON.parse(geom); } catch { geom = null; }
            }
            if (Array.isArray(geom) && geom.length > 1) {
                polyline = L.polyline(geom, { color: '#6366f1', weight: 5, opacity: 0.8 });
            }
        }
        if (!polyline && route.origin_lat && route.destination_lat) {
            polyline = L.polyline(
                [
                    [Number(route.origin_lat), Number(route.origin_lng)],
                    [Number(route.destination_lat), Number(route.destination_lng)],
                ],
                { color: '#f59e0b', weight: 4, dashArray: '10, 6', opacity: 0.7 }
            );
        }
        if (polyline) {
            polyline.bindPopup(`
                <strong>${delivery.delivery_number}</strong><br>
                ${delivery.driver?.user?.name || 'No driver'}<br>
                Truck: ${delivery.truck?.truck_number || '—'}<br>
                Status: ${formatStatus(delivery.status)}
            `);
            polyline.addTo(polylinesLayer);
        }

        // Destination marker
        if (route.destination_lat && route.destination_lng) {
            L.marker([Number(route.destination_lat), Number(route.destination_lng)], { icon: buildDestIcon() })
                .bindPopup(`<strong>Destination</strong><br>${route.destination}`)
                .addTo(markersLayer);
        }

        // Truck marker – placed at origin for now (future GPS can update this)
        const truckLat = route.origin_lat || montiLat;
        const truckLng = route.origin_lng || montiLng;
        L.marker([Number(truckLat), Number(truckLng)], { icon: buildTruckIcon() })
            .bindPopup(`
                <strong>${delivery.delivery_number}</strong><br>
                Driver: ${delivery.driver?.user?.name || '—'}<br>
                Truck: ${delivery.truck?.truck_number || '—'}<br>
                Status: ${formatStatus(delivery.status)}
            `)
            .addTo(markersLayer);
    });

    if (activeDeliveries.value.length > 0) {
        const bounds = L.latLngBounds([]);
        markersLayer.eachLayer((layer) => {
            if (layer.getLatLng) bounds.extend(layer.getLatLng());
        });
        polylinesLayer.eachLayer((layer) => {
            if (layer.getBounds) bounds.extend(layer.getBounds());
        });
        if (bounds.isValid()) map.fitBounds(bounds, { padding: [50, 50] });
    }
};

const flyToDelivery = (delivery) => {
    if (!map) return;
    const route = delivery.route;
    if (route?.route_geometry) {
        let geom = route.route_geometry;
        if (typeof geom === 'string') {
            try { geom = JSON.parse(geom); } catch { geom = null; }
        }
        if (Array.isArray(geom) && geom.length > 1) {
            map.fitBounds(L.polyline(geom).getBounds(), { padding: [50, 50] });
            return;
        }
    }
    if (route?.origin_lat && route?.destination_lat) {
        map.fitBounds([
            [Number(route.origin_lat), Number(route.origin_lng)],
            [Number(route.destination_lat), Number(route.destination_lng)],
        ], { padding: [50, 50] });
    }
};

watch(activeDeliveries, () => {
    nextTick(() => drawActiveDeliveries());
}, { deep: true });

onMounted(() => {
    nextTick(() => initMap());
});
</script>