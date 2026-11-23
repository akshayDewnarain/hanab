export type DelimiterChoice = 'auto' | ',' | ';' | '\t' | '|';

export type TabularFormat = 'csv' | 'txt' | 'xlsx';

export type TabularColumn = {
    id: string;
    label: string;
    samples: string[];
};

export type TabularSheet = {
    columns: TabularColumn[];
    rows: Record<string, string>[];
};

const DELIMITERS = [',', ';', '\t', '|'] as const;

export function parseDelimited(text: string, delimiter: string): string[][] {
    const rows: string[][] = [];
    let row: string[] = [];
    let cell = '';
    let quoted = false;

    const source = text.replace(/^\uFEFF/, '').replace(/\r\n/g, '\n').replace(/\r/g, '\n');

    for (let index = 0; index < source.length; index += 1) {
        const char = source[index];
        const next = source[index + 1];

        if (quoted) {
            if (char === '"' && next === '"') {
                cell += '"';
                index += 1;
            } else if (char === '"') {
                quoted = false;
            } else {
                cell += char;
            }
            continue;
        }

        if (char === '"') {
            quoted = true;
            continue;
        }
        if (char === delimiter) {
            row.push(cell);
            cell = '';
            continue;
        }
        if (char === '\n') {
            row.push(cell);
            rows.push(row);
            row = [];
            cell = '';
            continue;
        }
        cell += char;
    }

    if (cell !== '' || row.length > 0) {
        row.push(cell);
        rows.push(row);
    }

    return rows.filter((item) => item.some((value) => value.trim() !== ''));
}

export function detectDelimiter(text: string): string {
    const sample = text.replace(/^\uFEFF/, '').split(/\r?\n/).slice(0, 8).join('\n');
    let best: (typeof DELIMITERS)[number] = ',';
    let bestCount = -1;

    for (const delimiter of DELIMITERS) {
        const rows = parseDelimited(sample, delimiter);
        const width = rows[0]?.length ?? 0;
        if (rows.length === 0 || width < 2) {
            continue;
        }
        const consistent = rows.every((row) => row.length === width);
        const count = consistent ? width * rows.length : width;
        if (count > bestCount) {
            best = delimiter;
            bestCount = count;
        }
    }

    return best;
}

export async function readTabularFile(file: File, choice: DelimiterChoice): Promise<TabularSheet> {
    if (file.name.toLowerCase().endsWith('.xlsx')) {
        return sheetFromMatrix(await readXlsx(new Uint8Array(await file.arrayBuffer())));
    }

    const text = await file.text();
    const delimiter = choice === 'auto' ? detectDelimiter(text) : choice;
    return sheetFromMatrix(parseDelimited(text, delimiter));
}

export function sheetFromMatrix(matrix: string[][]): TabularSheet {
    const [header = [], ...body] = matrix;
    const used = new Map<string, number>();
    const columns: TabularColumn[] = header.map((label, index) => {
        const base = label.trim() || `Column ${index + 1}`;
        const seen = used.get(base) ?? 0;
        used.set(base, seen + 1);
        const id = seen === 0 ? base : `${base} ${seen + 1}`;
        return { id, label: base, samples: [] };
    });

    const rows = body
        .filter((row) => row.some((value) => value.trim() !== ''))
        .map((row) => {
            const record: Record<string, string> = {};
            columns.forEach((column, index) => {
                record[column.id] = (row[index] ?? '').trim();
            });
            return record;
        });

    for (const column of columns) {
        column.samples = rows
            .map((row) => row[column.id] ?? '')
            .filter((value) => value !== '')
            .slice(0, 3);
    }

    return { columns, rows };
}

export function encodeTabular(headers: string[], rows: string[][], format: TabularFormat, delimiter: string): Uint8Array {
    if (format === 'xlsx') {
        return encodeXlsx(headers, rows);
    }
    const chosen = format === 'txt' && delimiter === 'auto' ? ';' : delimiter === 'auto' ? ',' : delimiter;
    const lines = [headers, ...rows].map((row) => row.map((value) => quoteCell(value, chosen)).join(chosen));
    return new TextEncoder().encode(`\uFEFF${lines.join('\n')}`);
}

