import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

/**
 * Format date helper
 */
const getFormattedDate = () => {
  const d = new Date();
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  const hours = String(d.getHours()).padStart(2, '0');
  const minutes = String(d.getMinutes()).padStart(2, '0');
  return `${day}-${month}-${year} ${hours}:${minutes}`;
};

const getTimestampString = () => {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  const h = String(d.getHours()).padStart(2, '0');
  const min = String(d.getMinutes()).padStart(2, '0');
  return `${y}${m}${day}_${h}${min}`;
};

/**
 * Export Equipment List to Excel (.xlsx) with professional hospital header
 */
export const exportEquipmentToExcel = (items, hospitalInfo = {}) => {
  const hospitalName = hospitalInfo.name || 'RS ISLAM GONDANGLEGI';
  const hospitalSubtitle = hospitalInfo.subtitle || 'Instalasi Pemeliharaan Sarana Rumah Sakit (IPSRS)';
  const hospitalAddress = hospitalInfo.address || 'Jl. Hayam Wuruk No. 123, Gondanglegi, Malang';
  const hospitalPhone = hospitalInfo.phone || '(0341) 879222';
  const printTime = getFormattedDate();

  // Create worksheet data
  const data = [
    [hospitalName.toUpperCase()],
    [hospitalSubtitle],
    [`${hospitalAddress} • Telp: ${hospitalPhone}`],
    [''],
    ['LAPORAN REKAPITULASI INVENTARIS ALAT KESEHATAN (ALKES)'],
    [`Dicetak pada: ${printTime} WIB | Total Terdata: ${items.length} Unit`],
    [''],
    // Table Headers
    [
      'No',
      'Kode Aset',
      'Nama Alat Medis',
      'Merk',
      'Tipe / Model',
      'Nomor Seri (SN)',
      'Ruangan / Unit',
      'Kategori',
      'Jumlah',
      'Kondisi Operasional',
      'Tgl Kalibrasi',
      'Berlaku Sampai',
      'No. Sertifikat Kalibrasi'
    ]
  ];

  // Populate data rows
  items.forEach((item, index) => {
    let opStatus = item.operational_status || 'operasional';
    if (opStatus === 'operasional') opStatus = 'Operasional';
    else if (opStatus === 'rusak_ringan') opStatus = 'Rusak Ringan';
    else if (opStatus === 'rusak_berat') opStatus = 'Rusak Berat';
    else if (opStatus === 'afkir') opStatus = 'Afkir';

    data.push([
      index + 1,
      item.asset_code || '-',
      item.name || '-',
      item.brand || '-',
      item.model_type || '-',
      item.serial_number || '-',
      item.room_name || '-',
      item.category_name || '-',
      Number(item.quantity) || 1,
      opStatus,
      item.calibration_date || '-',
      item.valid_until || '-',
      item.certificate_number || '-'
    ]);
  });

  // Append signature rows at bottom
  const headName = hospitalInfo.head_name || hospitalInfo.headIpsrsName || '......................................................';
  const headNip = hospitalInfo.head_nip || hospitalInfo.headIpsrsNip || '............................................';
  const city = hospitalInfo.city || hospitalInfo.hospitalCity || 'Gondanglegi';

  data.push(['']);
  data.push(['']);
  data.push(['', '', '', '', '', '', '', '', '', `${city}, ` + printTime.split(' ')[0]]);
  data.push(['', '', '', '', '', '', '', '', '', 'Mengetahui,']);
  data.push(['', '', '', '', '', '', '', '', '', 'Kepala Instalasi Pemeliharaan Sarana (IPSRS)']);
  data.push(['']);
  data.push(['']);
  data.push(['', '', '', '', '', '', '', '', '', `( ${headName} )`]);
  data.push(['', '', '', '', '', '', '', '', '', `NIP/NIK: ${headNip}`]);

  const ws = XLSX.utils.aoa_to_sheet(data);

  // Set column widths
  ws['!cols'] = [
    { wch: 5 },   // No
    { wch: 16 },  // Kode Aset
    { wch: 32 },  // Nama Alat Medis
    { wch: 16 },  // Merk
    { wch: 18 },  // Model
    { wch: 18 },  // SN
    { wch: 28 },  // Ruangan
    { wch: 28 },  // Kategori
    { wch: 8 },   // Jumlah
    { wch: 18 },  // Kondisi
    { wch: 14 },  // Tgl Kalibrasi
    { wch: 14 },  // Berlaku Sampai
    { wch: 24 }   // No Sertifikat
  ];

  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Inventaris Alkes');

  const fileName = `Inventaris_Alkes_RSIG_${getTimestampString()}.xlsx`;
  XLSX.writeFile(wb, fileName);
};

