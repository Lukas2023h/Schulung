<script src="inc/functions.js"></script>

<!-- Buttons zum Laden der PHP-Dateien -->
<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <button id="userBtn" onclick="loadContent('page/admin_modulstatus.php', 'userBtn')" class="btn btn-secondary btn-lg w-100 py-3">Modulverwaltung</button>
        </div>
        <div class="col-md-4">
            <button id="schulungBtn" onclick="loadContent('page/admin_schulung.php', 'schulungBtn')" class="btn btn-secondary btn-lg w-100 py-3">Schulungsnachweise</button>
        </div>
        <div class="col-md-4">
            <button id="statusBtn" onclick="loadContent('page/admin_user.php', 'statusBtn')" class="btn btn-secondary btn-lg w-100 py-3">Mitarbeiterübersicht</button>
        </div>
    </div>
</div><br>

<!-- Bereich, in dem die PHP-Dateien geladen werden -->
<div id="content"></div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    function loadContent(page, activeButtonId) {
        const contentDiv = document.getElementById("content");

        const xhr = new XMLHttpRequest();
        xhr.open("GET", page, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                contentDiv.innerHTML = xhr.responseText;

                initPaginationInContent(contentDiv);

                if (page.includes("admin_modulstatus.php")) {
                    setupModulStatus();
                }
                if (page.includes("admin_user.php")) {
                    setupUserForm();
                }

                const scripts = contentDiv.querySelectorAll("script");
                scripts.forEach(script => {
                    const newScript = document.createElement("script");
                    if (script.src) {
                        newScript.src = script.src;
                    } else {
                        newScript.text = script.textContent;
                    }
                    document.body.appendChild(newScript);
                });
            }
        };
        xhr.send();

        ['userBtn', 'schulungBtn', 'statusBtn'].forEach(id => {
            const btn = document.getElementById(id);
            if (btn) {
                btn.classList.remove("btn-primary");
                btn.classList.add("btn-secondary");
            }
        });

        const activeBtn = document.getElementById(activeButtonId);
        if (activeBtn) {
            activeBtn.classList.remove("btn-secondary");
            activeBtn.classList.add("btn-primary");
        }
    }

    window.onload = function() {
        loadContent('page/admin_modulstatus.php', 'userBtn');
    };

    function setupUserForm() {
        const form = document.querySelector("#userForm");
        if (!form) return;

        form.addEventListener("submit", function(event) {
            event.preventDefault();
            const formData = new FormData(this);

            fetch("page/admin_user.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    document.getElementById("content").innerHTML = data;
                    setupUserForm();
                    initPaginationInContent(document.getElementById("content"));
                });
        });
    }

    function setupModulStatus() {
        function ladeModule() {
            $.post('page/admin_modulstatus.php', {
                ajax: 'filter',
                abteilung: $('#filterAbteilung').val(),
                status: $('#filterStatus').val(),
                mitarbeitername: $('#filterMitarbeiter').val()
            }, function(html) {
                $('#modulCardContainer').html(html);
            });
        }

        $('#filterForm select').on('change', ladeModule);

        let searchTimeout;
        $('#filterMitarbeiter').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                return false;
            }
        }).on('keyup', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => ladeModule(), 300);
        });

        $('#modulCardContainer').on('click', '.modul-card', function() {
            const modulId = $(this).data('modul-id');
            const abteilung = $('#filterAbteilung').val();
            const status = $('#filterStatus').val();
            const mitarbeitername = $('#filterMitarbeiter').val();

            const detailRow = $('#modul-detail-' + modulId);
            const isVisible = detailRow.is(':visible');

            $('.modul-card').removeClass('border-primary');
            $('.modul-detail').slideUp().html('');

            if (isVisible) return;

            $(this).addClass('border-primary');

            $.post('page/admin_modulstatus.php', {
                ajax: 'detail',
                modul_id: modulId,
                abteilung: abteilung,
                status: status,
                mitarbeitername: mitarbeitername
            }, function(data) {
                detailRow.html(data).slideDown();
            });
        });

        ladeModule();
    }

    function applyDateRangeFilter() {
        const fromDateVal = $("#fromDate").val();
        const toDateVal = $("#toDate").val();

        const fromDate = fromDateVal ? new Date(fromDateVal) : null;
        const toDate = toDateVal ? new Date(toDateVal) : null;

        $("#meineTabelle tbody tr").each(function() {
            const dateText = $(this).find("td").eq(1).text().trim();
            const rowDate = new Date(dateText);

            let show = true;
            if (fromDate && rowDate < fromDate) show = false;
            if (toDate && rowDate > toDate) show = false;

            $(this).toggle(show);
        });
    }

    function initPaginationInContent(rootElement) {
        const tables = rootElement.querySelectorAll("table[data-paginate]");
        tables.forEach(table => {
            paginateTable(table.id);
        });
    }
</script>