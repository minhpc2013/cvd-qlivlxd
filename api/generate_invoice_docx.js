/**
 * generate_invoice_docx.js
 * Nhận JSON từ stdin (arg[2]) và xuất file .docx ra đường dẫn (arg[3])
 * Usage: node generate_invoice_docx.js '<json>' '/tmp/output.docx'
 */

const {
    Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
    AlignmentType, BorderStyle, WidthType, ShadingType, VerticalAlign,
    HeadingLevel
} = require('docx');
const fs = require('fs');

// ── Đọc tham số ──────────────────────────────────────────────
// argv[2] = đường dẫn file JSON tạm, argv[3] = đường dẫn output .docx
const jsonPath   = process.argv[2];
const outputPath = process.argv[3];
const payload    = JSON.parse(fs.readFileSync(jsonPath, 'utf8'));
const { order, settings } = payload;

// ── Helpers ───────────────────────────────────────────────────
function vnd(amount) {
    return Number(amount || 0).toLocaleString('vi-VN') + ' đ';
}

function formatDate(str) {
    if (!str) return '—';
    const d = new Date(str);
    return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

// ── Màu sắc & style chung ─────────────────────────────────────
const ORANGE    = 'E85D04';
const ORANGE_BG = 'FFF0E6';
const GRAY_BG   = 'F3F4F6';
const BORDER_COLOR = 'E5E7EB';

const border = { style: BorderStyle.SINGLE, size: 1, color: BORDER_COLOR };
const noBorder = { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' };
const borders = { top: border, bottom: border, left: border, right: border };
const noBorders = { top: noBorder, bottom: noBorder, left: noBorder, right: noBorder };

function cell(children, opts = {}) {
    return new TableCell({
        borders: opts.borders ?? borders,
        width: opts.width ? { size: opts.width, type: WidthType.DXA } : undefined,
        shading: opts.bg ? { fill: opts.bg, type: ShadingType.CLEAR } : undefined,
        verticalAlign: VerticalAlign.CENTER,
        margins: { top: 100, bottom: 100, left: 150, right: 150 },
        columnSpan: opts.span,
        children,
    });
}

function txt(text, opts = {}) {
    return new TextRun({
        text: String(text ?? ''),
        font: 'Arial',
        size: opts.size ?? 22,
        bold: opts.bold ?? false,
        color: opts.color ?? '374151',
        italics: opts.italic ?? false,
    });
}

function para(runs, opts = {}) {
    return new Paragraph({
        alignment: opts.align ?? AlignmentType.LEFT,
        spacing: { before: opts.spaceBefore ?? 0, after: opts.spaceAfter ?? 60 },
        children: Array.isArray(runs) ? runs : [runs],
    });
}

// ── Tính toán ─────────────────────────────────────────────────
const items   = order.items || [];
const total   = Number(order.total || 0);
const paid    = Number(order.paid  || 0);
const debt    = Math.max(0, total - paid);
const change  = Math.max(0, paid - total);

// ── HEADER: Thông tin cửa hàng ────────────────────────────────
const headerSection = [
    // Tên cửa hàng
    new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 0, after: 80 },
        children: [txt(settings.store_name, { size: 36, bold: true, color: ORANGE })],
    }),
];

// Thông tin phụ (địa chỉ, SĐT, email)
const infoLines = [
    settings.store_address,
    settings.store_phone ? 'ĐT: ' + settings.store_phone : '',
    settings.store_email ? 'Email: ' + settings.store_email : '',
    settings.tax_code    ? 'MST: ' + settings.tax_code     : '',
].filter(Boolean);

infoLines.forEach(line => {
    headerSection.push(new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 0, after: 40 },
        children: [txt(line, { size: 20, color: '6B7280' })],
    }));
});

// Đường kẻ cam
headerSection.push(new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { before: 120, after: 120 },
    border: { bottom: { style: BorderStyle.SINGLE, size: 8, color: ORANGE, space: 1 } },
    children: [],
}));

// Tiêu đề HÓA ĐƠN BÁN HÀNG
headerSection.push(new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { before: 160, after: 80 },
    children: [txt('HÓA ĐƠN BÁN HÀNG', { size: 32, bold: true, color: '111827' })],
}));

headerSection.push(new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { before: 0, after: 200 },
    children: [txt('Mã đơn: ' + order.id, { size: 22, color: '6B7280' })],
}));