/**
 * Export Equipment List to Professional PDF (Landscape A4)
 */
export const exportEquipmentToPDF = (items, hospitalInfo = {}) => {
  const hospitalName = hospitalInfo.name || 'RS ISLAM GONDANGLEGI';
  const hospitalSubtitle = hospitalInfo.subtitle || 'Instalasi Pemeliharaan Sarana Rumah Sakit (IPSRS)';
  const hospitalAddress = hospitalInfo.address || 'Jl. Hayam Wuruk No. 123, Gondanglegi, Malang';
  const hospitalPhone = hospitalInfo.phone || '(0341) 879222';
  const printTime = getFormattedDate();

  // Create A4 Landscape PDF
  const doc = new jsPDF({
    orientation: 'landscape',
    unit: 'mm',
    format: 'a4'
  });

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();

  // Draw Kop Surat Header
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(14);
  doc.setTextColor(15, 23, 42); // slate-900
  doc.text(hospitalName.toUpperCase(), pageWidth / 2, 14, { align: 'center' });

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(9.5);
  doc.setTextColor(5, 150, 105); // emerald-600
  doc.text(hospitalSubtitle.toUpperCase(), pageWidth / 2, 19, { align: 'center' });

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8);
  doc.setTextColor(100, 116, 139); // slate-500
  doc.text(`${hospitalAddress} • Telepon: ${hospitalPhone}`, pageWidth / 2, 23.5, { align: 'center' });

  // Double Divider Line (Standard formal letterhead)
  doc.setDrawColor(15, 23, 42); // slate-900
  doc.setLineWidth(0.7);
  doc.line(14, 26.5, pageWidth - 14, 26.5);
  doc.setLineWidth(0.2);
  doc.line(14, 27.5, pageWidth - 14, 27.5);

  // Report Title Box
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(11);
  doc.setTextColor(15, 23, 42);
  doc.text('LAPORAN INVENTARIS & KELAIKAN ALAT KESEHATAN', pageWidth / 2, 34, { align: 'center' });

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8);
  doc.setTextColor(71, 85, 105);
  doc.text(`Waktu Cetak: ${printTime} WIB | Total Aset: ${items.length} Unit Terdata`, pageWidth / 2, 38.5, { align: 'center' });

  // Table Body Rows
  const tableRows = items.map((item, index) => {
    let opStatus = item.operational_status || 'operasional';
    if (opStatus === 'operasional') opStatus = 'Operasional';
    else if (opStatus === 'rusak_ringan') opStatus = 'Rusak Ringan';
    else if (opStatus === 'rusak_berat') opStatus = 'Rusak Berat';
    else if (opStatus === 'afkir') opStatus = 'Afkir';

    return [
      index + 1,
      item.asset_code || '-',
      item.name || '-',
      `${item.brand || '-'} / ${item.model_type || '-'}`,
      item.serial_number || '-',
      item.room_name || '-',
      item.quantity || 1,
      opStatus,
      item.calibration_date || '-',
      item.valid_until || '-'
    ];
  });

  // AutoTable Generation
  autoTable(doc, {
    startY: 42,
    head: [[
      'No',
      'Kode Aset',
      'Nama Alat Medis',
      'Merk / Tipe',
      'No. Seri (SN)',
      'Ruangan / Unit',
      'Jml',
      'Kondisi',
      'Tgl Kalibrasi',
      'Berlaku Sampai'
    ]],
    body: tableRows,
    theme: 'grid',
    headStyles: {
      fillColor: [15, 23, 42], // Slate-900
      textColor: [255, 255, 255],
      fontSize: 8,
      fontStyle: 'bold',
      halign: 'center',
      valign: 'middle'
    },
    styles: {
      fontSize: 7.5,
      cellPadding: 1.8,
      valign: 'middle',
      textColor: [30, 41, 59],
      lineColor: [203, 213, 225],
      lineWidth: 0.1
    },
    alternateRowStyles: {
      fillColor: [248, 250, 252] // Slate-50
    },
    columnStyles: {
      0: { halign: 'center', cellWidth: 10 },
      1: { halign: 'center', fontStyle: 'bold', cellWidth: 26 },
      2: { cellWidth: 54, fontStyle: 'bold' },
      3: { cellWidth: 40 },
      4: { halign: 'center', cellWidth: 26 },
      5: { cellWidth: 42 },
      6: { halign: 'center', cellWidth: 12 },
      7: { halign: 'center', cellWidth: 22 },
      8: { halign: 'center', cellWidth: 20 },
      9: { halign: 'center', fontStyle: 'bold', cellWidth: 22 }
    },
    margin: { left: 14, right: 14, bottom: 25 },
    didDrawPage: (data) => {
      // Footer on every page
      const pageCount = doc.internal.getNumberOfPages();
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(7.5);
      doc.setTextColor(148, 163, 184); // slate-400

      doc.text(
        `SIMPELKES - Sistem Manajemen Pemeliharaan Fasilitas & Elektromedis RS | Halaman ${doc.internal.getCurrentPageInfo().pageNumber} dari ${pageCount}`,
        14,
        pageHeight - 8
      );
      doc.text(
        'Dokumen Resmi Terverifikasi Rumah Sakit',
        pageWidth - 14,
        pageHeight - 8,
        { align: 'right' }
      );
    }
  });

  // Signature Block on the last page
  const finalY = doc.lastAutoTable.finalY + 8;
  const headName = hospitalInfo.head_name || hospitalInfo.headIpsrsName || null;
  const headNip = hospitalInfo.head_nip || hospitalInfo.headIpsrsNip || null;
  const city = hospitalInfo.city || hospitalInfo.hospitalCity || 'Gondanglegi';

  if (finalY < pageHeight - 35) {
    drawSignatureBlock(doc, pageWidth, finalY, printTime.split(' ')[0], headName, headNip, city);
  } else {
    // Add page for signature if table reaches the bottom
    doc.addPage();
    drawSignatureBlock(doc, pageWidth, 25, printTime.split(' ')[0], headName, headNip, city);
  }

  const fileName = `Laporan_Inventaris_Alkes_RSIG_${getTimestampString()}.pdf`;
  doc.save(fileName);
};

