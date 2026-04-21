<template>
    <div class="css-override" :class="{ 'css-override--open': cssOverrideOpen }">
        <textarea
            v-if="cssOverrideOpen"
            v-model="text"
            class="css-override__textarea"
            :placeholder="t('Incolla il css qui perché sia applicato al sito')"
        />
        <div v-if="cssOverrideOpen" class="css-override__footer">
            <BbButton class="bb-button--primary-outline" :text="t('Chiudi')" @click="cssOverrideOpen = false" />
            <BbButton :text="t('Salva')" />
        </div>
        <div v-if="!cssOverrideOpen" class="css-override__edit">
            <BbButton class="bb-button--primary-outline" icon="pencil_line" @click="cssOverrideOpen = true">{{ t('Modifica override stili css') }}</BbButton>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useLocalStorage, watchDebounced } from '@vueuse/core';
import { BbButton } from 'bitboss-ui';
import { onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const cssOverrideOpen = useLocalStorage('css-override-open', false);
const text = useLocalStorage('css-override', '');
const styleId = 'css-override';

const getStyleElement = () => {
    const tag = document.getElementById(styleId);
    if (!tag) {
        const style = document.createElement('style');
        style.id = styleId;
        document.head.appendChild(style);
        return style;
    }
    return tag;
};

const applyCss = () => {
    const style = getStyleElement();
    style.textContent = text.value;
};

onMounted(applyCss);
watchDebounced(text, applyCss, { debounce: 500 });
</script>

<style>
@reference '@/../css/base.css';
.css-override {
    @apply fixed right-0 bottom-0 z-[var(--bb-overlay-z-index)] m-[var(--min-px)] grid space-y-4 rounded-[var(--bb-radius)] border border-[var(--bb-border)] bg-[var(--bb-panel)] !p-[var(--min-px)];

    &--open {
        @apply z-50 w-[calc(100%-var(--min-px)*2)];
    }

    textarea {
        @apply min-h-[300px] rounded-[var(--bb-radius)] border border-[var(--bb-border)] bg-[var(--bb-panel)] p-[var(--min-px)] transition-all outline-none focus:ring focus:ring-[var(--bb-ring)] focus:outline-none;
    }
    .css-override__footer {
        @apply flex justify-end gap-x-4;
    }
}
</style>
