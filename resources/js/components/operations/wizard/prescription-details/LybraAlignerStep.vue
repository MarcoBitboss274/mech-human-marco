<script setup lang="ts">
import type { OperationCreateWizardForm } from '@/types/Operation';
import { BbRadio, BbTextarea } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    form: OperationCreateWizardForm & {
        errors: Record<string, string>;
    };
};

defineProps<Props>();
</script>

<template>
    <div class="admin-form__grid">
        <fieldset>
            <legend class="bb-label">{{ t('Cut line') }}</legend>
            <BbRadio
                v-model="form.lybra_aligner_details!.cut_line"
                :label="t('Linea di taglio dritta')"
                value="Linea di taglio dritta"
                name="cut_line"
            />
            <BbRadio
                v-model="form.lybra_aligner_details!.cut_line"
                :label="t('Linea di taglio a festoni')"
                value="Linea di taglio a festoni"
                name="cut_line"
            />
            <BbRadio
                v-model="form.lybra_aligner_details!.cut_line"
                :label="t('Indifferente')"
                value="Indifferente"
                name="cut_line"
                :errors="form.errors['lybra_aligner_details.cut_line']"
            />
        </fieldset>
        <BbTextarea
            v-model="form.lybra_aligner_details!.note"
            autocomplete="off"
            :label="t('Note')"
            :errors="form.errors['lybra_aligner_details.note']"
        />
    </div>
</template>
