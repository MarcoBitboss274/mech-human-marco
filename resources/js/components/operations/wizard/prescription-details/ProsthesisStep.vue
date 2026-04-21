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
            <legend class="bb-label">{{ t('Tipologia di protesi') }}</legend>
            <BbRadio v-model="form.prosthesis_details!.typology" :label="t('Corone e ponti')" value="Corone e ponti" name="prosthesis_typology" />
            <BbRadio
                v-model="form.prosthesis_details!.typology"
                :label="t('Full-arch')"
                value="Full-arch"
                name="prosthesis_typology"
                :errors="form.errors['prosthesis_details.typology']"
            />
        </fieldset>
        <fieldset v-if="form.prosthesis_details!.typology === 'Corone e ponti'">
            <legend class="bb-label">{{ t('Dettaglio Corone e Ponti') }}</legend>
            <BbRadio
                v-model="form.prosthesis_details!.crowns_and_bridges_details"
                :label="t('Zirconia monolitica su MH-Link')"
                value="Zirconia monolitica su MH-Link"
                name="crowns_and_bridges_details"
            />
            <BbRadio
                v-model="form.prosthesis_details!.crowns_and_bridges_details"
                :label="t('Zirconia monolitica avvitata su MH-Link custom')"
                value="Zirconia monolitica avvitata su MH-Link custom"
                name="crowns_and_bridges_details"
            />
            <BbRadio
                v-model="form.prosthesis_details!.crowns_and_bridges_details"
                :label="t('Corona in metallo ceramica avvitata')"
                value="Corona in metallo ceramica avvitata"
                name="crowns_and_bridges_details"
            />
            <BbRadio
                v-model="form.prosthesis_details!.crowns_and_bridges_details"
                :label="t('Provvisorio in PMMA avvitato su MH-Link')"
                value="Provvisorio in PMMA avvitato su MH-Link"
                name="crowns_and_bridges_details"
                :errors="form.errors['prosthesis_details.crowns_and_bridges_details']"
            />
        </fieldset>
        <fieldset v-if="form.prosthesis_details!.typology === 'Full-arch'">
            <legend class="bb-label">{{ t('Dettaglio Full-arch') }}</legend>
            <BbRadio
                v-model="form.prosthesis_details!.full_bridge_details"
                :label="t('Provvisorio parziale armato in cromo cobalto - fino a 5 elementi')"
                value="Provvisorio parziale armato in cromo cobalto - fino a 5 elementi"
                name="full_bridge_details"
            />
            <BbRadio
                v-model="form.prosthesis_details!.full_bridge_details"
                :label="t('Provvisorio parziale non armato - fino a 5 elementi')"
                value="Provvisorio parziale non armato - fino a 5 elementi"
                name="full_bridge_details"
            />
            <BbRadio
                v-model="form.prosthesis_details!.full_bridge_details"
                :label="t('Toronto provvisoria armata in cromo cobalto')"
                value="Toronto provvisoria armata in cromo cobalto"
                name="full_bridge_details"
            />
            <BbRadio
                v-model="form.prosthesis_details!.full_bridge_details"
                :label="t('Toronto provvisoria non armata in cromo cobalto')"
                value="Toronto provvisoria non armata in cromo cobalto"
                name="full_bridge_details"
            />
            <BbRadio
                v-model="form.prosthesis_details!.full_bridge_details"
                :label="t('Toronto Tekna su 4 impianti')"
                value="Toronto Tekna su 4 impianti"
                name="full_bridge_details"
            />
            <BbRadio
                v-model="form.prosthesis_details!.full_bridge_details"
                :label="t('Toronto Tekna su 6 impianti')"
                value="Toronto Tekna su 6 impianti"
                name="full_bridge_details"
            />
            <BbRadio
                v-model="form.prosthesis_details!.full_bridge_details"
                :label="t('Toronto MB su 4 impianti')"
                value="Toronto MB su 4 impianti"
                name="full_bridge_details"
            />
            <BbRadio
                v-model="form.prosthesis_details!.full_bridge_details"
                :label="t('Toronto MB su 6 impianti')"
                value="Toronto MB su 6 impianti"
                name="full_bridge_details"
                :errors="form.errors['prosthesis_details.full_bridge_details']"
            />
        </fieldset>
        <OdontogramInput
            v-model="form.prosthesis_details!.odontogram"
            :label="t('Odontogramma')"
            :errors="form.errors['prosthesis_details.odontogram']"
        />
        <BbSelect
            v-model="form.prosthesis_details!['3d_normal_model']"
            :label="t('Modello 3D normal per corone e ponti')"
            item-text="label"
            item-value="value"
            :items="[
                { label: t('Si'), value: 'Si' },
                { label: t('No'), value: 'No' },
            ]"
            :errors="form.errors['prosthesis_details.3d_normal_model']"
        />
        <BbSelect
            v-model="form.prosthesis_details!['3d_excellent_model']"
            :label="t('Modello 3D excellent per arcate complete')"
            item-text="label"
            item-value="value"
            :items="[
                { label: t('Si'), value: 'Si' },
                { label: t('No'), value: 'No' },
            ]"
            :errors="form.errors['prosthesis_details.3d_excellent_model']"
        />
        <BbTextarea v-model="form.prosthesis_details!.note" autocomplete="off" :label="t('Note')" :errors="form.errors['prosthesis_details.note']" />
    </div>
</template>
