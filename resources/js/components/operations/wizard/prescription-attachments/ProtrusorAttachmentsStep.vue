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
                <li>{{ t("Rilevazione dell'avanzamento mandibolare con occlusione") }}</li>
            </ul>
        </template>
        <template v-else>
            <div>
                <span class="bb-label">{{ t('Scansione intraorale') }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.protrusor_attachments!.scansione_intraorale"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['protrusor_attachments.scansione_intraorale']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.protrusor_attachments!.scansione_intraorale
                            ? t('File selezionato', { name: form.protrusor_attachments!.scansione_intraorale.name })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
            <div>
                <span class="bb-label">{{ t("Rilevazione dell'avanzamento mandibolare con occlusione") }}</span>
                <BbDropzone
                    v-slot="{ dragging }"
                    v-model="form.protrusor_attachments!.rilevazione_dell_avanzamento_mandibolare_con_occlusione"
                    class="rounded-xl border border-dashed p-10 text-center"
                    :errors="form.errors['protrusor_attachments.rilevazione_dell_avanzamento_mandibolare_con_occlusione']"
                >
                    <span v-if="dragging">{{ t('Rilascia i file') }}</span>
                    <span v-else>{{
                        form.protrusor_attachments!.rilevazione_dell_avanzamento_mandibolare_con_occlusione
                            ? t('File selezionato', {
                                  name: form.protrusor_attachments!.rilevazione_dell_avanzamento_mandibolare_con_occlusione.name,
                              })
                            : t('Seleziona un file')
                    }}</span>
                </BbDropzone>
            </div>
        </template>
    </div>
</template>
