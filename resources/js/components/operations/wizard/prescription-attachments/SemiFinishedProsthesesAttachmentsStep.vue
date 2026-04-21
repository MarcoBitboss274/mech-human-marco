<script setup lang="ts">
import type { OperationCreateWizardForm } from '@/types/Operation';
import { BbDropzone } from 'bitboss-ui';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

type Props = {
    form: OperationCreateWizardForm;
    manual: boolean | null;
};

defineProps<Props>();
</script>

<template>
    <div class="admin-form__grid">
        <template v-if="manual">
            <ul class="attachments-list">
                <li>{{ t('Progetto in STL da fresare oppure scansione digitale completa') }}</li>
                <li>{{ t('Scansione del provvisorio') }}</li>
            </ul>
        </template>
        <template v-else>
            <div>
                <span class="bb-label">{{ t('Progetto in STL da fresare oppure scansione digitale completa') }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.semi_finished_prostheses_attachments!.progetto_in_stl_da_fresare_oppure_scansione_digitale_completa"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['semi_finished_prostheses_attachments.progetto_in_stl_da_fresare_oppure_scansione_digitale_completa']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.semi_finished_prostheses_attachments!.progetto_in_stl_da_fresare_oppure_scansione_digitale_completa
                            ? t('File selezionato', {
                                  name: form.semi_finished_prostheses_attachments!.progetto_in_stl_da_fresare_oppure_scansione_digitale_completa.name,
                              })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
            <div>
                <span class="bb-label">{{ t('Scansione del provvisorio') }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.semi_finished_prostheses_attachments!.scansione_del_provvisorio"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['semi_finished_prostheses_attachments.scansione_del_provvisorio']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.semi_finished_prostheses_attachments!.scansione_del_provvisorio
                            ? t('File selezionato', { name: form.semi_finished_prostheses_attachments!.scansione_del_provvisorio.name })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
        </template>
    </div>
</template>