function quoteCell(value: string, delimiter: string): string {
    if (/["\n\r]/.test(value) || value.includes(delimiter)) {
        return `"${value.replace(/"/g, '""')}"`;
    }
    return value;
}

const encoder = new TextEncoder();
const decoder = new TextDecoder();

function concat(parts: Uint8Array[]): Uint8Array {
    const length = parts.reduce((sum, part) => sum + part.length, 0);
    const out = new Uint8Array(length);
    let offset = 0;
    for (const part of parts) {
        out.set(part, offset);
        offset += part.length;
    }
    return out;
}

function u16(value: number): Uint8Array {
    return Uint8Array.of(value & 0xff, (value >> 8) & 0xff);
}

function u32(value: number): Uint8Array {
    return Uint8Array.of(value & 0xff, (value >> 8) & 0xff, (value >> 16) & 0xff, (value >> 24) & 0xff);
}

function crc32(bytes: Uint8Array): number {
    let crc = ~0;
    for (let index = 0; index < bytes.length; index += 1) {
        crc ^= bytes[index];
        for (let bit = 0; bit < 8; bit += 1) {
            crc = (crc >>> 1) ^ (crc & 1 ? 0xedb88320 : 0);
        }
    }
    return ~crc >>> 0;
}

function encodeXlsx(headers: string[], rows: string[][]): Uint8Array {
    const strings: string[] = [];
    const indexOf = new Map<string, number>();
    const cell = (value: string): number => {
        const existing = indexOf.get(value);
        if (existing !== undefined) {
            return existing;
        }
        const index = strings.length;
        strings.push(value);
        indexOf.set(value, index);
        return index;
    };

    const matrix = [headers, ...rows];
    const sheetRows = matrix
        .map((row, rowIndex) => {
            const cells = row
                .map((value, columnIndex) => {
                    const ref = `${columnName(columnIndex)}${rowIndex + 1}`;
                    return `<c r="${ref}" t="s"><v>${cell(value)}</v></c>`;
                })
                .join('');
            return `<row r="${rowIndex + 1}">${cells}</row>`;
        })
        .join('');

    const shared = strings.map((value) => `<si><t xml:space="preserve">${escapeXml(value)}</t></si>`).join('');
    const files: Record<string, Uint8Array> = {
        '[Content_Types].xml': encoder.encode(
            '<?xml version="1.0" encoding="UTF-8"?>' +
                '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' +
                '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' +
                '<Default Extension="xml" ContentType="application/xml"/>' +
                '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' +
                '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' +
                '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>' +
                '</Types>',
        ),
        '_rels/.rels': encoder.encode(
            '<?xml version="1.0" encoding="UTF-8"?>' +
                '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' +
                '</Relationships>',
        ),
        'xl/workbook.xml': encoder.encode(
            '<?xml version="1.0" encoding="UTF-8"?>' +
                '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
                '<sheets><sheet name="Import" sheetId="1" r:id="rId1"/></sheets></workbook>',
        ),
        'xl/_rels/workbook.xml.rels': encoder.encode(
            '<?xml version="1.0" encoding="UTF-8"?>' +
                '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' +
                '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>' +
                '</Relationships>',
        ),
        'xl/worksheets/sheet1.xml': encoder.encode(
            '<?xml version="1.0" encoding="UTF-8"?>' +
                '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' +
                sheetRows +
                '</sheetData></worksheet>',
        ),
        'xl/sharedStrings.xml': encoder.encode(
            `<?xml version="1.0" encoding="UTF-8"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="${strings.length}" uniqueCount="${strings.length}">${shared}</sst>`,
        ),
    };

    return zipStore(files);
}

function columnName(index: number): string {
    let name = '';
    let current = index + 1;
    while (current > 0) {
        const remainder = (current - 1) % 26;
        name = String.fromCharCode(65 + remainder) + name;
        current = Math.floor((current - 1) / 26);
    }
    return name;
}

function escapeXml(value: string): string {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function zipStore(files: Record<string, Uint8Array>): Uint8Array {
    const now = new Date();
    const time = (now.getHours() << 11) | (now.getMinutes() << 5) | Math.floor(now.getSeconds() / 2);
    const date = ((now.getFullYear() - 1980) << 9) | ((now.getMonth() + 1) << 5) | now.getDate();
    const locals: Uint8Array[] = [];
    const centrals: Uint8Array[] = [];
    let offset = 0;

    for (const [name, data] of Object.entries(files)) {
        const nameBytes = encoder.encode(name);
        const crc = crc32(data);
        const local = concat([
            u32(0x04034b50),
            u16(20),
            u16(0),
            u16(0),
            u16(time),
            u16(date),
            u32(crc),
            u32(data.length),
            u32(data.length),
            u16(nameBytes.length),
            u16(0),
            nameBytes,
            data,
        ]);
        const central = concat([
            u32(0x02014b50),
            u16(20),
            u16(20),
            u16(0),
            u16(0),
            u16(time),
            u16(date),
            u32(crc),
            u32(data.length),
            u32(data.length),
            u16(nameBytes.length),
            u16(0),
            u16(0),
            u16(0),
            u16(0),
            u32(0),
            u32(offset),
            nameBytes,
        ]);
        locals.push(local);
        centrals.push(central);
        offset += local.length;
    }

    const central = concat(centrals);
    const end = concat([
        u32(0x06054b50),
        u16(0),
        u16(0),
        u16(Object.keys(files).length),
        u16(Object.keys(files).length),
        u32(central.length),
        u32(offset),
        u16(0),
    ]);

    return concat([...locals, central, end]);
}

export async function readXlsx(bytes: Uint8Array): Promise<string[][]> {
    const files = await unzip(bytes);
    const rels = decoder.decode(files.get('xl/_rels/workbook.xml.rels') ?? new Uint8Array());
    const sheetTarget = firstWorksheet(rels);
    const sheetPath = sheetTarget.startsWith('/') ? sheetTarget.slice(1) : `xl/${sheetTarget.replace(/^\.\//, '')}`;
    const sheet = decoder.decode(files.get(sheetPath) ?? new Uint8Array());
    const shared = decoder.decode(files.get('xl/sharedStrings.xml') ?? new Uint8Array());
    return matrixFromSheet(sheet, sharedStrings(shared));
}

function firstWorksheet(rels: string): string {
    const match = rels.match(/Type="[^"]*\/worksheet"[^>]*Target="([^"]+)"|Target="([^"]+)"[^>]*Type="[^"]*\/worksheet"/);
    return match?.[1] || match?.[2] || 'worksheets/sheet1.xml';
}

function sharedStrings(xml: string): string[] {
    const items: string[] = [];
    const pattern = /<si\b[^>]*>([\s\S]*?)<\/si>/g;
    let match: RegExpExecArray | null;
    while ((match = pattern.exec(xml))) {
        const texts = [...match[1].matchAll(/<t\b[^>]*>([\s\S]*?)<\/t>/g)].map((item) => unescapeXml(item[1]));
        items.push(texts.join(''));
    }
    return items;
}

function matrixFromSheet(xml: string, shared: string[]): string[][] {
    const rows: string[][] = [];
    const rowPattern = /<row\b([^>]*)>([\s\S]*?)<\/row>/g;
    let rowMatch: RegExpExecArray | null;
    while ((rowMatch = rowPattern.exec(xml))) {
        const cells = new Map<number, string>();
        const cellPattern = /<c\b([^>]*?)(?:\/>|>([\s\S]*?)<\/c>)/g;
        let cellMatch: RegExpExecArray | null;
        while ((cellMatch = cellPattern.exec(rowMatch[2]))) {
            const attrs = cellMatch[1];
            const body = cellMatch[2] ?? '';
            const ref = attrs.match(/\br="([A-Z]+)\d+"/i)?.[1];
            if (!ref) {
                continue;
            }
            const index = columnIndex(ref);
            const type = attrs.match(/\bt="([^"]+)"/)?.[1] ?? '';
            const value = body.match(/<v>([\s\S]*?)<\/v>/)?.[1] ?? '';
            if (type === 's') {
                cells.set(index, shared[Number(value)] ?? '');
            } else if (type === 'inlineStr') {
                cells.set(index, [...body.matchAll(/<t\b[^>]*>([\s\S]*?)<\/t>/g)].map((item) => unescapeXml(item[1])).join(''));
            } else {
                cells.set(index, unescapeXml(value));
            }
        }
        const width = Math.max(-1, ...cells.keys()) + 1;
        rows.push(Array.from({ length: width }, (_, index) => cells.get(index) ?? ''));
    }
    return rows.filter((row) => row.some((value) => value.trim() !== ''));
}

function columnIndex(letters: string): number {
    let index = 0;
    for (const char of letters.toUpperCase()) {
        index = index * 26 + (char.charCodeAt(0) - 64);
    }
    return index - 1;
}

function unescapeXml(value: string): string {
    return value
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&quot;/g, '"')
        .replace(/&apos;/g, "'")
        .replace(/&amp;/g, '&');
}

