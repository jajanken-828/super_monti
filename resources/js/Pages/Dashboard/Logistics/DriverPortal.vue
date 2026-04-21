<template>
    <Head title="Driver Portal" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto space-y-8 p-4 lg:p-10">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em]">
                        <Truck class="h-3.5 w-3.5" />
                        Driver Portal
                    </div>
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tighter uppercase">
                        My <span class="text-indigo-600">Dashboard</span>
                    </h1>
                    <p class="text-sm font-medium text-gray-500 italic">
                        View assigned trips, track routes, and manage your profile.
                    </p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700 pb-1">
                <button @click="activeTab = 'trip'"
                    :class="[
                        'px-6 py-2.5 rounded-t-xl text-[10px] font-black uppercase tracking-widest transition',
                        activeTab === 'trip'
                            ? 'bg-indigo-600 text-white shadow-sm'
                            : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800'
                    ]">
                    Active Trip
                </button>
                <button @click="activeTab = 'profile'"
                    :class="[
                        'px-6 py-2.5 rounded-t-xl text-[10px] font-black uppercase tracking-widest transition',
                        activeTab === 'profile'
                            ? 'bg-indigo-600 text-white shadow-sm'
                            : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800'
                    ]">
                    My Profile
                </button>
            </div>

            <!-- Active Trip Tab -->
            <div v-if="activeTab === 'trip'" class="space-y-6">
                <!-- Map Card -->
                <div v-if="activeDelivery" class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                                <MapPin class="h-4 w-4 text-indigo-600" />
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Your Route</p>
                                <p class="text-sm font-black text-gray-900 dark:text-white">
                                    {{ activeDelivery.route?.name || 'Delivery Route' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span v-if="simulating" class="text-amber-600 text-xs font-black animate-pulse">Simulating...</span>
                            <span :class="statusBadge(activeDelivery.status)" class="px-3 py-1 rounded-full text-[9px] font-black uppercase">
                                {{ formatStatus(activeDelivery.status) }}
                            </span>
                        </div>
                    </div>
                    <div class="relative p-3">
                        <div id="driver-map" class="w-full rounded-3xl z-0" style="height: 400px;"></div>
                    </div>
                </div>

                <!-- No Active Delivery -->
                <div v-else class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-12 text-center border border-gray-100 dark:border-gray-800">
                    <Truck class="h-16 w-16 mx-auto text-gray-300 mb-4" />
                    <p class="text-gray-500 font-bold">No active delivery assigned.</p>
                    <p class="text-xs text-gray-400">You will see your trip here once dispatched.</p>
                </div>

                <!-- Delivery Details & Actions -->
                <div v-if="activeDelivery" class="bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 text-white">
                        <p class="text-[10px] font-black opacity-80">DELIVERY #</p>
                        <p class="font-mono text-xl font-bold">{{ activeDelivery.delivery_number }}</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <!-- Trip Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex items-start gap-3">
                                <Users class="h-5 w-5 text-indigo-500 mt-0.5" />
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase">Driver</p>
                                    <p class="font-medium">{{ activeDelivery.driver?.user?.name || '—' }}</p>
                                    <p class="text-xs text-gray-500">License: {{ activeDelivery.driver?.license_number }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <Navigation class="h-5 w-5 text-emerald-500 mt-0.5" />
                                <div>
                                    <p class="text-[10px] font-black text-gray-400 uppercase">Route</p>
                                    <p class="font-medium">{{ activeDelivery.route?.name || '—' }}</p>
                                    <p class="text-xs text-gray-500">{{ activeDelivery.route?.origin }} → {{ activeDelivery.route?.destination }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Packages Summary -->
                        <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
                            <p class="text-[10px] font-black text-gray-400 uppercase">Packages on Board</p>
                            <p class="text-sm">{{ activeDelivery.packages?.length || 0 }} package(s) · {{ totalQuantity(activeDelivery) }} pcs total</p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-wrap gap-3 pt-2">
                            <button v-if="activeDelivery.status === 'dispatched' && !simulating"
                                @click="markInTransit(activeDelivery)"
                                class="flex-1 py-3 bg-blue-600 text-white rounded-xl text-[10px] font-black uppercase hover:bg-blue-700 transition flex items-center justify-center gap-2">
                                <Play class="h-4 w-4" /> Start Trip
                            </button>
                            <button v-if="activeDelivery.status === 'in_transit' && !simulating"
                                @click="openProofModal(activeDelivery)"
                                class="flex-1 py-3 bg-emerald-600 text-white rounded-xl text-[10px] font-black uppercase hover:bg-emerald-700 transition flex items-center justify-center gap-2">
                                <Camera class="h-4 w-4" /> Upload Proof & Complete
                            </button>
                            <!-- Simulate Delivery Button -->
                            <button v-if="!simulating && activeDelivery.status !== 'delivered'"
                                @click="startSimulation"
                                :disabled="!routeGeometry || routeGeometry.length < 2"
                                class="flex-1 py-3 bg-amber-600 text-white rounded-xl text-[10px] font-black uppercase hover:bg-amber-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                <FlaskConical class="h-4 w-4" /> Simulate Delivery
                            </button>
                            <button v-if="simulating"
                                disabled
                                class="flex-1 py-3 bg-gray-400 text-white rounded-xl text-[10px] font-black uppercase flex items-center justify-center gap-2">
                                <Loader2 class="h-4 w-4 animate-spin" /> Simulating...
                            </button>
                        </div>
                        <p v-if="!routeGeometry || routeGeometry.length < 2" class="text-xs text-amber-600 text-center">
                            Route geometry not available – simulation disabled.
                        </p>
                    </div>
                </div>
            </div>

            <!-- My Profile Tab -->
            <div v-if="activeTab === 'profile'" class="bg-white dark:bg-gray-900 rounded-[2.5rem] border border-gray-100 dark:border-gray-800 shadow-sm p-8">
                <h2 class="text-xl font-black text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <User class="h-5 w-5 text-indigo-500" /> Personal Information
                </h2>
                <form @submit.prevent="submitProfile" class="max-w-2xl space-y-5">
                    <!-- Profile Photo -->
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <img v-if="userPhotoPreview" :src="userPhotoPreview" class="h-16 w-16 rounded-full object-cover border-2 border-indigo-200" />
                            <div v-else class="h-16 w-16 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xl font-black">
                                {{ profileForm.name?.charAt(0) || user.name?.charAt(0) || '?' }}
                            </div>
                            <label class="absolute bottom-0 right-0 p-1 bg-white rounded-full shadow cursor-pointer">
                                <Camera class="h-3.5 w-3.5 text-gray-600" />
                                <input type="file" @change="handlePhotoUpload" accept="image/*" class="hidden" />
                            </label>
                        </div>
                        <div class="text-xs text-gray-500">Click the camera icon to change photo</div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Full Name</label>
                        <input v-model="profileForm.name" type="text" required
                            class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Email</label>
                        <input v-model="profileForm.email" type="email" required
                            class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">New Password (optional)</label>
                        <input v-model="profileForm.password" type="password"
                            class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-1">Confirm Password</label>
                        <input v-model="profileForm.password_confirmation" type="password"
                            class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm">
                    </div>
                    <button type="submit" :disabled="profileForm.processing"
                        class="px-6 py-3 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase hover:bg-indigo-700 transition disabled:opacity-50 flex items-center gap-2">
                        <Loader2 v-if="profileForm.processing" class="h-4 w-4 animate-spin" />
                        Save Changes
                    </button>
                </form>
            </div>
        </div>

        <!-- Proof Upload Modal -->
        <Teleport to="body">
            <div v-if="showProofModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeProofModal">
                <div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden">
                    <div class="px-6 py-4 bg-emerald-600 text-white flex justify-between items-center">
                        <h3 class="font-black text-lg">Complete Delivery</h3>
                        <button @click="closeProofModal" class="p-1 hover:bg-white/20 rounded-lg"><X class="h-5 w-5" /></button>
                    </div>
                    <form @submit.prevent="submitProof" class="p-6 space-y-5">
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-2">Proof Photo *</label>
                            <input type="file" @change="handleProofImage" accept="image/*" required class="text-sm" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-wider mb-2">Notes (Optional)</label>
                            <textarea v-model="proofForm.notes" rows="3"
                                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 px-4 py-3 text-sm"
                                placeholder="Any additional notes..."></textarea>
                        </div>
                        <button type="submit" :disabled="proofForm.processing"
                            class="w-full py-3 bg-emerald-600 text-white rounded-xl text-[10px] font-black uppercase hover:bg-emerald-700 transition flex items-center justify-center gap-2">
                            <Loader2 v-if="proofForm.processing" class="h-4 w-4 animate-spin" />
                            Submit & Complete
                        </button>
                    </form>
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
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Truck, MapPin, Users, Navigation, Camera, Play, FlaskConical, X, Loader2, User } from 'lucide-vue-next';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    deliveries: { type: Array, default: () => [] },
    driver: { type: Object, default: () => ({}) },
    user: { type: Object, default: () => ({}) },
    montiLocation: { type: Object, default: null },
});

const activeTab = ref('trip');
const showProofModal = ref(false);
const proofImage = ref(null);
const toast = ref({ show: false, type: 'success', message: '' });
const userPhotoPreview = ref(props.user?.profile_photo_path ? `/storage/${props.user.profile_photo_path}` : null);

// Simulation state
const simulating = ref(false);
const truckMarker = ref(null);
const routeGeometry = ref(null);

// Active delivery (first one, as driver usually has one active trip)
const activeDelivery = computed(() => props.deliveries.length > 0 ? props.deliveries[0] : null);

// Profile form
const profileForm = useForm({
    name: props.user?.name || '',
    email: props.user?.email || '',
    password: '',
    password_confirmation: '',
    profile_photo: null,
});

// Proof form
const proofForm = useForm({
    image: null,
    notes: '',
});

// Map instance
let map = null;
let routePolyline = null;
let markers = [];

// HQ coordinates (fallback)
const montiLat = props.montiLocation ? Number(props.montiLocation.latitude) : 14.3275;
const montiLng = props.montiLocation ? Number(props.montiLocation.longitude) : 120.9404;

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

const totalQuantity = (delivery) => {
    return delivery.packages?.reduce((sum, p) => sum + (p.quantity || 0), 0) || 0;
};

const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => { toast.value.show = false; }, 3000);
};

const handlePhotoUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        profileForm.profile_photo = file;
        userPhotoPreview.value = URL.createObjectURL(file);
    }
};

const submitProfile = () => {
    profileForm.post(route('logistics.driver.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            showToast('success', 'Profile updated successfully.');
            profileForm.password = '';
            profileForm.password_confirmation = '';
        },
        onError: (errors) => {
            showToast('error', Object.values(errors)[0] || 'Update failed.');
        }
    });
};

const markInTransit = (delivery) => {
    router.post(route('logistics.driver.transit', delivery.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showToast('success', 'Trip started.');
            // Reload to reflect status change
            router.reload({ only: ['deliveries'] });
        },
        onError: () => showToast('error', 'Failed to start trip.')
    });
};

const openProofModal = (delivery) => {
    proofForm.reset();
    proofImage.value = null;
    showProofModal.value = true;
};

const closeProofModal = () => {
    showProofModal.value = false;
};

const handleProofImage = (e) => {
    const file = e.target.files[0];
    if (file) {
        proofForm.image = file;
    }
};

const submitProof = () => {
    if (!proofForm.image) {
        showToast('error', 'Please select a proof image.');
        return;
    }
    proofForm.post(route('logistics.driver.proof', activeDelivery.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showToast('success', 'Delivery completed.');
            closeProofModal();
            router.reload({ only: ['deliveries'] });
        },
        onError: (errors) => {
            showToast('error', Object.values(errors)[0] || 'Upload failed.');
        }
    });
};