// ── THÔNG TIN KHÁCH HÀNG & ĐƠN HÀNG ─────────────────────────
const INFO_W = [2200, 4000, 1800, 1900]; // tổng 9900

const infoTable = new Table({
    width: { size: 9900, type: WidthType.DXA },
    columnWidths: INFO_W,
    borders: noBorders,
    rows: [
        new TableRow({ children: [
            cell([para([txt('Khách hàng:', { bold: true })])], { borders: noBorders, width: INFO_W[0] }),
            cell([para([txt(order.customer_name || 'Khách lẻ')])], { borders: noBorders, width: INFO_W[1] }),
            cell([para([txt('Ngày:', { bold: true })])], { borders: noBorders, width: INFO_W[2] }),
            cell([para([txt(formatDate(order.created_at))])], { borders: noBorders, width: INFO_W[3] }),
        ]}),
        new TableRow({ children: [
            cell([para([txt('Số điện thoại:', { bold: true })])], { borders: noBorders, width: INFO_W[0] }),
            cell([para([txt(order.phone || '—')])], { borders: noBorders, width: INFO_W[1] }),
            cell([para([txt('Trạng thái:', { bold: true })])], { borders: noBorders, width: INFO_W[2] }),
            cell([para([txt(
                order.status === 'delivered' ? 'Đã giao' :
                order.status === 'confirmed' ? 'Đã xác nhận' :
                order.status === 'cancelled' ? 'Đã huỷ' : 'Chờ xử lý'
            )])], { borders: noBorders, width: INFO_W[3] }),
        ]}),
    ],
});

// ── BẢNG SẢN PHẨM ─────────────────────────────────────────────
// columnWidths: STT, Tên SP, ĐVT, SL, Đơn giá, Thành tiền => tổng 9900
const COL_W = [500, 3200, 800, 700, 1500, 1200];

const headerRow = new TableRow({
    tableHeader: true,
    children: [
        cell([para([txt('STT',       { bold: true, color: 'FFFFFF' })], { align: AlignmentType.CENTER })], { bg: ORANGE, width: COL_W[0] }),
        cell([para([txt('Tên sản phẩm', { bold: true, color: 'FFFFFF' })])], { bg: ORANGE, width: COL_W[1] }),
        cell([para([txt('ĐVT',       { bold: true, color: 'FFFFFF' })], { align: AlignmentType.CENTER })], { bg: ORANGE, width: COL_W[2] }),
        cell([para([txt('SL',        { bold: true, color: 'FFFFFF' })], { align: AlignmentType.CENTER })], { bg: ORANGE, width: COL_W[3] }),
        cell([para([txt('Đơn giá',   { bold: true, color: 'FFFFFF' })], { align: AlignmentType.RIGHT  })], { bg: ORANGE, width: COL_W[4] }),
        cell([para([txt('Thành tiền',{ bold: true, color: 'FFFFFF' })], { align: AlignmentType.RIGHT  })], { bg: ORANGE, width: COL_W[5] }),
    ],
});

const itemRows = items.map((item, idx) => {
    const qty       = Number(item.qty || item.quantity || 0);
    const price     = Number(item.price || 0);
    const lineTotal = qty * price;
    const rowBg     = idx % 2 === 1 ? GRAY_BG : 'FFFFFF';
    // Lấy tên sản phẩm — tương thích cả key 'name' lẫn 'product_name'
    const name = item.name || item.product_name || '—';
    // Lấy đơn vị
    const unit = item.unit || '';

    return new TableRow({ children: [
        cell([para([txt(String(idx + 1))], { align: AlignmentType.CENTER })], { bg: rowBg, width: COL_W[0] }),
        cell([para([txt(name)])], { bg: rowBg, width: COL_W[1] }),
        cell([para([txt(unit)],  { align: AlignmentType.CENTER })], { bg: rowBg, width: COL_W[2] }),
        cell([para([txt(String(qty))], { align: AlignmentType.CENTER })], { bg: rowBg, width: COL_W[3] }),
        cell([para([txt(vnd(price))],      { align: AlignmentType.RIGHT })], { bg: rowBg, width: COL_W[4] }),
        cell([para([txt(vnd(lineTotal), { bold: true, color: ORANGE })], { align: AlignmentType.RIGHT })], { bg: rowBg, width: COL_W[5] }),
    ]});
});

const productTable = new Table({
    width: { size: 9900, type: WidthType.DXA },
    columnWidths: COL_W,
    rows: [headerRow, ...itemRows],
});

