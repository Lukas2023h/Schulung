<script src="inc/functions.js"></script>
<!-- Buttons zum Laden der PHP-Dateien -->
<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <button id="userBtn" onclick="loadContent('page/admin_modul.php', 'userBtn')" class="btn btn-secondary btn-lg w-100 py-3">Modulverwaltung</button>
        </div>
        <div class="col-md-4">
            <button id="schulungBtn" onclick="loadContent('page/admin_schulung.php', 'schulungBtn')" class="btn btn-secondary btn-lg w-100 py-3">Schulungsnachweisverwaltung</button>
        </div>
        <div class="col-md-4">
            <button id="statusBtn" onclick="loadContent('page/admin_user.php', 'statusBtn')" class="btn btn-secondary btn-lg w-100 py-3">Mitarbeiterverwaltung</button>
        </div>
    </div>
</div><br>  

<!-- Bereich, in dem die PHP-Dateien geladen werden -->
<div id="content"></div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        if (typeof paginateTable === "function") {
            // Automatisch alle Tabellen mit data-paginate paginieren
            document.querySelectorAll("table[data-paginate]").forEach(table => {
                paginateTable(table.id);
            });
        }
    });


    function loadContent(page, activeButtonId) {
        const contentDiv = document.getElementById("content");

        const xhr = new XMLHttpRequest();
        xhr.open("GET", page, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                contentDiv.innerHTML = xhr.responseText;

                initPaginationInContent(contentDiv);


                // Formularhandler nachladen
                if (page.includes("admin_modul.php")) {
                    setupModulForm();
                }
                if (page.includes("admin_user.php")) {
                    setupUserForm();
                }

                // Alle eingebetteten <script>-Blöcke erneut ausführen
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

        // Alle Buttons zurücksetzen
        ['userBtn', 'schulungBtn', 'statusBtn'].forEach(id => {
            const btn = document.getElementById(id);
            if (btn) {
                btn.classList.remove("btn-primary");
                btn.classList.add("btn-secondary");
            }
        });

        // Aktiven Button hervorheben
        const activeBtn = document.getElementById(activeButtonId);
        if (activeBtn) {
            activeBtn.classList.remove("btn-secondary");
            activeBtn.classList.add("btn-primary");
        }
    }

    // Seite startet mit Modulverwaltung
    window.onload = function() {
        loadContent('page/admin_modul.php', 'userBtn');
    };

    // Formular-Handling für Modulverwaltung
    function setupModulForm() {
        const form = document.getElementById("modulForm");
        if (!form) return;

        form.addEventListener("submit", function(event) {
            event.preventDefault();

            const formData = new FormData(form);

            fetch("page/admin_modul.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.text())
                .then(html => {
                    document.getElementById("content").innerHTML = html;
                    setupModulForm(); // neu initialisieren
                })
                .catch(error => console.error("Fehler beim Absenden:", error));
        });
    }

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
                    setupUserForm(); // erneut setzen

                    initPaginationInContent(document.getElementById("content"));
                });
        });
    }

    function initPaginationInContent(rootElement) {
        const tables = rootElement.querySelectorAll("table[data-paginate]");
        tables.forEach(table => {
            paginateTable(table.id);
        });
    }
</script>