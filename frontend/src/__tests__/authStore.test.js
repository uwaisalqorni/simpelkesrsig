import { describe, it, expect, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useAuthStore } from '../stores/authStore';

// Mock Web Storage API
const mockStorage = () => {
  let store = {};
  return {
    getItem: (key) => store[key] || null,
    setItem: (key, value) => { store[key] = String(value); },
    removeItem: (key) => { delete store[key]; },
    clear: () => { store = {}; }
  };
};

globalThis.localStorage = mockStorage();
globalThis.sessionStorage = mockStorage();

describe('authStore (Pinia)', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    globalThis.localStorage.clear();
    globalThis.sessionStorage.clear();
  });

  it('harus menginisialisasi state default tanpa token/user', () => {
    const auth = useAuthStore();
    expect(auth.user).toBeNull();
    expect(auth.token).toBeNull();
    expect(auth.isAuthenticated).toBe(false);
    expect(auth.role).toBe('');
    expect(auth.isSuperAdmin).toBe(false);
  });

  it('harus mengenali peran Super Admin dengan benar', () => {
    const auth = useAuthStore();
    auth.user = {
      id: 1,
      username: 'superadmin',
      role: 'super_admin',
      tenant_id: null
    };
    auth.token = 'mock_jwt_token_super';

    expect(auth.isAuthenticated).toBe(true);
    expect(auth.isSuperAdmin).toBe(true);
    expect(auth.isAdmin).toBe(true);
    expect(auth.isTeknisi).toBe(false);
    expect(auth.isRuangan).toBe(false);
  });

  it('harus mengenali peran Teknisi dan Unit Ruangan', () => {
    const auth = useAuthStore();
    
    // Test Teknisi
    auth.user = { id: 3, username: 'agus_elektro', role: 'teknisi', tenant_id: 1 };
    expect(auth.isTeknisi).toBe(true);
    expect(auth.isAdmin).toBe(false);

    // Test Ruangan
    auth.user = { id: 4, username: 'nurse_icu', role: 'ruangan', tenant_id: 1, room_name: 'ICU Sentral' };
    expect(auth.isRuangan).toBe(true);
    expect(auth.userRoom).toBe('ICU Sentral');
  });

  it('harus mendukung switchTenant oleh Super Admin', () => {
    const auth = useAuthStore();
    
    auth.switchTenant({ id: 2, name: 'Klinik Pratama Siliwangi Medical' });

    expect(auth.activeTenantId).toBe('2');
    expect(auth.activeTenantName).toBe('Klinik Pratama Siliwangi Medical');
    expect(auth.tenantId).toBe('2');
    expect(auth.tenantName).toBe('Klinik Pratama Siliwangi Medical');
    expect(globalThis.localStorage.getItem('simpelkes_active_tenant_id')).toBe('2');

    // Reset switchTenant (kembali ke holding)
    auth.switchTenant(null);
    expect(auth.activeTenantId).toBeNull();
    expect(globalThis.localStorage.getItem('simpelkes_active_tenant_id')).toBeNull();
  });

  it('harus membersihkan seluruh state dan storage saat logout', async () => {
    const auth = useAuthStore();
    auth.user = { id: 2, username: 'admin', role: 'admin', tenant_id: 1 };
    auth.token = 'valid_token_xyz';
    auth.activeTenantId = '1';
    globalThis.localStorage.setItem('simpelkes_token', 'valid_token_xyz');

    await auth.logout();

    expect(auth.user).toBeNull();
    expect(auth.token).toBeNull();
    expect(auth.activeTenantId).toBeNull();
    expect(auth.isAuthenticated).toBe(false);
    expect(globalThis.localStorage.getItem('simpelkes_token')).toBeNull();
  });
});
