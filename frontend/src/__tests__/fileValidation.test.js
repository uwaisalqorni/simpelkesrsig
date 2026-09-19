import { describe, it, expect } from 'vitest';
import { validateFile, getAcceptString, getMaxSizeMB } from '../utils/fileValidation';

describe('fileValidation Utility', () => {
  it('harus meloloskan file gambar yang valid (JPG < 5MB)', () => {
    const validFile = {
      name: 'defibrillator.jpg',
      type: 'image/jpeg',
      size: 2 * 1024 * 1024 // 2MB
    };

    const result = validateFile(validFile, 'image');
    expect(result.valid).toBe(true);
    expect(result.error).toBeNull();
  });

  it('harus menolak file gambar yang melebihi batas 5MB', () => {
    const hugeFile = {
      name: 'foto_besar.png',
      type: 'image/png',
      size: 7 * 1024 * 1024 // 7MB
    };

    const result = validateFile(hugeFile, 'image');
    expect(result.valid).toBe(false);
    expect(result.error).toContain('terlalu besar');
    expect(result.error).toContain('Maksimal 5MB');
  });

  it('harus menolak file berbahaya (.php, .exe, .sh)', () => {
    const maliciousFiles = [
      { name: 'shell.php', type: 'application/x-php', size: 1024 },
      { name: 'malware.exe', type: 'application/x-msdownload', size: 1024 },
      { name: 'script.sh', type: 'application/x-sh', size: 1024 }
    ];

    maliciousFiles.forEach(file => {
      const res = validateFile(file, 'image');
      expect(res.valid).toBe(false);
      expect(res.error).toBeDefined();
    });
  });

  it('harus menolak file kosong (0 bytes)', () => {
    const emptyFile = {
      name: 'blank.jpg',
      type: 'image/jpeg',
      size: 0
    };

    const result = validateFile(emptyFile, 'image');
    expect(result.valid).toBe(false);
    expect(result.error).toContain('File kosong');
  });

  it('harus memvalidasi sertifikat kalibrasi PDF hingga 10MB', () => {
    const pdfCert = {
      name: 'sertifikat_kalibrasi_2026.pdf',
      type: 'application/pdf',
      size: 8 * 1024 * 1024 // 8MB
    };

    const result = validateFile(pdfCert, 'certificate');
    expect(result.valid).toBe(true);
    expect(result.error).toBeNull();
  });

  it('harus mengembalikan string accept dan maxSizeMB yang sesuai preset', () => {
    expect(getMaxSizeMB('image')).toBe(5);
    expect(getMaxSizeMB('certificate')).toBe(10);
    expect(getMaxSizeMB('logo')).toBe(3);

    expect(getAcceptString('certificate')).toBe('application/pdf,image/jpeg,image/png');
  });
});
