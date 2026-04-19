import os, re

files = [
    "frontend/dashboards/admin.html",
    "frontend/dashboards/operator.html",
    "frontend/dashboards/stakeholder.html"
]

MODAL_HTML = """
<!-- MODAL: Preview Dokumen -->
<div class="modal-overlay" id="modalDocPreview">
  <div class="modal" style="width:800px; max-width:95vw;">
    <h3>Preview Dokumen</h3>
    <p class="sub" id="previewSub"></p>
    <div id="previewContainer" style="width:100%; height:500px; max-height:70vh; background:rgba(0,0,0,0.5); border-radius:8px; overflow:hidden; display:flex; align-items:center; justify-content:center; margin-bottom:14px;">
    </div>
    <div class="modal-footer">
       <a id="previewDownloadBtn" href="" target="_blank" class="btn btn-success">⬇️ Download File</a>
       <button class="btn btn-ghost" onclick="closeModal('modalDocPreview')">Tutup</button>
    </div>
  </div>
</div>
"""

JS_FUNC = """
window.openDocPreview = function(filepath, type, id) {
  document.getElementById('previewSub').textContent = `Dokumen: ${type} (${id})`;
  const ext = filepath.split('.').pop().toLowerCase();
  let html = '';
  if (['png','jpg','jpeg','gif'].includes(ext)) {
    html = `<img src="${filepath}" style="max-width:100%; max-height:100%; object-fit:contain;">`;
  } else {
    // try object for pdf for better compatibility over iframe
    if(ext === 'pdf') {
        html = `<object data="${filepath}" type="application/pdf" width="100%" height="100%">
                  <iframe src="${filepath}" style="width:100%; height:100%; border:none;"></iframe>
                </object>`;
    } else {
        html = `<iframe src="${filepath}" style="width:100%; height:100%; border:none;"></iframe>`;
    }
  }
  document.getElementById('previewContainer').innerHTML = html;
  document.getElementById('previewDownloadBtn').href = filepath;
  document.getElementById('modalDocPreview').classList.add('open');
};
"""

for fpath in files:
    if not os.path.exists(fpath):
        continue
    with open(fpath, 'r', encoding='utf-8') as f:
        content = f.read()

    if "modalDocPreview" in content:
        print(f"Skipping {fpath}, already contains modal")
        continue

    # Add Modal HTML
    if "<script src=\"../assets/js/api-client.js\"></script>" in content:
        content = content.replace("<script src=\"../assets/js/api-client.js\"></script>", MODAL_HTML + "\n<script src=\"../assets/js/api-client.js\"></script>")
    else:
        content = content.replace("</body>", MODAL_HTML + "\n</body>")

    # Add JS func
    content = content.replace("</script>\n</body>", JS_FUNC + "\n</script>\n</body>")

    # Replace Unduh button
    content = re.sub(
        r'<a href="\$\{d\.filepath\}" target="_blank" class="([^"]*)"(?:[^>]*)>([^<]*)</a>',
        r'<button class="\1" onclick="openDocPreview(\'${d.filepath}\', \'${d.type}\', \'${d.id}\')">👁 Preview</button>',
        content
    )

    with open(fpath, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Patched {fpath}")
