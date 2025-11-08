<?php
session_start();
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
  <!-- [Head] start -->
  <head>
    <title>Manual Input | MediFinder</title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Manual medicine input and analysis" />
    <meta name="keywords" content="medicine input, symptoms, medicine finder" />
    <meta name="author" content="MediFinder" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="/CEMO_System/system/assets/img/medifinder-logo.svg" type="image/x-icon" />

    <?php include 'includes/head-css.php'; ?>
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
        $pageTitle = 'Manual Input';
        $breadcrumbItems = ['Input'];
        $activeItem = 'Manual';
        include 'includes/breadcrumb.php';
        ?>

        <!-- [ Main Content ] start -->
        <div class="grid grid-cols-12 gap-x-6">
          <div class="col-span-12 xl:col-span-6">
            <div class="card">
              <div class="card-header">
                <h5>Enter symptoms or a medicine name</h5>
              </div>
              <div class="card-body">
                <textarea id="inputText" class="form-control mb-3" rows="6" placeholder="e.g., fever and headache or paracetamol 500mg"></textarea>
                <button id="btnAnalyze" class="btn btn-success w-100">
                  <i class="feather icon-zap mr-2"></i>Analyze
                </button>
              </div>
            </div>
          </div>
          <div class="col-span-12 xl:col-span-6">
            <div class="card">
              <div class="card-header">
                <h5>Results</h5>
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
    const btn = document.getElementById('btnAnalyze');
    const results = document.getElementById('results');
    btn.addEventListener('click', async () => {
        const text = document.getElementById('inputText').value.trim();
        if (!text) return;
        results.innerHTML = 'Analyzing...';
        const res = await fetch('/CEMO_System/system/api/analyze.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text })
        });
        const json = await res.json();
        results.innerHTML = renderResults(json);
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
                                <a class="btn btn-sm btn-outline-primary" href="/CEMO_System/system/locator.php?query=${encodeURIComponent(item.name)}">Find nearby</a>
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
