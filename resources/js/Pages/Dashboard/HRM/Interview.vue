<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Calendar, Clock, Video, MapPin, XCircle, CheckCircle,
    PlayCircle, User, Mail, Phone, Briefcase, CalendarDays,
    AlertTriangle, X, UserCheck, ArrowRight, ChevronRight,
    Building, FileText, Globe, ChevronLeft, Heart, BookOpen,
    Factory, CreditCard, Phone as PhoneIcon, MapPin as MapPinIcon,
    User as UserIcon, Award, ShieldCheck, Eye, Pencil, Save,
    Loader2, Trash2, Upload, Image as ImageIcon, Ban, Layers
} from 'lucide-vue-next';

const props = defineProps({
    applicants: { type: Array, default: () => [] },
    permissions: { type: Object, default: () => ({}) }
});

const canEdit = computed(() => props.permissions?.interview === 'edit');

// ─── Toast ────────────────────────────────────────────────────────────────────
const showToast   = ref(false);
const toastMessage = ref('');
const toastType   = ref('success');
const triggerToast = (msg, type = 'success') => {
    toastMessage.value = msg;
    toastType.value    = type;
    showToast.value    = true;
    setTimeout(() => { showToast.value = false; }, 4000);
};
const page = usePage();
if (page.props.flash?.message) triggerToast(page.props.flash.message);

// ─── Modal states ─────────────────────────────────────────────────────────────
const isScheduleModalOpen = ref(false);
const isPassModalOpen     = ref(false);
const isFailModalOpen     = ref(false);
const selectedApplicant   = ref(null);

// Tracks which applicant IDs are currently in "Interview Now" mode
const interviewingNowIds  = ref(new Set());

// Fail flow step: 'choose' | 'confirm_fail' | 'pass_to_other'
const failStep    = ref('choose');
const failReason  = ref('');
const otherModule = ref('');

// ─── Detail side panel ────────────────────────────────────────────────────────
const detailPanelOpen      = ref(false);
const detailPanelApplicant = ref(null);
const detailPanelTab       = ref('personal');

// ─── Schedule form ────────────────────────────────────────────────────────────
const scheduleForm = ref({
    scheduled_at: '', interview_type: '', duration: 45,
    interviewer: '', location: '', notes: ''
});

// ─── Module options ───────────────────────────────────────────────────────────
const modules = [
    { value: 'HRM',  label: 'Human Resource',        color: '#2563eb' },
    { value: 'ECO',  label: 'E-Commerce',             color: '#0891b2' },
    { value: 'CRM',  label: 'Customer Relationship',  color: '#0284c7' },
    { value: 'SCM',  label: 'Supply Chain',           color: '#16a34a' },
    { value: 'MAN',  label: 'Manufacturing',          color: '#059669' },
    { value: 'PROJ', label: 'Project Management',     color: '#d97706' },
    { value: 'FIN',  label: 'Finance',                color: '#dc2626' },
    { value: 'LOG',  label: 'Logistics',              color: '#ea580c' },
    { value: 'IT',   label: 'Information Technology', color: '#7c3aed' },
];

// ─── Computed ─────────────────────────────────────────────────────────────────
const todaysInterviews = computed(() => {
    const today = new Date();
    return props.applicants
        .filter(a => {
            if (!a.scheduled_at) return false;
            return new Date(a.scheduled_at).toDateString() === today.toDateString();
        })
        .sort((a, b) => new Date(a.scheduled_at) - new Date(b.scheduled_at));
});

// ─── Helpers ──────────────────────────────────────────────────────────────────
const getInitials = name =>
    name ? name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) : '?';

const formatDateTime = date =>
    date ? new Date(date).toLocaleString('en-US', {
        month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    }) : 'N/A';

const formatTime = date =>
    date ? new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';

const formatDateFull = date =>
    date ? new Date(date).toLocaleDateString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric'
    }) : 'N/A';

const isInterviewingNow = applicant => interviewingNowIds.value.has(applicant.id);
const hasSchedule       = applicant => !!applicant.scheduled_at;

const getStatusInfo = (applicant) => {
    if (isInterviewingNow(applicant))
        return { label: 'In Progress', color: 'text-amber-700 bg-amber-50 border-amber-200', dot: 'bg-amber-500' };
    if (hasSchedule(applicant))
        return { label: 'Scheduled', color: 'text-blue-700 bg-blue-50 border-blue-200', dot: 'bg-blue-500' };
    return { label: 'Pending', color: 'text-slate-600 bg-slate-100 border-slate-200', dot: 'bg-slate-400' };
};

const getInterviewTypeLabel = (type) => {
    const map = {
        phone: 'Phone Screen', video: 'Video Call',
        onsite: 'On-site', technical: 'Technical', behavioral: 'Behavioral'
    };
    return map[type?.toLowerCase()] || type || 'Interview';
};

const getInterviewTypeIcon = (type) => {
    const t = type?.toLowerCase();
    if (t === 'phone') return Phone;
    if (t === 'video') return Video;
    if (t === 'onsite') return MapPin;
    return Calendar;
};

// ─── Actions ──────────────────────────────────────────────────────────────────
const startInterviewNow = (applicant, event) => {
    event.stopPropagation();
    const next = new Set(interviewingNowIds.value);
    next.add(applicant.id);
    interviewingNowIds.value = next;
};

const openScheduleModal = (applicant, event) => {
    event?.stopPropagation();
    if (!canEdit.value) { triggerToast('No permission to schedule interviews.', 'error'); return; }
    selectedApplicant.value = applicant;
    scheduleForm.value = {
        scheduled_at:   applicant.scheduled_at ? new Date(applicant.scheduled_at).toISOString().slice(0, 16) : '',
        interview_type: applicant.interview_type || '',
        duration:       applicant.duration || 45,
        interviewer:    applicant.interviewer || '',
        location:       applicant.location || '',
        notes:          applicant.notes || ''
    };
    isScheduleModalOpen.value = true;
};

const openPassModal = (applicant, event) => {
    event?.stopPropagation();
    if (!canEdit.value) { triggerToast('No permission.', 'error'); return; }
    selectedApplicant.value = applicant;
    isPassModalOpen.value = true;
};

