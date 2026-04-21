<template>
    <BaseButton class="eye-toggle" type="button">
        <span class="label">{{ shown ? t('Nascondi') : t('Visualizza') }} {{ t('valore') }}</span>
        <BbIcon v-if="shown" :key="'false'" :color="color" :size="size" type="eye_closed" />
        <BbIcon v-else :key="'true'" :color="color" :size="size" type="eye" />
    </BaseButton>
</template>

<script setup lang="ts">
import type { BbIconProps } from 'bitboss-ui';
import { BaseButton, BbIcon } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
type Props = {
    shown: boolean;
} & Pick<BbIconProps, 'size' | 'color'>;

withDefaults(defineProps<Props>(), {
    size: '20',
});
</script>

<style>
@reference '@/../css/base.css';

.eye-toggle {
    @apply text-mix-400 inline-grid;
    border-radius: calc(var(--bb-radius) - 4px);
    > .label {
        @apply sr-only;
    }
    .bb-icon {
        @apply col-start-1 row-start-1;
    }
}
</style>
