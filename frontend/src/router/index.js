import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/authStore';

import LoginView from '../views/LoginView.vue';
import DashboardView from '../views/DashboardView.vue';
import EquipmentListView from '../views/equipment/EquipmentListView.vue';
import EquipmentDetailView from '../views/equipment/EquipmentDetailView.vue';
import TicketListView from '../views/tickets/TicketListView.vue';
import CreateTicketView from '../views/tickets/CreateTicketView.vue';
import TicketDetailView from '../views/tickets/TicketDetailView.vue';
import PreventiveView from '../views/maintenance/PreventiveView.vue';
import CalibrationView from '../views/maintenance/CalibrationView.vue';
import MaintenanceCalendarView from '../views/maintenance/MaintenanceCalendarView.vue';
import SparepartsView from '../views/spareparts/SparepartsView.vue';
import RoomsView from '../views/rooms/RoomsView.vue';
import UsersView from '../views/users/UsersView.vue';
import ReportsView from '../views/reports/ReportsView.vue';
import AuditLogsView from '../views/audit/AuditLogsView.vue';
import SettingsView from '../views/settings/SettingsView.vue';

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: LoginView,
    meta: { guestOnly: true }
  },
  {
    path: '/',
    name: 'Dashboard',
    component: DashboardView,
    meta: { requiresAuth: true }
  },
  {
    path: '/equipment',
    name: 'EquipmentList',
    component: EquipmentListView,
    meta: { requiresAuth: true }
  },
  {
    path: '/equipment/:id',
    name: 'EquipmentDetail',
    component: EquipmentDetailView,
    meta: { requiresAuth: true }
  },
  {
    path: '/tickets',
    name: 'TicketList',
    component: TicketListView,
    meta: { requiresAuth: true }
  },
  {
    path: '/tickets/create',
    name: 'CreateTicket',
    component: CreateTicketView,
    meta: { requiresAuth: true }
  },
  {
    path: '/tickets/:id',
    name: 'TicketDetail',
    component: TicketDetailView,
    meta: { requiresAuth: true }
  },
  {
    path: '/preventive',
    name: 'Preventive',
    component: PreventiveView,
    meta: { requiresAuth: true, roles: ['admin', 'teknisi'] }
  },
  {
    path: '/calibrations',
    name: 'Calibrations',
    component: CalibrationView,
    meta: { requiresAuth: true }
  },
  {
    path: '/maintenance-calendar',
    name: 'MaintenanceCalendar',
    component: MaintenanceCalendarView,
    meta: { requiresAuth: true }
  },
  {
    path: '/spareparts',
    name: 'Spareparts',
    component: SparepartsView,
    meta: { requiresAuth: true, roles: ['admin', 'teknisi'] }
  },
  {
    path: '/rooms',
    name: 'Rooms',
    component: RoomsView,
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/users',
    name: 'Users',
    component: UsersView,
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/reports',
    name: 'Reports',
    component: ReportsView,
    meta: { requiresAuth: true, roles: ['admin', 'teknisi'] }
  },
  {
    path: '/audit-logs',
    name: 'AuditLogs',
    component: AuditLogsView,
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/settings',
    name: 'Settings',
    component: SettingsView,
    meta: { requiresAuth: true, roles: ['admin'] }
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
];

export const getDynamicBase = () => {
  return window.location.pathname.startsWith('/simpelkesrsig') ? '/simpelkesrsig/' : '/';
};

const router = createRouter({
  history: createWebHistory(getDynamicBase()),
  routes,
  scrollBehavior() {
    return { top: 0 };
  }
});

// Navigation Guard
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'Login', query: { redirect: to.fullPath } });
  } else if (to.meta.guestOnly && authStore.isAuthenticated) {
    next({ name: 'Dashboard' });
  } else if (to.meta.roles && !to.meta.roles.includes(authStore.role)) {
    // If role unauthorized, redirect to Dashboard
    next({ name: 'Dashboard' });
  } else {
    next();
  }
});

export default router;
