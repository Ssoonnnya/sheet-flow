import { computed, ref } from 'vue';
import { api } from '@/spa/api/client';
import { ApiError, type User } from '@/spa/api/types';

const user = ref<User | null>(null);
const bootstrapped = ref(false);
const loading = ref(false);

export function useAuth() {
    const isAuthenticated = computed(() => user.value !== null);

    async function fetchUser(): Promise<User | null> {
        loading.value = true;

        try {
            user.value = await api.get<User>('/user');
            return user.value;
        } catch (error) {
            if (error instanceof ApiError && error.status === 401) {
                user.value = null;
                return null;
            }

            throw error;
        } finally {
            loading.value = false;
            bootstrapped.value = true;
        }
    }

    async function login(
        email: string,
        password: string,
        remember = false,
    ): Promise<User> {
        user.value = await api.post<User>('/login', {
            email,
            password,
            remember,
        });
        bootstrapped.value = true;

        return user.value;
    }

    async function register(
        name: string,
        email: string,
        password: string,
        passwordConfirmation: string,
    ): Promise<User> {
        user.value = await api.post<User>('/register', {
            name,
            email,
            password,
            password_confirmation: passwordConfirmation,
        });
        bootstrapped.value = true;

        return user.value;
    }

    async function logout(): Promise<void> {
        await api.post('/logout');
        user.value = null;
    }

    return {
        user,
        bootstrapped,
        loading,
        isAuthenticated,
        fetchUser,
        login,
        register,
        logout,
    };
}