// Map initialization
const initMap = () => {
    if (map) return;
    const el = document.getElementById('driver-map');
    if (!el) return;

    map = L.map('driver-map', { center: [montiLat, montiLng], zoom: 11 });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

    // HQ marker
    L.marker([montiLat, montiLng], {
        icon: L.divIcon({
            html: `<div style="background:#3b82f6;color:white;border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;border:3px solid white;font-size:14px;">🏭</div>`,
            iconSize: [30, 30],
            iconAnchor: [15, 15],
        })
    }).bindPopup('MontiTextiles HQ').addTo(map);

    drawRoute();
};

const drawRoute = () => {
    if (!map) return;

    // Clear previous layers
    markers.forEach(m => m.remove());
    markers = [];
    if (routePolyline) routePolyline.remove();
    if (truckMarker.value) truckMarker.value.remove();

    const delivery = activeDelivery.value;
    if (!delivery || !delivery.route) return;

    const route = delivery.route;

    // Parse geometry
    let geom = null;
    if (route.route_geometry) {
        if (typeof route.route_geometry === 'string') {
            try { geom = JSON.parse(route.route_geometry); } catch { geom = null; }
        } else if (Array.isArray(route.route_geometry)) {
            geom = route.route_geometry;
        }
    }
    routeGeometry.value = geom;

    // Draw polyline
    if (geom && geom.length > 1) {
        routePolyline = L.polyline(geom, { color: '#6366f1', weight: 5, opacity: 0.8 }).addTo(map);
        map.fitBounds(routePolyline.getBounds(), { padding: [50, 50] });
    } else if (route.origin_lat && route.destination_lat) {
        routePolyline = L.polyline(
            [
                [Number(route.origin_lat), Number(route.origin_lng)],
                [Number(route.destination_lat), Number(route.destination_lng)],
            ],
            { color: '#f59e0b', weight: 4, dashArray: '10, 6', opacity: 0.7 }
        ).addTo(map);
        map.fitBounds(routePolyline.getBounds(), { padding: [50, 50] });
    }

    // Destination marker
    if (route.destination_lat && route.destination_lng) {
        const destMarker = L.marker([Number(route.destination_lat), Number(route.destination_lng)], {
            icon: L.divIcon({
                html: `<div style="background:#10b981;color:white;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;border:2px solid white;font-size:14px;">📍</div>`,
                iconSize: [28, 28],
                iconAnchor: [14, 28],
            })
        }).bindPopup(`<strong>Destination</strong><br>${route.destination}`).addTo(map);
        markers.push(destMarker);
    }

    // Initial truck marker at origin
    const truckLat = geom && geom.length > 0 ? geom[0][0] : (route.origin_lat || montiLat);
    const truckLng = geom && geom.length > 0 ? geom[0][1] : (route.origin_lng || montiLng);
    truckMarker.value = L.marker([Number(truckLat), Number(truckLng)], {
        icon: L.divIcon({
            html: `<div style="background:#f59e0b;color:white;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:3px solid white;font-size:16px;">🚚</div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 16],
        })
    }).bindPopup(`<strong>${delivery.delivery_number}</strong><br>Driver: ${delivery.driver?.user?.name}`).addTo(map);
};

// Simulation animation
const startSimulation = () => {
    const geom = routeGeometry.value;
    if (!geom || geom.length < 2 || !truckMarker.value) {
        showToast('error', 'No route geometry available for simulation.');
        return;
    }

    simulating.value = true;
    const marker = truckMarker.value;
    const startTime = performance.now();
    const duration = 8000; // 8 seconds animation
    const totalPoints = geom.length;
    const totalDistance = geom.reduce((sum, point, i) => {
        if (i === 0) return 0;
        const prev = geom[i - 1];
        return sum + Math.sqrt((point[0] - prev[0]) ** 2 + (point[1] - prev[1]) ** 2);
    }, 0);

    let currentSegment = 0;
    let segmentProgress = 0;

    const animate = (now) => {
        const elapsed = now - startTime;
        const t = Math.min(elapsed / duration, 1);

        // Linear interpolation along the whole path
        const targetDistance = t * totalDistance;
        let distanceAccum = 0;
        let segStart = geom[0];
        let segEnd = geom[1];
        let segIndex = 0;

        for (let i = 0; i < totalPoints - 1; i++) {
            const p1 = geom[i];
            const p2 = geom[i + 1];
            const segDist = Math.sqrt((p2[0] - p1[0]) ** 2 + (p2[1] - p1[1]) ** 2);
            if (distanceAccum + segDist >= targetDistance) {
                segStart = p1;
                segEnd = p2;
                segIndex = i;
                break;
            }
            distanceAccum += segDist;
            if (i === totalPoints - 2) {
                // Past end – use last segment
                segStart = geom[totalPoints - 2];
                segEnd = geom[totalPoints - 1];
                segIndex = totalPoints - 2;
            }
        }

        const segDistance = Math.sqrt((segEnd[0] - segStart[0]) ** 2 + (segEnd[1] - segStart[1]) ** 2);
        const segT = segDistance > 0 ? (targetDistance - distanceAccum) / segDistance : 0;
        const lat = segStart[0] + (segEnd[0] - segStart[0]) * segT;
        const lng = segStart[1] + (segEnd[1] - segStart[1]) * segT;

        marker.setLatLng([lat, lng]);

        if (t < 1) {
            requestAnimationFrame(animate);
        } else {
            // Animation complete
            simulating.value = false;
            showToast('success', 'Simulation complete. You can now upload proof of delivery.');
            // Optionally open proof modal automatically
            openProofModal(activeDelivery.value);
        }
    };

    requestAnimationFrame(animate);
};

watch(activeDelivery, () => {
    nextTick(() => drawRoute());
}, { immediate: true });

onMounted(() => {
    nextTick(() => {
        if (activeTab.value === 'trip' && activeDelivery.value) {
            initMap();
        }
    });
});

watch(activeTab, (newTab) => {
    if (newTab === 'trip' && activeDelivery.value) {
        nextTick(() => initMap());
    }
});
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