const openFailModal = (applicant, event) => {
    event?.stopPropagation();
    if (!canEdit.value) { triggerToast('No permission.', 'error'); return; }
    selectedApplicant.value = applicant;
    failReason.value  = '';
    otherModule.value = '';
    failStep.value    = 'choose';
    isFailModalOpen.value = true;
};

const openDetailPanel = (applicant) => {
    detailPanelApplicant.value = applicant;
    detailPanelOpen.value      = true;
    detailPanelTab.value       = 'personal';
};
const closeDetailPanel = () => {
    detailPanelOpen.value = false;
    setTimeout(() => { detailPanelApplicant.value = null; }, 350);
};

const closeModals = () => {
    isScheduleModalOpen.value = false;
    isPassModalOpen.value     = false;
    isFailModalOpen.value     = false;
    selectedApplicant.value   = null;
    failStep.value    = 'choose';
    failReason.value  = '';
    otherModule.value = '';
    scheduleForm.value = { scheduled_at: '', interview_type: '', duration: 45, interviewer: '', location: '', notes: '' };
};

// ─── API ──────────────────────────────────────────────────────────────────────
const scheduleInterview = () => {
    if (!scheduleForm.value.scheduled_at || !scheduleForm.value.interview_type) {
        triggerToast('Please fill in all required fields.', 'error'); return;
    }
    router.post(route('hrm.interview.schedule', selectedApplicant.value.id), scheduleForm.value, {
        preserveScroll: true,
        onSuccess: () => { triggerToast('Interview scheduled successfully.'); closeModals(); },
        onError: (e)   => triggerToast(Object.values(e)[0] || 'Failed to schedule.', 'error')
    });
};

const passApplicant = () => {
    router.post(route('hrm.interview.pass', selectedApplicant.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => { triggerToast(`${selectedApplicant.value.name} passed the interview!`); closeModals(); },
        onError: (e)   => triggerToast(Object.values(e)[0] || 'Failed.', 'error')
    });
};

const failApplicant = () => {
    if (!failReason.value.trim()) { triggerToast('Please provide a reason for failure.', 'error'); return; }
    router.post(route('hrm.interview.fail', selectedApplicant.value.id), { reason: failReason.value }, {
        preserveScroll: true,
        onSuccess: () => { triggerToast(`${selectedApplicant.value.name} has been failed.`); closeModals(); },
        onError: (e)   => triggerToast(Object.values(e)[0] || 'Failed.', 'error')
    });
};

const passToOtherModule = () => {
    if (!otherModule.value) { triggerToast('Please select a module.', 'error'); return; }
    router.post(route('hrm.interview.pass-to-other', selectedApplicant.value.id), { module: otherModule.value }, {
        preserveScroll: true,
        onSuccess: () => { triggerToast(`Applicant forwarded to ${otherModule.value} module.`); closeModals(); },
        onError: (e)   => triggerToast(Object.values(e)[0] || 'Failed to pass to other module.', 'error')
    });
};

// ─── Image preview modal ──────────────────────────────────────────────────────
const imagePreview      = ref(null);
const openImagePreview  = (url, title) => { imagePreview.value = { url, title }; };
const closeImagePreview = () => { imagePreview.value = null; };

// ─── Helper to format notice period ──────────────────────────────────────────
const formatNoticePeriod = (period) => {
    if (!period) return '—';
    const map = { immediate: 'Immediate', '15_days': '15 Days', '30_days': '30 Days', '60_days': '60 Days' };
    return map[period] || period;
};
</script>

