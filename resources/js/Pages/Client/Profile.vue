<template>
    <Head title="Business Profile" />
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto space-y-10 p-4 lg:p-10">

            <!-- ── Header ────────────────────────────────────────────────── -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Building2 class="h-3.5 w-3.5" />
                        Account Management
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        Company <span class="text-indigo-600">Profile</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        Update your business info and pin your exact delivery location on the map.
                    </p>
                </div>
                <button @click="refresh" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <RefreshCw class="h-5 w-5 text-gray-500" />
                </button>
            </div>

            <!-- ── Profile Form ───────────────────────────────────────────── -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <form @submit.prevent="submit" class="p-8 space-y-8">

                    <!-- Company Details -->
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-widest text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                            <Briefcase class="h-4 w-4 text-indigo-500" /> Company Details
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Company Name *</label>
                                <input v-model="form.company_name" type="text" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Business Type *</label>
                                <input v-model="form.business_type" type="text" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">TIN Number</label>
                                <input v-model="form.tin_number" type="text"
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Contact Person *</label>
                                <input v-model="form.contact_person" type="text" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Phone Number *</label>
                                <input v-model="form.phone" type="tel" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Email (read-only)</label>
                                <input :value="client?.email" type="email" disabled
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800/50 px-4 py-3 text-sm text-gray-500 cursor-not-allowed">
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Address -->
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-widest text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                            <MapPin class="h-4 w-4 text-indigo-500" /> Delivery Address
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Street Address *</label>
                                <textarea v-model="form.company_address" rows="2" required
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500"></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">City</label>
                                    <input v-model="form.city" type="text"
                                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Province</label>
                                    <input v-model="form.province" type="text"
                                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Postal Code</label>
                                    <input v-model="form.postal_code" type="text"
                                        class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Pinpoint Location Map ──────────────────────────────── -->
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-widest text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                            <Navigation class="h-4 w-4 text-indigo-500" /> Pinpoint Your Location
                        </h3>
                        <p class="text-xs text-gray-400 mb-4 font-medium">
                            Click anywhere on the map (or drag the pin) to set your exact delivery location.
                            MontiTextiles uses this for route calculation.
                        </p>

                        <!-- Address search bar -->
                        <div class="flex gap-3 mb-3">
                            <div class="relative flex-1">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <input
                                    v-model="addressSearch"
                                    type="text"
                                    placeholder="Search address to jump to location..."
                                    class="w-full pl-10 pr-4 py-3 rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-indigo-500"
                                    @keydown.enter.prevent="searchAddress"
                                />
                            </div>
                            <button type="button" @click="searchAddress" :disabled="isSearching"
                                class="px-4 py-3 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-sm font-black text-gray-600 dark:text-gray-300 transition disabled:opacity-50 flex items-center gap-2">
                                <Loader2 v-if="isSearching" class="h-4 w-4 animate-spin" />
                                <Search v-else class="h-4 w-4" />
                            </button>
                            <button type="button" @click="useCurrentLocation" :disabled="isGettingLocation"
                                class="px-4 py-3 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 text-sm font-black text-indigo-600 transition disabled:opacity-50 flex items-center gap-2 whitespace-nowrap">
                                <Loader2 v-if="isGettingLocation" class="h-4 w-4 animate-spin" />
                                <Crosshair v-else class="h-4 w-4" />
                                My Location
                            </button>
                        </div>

                        <!-- The Map -->
                        <div class="relative rounded-3xl overflow-hidden border border-gray-200 dark:border-gray-700 shadow-inner" style="height:400px;">
                            <div id="client-map" class="w-full h-full z-0"></div>

                            <!-- No pin notice overlay -->
                            <div v-if="!form.latitude || !form.longitude"
                                class="absolute bottom-4 left-1/2 -translate-x-1/2 z-[1000] pointer-events-none">
                                <div class="bg-amber-600/90 backdrop-blur-sm text-white px-4 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 shadow-lg">
                                    <AlertTriangle class="h-3.5 w-3.5" />
                                    Tap the map to drop your location pin
                                </div>
                            </div>

                            <!-- Live coordinate badge -->
                            <div v-if="form.latitude && form.longitude"
                                class="absolute bottom-4 left-4 z-[1000] pointer-events-none">
                                <div class="bg-slate-900/85 backdrop-blur-sm text-white px-4 py-2.5 rounded-2xl text-[10px] font-black tracking-widest flex items-center gap-2 shadow-lg">
                                    <div class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></div>
                                    {{ Number(form.latitude).toFixed(6) }}, {{ Number(form.longitude).toFixed(6) }}
                                </div>
                            </div>

                            <!-- Map type badge -->
                            <div class="absolute top-4 left-4 z-[1000] pointer-events-none">
                                <div class="bg-slate-900/80 backdrop-blur-md text-white px-3 py-1.5 rounded-xl text-[9px] tracking-widest font-black uppercase flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full"></div>
                                    Interactive Pin Map
                                </div>
                            </div>
                        </div>

                        <!-- Coordinate inputs (kept for manual override) -->
                        <div class="mt-3 grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Latitude</label>
                                <input v-model.number="form.latitude" type="number" step="any"
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm font-mono focus:ring-2 focus:ring-indigo-500"
                                    @change="syncMapToForm">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Longitude</label>
                                <input v-model.number="form.longitude" type="number" step="any"
                                    class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm font-mono focus:ring-2 focus:ring-indigo-500"
                                    @change="syncMapToForm">
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1 font-medium">
                            These coordinates will appear as a pin on the Logistics Routes map for delivery planning.
                        </p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                        <button type="submit" :disabled="form.processing"
                            class="px-8 py-3 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            Update Profile
                        </button>
                        <button type="button" @click="resetForm"
                            class="px-8 py-3 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Toast -->
            <Transition name="toast">
                <div v-if="toast.show"
                    class="fixed bottom-8 right-8 z-[9999] px-6 py-3 rounded-xl shadow-lg text-white font-bold text-sm flex items-center gap-2"
                    :class="toast.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'">
                    <CheckCircle2 v-if="toast.type === 'success'" class="h-4 w-4" />
                    <X v-else class="h-4 w-4" />
                    {{ toast.message }}
                </div>
            </Transition>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, nextTick } from 'vue';
