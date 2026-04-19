import os
import re

files = [
    "frontend/dashboards/admin.html",
    "frontend/dashboards/operator.html",
    "frontend/dashboards/stakeholder.html"
]

HTML_FILTER = """        <label>Hari:</label>
        <select id="docDay" onchange="renderDocuments(1)">
          <option value="">Semua</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option><option value="20">20</option><option value="21">21</option><option value="22">22</option><option value="23">23</option><option value="24">24</option><option value="25">25</option><option value="26">26</option><option value="27">27</option><option value="28">28</option><option value="29">29</option><option value="30">30</option><option value="31">31</option>
        </select>
        <label>Bulan:</label>
        <select id="docMonth" onchange="renderDocuments(1)">
          <option value="">Semua</option><option value="1">Januari</option><option value="2">Februari</option><option value="3">Maret</option><option value="4">April</option><option value="5">Mei</option><option value="6">Juni</option><option value="7">Juli</option><option value="8">Agustus</option><option value="9">September</option><option value="10">Oktober</option><option value="11">November</option><option value="12">Desember</option>
        </select>
        <label>Tahun:</label>
        <select id="docYear" onchange="renderDocuments(1)">
          <option value="">Semua</option><option value="2024">2024</option><option value="2025">2025</option><option value="2026">2026</option>
        </select>
        <button class="btn btn-success" onclick="downloadDocExcel()">📥 Laporan Excel</button>"""

JS_FILTER = """let _allDocData = [];
async function renderDocuments(page = 1) {
  const reqData = {
    search: document.getElementById('docSearch')?.value || '',
    status: document.getElementById('docFilter')?.value || '',
  };
  const data = await API.getDocuments(reqData);
  
  const day = document.getElementById('docDay')?.value || '';
  const month = document.getElementById('docMonth')?.value || '';
  const year = document.getElementById('docYear')?.value || '';
  
  const filtered = data.filter(d => {
    if (!d.created_at) return true;
    const date = new Date(d.created_at);
    if (day && date.getDate() !== parseInt(day)) return false;
    if (month && date.getMonth() + 1 !== parseInt(month)) return false;
    if (year && date.getFullYear() !== parseInt(year)) return false;
    return true;
  });
  
  _allDocData = filtered;

  const start  = (page - 1) * ITEMS_PER_PAGE;
  const sliced = filtered.slice(start, start + ITEMS_PER_PAGE);"""

JS_EXPORT = """
async function downloadDocExcel() {
  if (!_allDocData || _allDocData.length === 0) {
    alert('Tidak ada data dokumen untuk diunduh'); return;
  }
  
  const day = document.getElementById('docDay')?.value || '';
  const month = document.getElementById('docMonth')?.value || '';
  const year  = document.getElementById('docYear')?.value || '';
  const filterLabel = `${day?day+'-':''}${month?month+'-':''}${year||'Semua'}`;

  const headers = ['ID Dokumen', 'ID Kontainer', 'Vessel', 'Tipe Dokumen', 'Status', 'Catatan', 'Tanggal Dibuat', 'Link File'];
  const rows = _allDocData.map(d => [
    d.id,
    d.container_id || '',
    d.vessel || '',
    d.type || '',
    d.status || '',
    d.notes || '',
    d.created_at ? d.created_at.split('T').join(' ').split('.')[0] : '',
    d.filepath || ''
  ]);

  const wsData = [headers, ...rows];
  const ws = XLSX.utils.aoa_to_sheet(wsData);
  ws['!cols'] = [12, 16, 20, 20, 12, 25, 18, 50].map(w=>({wch:w}));

  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Laporan Dokumen');
  XLSX.writeFile(wb, `Laporan_Dokumen_CMS_${filterLabel}.xlsx`);
}
"""

for file in files:
    if not os.path.exists(file):
        continue
    with open(file, 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. Add Excel library if not present
    if "xlsx.full.min.js" not in content:
        content = content.replace("</head>", '<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>\n</head>')

    # 2. Patch HTML filter bar for Documents section
    doc_filter_re = r'(<div class="section"[^>]*id="sec-documents"[^>]*>.*?<div class="filter-bar">.*?)(</div>\s*<div class="box">)'
    
    match = re.search(doc_filter_re, content, re.DOTALL)
    if match:
        original = match.group(0)
        # Avoid double patch
        if "id=\"docDay\"" not in original:
            # We append HTML_FILTER just before the closing </div> of filter-bar
            new_filter_bar = original.replace("</div>\n      <div class=\"box\">", "\n" + HTML_FILTER + "\n      </div>\n      <div class=\"box\">")
            if new_filter_bar == original:
                 new_filter_bar = original.replace("</div>\n        <div class=\"box\">", "\n" + HTML_FILTER + "\n      </div>\n        <div class=\"box\">")
            content = content.replace(original, new_filter_bar)
            
    # 3. Patch JS renderDocuments
    render_doc_re1 = r'async function renderDocuments\(page = 1\) \{[\s\S]*?const start\s*=\s*\(page - 1\) \* ITEMS_PER_PAGE;\s*const sliced = data.slice\(start, start \+ ITEMS_PER_PAGE\);'
    match_js = re.search(render_doc_re1, content)
    if match_js:
        content = content.replace(match_js.group(0), JS_FILTER)
        content = content.replace('buildPagination(data.length,', 'buildPagination(filtered.length,')

    # 4. Inject downloadDocExcel function
    if "function downloadDocExcel" not in content:
        content = content.replace("</script>\n</body>", JS_EXPORT + "\n</script>\n</body>")

    with open(file, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Patched {file}")
