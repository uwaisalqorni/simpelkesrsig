import { describe, it, expect, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useSettingStore } from '../stores/settingStore';

describe('settingStore (Pinia)', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('harus memuat default state konfigurasi rumah sakit dan telegram', () => {
    const store = useSettingStore();
    expect(store.settings).toBeDefined();
    expect(store.hospitalName).toBe('RS Islam Gondanglegi');
    expect(store.settings.telegram_notif_emergency).toBe(1);
    expect(store.settings.telegram_notif_routine).toBe(1);
  });

  it('harus membaca getter logo default jika logo null', () => {
    const store = useSettingStore();
    store.settings.hospital_logo = null;
    expect(store.hospitalLogoUrl).toBe('/logo.svg');
  });

  it('harus mengembalikan telegramBotToken dan chatId sesuai state', () => {
    const store = useSettingStore();
    store.settings.telegram_bot_token = '123456:TEST_TOKEN';
    store.settings.telegram_chat_id = '-100987654321';

    expect(store.telegramBotToken).toBe('123456:TEST_TOKEN');
    expect(store.telegramChatId).toBe('-100987654321');
  });

  it('harus membaca nama dan NIP Kepala Instalasi IPSRS', () => {
    const store = useSettingStore();
    expect(store.headIpsrsName).toBe('Ahmad Elektromedik, S.Tr.Kes');
    expect(store.headIpsrsNip).toBe('19850712 201001 1 002');

    store.settings.head_ipsrs_name = 'Dr. Ir. Bambang, M.T.';
    store.settings.head_ipsrs_nip = '19750101 200003 1 001';

    expect(store.headIpsrsName).toBe('Dr. Ir. Bambang, M.T.');
    expect(store.headIpsrsNip).toBe('19750101 200003 1 001');
  });
});
