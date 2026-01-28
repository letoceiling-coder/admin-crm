<template>
  <div class="payment-detail-page">
    <div class="mb-6">
      <router-link
        to="/admin/payments"
        class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center gap-2"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Назад к списку платежей
      </router-link>

      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Платёж {{ payment?.payment_number || '' }}</h1>
          <p class="text-gray-600 mt-1">Диагностика провайдера и история webhook/capture/refund</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <a
            v-if="yooKassaLink"
            :href="yooKassaLink"
            target="_blank"
            rel="noopener noreferrer"
            class="h-10 px-4 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 inline-flex items-center gap-2"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7v7"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14L21 3"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7v14h14"></path>
            </svg>
            Открыть оплату
          </a>

          <button
            v-if="canSyncYooKassa"
            @click="doSyncYooKassa"
            :disabled="actionLoading"
            class="h-10 px-4 rounded-lg border border-blue-300 bg-blue-50 text-blue-800 hover:bg-blue-100 disabled:opacity-50 inline-flex items-center gap-2"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Принудительно обновить статус из ЮКассы
          </button>

          <button
            v-if="canCapture"
            @click="doCapture"
            :disabled="actionLoading"
            class="h-10 px-4 rounded-lg bg-amber-600 text-white hover:bg-amber-700 disabled:opacity-50"
          >
            Capture
          </button>
          <button
            v-if="canRefund"
            @click="doRefund"
            :disabled="actionLoading"
            class="h-10 px-4 rounded-lg bg-purple-600 text-white hover:bg-purple-700 disabled:opacity-50"
          >
            Возврат
          </button>
        </div>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <p class="text-red-800">{{ error }}</p>
    </div>

    <div v-else-if="payment" class="space-y-6">
      <!-- Summary -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="space-y-1">
            <div class="text-sm text-gray-500">ID</div>
            <div class="font-mono text-gray-900">{{ payment.id }}</div>
          </div>
          <div class="space-y-1">
            <div class="text-sm text-gray-500">Провайдер</div>
            <div class="font-medium text-gray-900">{{ payment.payment_provider || '—' }}</div>
          </div>
          <div class="space-y-1">
            <div class="text-sm text-gray-500">YooKassa ID</div>
            <div class="font-mono text-gray-900">{{ payment.transaction_id || '—' }}</div>
          </div>
          <div class="space-y-1">
            <div class="text-sm text-gray-500">Статус</div>
            <span :class="statusClass" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
              {{ statusLabel }}
            </span>
          </div>
          <div class="space-y-1">
            <div class="text-sm text-gray-500">Сумма</div>
            <div class="font-bold text-gray-900">{{ formatCurrency(payment.amount) }}</div>
          </div>
        </div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div class="text-gray-700">
            <span class="text-gray-500">Магазин (shop_id):</span> {{ payment.shop_id || '—' }}
          </div>
          <div class="text-gray-700">
            <span class="text-gray-500">Заказ:</span>
            <router-link
              v-if="payment.order?.id"
              :to="`/admin/orders/${payment.order.id}/edit`"
              class="text-blue-600 hover:text-blue-800 underline"
            >
              {{ payment.order.order_number }}
            </router-link>
            <span v-else>—</span>
          </div>
          <div class="text-gray-700"><span class="text-gray-500">Создан:</span> {{ formatDateTime(payment.created_at) }}</div>
          <div class="text-gray-700"><span class="text-gray-500">Оплачен:</span> {{ payment.paid_at ? formatDateTime(payment.paid_at) : '—' }}</div>
        </div>
      </div>

      <!-- Timeline blocks -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">История провайдера</h2>

        <div v-if="lastSync" class="mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
          <div class="text-sm font-semibold text-gray-800 mb-1">Последняя синхронизация из ЮКассы</div>
          <div class="text-sm text-gray-700">
            <span class="text-gray-500">at:</span> {{ lastSync.at || '—' }}
          </div>
          <div class="text-sm text-gray-700">
            <span class="text-gray-500">status (ЮКасса):</span> <span class="font-mono">{{ lastSync.response?.status || '—' }}</span>
          </div>
        </div>

        <div v-if="lastWebhook" class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
          <div class="text-sm font-semibold text-gray-800 mb-1">Последний webhook</div>
          <div class="text-sm text-gray-700">
            <span class="text-gray-500">event:</span> <span class="font-mono">{{ lastWebhook.event }}</span>
          </div>
          <div class="text-sm text-gray-700">
            <span class="text-gray-500">received_at:</span> {{ lastWebhook.received_at || '—' }}
          </div>
        </div>

        <div v-if="captures.length" class="mb-4">
          <div class="text-sm font-semibold text-gray-800 mb-2">Capture (ручной/авто)</div>
          <ul class="space-y-2">
            <li v-for="(c, idx) in captures" :key="idx" class="p-3 bg-gray-50 rounded-lg border border-gray-200">
              <div class="text-xs text-gray-500">{{ c.at || '—' }}</div>
              <div class="text-sm text-gray-800">
                status: <span class="font-mono">{{ c.response?.status || '—' }}</span>
              </div>
            </li>
          </ul>
        </div>

        <div v-if="refunds.length" class="mb-4">
          <div class="text-sm font-semibold text-gray-800 mb-2">Refunds</div>
          <ul class="space-y-2">
            <li v-for="(r, idx) in refunds" :key="idx" class="p-3 bg-gray-50 rounded-lg border border-gray-200">
              <div class="text-xs text-gray-500">{{ r.at || '—' }}</div>
              <div class="text-sm text-gray-800">
                status: <span class="font-mono">{{ r.response?.status || '—' }}</span>,
                amount: <span class="font-mono">{{ r.amount ?? '—' }}</span>
              </div>
            </li>
          </ul>
        </div>

        <div v-if="!lastSync && !lastWebhook && !captures.length && !refunds.length" class="text-sm text-gray-500">
          Истории пока нет (синхронизация/webhook/capture/refund ещё не выполнялись).
        </div>
      </div>

      <!-- Raw JSON -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between gap-2 mb-3">
          <h2 class="text-lg font-semibold text-gray-900">provider_payload (raw)</h2>
          <button
            type="button"
            @click="copyPayload"
            class="h-10 px-4 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
          >
            Копировать JSON
          </button>
        </div>
        <pre class="text-xs bg-gray-950 text-gray-100 rounded-lg p-4 overflow-auto max-h-[520px]">{{ payloadPretty }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '@/api/axios';
import Swal from 'sweetalert2';

const route = useRoute();
const id = computed(() => Number(route.params.id));

const payment = ref(null);
const loading = ref(false);
const error = ref(null);
const actionLoading = ref(false);

const fetchPayment = async () => {
  loading.value = true;
  error.value = null;
  try {
    const resp = await apiClient.get(`/admin/payments/${id.value}`);
    payment.value = resp.data?.data || resp.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Не удалось загрузить платеж';
  } finally {
    loading.value = false;
  }
};

const yooKassaLink = computed(() => {
  const p = payment.value;
  return p?.provider_payload?.created?.confirmation?.confirmation_url || null;
});

const canCapture = computed(() => {
  const p = payment.value;
  return p?.payment_provider === 'yookassa' && p?.transaction_id && (p?.status === 'pending' || p?.status === 'processing');
});

const canRefund = computed(() => {
  const p = payment.value;
  return p?.payment_provider === 'yookassa' && p?.transaction_id && p?.status === 'completed';
});

const canSyncYooKassa = computed(() => {
  const p = payment.value;
  return p?.payment_provider === 'yookassa' && p?.transaction_id;
});

const payloadPretty = computed(() => JSON.stringify(payment.value?.provider_payload ?? {}, null, 2));

const lastWebhook = computed(() => payment.value?.provider_payload?.last_webhook || null);
const lastSync = computed(() => payment.value?.provider_payload?.last_sync || null);
const captures = computed(() => payment.value?.provider_payload?.capture || []);
const refunds = computed(() => payment.value?.provider_payload?.refunds || []);

const statusLabel = computed(() => {
  const s = payment.value?.status;
  const labels = { pending: 'Ожидает', processing: 'В обработке', completed: 'Завершен', failed: 'Неудачный', refunded: 'Возвращен' };
  return labels[s] || s || '—';
});

const statusClass = computed(() => {
  const s = payment.value?.status;
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    refunded: 'bg-gray-100 text-gray-800',
  };
  return classes[s] || 'bg-gray-100 text-gray-800';
});

