<template>
    <div class="profile-view">
        <form class="centered-container-no-min">
            <h1 class="page__title">{{ t('Aggiorna i tuoi dati') }}</h1>
            <p class="page__subtitle">{{ t('In questa pagina puoi aggiornare i tuoi dati personali.') }}</p>
            <section class="profile-view__propic-section">
                <div class="propic-container">
                    <BbDropzone v-slot="{ dragging }" v-model="propic" :accept="['image/*']">
                        <div class="dropzone__inner-container">
                            <img v-if="propicPreview" class="propic__new-image" :src="propicPreview" />
                            <img
                                v-else
                                class="propic__current-image"
                                :size="user.avatar_srcset ? '200px' : undefined"
                                :src="avatar ? route('media.index', { media: avatar }) : null"
                                :srcset="user.avatar_srcset ?? undefined"
                            />
                            <span class="propic__overlay">
                                <span v-if="dragging" class="propic__overlay-text">{{ t('Rilascia per cambiare la foto profilo') }}</span>
                                <span v-else class="propic__overlay-text">{{ t('Clicca o trascina per aggiornare la foto') }}</span>
                                <BbIcon type="shutter" />
                            </span>
                        </div>
                    </BbDropzone>
                </div>
                <CardContainer>
                    <div class="form-grid">
                        <BbTextInput v-model="form.name" autocomplete="off" :errors="form.errors.name" :label="t('Nome')" required />
                        <BbTextInput v-model="form.surname" autocomplete="off" :errors="form.errors.surname" :label="t('Cognome')" required />
                        <BbTextInput v-model="form.email" autocomplete="off" :errors="form.errors.email" :label="t('Email')" required />
                    </div>
                </CardContainer>
            </section>
            <section class="profile-view__password">
                <CardContainer>
                    <div class="form-grid">
                        <BbTextInput
                            v-model="form.password"
                            autocomplete="new-password"
                            :label="t('Password')"
                            :placeholder="t('Digita la tua nuova password')"
                            :type="passwordType"
                            :errors="form.errors.password"
                        >
                            <template #append>
                                <EyeToggle
                                    :shown="passwordType === 'text'"
                                    @click="passwordType = passwordType === 'text' ? 'password' : 'text'"
                                />
                            </template>
                        </BbTextInput>
                        <BbTextInput
                            v-model="form.password_confirmation"
                            autocomplete="new-password"
                            :label="t('Conferma password')"
                            :placeholder="t('Conferma la tua nuova password')"
                            :type="passwordType"
                        >
                            <template #append>
                                <EyeToggle
                                    :shown="passwordType === 'text'"
                                    @click="passwordType = passwordType === 'text' ? 'password' : 'text'"
                                />
                            </template>
                        </BbTextInput>
                    </div>
                </CardContainer>
            </section>
            <div class="profile-view__actions">
                <BbButton :loading="form.processing" @click="save">{{ t('Salva') }}</BbButton>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import CardContainer from '@/components/common/CardContainer.vue';
import EyeToggle from '@/components/common/EyeToggle.vue';
import WorkspaceLayout from '@/layouts/WorkspaceLayout.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useObjectUrl } from '@vueuse/core';
import { BbButton, BbDropzone, BbIcon, BbTextInput, useToast } from 'bitboss-ui';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineOptions({
    layout: (h: any, page: any) => h(WorkspaceLayout, { title: 'Profilo' }, () => [page]),
});

const user = computed(() => usePage().props.auth.user);
const avatar = computed(() => usePage().props.avatar);
const { toast } = useToast();

const form = useForm({
    avatar: null,
    name: user.value.name,
    surname: user.value.surname,
    email: user.value.email,
    password: null,
    password_confirmation: null,
});

const propic = ref<null | File>(null);
const propicPreview = useObjectUrl(propic);
const passwordType = ref('password');

const save = () => {
    form.transform((data) => {
        data.avatar = propic.value;
        return data;
    }).post(route('workspace.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            toast({
                theme: 'success',
                text: t('Profilo aggiornato con successo'),
            });
        },
    });
};
</script>

<style>
@reference '@/../css/base.css';

.profile-view {
    @apply mx-auto w-full max-w-3xl;

    .profile-view__propic-section {
        @apply mb-6;

        .propic-container {
            @apply -mb-10 flex items-center justify-center;

            .bb-dropzone {
                @apply grid overflow-hidden rounded-full border-4 border-[var(--bb-border)] bg-[var(--bb-primary)];

                .dropzone__inner-container {
                    @apply grid h-40 w-40 grid-cols-1 grid-rows-1;

                    .propic__current-image,
                    .propic__new-image {
                        @apply col-start-1 row-start-1 h-full w-full object-cover;
                    }

                    .propic__overlay {
                        @apply relative col-start-1 row-start-1 flex cursor-pointer items-center justify-center bg-[var(--bb-primary)] p-[var(--min-px)] text-white opacity-0 transition-opacity hover:opacity-100;

                        .propic__overlay-text {
                            @apply block text-center text-[14px] font-semibold text-white;
                        }

                        .bb-icon {
                            @apply absolute bottom-6 left-1/2 -translate-x-1/2;
                        }
                    }
                }
            }
        }

        .card {
            @apply mx-auto;

            .form-grid {
                @apply grid-cols-1 pt-8 md:grid md:grid-cols-2;

                .x-address-select {
                    @apply col-span-2;
                }
            }
        }
    }

    .profile-view__password {
        @apply mb-6;

        .card {
            @apply mx-auto;

            .form-grid {
                @apply grid-cols-1 md:grid md:grid-cols-2;

                .bb-text-input {
                    @apply col-span-2;
                }
            }
        }
    }

    .profile-view__actions {
        @apply mx-auto w-full text-right;
    }

    .card {
        @apply p-[var(--min-px)] pt-5 pb-[var(--min-px)];
    }
}
</style>
