import { describe, it, expect, vi } from 'vitest';
import { exportPreventiveWorkOrderToPDF } from '../utils/exportUtils';

describe('Preventive Work Order Export (exportUtils)', () => {
  it('harus mengekspor fungsi exportPreventiveWorkOrderToPDF', () => {
    expect(typeof exportPreventiveWorkOrderToPDF).toBe('function');
  });

  it('harus berhasil memproses lembar kerja PM tanpa throw error', () => {
    const mockSchedule = {
      id: 101,
      sp_number: 'SPK-PM-2026-0101',
      equipment_name: 'Patient Monitor Bionet BM3',
      asset_code: 'ALK-ICU-001',
      brand: 'Bionet',
      model_type: 'BM3 Pro',
      serial_number: 'BM3-889922',
      room_name: 'ICU Isolasi',
      frequency: 'quarterly',
      executor_type: 'internal',
      final_condition: 'laik_pakai',
      technician_name: 'Ahmad Elektromedik',
      supervisor_name: 'Faiz Kurniawan, S.Tr.Kes',
      execution_start_at: '2026-09-19 08:30:00',
      execution_end_at: '2026-09-19 09:45:00',
      completed_at: '2026-09-19 09:45:00',
      notes: 'Pemeriksaan berkala selesai sesuai standar SOP MFK 8.',
      inspection_checklist: {
        casing: 'baik',
        battery: 'baik',
        mounting: 'baik',
        power_cord: 'baik',
        filter: 'baik',
        probe_connector: 'baik',
        alarm: 'baik',
        controls: 'baik'
      },
      maintenance_actions: {
        cleaning: 'ya',
        lubricating: 'ya',
        tightening: 'ya',
        replacement: 'na'
      },
      electrical_safety: {
        grounding_resistance: '0.12',
        leakage_current: '42.0'
      }
    };

    const mockHospitalInfo = {
      name: 'RS Islam Gondanglegi',
      subtitle: 'Instalasi Pemeliharaan Sarana Rumah Sakit (IPSRS)',
      address: 'Jl. Hayam Wuruk No. 123, Gondanglegi, Malang',
      phone: '(0341) 879222',
      city: 'Gondanglegi',
      headName: 'Faiz Kurniawan, S.Tr.Kes',
      headNip: '19890412 201402 1 003'
    };

    expect(() => {
      exportPreventiveWorkOrderToPDF(mockSchedule, mockHospitalInfo);
    }).not.toThrow();
  });
});
