<script setup lang="ts">
import GoogleLoginButton from '@/components/GoogleLoginButton.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import type { SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { LoaderCircle, PlayCircle } from 'lucide-vue-next';
import { computed } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const page = usePage<SharedData>();

/** Sólo tiene valor en la demo pública; en la productiva es null. */
const demo = computed(() => page.props.demo);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

/**
 * Entra directo con la cuenta compartida, sin obligar a copiar y pegar las
 * credenciales que están ahí al lado.
 */
const entrarALaDemo = () => {
    if (!demo.value) {
        return;
    }

    form.email = demo.value.email;
    form.password = demo.value.password;
    submit();
};
</script>

<template>
    <AuthBase title="Iniciá sesión en tu cuenta" description="Ingresá tu email y contraseña para acceder">
        <Head title="Iniciar sesión" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <div v-if="demo" class="mb-6 rounded-lg border border-primary/30 bg-primary/5 p-4">
            <p class="text-sm font-medium">Probá la demo</p>
            <p class="mt-1 text-sm text-muted-foreground">
                Entrá con una cuenta de ejemplo ya cargada. Es compartida y se reinicia cada noche, así que podés tocar lo que quieras.
            </p>
            <Button type="button" variant="secondary" class="mt-3 w-full" :disabled="form.processing" @click="entrarALaDemo">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                <PlayCircle v-else class="h-4 w-4" />
                Entrar a la demo
            </Button>
            <p class="mt-2 text-center text-xs text-muted-foreground">{{ demo.email }} · {{ demo.password }}</p>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Correo electrónico</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autofocus
                        tabindex="1"
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="email@ejemplo.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Contraseña</Label>
                        <TextLink v-if="canResetPassword && !demo" :href="route('password.request')" class="text-sm" tabindex="5">
                            ¿Olvidaste tu contraseña?
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        required
                        tabindex="2"
                        autocomplete="current-password"
                        v-model="form.password"
                        placeholder="Contraseña"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between" tabindex="3">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" v-model:checked="form.remember" tabindex="4" />
                        <span>Recordarme</span>
                    </Label>
                </div>

                <Button type="submit" class="mt-4 w-full" tabindex="4" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Iniciar sesión
                </Button>
            </div>

            <template v-if="!demo">
                <div class="flex items-center gap-3 text-xs text-muted-foreground">
                    <span class="h-px flex-1 bg-border"></span>
                    o
                    <span class="h-px flex-1 bg-border"></span>
                </div>

                <GoogleLoginButton />

                <div class="text-center text-sm text-muted-foreground">
                    ¿No tenés una cuenta?
                    <TextLink :href="route('register')" :tabindex="5">Registrate</TextLink>
                </div>
            </template>
        </form>
    </AuthBase>
</template>
