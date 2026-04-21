<script setup lang="ts">
import OdontogramInput from '@/components/prescriptions/OdontogramInput.vue';
import type { OperationCreateWizardForm } from '@/types/Operation';
import { BbNumberInput, BbRadio, BbSelect, BbTextarea } from 'bitboss-ui';
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
            <legend class="bb-label">{{ t('Tipologia protrusore') }}</legend>
            <BbRadio
                v-model="form.protrusor_details!.protrusor_typology"
                :label="t('Protrusor con sensore')"
                value="Protrusor con sensore"
                name="protrusor_typology"
            />
            <BbRadio
                v-model="form.protrusor_details!.protrusor_typology"
                :label="t('Protrusor senza sensore')"
                value="Protrusor senza sensore"
                name="protrusor_typology"
                :errors="form.errors['protrusor_details.protrusor_typology']"
            />
        </fieldset>
        <OdontogramInput
            v-model="form.protrusor_details!.odontogram"
            :label="t('Odontogramma')"
            :errors="form.errors['protrusor_details.odontogram']"
        />
        <BbSelect
            v-model="form.protrusor_details!.jig"
            :label="t('Jig di Deprogrammazione muscolare')"
            item-text="label"
            item-value="value"
            :items="[
                {
                    label: t('Si'),
                    value: 'Si',
                },
                {
                    label: t('No'),
                    value: 'No',
                },
            ]"
            :errors="form.errors['protrusor_details.jig']"
        />

        <BbSelect
            v-model="form.protrusor_details!.remaining_upper_teeth"
            :label="t('Denti residui arcata Superiore')"
            item-text="label"
            item-value="value"
            :items="[
                {
                    label: t('Meno di 8'),
                    value: 'Meno di 8',
                },
                {
                    label: t('Più di 8'),
                    value: 'Più di 8',
                },
            ]"
            :errors="form.errors['protrusor_details.remaining_upper_teeth']"
        />
        <BbSelect
            v-model="form.protrusor_details!.remaining_lower_teeth"
            :label="t('Denti residui arcata Inferiore')"
            item-text="label"
            item-value="value"
            :items="[
                {
                    label: t('Meno di 8'),
                    value: 'Meno di 8',
                },
                {
                    label: t('Più di 8'),
                    value: 'Più di 8',
                },
            ]"
            :errors="form.errors['protrusor_details.remaining_lower_teeth']"
        />
        <BbSelect
            v-model="form.protrusor_details!.transpalatal_arch"
            :label="t('Arco Palatale in metallo')"
            item-text="label"
            item-value="value"
            :items="[
                {
                    label: t('Si'),
                    value: 'Si',
                },
                {
                    label: t('No'),
                    value: 'No',
                },
            ]"
            :errors="form.errors['protrusor_details.transpalatal_arch']"
        />
        <p class="my-2">
            Dalla lunghezza rilevata e registrata di massima retrusione a massima protrusione, che è di
            <BbNumberInput
                v-model="form.protrusor_details!.mandibular_advancement"
                class="w-36"
                autocomplete="off"
                :label="t('Avanzamento mandibolare')"
                hide-label
                :errors="form.errors['protrusor_details.mandibular_advancement']"
            >
                <template #suffix> mm</template>
            </BbNumberInput>
            si richiede di disporre un avanzamento mandibolare partendo dalla massima retrusione di
            <BbNumberInput
                v-model="form.protrusor_details!.mandibular_advancement_2"
                class="w-36"
                autocomplete="off"
                :label="t('Avanzamento mandibolare 2')"
                hide-label
                :errors="form.errors['protrusor_details.mandibular_advancement_2']"
            >
                <template #suffix> mm</template>
            </BbNumberInput>
        </p>

        <BbTextarea v-model="form.protrusor_details!.note" autocomplete="off" :label="t('Note')" :errors="form.errors['protrusor_details.note']" />
    </div>
</template>
