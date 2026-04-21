<script setup lang="ts">
import OdontogramInput from '@/components/prescriptions/OdontogramInput.vue';
import type { OperationCreateWizardForm } from '@/types/Operation';
import { BbRadio, BbSelect, BbTextarea } from 'bitboss-ui';
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
            <legend class="bb-label">{{ t('Tipologia di chirurgia') }}</legend>
            <BbRadio
                v-model="form.guided_surgery_details!.surgery_typology"
                :label="t('Progettazione chirurgica')"
                value="Progettazione chirurgica"
                name="surgery_typology"
            />
            <BbRadio
                v-model="form.guided_surgery_details!.surgery_typology"
                :label="t('Progettazione chirurgica + dima')"
                value="Progettazione chirurgica + dima"
                name="surgery_typology"
            />
            <BbRadio
                v-model="form.guided_surgery_details!.surgery_typology"
                :label="t('Progettazione chirurgica + dima + provvisorio armato in cromo cobalto (fino a 5 elementi)')"
                value="Progettazione chirurgica + dima + provvisorio armato in cromo cobalto (fino a 5 elementi)"
                name="surgery_typology"
            />
            <BbRadio
                v-model="form.guided_surgery_details!.surgery_typology"
                :label="t('Progettazione chirurgica + dima + Toronto provvisoria armata in cromo cobalto')"
                value="Progettazione chirurgica + dima + Toronto provvisoria armata in cromo cobalto"
                name="surgery_typology"
                :errors="form.errors['guided_surgery_details.surgery_typology']"
            />
        </fieldset>
        <OdontogramInput
            v-model="form.guided_surgery_details!.odontogram"
            :label="t('Odontogramma')"
            :errors="form.errors['guided_surgery_details.odontogram']"
        />
        <BbSelect
            v-model="form.guided_surgery_details!.desired_implant_line"
            :label="t('Linea implantare desiderata')"
            item-text="label"
            item-value="value"
            :items="[
                { label: t('Shard'), value: 'Shard' },
                { label: t('Even'), value: 'Even' },
                { label: t('Avior'), value: 'Avior' },
                { label: t('Avior Plus'), value: 'Avior Plus' },
                { label: t('Bond DW'), value: 'Bond DW' },
                { label: t('Bond DZ'), value: 'Bond DZ' },
            ]"
            :errors="form.errors['guided_surgery_details.desired_implant_line']"
        />
        <BbTextarea
            v-model="form.guided_surgery_details!.additional_info"
            autocomplete="off"
            :label="t('Informazioni aggiuntive sulla protesi')"
            :errors="form.errors['guided_surgery_details.additional_info']"
        />
        <BbTextarea
            v-model="form.guided_surgery_details!.note"
            autocomplete="off"
            :label="t('Note')"
            :errors="form.errors['guided_surgery_details.note']"
        />
    </div>
</template>