async function unzip(bytes: Uint8Array): Promise<Map<string, Uint8Array>> {
    const files = new Map<string, Uint8Array>();
    const directory = centralDirectory(bytes);
    for (const entry of directory) {
        const method = readU16(bytes, entry.offset + 8);
        const nameLength = readU16(bytes, entry.offset + 26);
        const extraLength = readU16(bytes, entry.offset + 28);
        const dataStart = entry.offset + 30 + nameLength + extraLength;
        const compressed = bytes.subarray(dataStart, dataStart + entry.compressedSize);
        files.set(entry.name, method === 0 ? compressed : await inflateRaw(compressed));
    }
    return files;
}

function centralDirectory(bytes: Uint8Array): { name: string; offset: number; compressedSize: number }[] {
    const start = Math.max(0, bytes.length - 22 - 65535);
    let end = -1;
    for (let index = bytes.length - 22; index >= start; index -= 1) {
        if (readU32(bytes, index) === 0x06054b50) {
            end = index;
            break;
        }
    }
    if (end < 0) {
        return [];
    }
    const count = readU16(bytes, end + 10);
    let offset = readU32(bytes, end + 16);
    const entries: { name: string; offset: number; compressedSize: number }[] = [];
    for (let index = 0; index < count && offset + 46 <= bytes.length; index += 1) {
        if (readU32(bytes, offset) !== 0x02014b50) {
            break;
        }
        const compressedSize = readU32(bytes, offset + 20);
        const nameLength = readU16(bytes, offset + 28);
        const extraLength = readU16(bytes, offset + 30);
        const commentLength = readU16(bytes, offset + 32);
        const localOffset = readU32(bytes, offset + 42);
        const name = decoder.decode(bytes.subarray(offset + 46, offset + 46 + nameLength));
        entries.push({ name, offset: localOffset, compressedSize });
        offset += 46 + nameLength + extraLength + commentLength;
    }
    return entries;
}

function readU16(bytes: Uint8Array, offset: number): number {
    return bytes[offset] | (bytes[offset + 1] << 8);
}

function readU32(bytes: Uint8Array, offset: number): number {
    return (bytes[offset] | (bytes[offset + 1] << 8) | (bytes[offset + 2] << 16) | (bytes[offset + 3] << 24)) >>> 0;
}

async function inflateRaw(data: Uint8Array): Promise<Uint8Array> {
    const stream = new DecompressionStream('deflate-raw');
    const writer = stream.writable.getWriter();
    await writer.write(data);
    await writer.close();
    const reader = stream.readable.getReader();
    const chunks: Uint8Array[] = [];
    for (;;) {
        const { done, value } = await reader.read();
        if (done) {
            break;
        }
        if (value) {
            chunks.push(value);
        }
    }
    return concat(chunks);
}