/**
 * Export Executive Recap / Reports to Excel (.xlsx) with multiple sheets
 */
export const exportReportsToExcel = (recap, hospitalInfo = {}) => {
  const hospitalName = hospitalInfo.name || 'RS ISLAM GONDANGLEGI';
  const hospitalSubtitle = hospitalInfo.subtitle || 'Instalasi Pemeliharaan Sarana Rumah Sakit (IPSRS)';
  const hospitalAddress = hospitalInfo.address || 'Jl. Hayam Wuruk No. 123, Gondanglegi, Malang';
  const hospitalPhone = hospitalInfo.phone || '(0341) 879222';
  const printTime = getFormattedDate();

  const wb = XLSX.utils.book_new();

  // 1. Sheet Distribusi per Ruangan
  const roomData = [
    [hospitalName.toUpperCase()],
    [hospitalSubtitle],
    [`${hospitalAddress} • Telp: ${hospitalPhone}`],
    [''],
    ['REKAPITULASI INVENTARIS ALKES BERDASARKAN RUANGAN / UNIT'],
    [`Dicetak pada: ${printTime} WIB`],
    [''],
    ['Kode Ruangan', 'Nama Ruangan / Unit', 'Total Alkes', 'Operasional', 'Rusak Ringan', 'Rusak Berat', 'Tingkat Kesiapan (%)']
  ];

  (recap.by_room || []).forEach(r => {
    const readiness = r.total_alkes > 0 ? Math.round((r.operasional / r.total_alkes) * 100) : 0;
    roomData.push([
      r.room_code || '-',
      r.room_name || '-',
      Number(r.total_alkes) || 0,
      Number(r.operasional) || 0,
      Number(r.rusak_ringan) || 0,
      Number(r.rusak_berat) || 0,
      `${readiness}%`
    ]);
  });

  const wsRoom = XLSX.utils.aoa_to_sheet(roomData);
  wsRoom['!cols'] = [
    { wch: 16 },
    { wch: 35 },
    { wch: 14 },
    { wch: 14 },
    { wch: 14 },
    { wch: 14 },
    { wch: 22 }
  ];
  XLSX.utils.book_append_sheet(wb, wsRoom, 'Rekap Unit Ruangan');

  // 2. Sheet Klasifikasi Risiko
  const catData = [
    [hospitalName.toUpperCase()],
    [hospitalSubtitle],
    [''],
    ['DISTRIBUSI ALAT MEDIS BERDASARKAN TINGKAT RISIKO (PERMENKES 65/2016)'],
    [`Dicetak pada: ${printTime} WIB`],
    [''],
    ['Kategori Alat Medis', 'Klasifikasi Risiko', 'Jumlah Unit Terpasang']
  ];

  (recap.by_category || []).forEach(c => {
    catData.push([
      c.category_name || '-',
      (c.risk_level || '-').toUpperCase() + ' RISK',
      Number(c.total_alkes) || 0
    ]);
  });

  const wsCat = XLSX.utils.aoa_to_sheet(catData);
  wsCat['!cols'] = [
    { wch: 45 },
    { wch: 22 },
    { wch: 24 }
  ];
  XLSX.utils.book_append_sheet(wb, wsCat, 'Kategori Risiko Alkes');

  // 3. Sheet Tren Bulanan & Kepatuhan SPM
  if (recap.monthly_trends || recap.spm_compliance) {
    const trendData = [
      [hospitalName.toUpperCase()],
      [hospitalSubtitle],
      [''],
      ['TREN KERUSAKAN BULANAN & INDIKATOR KEPATUHAN SPM TEKNISI'],
      [`Dicetak pada: ${printTime} WIB`],
      [''],
      ['Bulan / Periode', 'Laporan Masuk', 'Selesai Diperbaiki', 'Tiket Darurat (Emergency)']
    ];

    (recap.monthly_trends || []).forEach(t => {
      trendData.push([
        t.month_label || t.period_ym,
        Number(t.total_reported) || 0,
        Number(t.total_closed) || 0,
        Number(t.emergency_count) || 0
      ]);
    });

    if (recap.spm_compliance) {
      trendData.push(['']);
      trendData.push(['RINGKASAN INDIKATOR SPM KECEPATAN RESPON']);
      trendData.push(['Respon Cepat (<= 15 Menit)', `${recap.spm_compliance.fast_under_15m || 0} Tiket`]);
      trendData.push(['Respon Standar (15 - 30 Menit)', `${recap.spm_compliance.standard_15_30m || 0} Tiket`]);
      trendData.push(['Respon Cukup (30 - 60 Menit)', `${recap.spm_compliance.moderate_30_60m || 0} Tiket`]);
      trendData.push(['Respon Terlambat (> 60 Menit)', `${recap.spm_compliance.late_over_60m || 0} Tiket`]);
      trendData.push(['Rata-rata Respon SPM', `${recap.spm_compliance.avg_response_minutes || 0} Menit`]);
    }

    const wsTrend = XLSX.utils.aoa_to_sheet(trendData);
    wsTrend['!cols'] = [
      { wch: 32 },
      { wch: 18 },
      { wch: 20 },
      { wch: 25 }
    ];
    XLSX.utils.book_append_sheet(wb, wsTrend, 'Tren & SPM Servis');
  }

  const fileName = `Rekap_Eksekutif_IPSRS_RSIG_${getTimestampString()}.xlsx`;
  XLSX.writeFile(wb, fileName);
};