function formatCurrency(amount) {
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(amount ?? 0);
}

function formatDateTime(val) {
  if (!val) return '—';
  try {
    return new Date(val).toLocaleString('ru-RU');
  } catch {
    return String(val);
  }
}

async function copyPayload() {
  try {
    await navigator.clipboard.writeText(payloadPretty.value);
    await Swal.fire({ icon: 'success', title: 'Скопировано', timer: 1200, showConfirmButton: false });
  } catch {
    await Swal.fire({ icon: 'error', title: 'Не удалось скопировать' });
  }
}

async function doCapture() {
  if (!payment.value) return;
  const confirm = await Swal.fire({
    icon: 'question',
    title: 'Подтвердить платеж (capture)?',
    text: `Платеж ${payment.value.payment_number}. YooKassa ID: ${payment.value.transaction_id}`,
    showCancelButton: true,
    confirmButtonText: 'Capture',
    cancelButtonText: 'Отмена',
  });
  if (!confirm.isConfirmed) return;
  actionLoading.value = true;
  try {
    const resp = await apiClient.post(`/admin/payments/${payment.value.id}/yookassa/capture`);
    payment.value = resp.data?.data || resp.data;
    await Swal.fire({ icon: 'success', title: 'Готово', timer: 1200, showConfirmButton: false });
  } catch (err) {
    await Swal.fire({ icon: 'error', title: 'Ошибка', text: err.response?.data?.message || 'Не удалось выполнить capture' });
  } finally {
    actionLoading.value = false;
  }
}

