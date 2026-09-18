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
  data.push(['']);
  data.push(['']);
  data.push(['', '', '', '', '', '', '', '', '', 'Gondanglegi, ' + printTime.split(' ')[0]]);
  data.push(['', '', '', '', '', '', '', '', '', 'Mengetahui,']);
  data.push(['', '', '', '', '', '', '', '', '', 'Kepala Instalasi Pemeliharaan Sarana (IPSRS)']);
  data.push(['']);
  data.push(['']);
  data.push(['', '', '', '', '', '', '', '', '', '( ...................................................... )']);
  data.push(['', '', '', '', '', '', '', '', '', 'NIP/NIK: ............................................']);

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
  if (finalY < pageHeight - 35) {
    drawSignatureBlock(doc, pageWidth, finalY, printTime.split(' ')[0]);
  } else {
    // Add page for signature if table reaches the bottom
    doc.addPage();
    drawSignatureBlock(doc, pageWidth, 25, printTime.split(' ')[0]);
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

  const fileName = `Rekap_Eksekutif_IPSRS_RSIG_${getTimestampString()}.xlsx`;
  XLSX.writeFile(wb, fileName);
};

const drawSignatureBlock = (doc, pageWidth, startY, dateStr) => {
  const rightColX = pageWidth - 65;
  doc.setFont('helvetica', 'normal');
  doc.setFontSize(8);
  doc.setTextColor(51, 65, 85);

  doc.text(`Gondanglegi, ${dateStr}`, rightColX, startY);
  doc.text('Mengetahui,', rightColX, startY + 4.5);
  doc.setFont('helvetica', 'bold');
  doc.text('Kepala Instalasi Pemeliharaan Sarana (IPSRS)', rightColX, startY + 9);

  // Line for sign
  doc.setFont('helvetica', 'normal');
  doc.text('( .............................................................. )', rightColX, startY + 28);
  doc.text('NIP / NIK: ...............................................', rightColX, startY + 32.5);
};