// ── BẢNG TỔNG TIỀN ────────────────────────────────────────────
const SUMMARY_W = [7200, 2700]; // tổng 9900

function summaryRow(label, value, highlight = false) {
    return new TableRow({ children: [
        cell([para([txt(label, { bold: highlight })])], { borders: noBorders, bg: highlight ? ORANGE_BG : 'FFFFFF', width: SUMMARY_W[0] }),
        cell([para([txt(value, { bold: highlight, color: highlight ? ORANGE : '374151' })], { align: AlignmentType.RIGHT })], { borders: noBorders, bg: highlight ? ORANGE_BG : 'FFFFFF', width: SUMMARY_W[1] }),
    ]});
}

const summaryTable = new Table({
    width: { size: 9900, type: WidthType.DXA },
    columnWidths: SUMMARY_W,
    rows: [
        summaryRow('Tổng cộng:', vnd(total), true),
        summaryRow('Tiền khách trả:', vnd(paid)),
        ...(change > 0 ? [summaryRow('Tiền thừa trả lại:', vnd(change))] : []),
        ...(debt   > 0 ? [summaryRow('Còn nợ:', vnd(debt))] : []),
    ],
});

// ── GHI CHÚ & CHỮ KÝ ─────────────────────────────────────────
const noteSection = [];

if (order.note) {
    noteSection.push(
        para([txt('Ghi chú: ', { bold: true }), txt(order.note)], { spaceBefore: 200 })
    );
}

// Đường kẻ
noteSection.push(new Paragraph({
    spacing: { before: 240, after: 240 },
    border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: BORDER_COLOR, space: 1 } },
    children: [],
}));

// Chữ ký — dùng bảng 2 cột
const SIG_W = [4950, 4950];
const sigTable = new Table({
    width: { size: 9900, type: WidthType.DXA },
    columnWidths: SIG_W,
    borders: noBorders,
    rows: [
        new TableRow({ children: [
            cell([para([txt('Khách hàng', { bold: true })],            { align: AlignmentType.CENTER })], { borders: noBorders, width: SIG_W[0] }),
            cell([para([txt('Người bán', { bold: true })],             { align: AlignmentType.CENTER })], { borders: noBorders, width: SIG_W[1] }),
        ]}),
        new TableRow({ children: [
            cell([para([txt('(Ký, ghi rõ họ tên)', { size: 18, color: '9CA3AF', italic: true })], { align: AlignmentType.CENTER })], { borders: noBorders, width: SIG_W[0] }),
            cell([para([txt('(Ký, ghi rõ họ tên)', { size: 18, color: '9CA3AF', italic: true })], { align: AlignmentType.CENTER })], { borders: noBorders, width: SIG_W[1] }),
        ]}),
        // Khoảng trống ký tên
        new TableRow({ children: [
            cell([para([txt('')], {}), para([txt('')], {}), para([txt('')], {})], { borders: noBorders, width: SIG_W[0] }),
            cell([para([txt('')], {}), para([txt('')], {}), para([txt('')], {})], { borders: noBorders, width: SIG_W[1] }),
        ]}),
    ],
});

// Footer
const footerPara = new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { before: 200, after: 0 },
    children: [txt(settings.invoice_footer || 'Cảm ơn quý khách đã mua hàng!', { size: 20, italic: true, color: '9CA3AF' })],
});

// ── GHÉP TÀI LIỆU ─────────────────────────────────────────────
const doc = new Document({
    styles: {
        default: {
            document: { run: { font: 'Arial', size: 22 } },
        },
    },
    sections: [{
        properties: {
            page: {
                size: { width: 11906, height: 16838 }, // A4
                margin: { top: 1000, right: 1000, bottom: 1000, left: 1000 },
            },
        },
        children: [
            ...headerSection,
            new Paragraph({ spacing: { before: 0, after: 160 }, children: [] }),
            infoTable,
            new Paragraph({ spacing: { before: 160, after: 160 }, children: [] }),
            productTable,
            new Paragraph({ spacing: { before: 160, after: 0 }, children: [] }),
            summaryTable,
            ...noteSection,
            sigTable,
            footerPara,
        ],
    }],
});

Packer.toBuffer(doc).then(buffer => {
    fs.writeFileSync(outputPath, buffer);
    process.exit(0);
}).catch(err => {
    console.error(err.message);
    process.exit(1);
});