import {
    Building2, RefreshCw, Briefcase, MapPin, Navigation,
    Loader2, Search, Crosshair, AlertTriangle, CheckCircle2, X
} from 'lucide-vue-next';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';

// ── Props & state ─────────────────────────────────────────────────────────
const page   = usePage();
const client = ref(page.props.client ?? page.props.auth?.client);

const form = useForm({
    company_name:    client.value?.company_name    || '',
    business_type:   client.value?.business_type   || '',
    tin_number:      client.value?.tin_number      || '',
    contact_person:  client.value?.contact_person  || '',
    phone:           client.value?.phone           || '',
    company_address: client.value?.company_address || '',
    city:            client.value?.city            || '',
    province:        client.value?.province        || '',
    postal_code:     client.value?.postal_code     || '',
    latitude:        client.value?.latitude  ? Number(client.value.latitude)  : null,
    longitude:       client.value?.longitude ? Number(client.value.longitude) : null,
});

const toast             = ref({ show: false, type: 'success', message: '' });
const addressSearch     = ref('');
const isSearching       = ref(false);
const isGettingLocation = ref(false);

// ── Leaflet ────────────────────────────────────────────────────────────────
let clientMap    = null;
let clientMarker = null;

const buildPinIcon = () => L.divIcon({
    className: '',
    html: `<div style="
        background:#6366f1;color:white;border-radius:50%;
        width:36px;height:36px;
        display:flex;align-items:center;justify-content:center;
        border:4px solid white;box-shadow:0 4px 14px rgba(99,102,241,0.55);
        font-size:16px;cursor:pointer;">📍</div>
    <div style="
        position:absolute;bottom:-8px;left:50%;transform:translateX(-50%);
        width:0;height:0;border-left:7px solid transparent;
        border-right:7px solid transparent;border-top:10px solid #6366f1;"></div>`,
    iconSize:   [36, 44],
    iconAnchor: [18, 44],
});

const placeMarker = (lat, lng) => {
    if (clientMarker) {
        clientMarker.setLatLng([lat, lng]);
    } else {
        clientMarker = L.marker([lat, lng], {
            icon:      buildPinIcon(),
            draggable: true,
        }).addTo(clientMap);

        clientMarker.on('dragend', (e) => {
            const pos        = e.target.getLatLng();
            form.latitude    = parseFloat(pos.lat.toFixed(8));
            form.longitude   = parseFloat(pos.lng.toFixed(8));
        });
    }
    form.latitude  = parseFloat(lat.toFixed(8));
    form.longitude = parseFloat(lng.toFixed(8));
};

