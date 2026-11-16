import {
    createRouter,
    createWebHistory,
    type RouteRecordRaw,
} from 'vue-router';
import { useAuth } from '@/spa/composables/useAuth';

const routes: RouteRecordRaw[] = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/spa/views/LoginView.vue'),
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('@/spa/views/RegisterView.vue'),
        meta: { guest: true },
    },
    {
        path: '/dashboard',
        redirect: '/sources',
    },
    {
        path: '/sources',
        name: 'sources',
        component: () => import('@/spa/views/SourcesView.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/sources/:id',
        name: 'source-detail',
        component: () => import('@/spa/views/SourceDetailView.vue'),
        meta: { requiresAuth: true },
        props: true,
    },
    {
        path: '/runs',
        name: 'runs',
        component: () => import('@/spa/views/RunsView.vue'),
        meta: { requiresAuth: true },
    },
];

const router = createRouter({
    history: createWebHistory('/app'),
    routes,
});

router.beforeEach(async (to) => {
    const { bootstrapped, fetchUser, isAuthenticated } = useAuth();

    if (!bootstrapped.value) {
        await fetchUser();
    }

    if (to.meta.requiresAuth && !isAuthenticated.value) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guest && isAuthenticated.value) {
        return { name: 'sources' };
    }

    return true;
});

export default router;