<template>
    <Head title="Interview Management" />
    <AuthenticatedLayout>

        <!-- ═══ TOAST ══════════════════════════════════════════════════════════ -->
        <Transition name="toast">
            <div v-if="showToast"
                class="fixed top-4 right-4 z-[200] flex items-center gap-3 px-5 py-3.5 rounded-2xl shadow-xl border max-w-xs sm:max-w-sm"
                :class="toastType === 'error'
                    ? 'bg-white border-red-200 text-slate-800'
                    : 'bg-white border-emerald-200 text-slate-800'">
                <div class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center"
                    :class="toastType === 'error' ? 'bg-red-100' : 'bg-emerald-100'">
                    <CheckCircle v-if="toastType !== 'error'" class="w-4 h-4 text-emerald-600" />
                    <XCircle v-else class="w-4 h-4 text-red-500" />
                </div>
                <p class="text-sm font-semibold leading-tight">{{ toastMessage }}</p>
            </div>
        </Transition>

        <!-- ═══ PAGE CONTENT ════════════════════════════════════════════════════ -->
        <div class="min-h-screen bg-gray-50/60 pb-24">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 py-6 space-y-6">

                <!-- Header -->
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold text-blue-600 uppercase tracking-[0.15em] mb-1">
                            Human Resource · Interviews
                        </p>
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-none">
                            Candidates
                        </h1>
                        <p class="text-sm text-slate-500 mt-1.5 font-medium">
                            {{ props.applicants.length }} candidate{{ props.applicants.length !== 1 ? 's' : '' }} assigned
                        </p>
                    </div>
                    <span v-if="canEdit"
                        class="shrink-0 inline-flex items-center gap-1.5 text-[10px] font-bold text-blue-700 bg-blue-50 border border-blue-200 px-2.5 py-1.5 rounded-full uppercase tracking-wide mt-1">
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500 animate-pulse inline-block"></span>
                        Full Access
                    </span>
                    <span v-else
                        class="shrink-0 inline-flex items-center gap-1.5 text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1.5 rounded-full uppercase tracking-wide mt-1">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-400 inline-block"></span>
                        View Only
                    </span>
                </div>

                <!-- ─── Today's Interviews ─────────────────────────────────── -->
                <div v-if="todaysInterviews.length > 0">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="h-4 w-1 rounded-full bg-amber-400"></div>
                        <h2 class="text-[11px] font-black text-slate-700 uppercase tracking-widest">Today's Interviews</h2>
                        <span class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded-full">
                            {{ todaysInterviews.length }}
                        </span>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                        <div class="divide-y divide-slate-50">
                            <div v-for="applicant in todaysInterviews" :key="'today-' + applicant.id"
                                class="flex items-center gap-3 sm:gap-4 px-4 py-3.5 hover:bg-slate-50/70 transition-colors cursor-pointer"
                                @click="openDetailPanel(applicant)">
                                <!-- Time -->
                                <div class="text-center shrink-0 w-11">
                                    <p class="text-xs font-black text-blue-600 leading-tight">{{ formatTime(applicant.scheduled_at) }}</p>
                                </div>
                                <!-- Avatar -->
                                <div class="shrink-0">
                                    <img v-if="applicant.profile_photo" :src="applicant.profile_photo" :alt="applicant.name"
                                        class="h-9 w-9 rounded-full object-cover ring-2 ring-white shadow" />
                                    <div v-else
                                        class="h-9 w-9 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-black shadow">
                                        {{ getInitials(applicant.name) }}
                                    </div>
                                </div>
                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-slate-900 truncate">{{ applicant.name }}</p>
                                    <p class="text-[11px] text-slate-500 truncate">{{ applicant.position_applied }}</p>
                                </div>
                                <!-- Type badge -->
                                <span class="hidden sm:inline text-[10px] font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-lg capitalize shrink-0">
                                    {{ getInterviewTypeLabel(applicant.interview_type) }}
                                </span>
                                <!-- Interview Now -->
                                <div class="shrink-0" v-if="canEdit">
                                    <button v-if="!isInterviewingNow(applicant)"
                                        @click="startInterviewNow(applicant, $event)"
                                        class="interview-now-btn flex items-center gap-1 text-[10px] font-black text-white bg-blue-600 hover:bg-blue-700 px-2.5 py-1.5 rounded-lg uppercase tracking-wide transition-all active:scale-95">
                                        <PlayCircle class="h-3 w-3" /> Now
                                    </button>
                                    <span v-else class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-1 rounded-lg">
                                        In Progress
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ─── All Candidates ─────────────────────────────────────── -->
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <div class="h-4 w-1 rounded-full bg-slate-300"></div>
                        <h2 class="text-[11px] font-black text-slate-700 uppercase tracking-widest">All Candidates</h2>
                    </div>

                    <!-- Empty state -->
                    <div v-if="props.applicants.length === 0"
                        class="bg-white rounded-2xl border border-slate-100 shadow-sm p-12 text-center">
                        <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <Calendar class="h-8 w-8 text-slate-400" />
                        </div>
                        <h3 class="text-base font-bold text-slate-700">No candidates yet</h3>
                        <p class="text-sm text-slate-400 mt-1 max-w-xs mx-auto">
                            Candidates accepted by HR and assigned to your department will appear here.
                        </p>
                    </div>

                    <!-- Applicant cards -->
                    <div v-else class="space-y-3">
                        <div v-for="applicant in props.applicants" :key="applicant.id"
                            class="applicant-card bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden cursor-pointer hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 active:scale-[0.99]"
                            @click="openDetailPanel(applicant)">

                            <!-- Status accent line -->
                            <div class="h-0.5 w-full"
                                :class="isInterviewingNow(applicant) ? 'bg-amber-400'
                                      : hasSchedule(applicant)       ? 'bg-blue-500'
                                      : 'bg-slate-200'">
                            </div>

                            <div class="p-4">
                                <div class="flex items-start gap-3.5">
                                    <!-- Avatar -->
                                    <div class="shrink-0 relative">
                                        <img v-if="applicant.profile_photo"
                                            :src="applicant.profile_photo" :alt="applicant.name"
                                            class="h-12 w-12 sm:h-14 sm:w-14 rounded-xl object-cover ring-2 ring-slate-100 shadow" />
                                        <div v-else
                                            class="h-12 w-12 sm:h-14 sm:w-14 rounded-xl bg-blue-600 flex items-center justify-center text-white text-sm sm:text-base font-black shadow">
                                            {{ getInitials(applicant.name) }}
                                        </div>
                                        <!-- Today indicator -->
                                        <span v-if="todaysInterviews.find(a => a.id === applicant.id)"
                                            class="absolute -top-1 -right-1 h-3.5 w-3.5 rounded-full bg-amber-400 border-2 border-white"></span>
                                    </div>

                                    <!-- Name & info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <h3 class="text-sm font-bold text-slate-900 truncate">{{ applicant.name }}</h3>
                                                <p class="text-[11px] text-slate-500 truncate">{{ applicant.email }}</p>
                                                <p class="text-[11px] font-semibold text-blue-600 mt-0.5 truncate">
                                                    {{ applicant.position_applied }}
                                                </p>
                                            </div>
                                            <!-- Status badge -->
                                            <span :class="['shrink-0 inline-flex items-center gap-1 text-[10px] font-bold border px-2 py-0.5 rounded-full uppercase tracking-wide', getStatusInfo(applicant).color]">
                                                <span :class="['h-1.5 w-1.5 rounded-full', getStatusInfo(applicant).dot]"></span>
                                                {{ getStatusInfo(applicant).label }}
                                            </span>
                                        </div>

                                        <!-- Scheduled time -->
                                        <div v-if="applicant.scheduled_at" class="flex items-center gap-1.5 mt-2">
                                            <Calendar class="h-3 w-3 text-blue-400 shrink-0" />
                                            <span class="text-[11px] text-slate-500">{{ formatDateTime(applicant.scheduled_at) }}</span>
                                            <span v-if="applicant.interview_type"
                                                class="text-[10px] font-semibold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded-md ml-1 capitalize">
                                                {{ getInterviewTypeLabel(applicant.interview_type) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action buttons -->
                            <div v-if="canEdit" class="px-4 pb-4" @click.stop>
                                <!-- State 1: No schedule -->
                                <div v-if="!hasSchedule(applicant) && !isInterviewingNow(applicant)">
                                    <button @click="openScheduleModal(applicant, $event)"
                                        class="w-full flex items-center justify-center gap-2 py-2.5 px-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95 shadow-sm shadow-blue-200">
                                        <CalendarDays class="h-3.5 w-3.5" /> Set Schedule
                                    </button>
                                </div>

                                <!-- State 2: Scheduled, not started -->
                                <div v-else-if="hasSchedule(applicant) && !isInterviewingNow(applicant)" class="flex gap-2">
                                    <button @click="openScheduleModal(applicant, $event)"
                                        class="flex items-center justify-center gap-1.5 py-2.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95">
                                        <Calendar class="h-3.5 w-3.5" /> Reschedule
                                    </button>
                                    <button @click="startInterviewNow(applicant, $event)"
                                        class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95 shadow-sm shadow-blue-200">
                                        <PlayCircle class="h-3.5 w-3.5" /> Interview Now
                                    </button>
                                </div>

                                <!-- State 3: In progress -->
                                <div v-else-if="isInterviewingNow(applicant)" class="flex gap-2">
                                    <button @click="openPassModal(applicant, $event)"
                                        class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95 shadow-sm shadow-emerald-200">
                                        <CheckCircle class="h-3.5 w-3.5" /> Pass
                                    </button>
                                    <button @click="openFailModal(applicant, $event)"
                                        class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-white hover:bg-red-50 text-red-600 border border-red-200 rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95">
                                        <XCircle class="h-3.5 w-3.5" /> Fail
                                    </button>
                                </div>
                            </div>

                            <!-- View-only -->
                            <div v-else class="px-4 pb-4">
                                <p class="text-[11px] text-center text-slate-400 italic">
                                    View only · Contact HR to manage this interview
                                </p>
                            </div>

                            <!-- Tap hint -->
                            <div class="flex items-center justify-end gap-1 px-4 pb-2.5">
                                <p class="text-[10px] text-slate-300">Tap to view profile</p>
                                <ChevronRight class="h-3 w-3 text-slate-300" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════════
             DETAIL SIDE PANEL
        ════════════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition name="panel-backdrop">
                <div v-if="detailPanelOpen"
                    class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm"
                    @click="closeDetailPanel">
                </div>
            </Transition>

            <Transition name="panel-slide">
                <div v-if="detailPanelOpen"
                    class="fixed top-0 right-0 bottom-0 z-50 w-full max-w-md bg-white shadow-2xl flex flex-col overflow-hidden">

                    <!-- Panel header -->
                    <div class="relative bg-blue-600 px-5 pt-10 pb-6">
                        <button @click="closeDetailPanel"
                            class="absolute top-4 right-4 h-8 w-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors">
                            <X class="h-4 w-4 text-white" />
                        </button>
                        <div class="flex items-center gap-4">
                            <img v-if="detailPanelApplicant?.profile_photo"
                                :src="detailPanelApplicant.profile_photo" :alt="detailPanelApplicant?.name"
                                class="h-16 w-16 sm:h-20 sm:w-20 rounded-2xl object-cover ring-4 ring-white/30 shadow-xl shrink-0" />
                            <div v-else
                                class="h-16 w-16 sm:h-20 sm:w-20 rounded-2xl bg-white/20 flex items-center justify-center text-white text-xl font-black ring-4 ring-white/20 shadow-xl shrink-0">
                                {{ getInitials(detailPanelApplicant?.name) }}
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-lg font-black text-white truncate">{{ detailPanelApplicant?.name }}</h2>
                                <p class="text-blue-200 text-sm font-medium truncate">{{ detailPanelApplicant?.position_applied }}</p>
                                <span :class="[
                                    'mt-2 inline-flex items-center gap-1.5 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-widest border',
                                    detailPanelApplicant && getStatusInfo(detailPanelApplicant).color
                                ]">
                                    <span :class="['h-1.5 w-1.5 rounded-full', detailPanelApplicant && getStatusInfo(detailPanelApplicant).dot]"></span>
                                    {{ detailPanelApplicant && getStatusInfo(detailPanelApplicant).label }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs (scrollable on small screens) -->
                    <div class="flex gap-0.5 bg-white border-b border-slate-100 px-3 pt-3 overflow-x-auto scrollbar-hide">
                        <button v-for="tab in ['personal','contact','ids','education','employment','family']"
                            :key="tab"
                            @click="detailPanelTab = tab"
                            :class="[
                                'px-3 py-2 text-xs font-bold rounded-t-lg transition-all whitespace-nowrap capitalize',
                                detailPanelTab === tab
                                    ? 'bg-blue-50 text-blue-600 border-b-2 border-blue-500'
                                    : 'text-slate-400 hover:text-slate-600'
                            ]">
                            {{ tab }}
                        </button>
                    </div>

                    <!-- Panel body (scrollable) -->
                    <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4">

                        <!-- PERSONAL TAB -->
                        <div v-if="detailPanelTab === 'personal'" class="space-y-4">
                            <div class="panel-section">
                                <div class="panel-section-header">
                                    <UserIcon class="h-4 w-4 text-blue-500" />
                                    <p class="panel-section-title">Basic Information</p>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="col-span-2"><p class="data-label">Full Name</p><p class="data-value">{{ detailPanelApplicant?.first_name }} {{ detailPanelApplicant?.middle_name }} {{ detailPanelApplicant?.last_name }}</p></div>
                                    <div><p class="data-label">Date of Birth</p><p class="data-value">{{ formatDateFull(detailPanelApplicant?.date_of_birth) }}</p></div>
                                    <div><p class="data-label">Place of Birth</p><p class="data-value">{{ detailPanelApplicant?.place_of_birth || '—' }}</p></div>
                                    <div><p class="data-label">Age</p><p class="data-value">{{ detailPanelApplicant?.age || '—' }}</p></div>
                                    <div><p class="data-label">Sex</p><p class="data-value capitalize">{{ detailPanelApplicant?.sex || '—' }}</p></div>
                                    <div><p class="data-label">Civil Status</p><p class="data-value capitalize">{{ detailPanelApplicant?.civil_status || '—' }}</p></div>
                                    <div><p class="data-label">Citizenship</p><p class="data-value">{{ detailPanelApplicant?.citizenship || '—' }}</p></div>
                                    <div><p class="data-label">Religion</p><p class="data-value">{{ detailPanelApplicant?.religion || '—' }}</p></div>
                                    <div><p class="data-label">Weight / Height</p><p class="data-value">{{ detailPanelApplicant?.weight }} kg / {{ detailPanelApplicant?.height }} cm</p></div>
                                    <div><p class="data-label">Languages</p><p class="data-value">{{ detailPanelApplicant?.languages || '—' }}</p></div>
                                    <div class="col-span-2"><p class="data-label">Special Skills</p><p class="data-value">{{ detailPanelApplicant?.special_skills || '—' }}</p></div>
                                    <div class="col-span-2"><p class="data-label">Machine Operation</p><p class="data-value">{{ detailPanelApplicant?.machine_operation || '—' }}</p></div>
                                </div>
                            </div>
                        </div>

                        <!-- CONTACT TAB -->
                        <div v-if="detailPanelTab === 'contact'" class="space-y-4">
                            <div class="panel-section">
                                <div class="panel-section-header">
                                    <PhoneIcon class="h-4 w-4 text-blue-500" />
                                    <p class="panel-section-title">Contact & Address</p>
                                </div>
                                <div><p class="data-label">Email</p><p class="data-value">{{ detailPanelApplicant?.email }}</p></div>
                                <div><p class="data-label">Phone</p><p class="data-value">{{ detailPanelApplicant?.phone }}</p></div>
                                <div><p class="data-label">Address</p><p class="data-value">{{ detailPanelApplicant?.street_address }}, {{ detailPanelApplicant?.city }}, {{ detailPanelApplicant?.state_province }} {{ detailPanelApplicant?.postal_zip_code }}</p></div>
                            </div>
                            <div class="panel-section">
                                <div class="panel-section-header">
                                    <AlertTriangle class="h-4 w-4 text-amber-500" />
                                    <p class="panel-section-title">Emergency Contact</p>
                                </div>
                                <div><p class="data-label">Name</p><p class="data-value">{{ detailPanelApplicant?.emergency_name || '—' }}</p></div>
                                <div><p class="data-label">Relationship</p><p class="data-value">{{ detailPanelApplicant?.emergency_relationship || '—' }}</p></div>
                                <div><p class="data-label">Phone</p><p class="data-value">{{ detailPanelApplicant?.emergency_phone || '—' }}</p></div>
                                <div><p class="data-label">Address</p><p class="data-value">{{ detailPanelApplicant?.emergency_address || '—' }}</p></div>
                            </div>
                        </div>

                        <!-- IDs TAB -->
                        <div v-if="detailPanelTab === 'ids'" class="space-y-4">
                            <div class="panel-section">
                                <div class="panel-section-header">
                                    <CreditCard class="h-4 w-4 text-blue-500" />
                                    <p class="panel-section-title">Government IDs</p>
                                </div>
                                <div>
                                    <p class="data-label">SSS Number</p>
                                    <p class="data-value">{{ detailPanelApplicant?.sss_number || '—' }}</p>
                                    <button v-if="detailPanelApplicant?.sss_file_url" @click="openImagePreview(detailPanelApplicant.sss_file_url, 'SSS ID')"
                                        class="mt-1.5 text-xs text-blue-600 font-semibold hover:underline">View SSS ID</button>
                                </div>
                                <div>
                                    <p class="data-label">PhilHealth Number</p>
                                    <p class="data-value">{{ detailPanelApplicant?.philhealth_number || '—' }}</p>
                                    <button v-if="detailPanelApplicant?.philhealth_file_url" @click="openImagePreview(detailPanelApplicant.philhealth_file_url, 'PhilHealth ID')"
                                        class="mt-1.5 text-xs text-blue-600 font-semibold hover:underline">View PhilHealth ID</button>
                                </div>
                                <div>
                                    <p class="data-label">Pag-IBIG Number</p>
                                    <p class="data-value">{{ detailPanelApplicant?.pagibig_number || '—' }}</p>
                                    <button v-if="detailPanelApplicant?.pagibig_file_url" @click="openImagePreview(detailPanelApplicant.pagibig_file_url, 'Pag-IBIG ID')"
                                        class="mt-1.5 text-xs text-blue-600 font-semibold hover:underline">View Pag-IBIG ID</button>
                                </div>
                            </div>
                        </div>

                        <!-- EDUCATION TAB -->
                        <div v-if="detailPanelTab === 'education'" class="space-y-4">
                            <div class="panel-section">
                                <div class="panel-section-header">
                                    <BookOpen class="h-4 w-4 text-blue-500" />
                                    <p class="panel-section-title">Educational Background</p>
                                </div>
                                <div><p class="data-label">Elementary</p><p class="data-value">{{ detailPanelApplicant?.elementary_school || '—' }} {{ detailPanelApplicant?.elementary_year ? `(${detailPanelApplicant.elementary_year})` : '' }}</p></div>
                                <div><p class="data-label">High School</p><p class="data-value">{{ detailPanelApplicant?.high_school || '—' }} {{ detailPanelApplicant?.high_year ? `(${detailPanelApplicant.high_year})` : '' }}</p></div>
                                <div><p class="data-label">College</p><p class="data-value">{{ detailPanelApplicant?.college || '—' }} {{ detailPanelApplicant?.college_year ? `(${detailPanelApplicant.college_year})` : '' }}</p></div>
                                <div><p class="data-label">Vocational</p><p class="data-value">{{ detailPanelApplicant?.vocational || '—' }} {{ detailPanelApplicant?.vocational_year ? `(${detailPanelApplicant.vocational_year})` : '' }}</p></div>
                            </div>
                        </div>

                        <!-- EMPLOYMENT TAB -->
                        <div v-if="detailPanelTab === 'employment'" class="space-y-4">
                            <div class="panel-section">
                                <div class="panel-section-header">
                                    <Briefcase class="h-4 w-4 text-blue-500" />
                                    <p class="panel-section-title">Employment History</p>
                                </div>
                                <div v-if="detailPanelApplicant?.employment_records && detailPanelApplicant.employment_records.length">
                                    <div v-for="(rec, idx) in detailPanelApplicant.employment_records" :key="idx"
                                        class="mb-3 pb-3 border-b border-slate-100 last:border-0 last:pb-0 last:mb-0">
                                        <p class="text-sm font-bold text-slate-800">{{ rec.company }}</p>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ rec.years }} · {{ rec.salary }} · {{ rec.position }}</p>
                                        <p class="text-xs text-slate-400 mt-0.5">{{ rec.reason }}</p>
                                    </div>
                                </div>
                                <div v-else-if="detailPanelApplicant?.previous_employment_company">
                                    <div><p class="data-label">Company</p><p class="data-value">{{ detailPanelApplicant.previous_employment_company }}</p></div>
                                    <div><p class="data-label">When</p><p class="data-value">{{ detailPanelApplicant.previous_employment_when }}</p></div>
                                    <div><p class="data-label">Position</p><p class="data-value">{{ detailPanelApplicant.previous_employment_position }}</p></div>
                                    <div><p class="data-label">Department</p><p class="data-value">{{ detailPanelApplicant.previous_employment_department }}</p></div>
                                </div>
                                <p v-else class="text-sm text-slate-400 italic">No employment history provided.</p>
                            </div>
                        </div>

                        <!-- FAMILY TAB -->
                        <div v-if="detailPanelTab === 'family'" class="space-y-4">
                            <div class="panel-section">
                                <div class="panel-section-header">
                                    <Heart class="h-4 w-4 text-red-400" />
                                    <p class="panel-section-title">Family Background</p>
                                </div>
                                <div><p class="data-label">Father</p><p class="data-value">{{ detailPanelApplicant?.father_name || '—' }}<br/><span class="text-slate-400">{{ detailPanelApplicant?.father_address || '' }}</span></p></div>
                                <div><p class="data-label">Mother</p><p class="data-value">{{ detailPanelApplicant?.mother_name || '—' }}<br/><span class="text-slate-400">{{ detailPanelApplicant?.mother_address || '' }}</span></p></div>
                                <div><p class="data-label">Spouse</p><p class="data-value">{{ detailPanelApplicant?.spouse_name || '—' }} {{ detailPanelApplicant?.spouse_occupation ? `(${detailPanelApplicant.spouse_occupation})` : '' }}</p></div>
                                <div><p class="data-label">Number of Children</p><p class="data-value">{{ detailPanelApplicant?.number_of_children || 0 }}</p></div>
                                <div v-if="detailPanelApplicant?.children && detailPanelApplicant.children.length">
                                    <p class="data-label">Children</p>
                                    <div v-for="(child, i) in detailPanelApplicant.children" :key="i" class="text-sm text-slate-700">
                                        — {{ typeof child === 'object' ? child.name : child }}
                                    </div>
                                </div>
                            </div>
                            <div class="panel-section">
                                <div class="panel-section-header">
                                    <Globe class="h-4 w-4 text-blue-500" />
                                    <p class="panel-section-title">Referral & Relations</p>
                                </div>
                                <div><p class="data-label">Referred By</p><p class="data-value">{{ detailPanelApplicant?.referred_by || '—' }}<br/><span class="text-slate-400">{{ detailPanelApplicant?.referred_by_address || '' }}</span></p></div>
                                <div><p class="data-label">Related Employees</p><p class="data-value">{{ detailPanelApplicant?.related_employees || '—' }}</p></div>
                            </div>
                        </div>

                        <!-- Quick actions inside panel -->
                        <div v-if="canEdit" class="space-y-2 pt-2" @click.stop>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Quick Actions</p>
                            <button v-if="!hasSchedule(detailPanelApplicant) || isInterviewingNow(detailPanelApplicant)"
                                @click="openScheduleModal(detailPanelApplicant, $event)"
                                class="w-full flex items-center justify-center gap-2 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95">
                                <CalendarDays class="h-4 w-4" /> Set Schedule
                            </button>
                            <button v-if="hasSchedule(detailPanelApplicant) && !isInterviewingNow(detailPanelApplicant)"
                                @click="startInterviewNow(detailPanelApplicant, $event)"
                                class="w-full flex items-center justify-center gap-2 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95">
                                <PlayCircle class="h-4 w-4" /> Interview Now
                            </button>
                            <div v-if="isInterviewingNow(detailPanelApplicant)" class="grid grid-cols-2 gap-2">
                                <button @click="openPassModal(detailPanelApplicant, $event)"
                                    class="flex items-center justify-center gap-2 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95">
                                    <CheckCircle class="h-4 w-4" /> Pass
                                </button>
                                <button @click="openFailModal(detailPanelApplicant, $event)"
                                    class="flex items-center justify-center gap-2 py-3 bg-white hover:bg-red-50 text-red-600 border border-red-200 rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95">
                                    <XCircle class="h-4 w-4" /> Fail
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ═══════════════════════════════════════════════════════════════════════
             SCHEDULE MODAL
        ════════════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="isScheduleModalOpen"
                    class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm"
                    @click.self="closeModals">
                    <div class="bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-2xl shadow-2xl overflow-hidden max-h-[92vh] flex flex-col">

                        <!-- Header -->
                        <div class="px-5 pt-5 pb-4 border-b border-slate-100 flex items-center justify-between shrink-0">
                            <div>
                                <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest">Schedule</p>
                                <h2 class="text-base font-black text-slate-900 mt-0.5">Set Interview</h2>
                                <p class="text-xs text-slate-400 mt-0.5">{{ selectedApplicant?.name }}</p>
                            </div>
                            <button @click="closeModals"
                                class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors text-slate-500 font-bold text-lg leading-none">
                                &times;
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="overflow-y-auto p-5 space-y-4 flex-1">
                            <div>
                                <label class="form-label">Date & Time <span class="text-red-400">*</span></label>
                                <input type="datetime-local" v-model="scheduleForm.scheduled_at" class="form-input" />
                            </div>
                            <div>
                                <label class="form-label">Interview Type <span class="text-red-400">*</span></label>
                                <select v-model="scheduleForm.interview_type" class="form-input">
                                    <option value="">Select type</option>
                                    <option value="phone">Phone Screen</option>
                                    <option value="technical">Technical Interview</option>
                                    <option value="behavioral">Behavioral Interview</option>
                                    <option value="onsite">On-site Interview</option>
                                    <option value="video">Video Conference</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="form-label">Duration (min)</label>
                                    <select v-model="scheduleForm.duration" class="form-input">
                                        <option value="15">15 min</option>
                                        <option value="30">30 min</option>
                                        <option value="45">45 min</option>
                                        <option value="60">60 min</option>
                                        <option value="90">90 min</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Location</label>
                                    <input type="text" v-model="scheduleForm.location" class="form-input" placeholder="Office / Link" />
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Interviewer(s)</label>
                                <input type="text" v-model="scheduleForm.interviewer" class="form-input" placeholder="Name(s) of interviewer" />
                            </div>
                            <div>
                                <label class="form-label">Notes</label>
                                <textarea v-model="scheduleForm.notes" rows="2" class="form-input resize-none" placeholder="Additional instructions..."></textarea>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-5 pb-5 pt-4 border-t border-slate-100 flex gap-3 shrink-0">
                            <button @click="closeModals"
                                class="flex-1 py-3 rounded-xl text-sm font-bold text-slate-500 hover:bg-slate-50 transition-colors border border-slate-200">
                                Cancel
                            </button>
                            <button @click="scheduleInterview"
                                class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-sm shadow-blue-200 transition-all active:scale-95">
                                Confirm Schedule
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ═══════════════════════════════════════════════════════════════════════
             PASS MODAL
        ════════════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="isPassModalOpen"
                    class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
                    @click.self="closeModals">
                    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden sweet-bounce">

                        <!-- Icon -->
                        <div class="pt-10 pb-5 px-8 text-center">
                            <div class="w-20 h-20 rounded-full bg-emerald-50 border-4 border-emerald-100 flex items-center justify-center mx-auto mb-5 sweet-icon">
                                <CheckCircle class="w-9 h-9 text-emerald-500" />
                            </div>
                            <h2 class="text-xl font-black text-slate-900 mb-2">Pass Candidate?</h2>
                            <p class="text-sm text-slate-500 leading-relaxed">
                                Passing <strong class="text-slate-800">{{ selectedApplicant?.name }}</strong> will
                                convert them into a <strong class="text-blue-600">Trainee</strong> in your department.
                            </p>
                        </div>

                        <div class="border-t border-slate-100"></div>
                        <div class="flex">
                            <button @click="closeModals"
                                class="flex-1 py-4 text-sm font-bold text-slate-500 hover:bg-slate-50 transition-colors border-r border-slate-100">
                                Cancel
                            </button>
                            <button @click="passApplicant"
                                class="flex-1 py-4 text-sm font-black text-emerald-600 hover:bg-emerald-50 transition-colors">
                                Yes, Pass
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ═══════════════════════════════════════════════════════════════════════
             FAIL MODAL
        ════════════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="isFailModalOpen"
                    class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm"
                    @click.self="closeModals">
                    <div class="bg-white w-full sm:max-w-sm sm:rounded-2xl rounded-t-2xl shadow-2xl overflow-hidden">

                        <!-- Header -->
                        <div class="px-5 pt-5 pb-4 border-b border-slate-100 flex items-center gap-3">
                            <button v-if="failStep !== 'choose'" @click="failStep = 'choose'"
                                class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors shrink-0">
                                <ChevronLeft class="h-4 w-4 text-slate-600" />
                            </button>
                            <div class="flex-1 min-w-0">
                                <p class="text-[10px] font-bold text-red-500 uppercase tracking-widest">Outcome</p>
                                <h2 class="text-base font-black text-slate-900 mt-0.5">
                                    {{ failStep === 'choose'       ? 'Fail Candidate'
                                     : failStep === 'confirm_fail' ? 'Confirm Failure'
                                     : 'Pass to Another Module' }}
                                </h2>
                                <p class="text-xs text-slate-400 truncate">{{ selectedApplicant?.name }}</p>
                            </div>
                            <button @click="closeModals"
                                class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors shrink-0 text-slate-500 font-bold text-lg leading-none">
                                &times;
                            </button>
                        </div>

                        <!-- Step: choose -->
                        <div v-if="failStep === 'choose'" class="p-5 space-y-3">
                            <p class="text-xs text-slate-400 text-center pb-1">What would you like to do with this candidate?</p>

                            <button @click="failStep = 'confirm_fail'"
                                class="w-full flex items-center gap-4 p-4 rounded-xl border border-red-100 hover:border-red-300 hover:bg-red-50 text-left transition-all group">
                                <div class="h-10 w-10 rounded-xl bg-red-50 group-hover:bg-red-100 flex items-center justify-center shrink-0 transition-colors">
                                    <Ban class="h-5 w-5 text-red-500" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-slate-900">Fail Candidate</p>
                                    <p class="text-xs text-slate-400">Permanently reject with a reason</p>
                                </div>
                                <ChevronRight class="h-4 w-4 text-slate-300 group-hover:text-red-400 transition-colors" />
                            </button>

                            <button @click="failStep = 'pass_to_other'"
                                class="w-full flex items-center gap-4 p-4 rounded-xl border border-blue-100 hover:border-blue-300 hover:bg-blue-50 text-left transition-all group">
                                <div class="h-10 w-10 rounded-xl bg-blue-50 group-hover:bg-blue-100 flex items-center justify-center shrink-0 transition-colors">
                                    <Layers class="h-5 w-5 text-blue-600" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-slate-900">Pass to Another Module</p>
                                    <p class="text-xs text-slate-400">Forward for interview in other department</p>
                                </div>
                                <ChevronRight class="h-4 w-4 text-slate-300 group-hover:text-blue-400 transition-colors" />
                            </button>

                            <button @click="closeModals"
                                class="w-full py-3 text-slate-400 text-xs font-bold uppercase tracking-wide rounded-xl hover:bg-slate-50 transition-colors">
                                Cancel
                            </button>
                        </div>

                        <!-- Step: confirm_fail -->
                        <div v-else-if="failStep === 'confirm_fail'" class="p-5 space-y-4">
                            <div class="flex items-start gap-3 p-3.5 bg-red-50 rounded-xl border border-red-100 text-xs text-red-700 font-medium">
                                <AlertTriangle class="w-4 h-4 shrink-0 mt-0.5" />
                                This candidate will be permanently archived. This cannot be undone.
                            </div>
                            <div>
                                <label class="form-label">Reason for Failure <span class="text-red-400">*</span></label>
                                <textarea v-model="failReason" rows="4" class="form-input resize-none"
                                    placeholder="e.g., Insufficient technical skills, poor communication..."></textarea>
                            </div>
                            <div class="flex gap-3">
                                <button @click="failStep = 'choose'"
                                    class="flex-1 py-3 rounded-xl text-sm font-bold text-slate-500 hover:bg-slate-50 transition-colors border border-slate-200">
                                    Back
                                </button>
                                <button @click="failApplicant" :disabled="!failReason.trim()"
                                    class="flex-1 py-3 bg-red-500 hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl text-sm font-bold shadow-sm shadow-red-200 transition-all active:scale-95">
                                    Confirm Fail
                                </button>
                            </div>
                        </div>

                        <!-- Step: pass_to_other -->
                        <div v-else-if="failStep === 'pass_to_other'" class="p-5 space-y-4">
                            <div>
                                <label class="form-label">Select Department <span class="text-red-400">*</span></label>
                                <div class="space-y-1.5 max-h-64 overflow-y-auto pr-1">
                                    <label v-for="mod in modules" :key="mod.value"
                                        class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-all"
                                        :class="otherModule === mod.value
                                            ? 'border-blue-300 bg-blue-50'
                                            : 'border-slate-100 hover:border-slate-200 hover:bg-slate-50'">
                                        <input type="radio" v-model="otherModule" :value="mod.value" class="sr-only" />
                                        <div class="h-7 w-7 rounded-lg flex items-center justify-center shrink-0"
                                            :style="{ backgroundColor: mod.color + '18', color: mod.color }">
                                            <Building class="h-3.5 w-3.5" />
                                        </div>
                                        <span class="text-sm font-semibold text-slate-700 flex-1">{{ mod.label }}</span>
                                        <div v-if="otherModule === mod.value"
                                            class="h-5 w-5 rounded-full bg-blue-600 flex items-center justify-center shrink-0">
                                            <CheckCircle class="h-3 w-3 text-white" />
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <button @click="failStep = 'choose'"
                                    class="flex-1 py-3 rounded-xl text-sm font-bold text-slate-500 hover:bg-slate-50 transition-colors border border-slate-200">
                                    Back
                                </button>
                                <button @click="passToOtherModule" :disabled="!otherModule"
                                    class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl text-sm font-bold shadow-sm shadow-blue-200 transition-all active:scale-95">
                                    Forward
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ═══════════════════════════════════════════════════════════════════════
             IMAGE PREVIEW MODAL
        ════════════════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="imagePreview"
                    class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                    @click.self="closeImagePreview">
                    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                            <h3 class="text-base font-bold text-slate-900">{{ imagePreview.title }}</h3>
                            <button @click="closeImagePreview"
                                class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors">
                                <X class="w-4 h-4 text-slate-600" />
                            </button>
                        </div>
                        <div class="p-4 flex justify-center bg-slate-100">
                            <img :src="imagePreview.url" :alt="imagePreview.title"
                                class="max-w-full max-h-[70vh] object-contain rounded-lg" />
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AuthenticatedLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;0,9..40,900&display=swap');
* { font-family: 'DM Sans', sans-serif; }