const initClientMap = () => {
    const startLat = form.latitude  || 14.5995;
    const startLng = form.longitude || 120.9842;
    const startZoom = form.latitude ? 16 : 12;

    const street    = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 });
    const satellite = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        { maxZoom: 20 }
    );

    clientMap = L.map('client-map', {
        center: [startLat, startLng],
        zoom:   startZoom,
        layers: [street],
    });
    L.control.layers({ 'Street': street, 'Satellite': satellite }).addTo(clientMap);

    // If the client already has a saved location, show it
    if (form.latitude && form.longitude) {
        placeMarker(form.latitude, form.longitude);
    }

    // Click-to-pin
    clientMap.on('click', (e) => {
        placeMarker(e.latlng.lat, e.latlng.lng);
        clientMap.panTo([e.latlng.lat, e.latlng.lng]);
    });
};

// Keep map marker in sync when form coords are manually typed
const syncMapToForm = () => {
    if (!clientMap || !form.latitude || !form.longitude) return;
    placeMarker(form.latitude, form.longitude);
    clientMap.panTo([form.latitude, form.longitude]);
};

// ── Address Search (Nominatim) ─────────────────────────────────────────────
const searchAddress = async () => {
    if (!addressSearch.value.trim()) return;
    isSearching.value = true;
    try {
        const res = await axios.get('https://nominatim.openstreetmap.org/search', {
            params: { q: addressSearch.value, format: 'json', limit: 1, countrycodes: 'ph' },
            headers: { 'Accept-Language': 'en' },
        });
        if (res.data.length) {
            const { lat, lon, display_name } = res.data[0];
            placeMarker(parseFloat(lat), parseFloat(lon));
            clientMap.setView([parseFloat(lat), parseFloat(lon)], 17);
            showToast('success', `Found: ${display_name.split(',').slice(0, 3).join(',')}`);
        } else {
            showToast('error', 'Address not found. Try a more specific search.');
        }
    } catch {
        showToast('error', 'Search failed. Please try again.');
    } finally {
        isSearching.value = false;
    }
};

// ── GPS Current Location ───────────────────────────────────────────────────
const useCurrentLocation = () => {
    if (!navigator.geolocation) {
        showToast('error', 'Geolocation not supported by your browser.');
        return;
    }
    isGettingLocation.value = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            placeMarker(pos.coords.latitude, pos.coords.longitude);
            clientMap.setView([pos.coords.latitude, pos.coords.longitude], 17);
            showToast('success', 'GPS location captured.');
            isGettingLocation.value = false;
        },
        () => {
            showToast('error', 'Unable to retrieve GPS location. Please allow location access.');
            isGettingLocation.value = false;
        },
        { enableHighAccuracy: true }
    );
};

// ── Helpers ────────────────────────────────────────────────────────────────
const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3500);
};

const submit = () => {
    form.patch(route('client.profile.update'), {
        preserveScroll: true,
        onSuccess: () => showToast('success', 'Profile updated successfully.'),
        onError: (errors) => showToast('error', Object.values(errors)[0] || 'Update failed.'),
    });
};

const resetForm = () => {
    form.company_name    = client.value?.company_name    || '';
    form.business_type   = client.value?.business_type   || '';
    form.tin_number      = client.value?.tin_number      || '';
    form.contact_person  = client.value?.contact_person  || '';
    form.phone           = client.value?.phone           || '';
    form.company_address = client.value?.company_address || '';
    form.city            = client.value?.city            || '';
    form.province        = client.value?.province        || '';
    form.postal_code     = client.value?.postal_code     || '';
    form.latitude        = client.value?.latitude  ? Number(client.value.latitude)  : null;
    form.longitude       = client.value?.longitude ? Number(client.value.longitude) : null;
    syncMapToForm();
};

const refresh = () => router.reload({ only: ['client'] });

onMounted(() => nextTick(() => initClientMap()));
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(20px); }

:deep(.leaflet-control-layers) {
    border-radius: 1rem !important;
    border: none !important;
    box-shadow: 0 4px 15px rgba(0,0,0,0.12);
    padding: 6px 10px;
    font-weight: 900;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
:deep(.leaflet-popup-content-wrapper) {
    border-radius: 1rem !important;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}
:deep(.leaflet-popup-tip) { display: none; }
</style>