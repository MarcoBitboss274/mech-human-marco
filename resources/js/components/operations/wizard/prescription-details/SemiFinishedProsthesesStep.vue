<script setup lang="ts">
import OdontogramInput from '@/components/prescriptions/OdontogramInput.vue';
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
            <legend class="bb-label">{{ t('Tipologia del semilavorato') }}</legend>
            <BbRadio
                v-model="form.semi_finished_prostheses_details!.typology"
                :label="t('Corone e ponti')"
                value="Corone e ponti"
                name="semi_finished_prostheses_typology"
            />
            <BbRadio
                v-model="form.semi_finished_prostheses_details!.typology"
                :label="t('Full-arch')"
                value="Full-arch"
                name="semi_finished_prostheses_typology"
                :errors="form.errors['semi_finished_prostheses_details.typology']"
            />
        </fieldset>
        <fieldset v-if="form.semi_finished_prostheses_details!.typology === 'Corone e ponti'">
            <legend class="bb-label">{{ t('Dettaglio Corone e Ponti') }}</legend>
            <BbRadio
                v-model="form.semi_finished_prostheses_details!.crowns_and_bridges_details"
                :label="t('Zirconia monolitica su MH-Link - semilavorato')"
                value="Zirconia monolitica su MH-Link - semilavorato"
                name="semi_finished_crowns_and_bridges_details"
            />
            <BbRadio
                v-model="form.semi_finished_prostheses_details!.crowns_and_bridges_details"
                :label="t('Zirconia monolitica avvitata su MH-Link custom - semilavorato')"
                value="Zirconia monolitica avvitata su MH-Link custom - semilavorato"
                name="semi_finished_crowns_and_bridges_details"
            />
            <BbRadio
                v-model="form.semi_finished_prostheses_details!.crowns_and_bridges_details"
                :label="t('Corona in metallo ceramica avvitata - semilavorato')"
                value="Corona in metallo ceramica avvitata - semilavorato"
                name="semi_finished_crowns_and_bridges_details"
                :errors="form.errors['semi_finished_prostheses_details.crowns_and_bridges_details']"
            />
        </fieldset>
        <fieldset v-if="form.semi_finished_prostheses_details!.typology === 'Full-arch'">
            <legend class="bb-label">{{ t('Dettaglio Full-arch') }}</legend>
            <BbRadio
                v-model="form.semi_finished_prostheses_details!.full_bridge_details"
                :label="t('Toronto Tekna su 4 impianti - semilavorato')"
                value="Toronto Tekna su 4 impianti - semilavorato"
                name="semi_finished_full_bridge_details"
            />
            <BbRadio
                v-model="form.semi_finished_prostheses_details!.full_bridge_details"
                :label="t('Toronto Tekna su 6 impianti - semilavorato')"
                value="Toronto Tekna su 6 impianti - semilavorato"
                name="semi_finished_full_bridge_details"
                :errors="form.errors['semi_finished_prostheses_details.full_bridge_details']"
            />
        </fieldset>
        <OdontogramInput
            v-model="form.semi_finished_prostheses_details!.odontogram"
            :label="t('Odontogramma')"
            :errors="form.errors['semi_finished_prostheses_details.odontogram']"
        />
        <BbTextarea
            v-model="form.semi_finished_prostheses_details!.note"
            autocomplete="off"
            :label="t('Note')"
            :errors="form.errors['semi_finished_prostheses_details.note']"
        />
    </div>
</template>
