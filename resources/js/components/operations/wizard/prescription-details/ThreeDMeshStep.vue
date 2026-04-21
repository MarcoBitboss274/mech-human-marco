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
            <legend class="bb-label">{{ t('Dimensioni 3D Mesh') }}</legend>
            <BbRadio v-model="form.three_d_mesh_details!.dimension" :label="t('Small')" value="Small" name="dimension" />
            <BbRadio v-model="form.three_d_mesh_details!.dimension" :label="t('Medium')" value="Medium" name="dimension" />
            <BbRadio
                v-model="form.three_d_mesh_details!.dimension"
                :label="t('Large')"
                value="Large"
                name="dimension"
                :errors="form.errors['three_d_mesh_details.dimension']"
            />
        </fieldset>
        <OdontogramInput
            v-model="form.three_d_mesh_details!.odontogram"
            :label="t('Odontogramma')"
            :errors="form.errors['three_d_mesh_details.odontogram']"
        />
        <BbSelect
            v-model="form.three_d_mesh_details!.outer_finish"
            :label="t('Finitura esterna')"
            item-text="label"
            item-value="value"
            :items="[
                { label: t('Liscia'), value: 'Liscia' },
                { label: t('Ruvida'), value: 'Ruvida' },
            ]"
            :errors="form.errors['three_d_mesh_details.outer_finish']"
        />
        <BbSelect
            v-model="form.three_d_mesh_details!.inner_finish"
            :label="t('Finitura interna')"
            item-text="label"
            item-value="value"
            :items="[
                { label: t('Liscia'), value: 'Liscia' },
                { label: t('Ruvida'), value: 'Ruvida' },
            ]"
            :errors="form.errors['three_d_mesh_details.inner_finish']"
        />
        <BbSelect
            v-model="form.three_d_mesh_details!.pattern"
            :label="t('Pattern')"
            item-text="label"
            item-value="value"
            :items="[
                { label: t('Pieno'), value: 'Pieno' },
                { label: t('Fori'), value: 'Fori' },
                { label: t('Microfori'), value: 'Microfori' },
            ]"
            :errors="form.errors['three_d_mesh_details.pattern']"
        />
        <BbSelect
            v-model="form.three_d_mesh_details!.stress_breakers"
            :label="t('Inviti a rottura')"
            item-text="label"
            item-value="value"
            :items="[
                { label: t('Si'), value: 'Si' },
                { label: t('No'), value: 'No' },
            ]"
            :errors="form.errors['three_d_mesh_details.stress_breakers']"
        />
        <BbSelect
            v-model="form.three_d_mesh_details!['3d_model']"
            :label="t('Modello 3D')"
            item-text="label"
            item-value="value"
            :items="[
                { label: t('Si'), value: 'Si' },
                { label: t('No'), value: 'No' },
            ]"
            :errors="form.errors['three_d_mesh_details.3d_model']"
        />
        <BbNumberInput
            v-model="form.three_d_mesh_details!.screw_diameter"
            autocomplete="off"
            :min="0"
            :label="t('Diametro delle viti da osteosintesi')"
            :errors="form.errors['three_d_mesh_details.screw_diameter']"
        />
        <BbTextInput
            v-model="form.three_d_mesh_details!.shared_project_note"
            autocomplete="off"
            :label="t('Preferenze di giorni e orari per progettazione condivisa')"
            :errors="form.errors['three_d_mesh_details.shared_project_note']"
        />
        <BbTextarea
            v-model="form.three_d_mesh_details!.note"
            autocomplete="off"
            :label="t('Note')"
            :errors="form.errors['three_d_mesh_details.note']"
        />
    </div>
</template>