async function doSyncYooKassa() {
  if (!payment.value) return;
  actionLoading.value = true;
  try {
    const resp = await apiClient.post(`/admin/payments/${payment.value.id}/yookassa/sync`);
    payment.value = resp.data?.data || resp.data;
    await Swal.fire({ icon: 'success', title: 'Статус обновлён из ЮКассы', timer: 1200, showConfirmButton: false });
  } catch (err) {
    await Swal.fire({ icon: 'error', title: 'Ошибка', text: err.response?.data?.message || 'Не удалось обновить статус' });
  } finally {
    actionLoading.value = false;
  }
}

async function doRefund() {
  if (!payment.value) return;
  const res = await Swal.fire({
    title: 'Возврат',
    input: 'text',
    inputLabel: 'Сумма возврата (оставьте пустым для полного)',
    inputPlaceholder: String(payment.value.amount),
    showCancelButton: true,
    confirmButtonText: 'Сделать возврат',
    cancelButtonText: 'Отмена',
  });
  if (!res.isConfirmed) return;
  const amount = (res.value || '').trim();
  actionLoading.value = true;
  try {
    const payload = {};
    if (amount) payload.amount = Number(amount);
    const resp = await apiClient.post(`/admin/payments/${payment.value.id}/yookassa/refund`, payload);
    payment.value = resp.data?.data || resp.data;
    await Swal.fire({ icon: 'success', title: 'Готово', timer: 1200, showConfirmButton: false });
  } catch (err) {
    await Swal.fire({ icon: 'error', title: 'Ошибка', text: err.response?.data?.message || 'Не удалось выполнить возврат' });
  } finally {
    actionLoading.value = false;
  }
}

onMounted(() => {
  fetchPayment();
});
</script>

