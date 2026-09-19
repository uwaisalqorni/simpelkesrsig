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
});
