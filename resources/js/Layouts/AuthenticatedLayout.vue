<script setup>
import Sidebar from './Sidebar.vue'
import MobileSidebar from './MobileSidebar.vue'
import { usePage } from '@inertiajs/vue3'
import { computed, onMounted } from 'vue'
import axios from 'axios'

const page = usePage()

// User data from Inertia shared props
const user = computed(() => page.props.auth.user)

/**
 * BACKGROUND GEOFENCE DETECTOR
 * This runs when any page using this layout is loaded.
 * It injects GPS coordinates into the headers for the Middleware to check.
 */
onMounted(() => {
    if (navigator.geolocation) {
        // We use watchPosition to keep coordinates fresh as the user moves
        navigator.geolocation.watchPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;

                // 1. Set global Axios headers for background requests
                axios.defaults.headers.common['X-User-Lat'] = lat;
                axios.defaults.headers.common['X-User-Lng'] = lng;

                // 2. Also attach to Inertia global headers for page visits/links
                // This ensures that even clicking a link triggers the middleware check
                page.props.ziggy.location_headers = {
                    'X-User-Lat': lat,
                    'X-User-Lng': lng
                };
                
                console.log(`📡 Geofence Active: ${lat}, ${lng}`);
            },
            (err) => {
                console.error("❌ Geofence Sensor Error:", err.message);
            },
            { 
                enableHighAccuracy: true, 
                maximumAge: 0, 
                timeout: 10000 
            }
        );
    } else {
        console.error("❌ Browser does not support Geolocation.");
    }
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-zinc-950 transition-colors duration-300">
        <Sidebar />

        <div class="md:hidden bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-40">
            <div class="px-4 py-4 flex items-center justify-between h-16">
                <div class="flex items-center">
                    <MobileSidebar />
                    <div class="ml-4">
                        <h1 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                            Monti <span class="text-blue-600">Textile</span>
                        </h1>
                    </div>
                </div>
                <div
                    class="h-8 w-8 rounded-lg bg-blue-600 flex items-center justify-center text-white text-xs font-bold shadow-sm">
                    {{ user?.name?.charAt(0) }}
                </div>
            </div>
        </div>

        <div class="md:pl-64 flex flex-col flex-1">
            <main class="py-8">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div v-if="axios.defaults.headers.common['X-User-Lat']" 
                         class="mb-4 flex items-center gap-2 text-[10px] text-emerald-500 font-bold uppercase tracking-widest">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        Geofence Protection Active
                    </div>

                    <div class="animate-in fade-in duration-500">
                        <slot />
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
.animate-in {
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>