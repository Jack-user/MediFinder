<?php
session_start();
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
  <!-- [Head] start -->
  <head>
    <title>Upload Prescription | MediFinder</title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Upload and analyze prescription images" />
    <meta name="keywords" content="prescription upload, OCR, medicine finder" />
    <meta name="author" content="MediFinder" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="/medi/assets/img/medifinder-logo.svg" type="image/x-icon" />

    <?php include 'includes/head-css.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5.1.1/dist/tesseract.min.js"></script>
  </head>
  <!-- [Head] end -->
  <!-- [Body] Start -->

  <body>
    <?php include 'includes/loader.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>

    <!-- [ Main Content ] start -->
    <div class="pc-container">
      <div class="pc-content">
        <?php
        $pageTitle = 'Upload Prescription';
        $breadcrumbItems = ['Prescription'];
        $activeItem = 'Upload';
        include 'includes/breadcrumb.php';
        ?>

        <!-- [ Main Content ] start -->
        <div class="grid grid-cols-12 gap-x-6">
          <div class="col-span-12 xl:col-span-6">
            <div class="card">
              <div class="card-header">
                <h5>1) Upload prescription image</h5>
              </div>
              <div class="card-body">
                <p class="text-muted small mb-3">OCR is processed in your browser for privacy.</p>
                <input type="file" class="form-control mb-3" id="fileInput" accept="image/*" />
                <div class="border rounded p-3 text-center" id="preview" style="min-height: 200px; display: flex; align-items: center; justify-content: center;">
                  <div class="text-muted">No image selected</div>
                </div>
                <div class="mt-3">
                  <button class="btn btn-primary w-100" id="btnExtract" disabled>
                    <i class="feather icon-search mr-2"></i>Extract text
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div class="col-span-12 xl:col-span-6">
            <div class="card mb-3">
              <div class="card-header">
                <h5>2) Extracted text</h5>
              </div>
              <div class="card-body">
                <textarea class="form-control" id="extractedText" rows="8" placeholder="Recognized prescription text will appear here..."></textarea>
                <div class="mt-3">
                  <button class="btn btn-success w-100" id="btnAnalyze" disabled>
                    <i class="feather icon-zap mr-2"></i>Analyze & Recommend
                  </button>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header">
                <h5>3) Results</h5>
              </div>
              <div class="card-body">
                <div id="results" class="text-muted small">No results yet.</div>
              </div>
            </div>
          </div>
        </div>
        <!-- [ Main Content ] end -->
      </div>
    </div>
    <!-- [ Main Content ] end -->
    
    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/footer-js.php'; ?>

    <script>
    const fileInput = document.getElementById('fileInput');
    const preview = document.getElementById('preview');
    const btnExtract = document.getElementById('btnExtract');
    const btnAnalyze = document.getElementById('btnAnalyze');
    const extractedText = document.getElementById('extractedText');
    const results = document.getElementById('results');

    let currentImage = null;
    let currentFileName = null;

    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (!file) { return; }
        currentFileName = file.name;
        const reader = new FileReader();
        reader.onload = (e) => {
            currentImage = new Image();
            currentImage.onload = () => {
                preview.innerHTML = '';
                preview.appendChild(currentImage);
                currentImage.style.maxWidth = '100%';
                currentImage.style.maxHeight = '260px';
                btnExtract.disabled = false;
            }
            currentImage.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });

    btnExtract.addEventListener('click', async () => {
        if (!currentImage) return;
        btnExtract.disabled = true;
        btnExtract.innerHTML = '<i class="feather icon-loader mr-2"></i>Extracting...';
        const { data } = await Tesseract.recognize(currentImage.src, 'eng', { logger: m => {} });
        extractedText.value = (data.text || '').trim();
        btnExtract.innerHTML = '<i class="feather icon-search mr-2"></i>Extract text';
        btnExtract.disabled = false;
        btnAnalyze.disabled = extractedText.value.length === 0;
    });

    btnAnalyze.addEventListener('click', async () => {
        const text = extractedText.value.trim();
        if (!text) return;
        results.innerHTML = 'Analyzing...';
        const res = await fetch('/medi/api/analyze.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text })
        });
        const json = await res.json();
        results.innerHTML = renderResults(json);
        
        // Save to database if logged in
        if (currentFileName && text) {
            try {
                await fetch('/medi/api/save_upload.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        filename: currentFileName,
                        extracted_text: text
                    })
                });
            } catch (e) {
                // Silently fail if not logged in or error
            }
        }
    });

    function renderResults(data) {
        if (!data || !data.items || data.items.length === 0) {
            return '<div class="text-muted">No medicines identified.</div>';
        }
        return `
            <div class="list-group">
                ${data.items.map(item => `
                    <div class="list-group-item">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-semibold">${escapeHtml(item.name)}</div>
                                <div class="text-muted small">${escapeHtml(item.use || '—')}</div>
                                ${item.alternatives && item.alternatives.length ? `<div class="mt-1 small"><span class="text-success">Alternatives:</span> ${item.alternatives.map(escapeHtml).join(', ')}</div>` : ''}
                            </div>
                            <div>
                                <a class="btn btn-sm btn-outline-primary" href="/medi/locator.php?query=${encodeURIComponent(item.name)}">Find nearby</a>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    function escapeHtml(s) {
        return s.replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
    }
    </script>
  </body>
  <!-- [Body] end -->
</html>