const drawSignatureBlock = (doc, pageWidth, startY, dateStr, headName = null, headNip = null, city = 'Gondanglegi') => {
  const rightColX = pageWidth - 65;
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8);
  doc.setTextColor(51, 65, 85);

  doc.text(`${city}, ${dateStr}`, rightColX, startY);
  doc.text('Mengetahui,', rightColX, startY + 4.5);
  doc.setFont('helvetica', 'bold');
  doc.text('Kepala Instalasi Pemeliharaan Sarana (IPSRS)', rightColX, startY + 9);

  // Line for sign
  doc.setFont('helvetica', 'normal');
  const nameToPrint = headName ? `( ${headName} )` : '( .............................................................. )';
  const nipToPrint = headNip ? `NIP / NIK: ${headNip}` : 'NIP / NIK: ...............................................';
  doc.text(nameToPrint, rightColX, startY + 28);
  doc.text(nipToPrint, rightColX, startY + 32.5);
};

/**
 * Export Preventive Maintenance Work Order (Lembar Kerja IPSRS Standar Kemenkes / MFK 8) to PDF
 */
export const exportPreventiveWorkOrderToPDF = (schedule, hospitalInfo = {}) => {
  const hospitalName = hospitalInfo.name || 'RS ISLAM GONDANGLEGI';
  const hospitalSubtitle = hospitalInfo.subtitle || 'Instalasi Pemeliharaan Sarana Rumah Sakit (IPSRS)';
  const hospitalAddress = hospitalInfo.address || 'Jl. Hayam Wuruk No. 123, Gondanglegi, Malang';
  const hospitalPhone = hospitalInfo.phone || '(0341) 879222';
  const headName = hospitalInfo.headName || 'Faiz Kurniawan, S.Tr.Kes';
  const headNip = hospitalInfo.headNip || '19890412 201402 1 003';
  const city = hospitalInfo.city || 'Gondanglegi';
  const printTime = getFormattedDate();

  const doc = new jsPDF({
    orientation: 'portrait',
    unit: 'mm',
    format: 'a4'
  });

  const pageWidth = doc.internal.pageSize.getWidth();
  const pageHeight = doc.internal.pageSize.getHeight();

  // Header Kop Surat
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(13);
  doc.setTextColor(15, 23, 42); // slate-900
  doc.text(hospitalName.toUpperCase(), pageWidth / 2, 13, { align: 'center' });

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(9);
  doc.setTextColor(5, 150, 105); // emerald-600
  doc.text(hospitalSubtitle.toUpperCase(), pageWidth / 2, 17.5, { align: 'center' });

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(7.5);
  doc.setTextColor(100, 116, 139); // slate-500
  doc.text(`${hospitalAddress} • Telp: ${hospitalPhone}`, pageWidth / 2, 21.5, { align: 'center' });

  // Double divider lines
  doc.setDrawColor(5, 150, 105);
  doc.setLineWidth(0.7);
  doc.line(14, 24, pageWidth - 14, 24);
  doc.setDrawColor(203, 213, 225);
  doc.setLineWidth(0.2);
  doc.line(14, 25, pageWidth - 14, 25);

  // Form Title
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(10.5);
  doc.setTextColor(15, 23, 42);
  doc.text('LEMBAR KERJA PEMELIHARAAN PREVENTIF & PEMANTAUAN FUNGSI ALKES', pageWidth / 2, 30.5, { align: 'center' });

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(7);
  doc.setTextColor(71, 85, 105);
  doc.text('Standar Kemenkes RI / MFK 8 - Manajemen Fasilitas & Keselamatan Rumah Sakit', pageWidth / 2, 34.5, { align: 'center' });

  // Table Identitas Alat
  const spNumber = schedule.sp_number || `LK-PM-${String(schedule.id).padStart(5, '0')}`;
  const brandModel = `${schedule.equipment_brand || schedule.brand || '-'} / ${schedule.equipment_model || schedule.model_type || '-'}`;
  const conditionLabel = (schedule.final_condition || 'laik_pakai').replace('_', ' ').toUpperCase();

  autoTable(doc, {
    startY: 37,
    theme: 'plain',
    styles: { fontSize: 7, cellPadding: 1, textColor: [30, 41, 59] },
    columnStyles: {
      0: { fontStyle: 'bold', cellWidth: 34, textColor: [71, 85, 105] },
      1: { cellWidth: 58 },
      2: { fontStyle: 'bold', cellWidth: 34, textColor: [71, 85, 105] },
      3: { cellWidth: 56 }
    },
    body: [
      [
        'No. SP / Lembar Kerja', `: ${spNumber}`,
        'Tanggal Pelaksanaan', `: ${schedule.execution_start_at ? schedule.execution_start_at.substring(0, 16) : (schedule.scheduled_date || '-')} s/d ${schedule.execution_end_at ? schedule.execution_end_at.substring(11, 16) : '-'}`
      ],
      [
        'Nama Alat Medis', `: ${schedule.equipment_name || '-'}`,
        'Ruangan / Lokasi', `: ${schedule.room_name || '-'}`
      ],
      [
        'Kode Aset / Inventaris', `: ${schedule.asset_code || '-'}`,
        'Pelaksana Pemeliharaan', `: ${schedule.executor_type === 'external' ? 'Vendor Eksternal' : 'Teknisi Internal IPSRS'}`
      ],
      [
        'Merk / Model / Tipe', `: ${brandModel}`,
        'Siklus Frekuensi', `: ${schedule.frequency || '3 Bulanan'}`
      ],
      [
        'Nomor Seri (SN)', `: ${schedule.serial_number || '-'}`,
        'Kondisi Akhir Alat', `: ${conditionLabel}`
      ]
    ],
    margin: { left: 14, right: 14 }
  });

  let currentY = doc.lastAutoTable.finalY + 2.5;

  // 1. Pemantauan Fungsi (8 items)
  const inspectionMap = {
    casing: 'Casing / Kotak / Rangka Alat',
    battery: 'Baterai / Catu Daya Cadangan',
    mounting: 'Roda / Kaki / Braket Pemasangan',
    power_cord: 'Kabel Power / Steker / Kabel Grounding',
    filter: 'Filter Udara / Saringan Debu',
    probe_connector: 'Konektor, Probe, Leadwire / Sensor',
    alarm: 'Sistem Alarm & Indikator Peringatan Visual',
    controls: 'Tombol, Saklar & Display Kontrol'
  };

  const inspChecklist = typeof schedule.inspection_checklist === 'object' && schedule.inspection_checklist !== null
    ? schedule.inspection_checklist
    : {};

  const inspectionRows = Object.keys(inspectionMap).map((k, idx) => {
    const val = inspChecklist[k] || 'N/A';
    return [
      idx + 1,
      inspectionMap[k],
      val.toUpperCase()
    ];
  });

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8);
  doc.setTextColor(15, 23, 42);
  doc.text('I. PEMANTAUAN FUNGSI & FISIK OPERASIONAL ALAT', 14, currentY);

  autoTable(doc, {
    startY: currentY + 1,
    head: [['No', 'Komponen / Parameter Uji Fungsi Fisik', 'Kondisi']],
    body: inspectionRows,
    theme: 'grid',
    headStyles: { fillColor: [15, 23, 42], textColor: [255, 255, 255], fontSize: 6.5, fontStyle: 'bold', halign: 'center' },
    styles: { fontSize: 6.5, cellPadding: 0.9, valign: 'middle', lineColor: [203, 213, 225], lineWidth: 0.1 },
    alternateRowStyles: { fillColor: [248, 250, 252] },
    columnStyles: {
      0: { halign: 'center', cellWidth: 8 },
      1: { cellWidth: 140 },
      2: { halign: 'center', cellWidth: 34, fontStyle: 'bold' }
    },
    margin: { left: 14, right: 14 }
  });

  currentY = doc.lastAutoTable.finalY + 2.5;

  // 2. Tindakan Pemeliharaan Preventif (4 items)
  const actionMap = {
    cleaning: 'Pembersihan Fisik, Sasis & Debu Internal',
    lubricating: 'Pelumasan Bagian Bergerak / Mekanis',
    tightening: 'Pengencangan Baut, Mur & Soket Longgar',
    replacement: 'Penggantian Komponen Aus / Filter Baru'
  };
  const maintActions = typeof schedule.maintenance_actions === 'object' && schedule.maintenance_actions !== null
    ? schedule.maintenance_actions
    : {};

  const actionRows = Object.keys(actionMap).map((k, idx) => {
    const val = maintActions[k] || 'N/A';
    return [
      idx + 1,
      actionMap[k],
      val.toUpperCase()
    ];
  });

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8);
  doc.setTextColor(15, 23, 42);
  doc.text('II. TINDAKAN PEMELIHARAAN PREVENTIF', 14, currentY);

  autoTable(doc, {
    startY: currentY + 1,
    head: [['No', 'Kegiatan Pemeliharaan Preventif', 'Tindakan']],
    body: actionRows,
    theme: 'grid',
    headStyles: { fillColor: [5, 150, 105], textColor: [255, 255, 255], fontSize: 6.5, fontStyle: 'bold', halign: 'center' },
    styles: { fontSize: 6.5, cellPadding: 0.9, valign: 'middle', lineColor: [203, 213, 225], lineWidth: 0.1 },
    alternateRowStyles: { fillColor: [248, 250, 252] },
    columnStyles: {
      0: { halign: 'center', cellWidth: 8 },
      1: { cellWidth: 140 },
      2: { halign: 'center', cellWidth: 34, fontStyle: 'bold' }
    },
    margin: { left: 14, right: 14 }
  });

  currentY = doc.lastAutoTable.finalY + 2.5;

  // 3. Pengukuran Keselamatan Listrik
  const elecSafety = typeof schedule.electrical_safety === 'object' && schedule.electrical_safety !== null
    ? schedule.electrical_safety
    : {};

  const groundingVal = elecSafety.grounding_resistance !== undefined && elecSafety.grounding_resistance !== '' ? elecSafety.grounding_resistance : '-';
  const leakageVal = elecSafety.leakage_current !== undefined && elecSafety.leakage_current !== '' ? elecSafety.leakage_current : '-';

  const isGroundingOk = groundingVal !== '-' && parseFloat(groundingVal) <= 0.20;
  const isLeakageOk = leakageVal !== '-' && parseFloat(leakageVal) <= 100;

  const safetyRows = [
    ['1', 'Tahanan Pembumian / Grounding Wire', '<= 0.20 Ohm', groundingVal !== '-' ? `${groundingVal} Ohm` : '-', isGroundingOk ? 'MEMENUHI SYARAT' : (groundingVal !== '-' ? 'MELEBIHI AMBANG' : '-')],
    ['2', 'Arus Bocor Sasis / Chassis Leakage', '<= 100.0 uA', leakageVal !== '-' ? `${leakageVal} uA` : '-', isLeakageOk ? 'MEMENUHI SYARAT' : (leakageVal !== '-' ? 'MELEBIHI AMBANG' : '-')]
  ];

  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8);
  doc.setTextColor(15, 23, 42);
  doc.text('III. PENGUKURAN KESELAMATAN LISTRIK (ELECTRICAL SAFETY TEST)', 14, currentY);

  autoTable(doc, {
    startY: currentY + 1,
    head: [['No', 'Parameter Uji Kelistrikan', 'Nilai Acuan Standar', 'Hasil Terukur', 'Evaluasi']],
    body: safetyRows,
    theme: 'grid',
    headStyles: { fillColor: [30, 41, 59], textColor: [255, 255, 255], fontSize: 6.5, fontStyle: 'bold', halign: 'center' },
    styles: { fontSize: 6.5, cellPadding: 0.9, valign: 'middle', lineColor: [203, 213, 225], lineWidth: 0.1 },
    alternateRowStyles: { fillColor: [248, 250, 252] },
    columnStyles: {
      0: { halign: 'center', cellWidth: 8 },
      1: { cellWidth: 70 },
      2: { halign: 'center', cellWidth: 36 },
      3: { halign: 'center', fontStyle: 'bold', cellWidth: 34 },
      4: { halign: 'center', fontStyle: 'bold', cellWidth: 34 }
    },
    margin: { left: 14, right: 14 }
  });

  currentY = doc.lastAutoTable.finalY + 2.5;

  // IV. Catatan & Kesimpulan
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(8);
  doc.setTextColor(15, 23, 42);
  doc.text('IV. KESIMPULAN & CATATAN TEKNISI', 14, currentY);

  autoTable(doc, {
    startY: currentY + 1,
    theme: 'grid',
    styles: { fontSize: 7, cellPadding: 1.5, lineColor: [203, 213, 225], lineWidth: 0.1 },
    body: [
      [
        `Kondisi Akhir Alat: [ ${conditionLabel} ]\nCatatan / Tindak Lanjut: ${schedule.notes || 'Pemeliharaan preventif selesai sesuai SOP MFK 8. Alat medis berfungsi normal dan aman digunakan.'}`
      ]
    ],
    margin: { left: 14, right: 14 }
  });

  currentY = doc.lastAutoTable.finalY + 4;

  // Tanda Tangan
  const leftX = 20;
  const rightX = pageWidth - 75;

  doc.setFont('helvetica', 'normal');
  doc.setFontSize(7);
  doc.setTextColor(51, 65, 85);

  doc.text('Petugas / Teknisi Pelaksana,', leftX, currentY);
  doc.text(`${city}, ${schedule.completed_at ? schedule.completed_at.substring(0, 10) : getFormattedDate().substring(0, 10)}`, rightX, currentY);
  doc.text('Mengetahui / Penanggung Jawab,', rightX, currentY + 3.5);

  const techName = schedule.technician_name ? `( ${schedule.technician_name} )` : '( ................................................. )';
  const spvName = schedule.supervisor_name || headName;
  const spvNameToPrint = spvName ? `( ${spvName} )` : '( ................................................. )';
  const spvNipToPrint = (schedule.supervisor_name ? '' : (headNip ? `NIP/NIK: ${headNip}` : ''));

  doc.text(techName, leftX, currentY + 19);
  doc.text('Teknisi Elektromedis IPSRS', leftX, currentY + 23);

  doc.text(spvNameToPrint, rightX, currentY + 19);
  doc.text(spvNipToPrint || 'Kepala Ruangan / Penanggung Jawab', rightX, currentY + 23);

  // Footer info
  doc.setFontSize(6.5);
  doc.setTextColor(148, 163, 184);
  doc.text(`Dicetak dari SIMPELKES-RSIG pada ${printTime} WIB • Formulir Pemeliharaan Elektromedik Standar MFK 8`, pageWidth / 2, pageHeight - 5, { align: 'center' });

  const fileName = `LK_IPSRS_${schedule.asset_code || schedule.id}_${getTimestampString()}.pdf`;
  doc.save(fileName);
};