/* ── Form ─────────────────────────────────────────────── */
.form-label {
    display: block;
    font-size: 10px;
    font-weight: 800;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    margin-bottom: 6px;
}
.form-input {
    width: 100%;
    padding: 11px 14px;
    border-radius: 12px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    font-size: 13px;
    font-weight: 500;
    color: #1e293b;
    transition: border-color 0.15s, box-shadow 0.15s;
    outline: none;
    appearance: none;
}
.form-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* ── Panel section ────────────────────────────────────── */
.panel-section {
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 14px;
    padding: 14px;
    space-y: 10px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.panel-section-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e2e8f0;
}
.panel-section-title {
    font-size: 11px;
    font-weight: 800;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.data-label {
    font-size: 10px;
    color: #94a3b8;
    margin-bottom: 2px;
    font-weight: 500;
}
.data-value {
    font-size: 13px;
    color: #1e293b;
    font-weight: 500;
    line-height: 1.5;
}

/* ── Toast ────────────────────────────────────────────── */
.toast-enter-active, .toast-leave-active { transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(20px) scale(0.95); }

/* ── Modal ────────────────────────────────────────────── */
.modal-enter-active, .modal-leave-active { transition: all 0.25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }

/* ── Panel slide ──────────────────────────────────────── */
.panel-slide-enter-active { transition: transform 0.35s cubic-bezier(0.32, 0.72, 0, 1); }
.panel-slide-leave-active { transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1); }
.panel-slide-enter-from  { transform: translateX(100%); }
.panel-slide-leave-to    { transform: translateX(100%); }
.panel-backdrop-enter-active { transition: opacity 0.3s ease; }
.panel-backdrop-leave-active { transition: opacity 0.3s ease; }
.panel-backdrop-enter-from, .panel-backdrop-leave-to { opacity: 0; }

/* ── Card hover ───────────────────────────────────────── */
.applicant-card { transition: transform 0.15s ease, box-shadow 0.15s ease; }

/* ── SweetAlert-style bounce ──────────────────────────── */
.sweet-bounce { animation: sweetBounce 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes sweetBounce {
    from { transform: scale(0.7); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}
.sweet-icon { animation: iconPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 0.1s both; }
@keyframes iconPop {
    from { transform: scale(0.5); opacity: 0; }
    60%  { transform: scale(1.15); }
    to   { transform: scale(1); opacity: 1; }
}

/* ── Interview Now pulse ──────────────────────────────── */
.interview-now-btn { animation: pulseBlue 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
@keyframes pulseBlue {
    0%, 100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.35); }
    50%       { box-shadow: 0 0 0 6px rgba(37, 99, 235, 0); }
}

/* ── Scrollbar ────────────────────────────────────────── */
::-webkit-scrollbar { width: 4px; height: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 999px; }
::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>