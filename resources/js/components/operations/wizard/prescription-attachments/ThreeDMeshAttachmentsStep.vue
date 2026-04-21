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
                <li>{{ t('Scansione intraorale') }}</li>
                <li>{{ t('CBCT allineabile con la scansione rilevata') }}</li>
                <li>{{ t('Ceratura diagnostica') }}</li>
                <li>{{ t('Scansione facciale o foto del sorriso') }}</li>
            </ul>
        </template>
        <template v-else>
            <div>
                <span class="bb-label">{{ t('Scansione intraorale') }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.three_d_mesh_attachments!.scansione_intraorale"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['three_d_mesh_attachments.scansione_intraorale']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.three_d_mesh_attachments!.scansione_intraorale
                            ? t('File selezionato', { name: form.three_d_mesh_attachments!.scansione_intraorale.name })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
            <div>
                <span class="bb-label">{{ t('CBCT allineabile con la scansione rilevata') }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.three_d_mesh_attachments!.cbct_allineabile_con_la_scansione_rilevata"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['three_d_mesh_attachments.cbct_allineabile_con_la_scansione_rilevata']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.three_d_mesh_attachments!.cbct_allineabile_con_la_scansione_rilevata
                            ? t('File selezionato', { name: form.three_d_mesh_attachments!.cbct_allineabile_con_la_scansione_rilevata.name })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
            <div>
                <span class="bb-label">{{ t('Ceratura diagnostica') }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.three_d_mesh_attachments!.ceratura_diagnostica"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['three_d_mesh_attachments.ceratura_diagnostica']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.three_d_mesh_attachments!.ceratura_diagnostica
                            ? t('File selezionato', { name: form.three_d_mesh_attachments!.ceratura_diagnostica.name })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
            <div>
                <span class="bb-label">{{ t('Scansione facciale o foto del sorriso') }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.three_d_mesh_attachments!.scansione_facciale_o_foto_del_sorriso"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['three_d_mesh_attachments.scansione_facciale_o_foto_del_sorriso']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.three_d_mesh_attachments!.scansione_facciale_o_foto_del_sorriso
                            ? t('File selezionato', { name: form.three_d_mesh_attachments!.scansione_facciale_o_foto_del_sorriso.name })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
        </template>
    </div>
</template>
