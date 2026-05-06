<template>
    <EmptyLayout title="Account non associato">
        <div class="orphan-page">
            <div class="orphan-page__card">
                <h1 class="orphan-page__title">{{ t('Account non associato a nessun fornitore') }}</h1>
                <p class="orphan-page__text">
                    {{ t('Il tuo account non è attualmente associato a nessuna azienda Fornitore.') }}
                </p>
                <p class="orphan-page__text">
                    {{ t('Per ricevere accesso all\'area di lavoro, contatta M&H.') }}
                </p>
                <div class="orphan-page__actions">
                    <BbButton prepend:icon="logout" @click="logout">{{ t('Logout') }}</BbButton>
                </div>
            </div>
        </div>
    </EmptyLayout>
</template>

<script setup lang="ts">
import EmptyLayout from '@/layouts/app/EmptyLayout.vue';
import { router } from '@inertiajs/vue3';
import { BbButton } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const logout = () => {
    router.post(route('logout'));
};
</script>

<style scoped>
.orphan-page {
    display: grid;
    place-items: center;
    min-height: 100vh;
    padding: 32px;
    background: var(--bb-panel);
}

.orphan-page__card {
    max-width: 480px;
    padding: 32px;
    background: var(--bb-surface, #fff);
    border: 2px solid var(--bb-border, #e2e8f0);
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    align-items: center;
    text-align: center;
}

.orphan-page__title {
    font-size: 22px;
    font-weight: 700;
    margin: 0;
}

.orphan-page__text {
    margin: 0;
    color: var(--bb-text-muted, #555);
    font-size: 14px;
}

.orphan-page__actions {
    margin-top: 8px;
}
</style>
