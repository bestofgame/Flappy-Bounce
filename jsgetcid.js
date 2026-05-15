// Liste des éléments pour la gestion d'état (Modèle jsgetcid.info)
var listItem = new Array("tbxIID", "btnGetcid", "tbxCid", "tarCMD", "cbbVersion");

$(document).ready(function () {
    const iidInput = $("#tbxIID");
    const statusDiv = $("#ocr-status"); // ID mis à jour pour correspondre au nouveau PHP

    // 1. Logique de formatage et nettoyage en temps réel (jsgetcid.js)
    iidInput.on("paste input", function () {
        var t = this.value.replace(/\D/g, ""); // Nettoyage strict
        $("#cnt").text(t.length); // Compteur mis à jour (ID: cnt)
        if (63 == t.length || 54 == t.length) {
            this.value = t.match(new RegExp(".{1," + t.length / 9 + "}", "g")).join("-");
        } else {
            this.value = t;
        }
    });

    // 2. Gestion de l'OCR de PRÉCISION (Regex blocs 6-7)
    $("#img-up").on("change", function(e) {
        const file = e.target.files[0];
        if (!file) return;

        statusDiv.show().attr("class", "mt-2 text-center small text-primary").html("Analyse en cours... <span id='progress'>0</span>%");
        
        Tesseract.recognize(file, 'eng', { 
            logger: m => { if(m.status === 'recognizing text') $("#progress").text(Math.floor(m.progress * 100)); }
        }).then(({ data: { text } }) => {
            // Recherche de blocs de 6 ou 7 chiffres uniquement
            const blocks = text.match(/\d{6,7}/g);
            
            if (blocks && blocks.length >= 9) {
                const finalIid = blocks.slice(-9).join('');
                iidInput.val(finalIid).trigger('input'); // Déclenche le formatage auto
                statusDiv.attr("class", "mt-2 text-center small text-success fw-bold").text("✅ IID détecté !");
            } else {
                // Secours : Nettoyage global si les blocs échouent
                let clean = text.replace(/\D/g, '');
                iidInput.val(clean.slice(-63)).trigger('input');
                statusDiv.attr("class", "mt-2 text-center small text-warning fw-bold").text("⚠️ Vérifiez l'IID extrait.");
            }
        });
    });

    // 3. Gestion des scripts CMD (Modèle jsgetcid.js)
    $("#cbbVersion").on("change", function() {
        var cid = $("#tbxCid").val();
        var version = $(this).val();
        if (cid && cid.length >= 48 && version != "") {
            $("#tarCMD").val(GetCommand(version, cid.replace(/\D/g, "")));
        }
    });

    $("#btnCopyCMD").on("click", function() {
        var cmd = $("#tarCMD").val();
        if(cmd) {
            navigator.clipboard.writeText(cmd).then(() => alert("✅ Script copié !"));
        }
    });
});

// Fonctions utilitaires du modèle original
function ValidateIID(iid) {
    return iid.replace(/\D/g, '').trim();
}

function GetCommand(version, cid) {
    if (version == "4") {
        return "set CID=" + cid + "\nFor /f \"delims=\" %a in ('dir /s /b \"%ProgramFiles%\\Microsoft Office\\ospp.vbs\"') do cd /d %~dpa\ncscript OSPP.VBS /actcid:%CID%\ncscript OSPP.vbs /act\npause";
    } else if (version == "0") {
        return "set CID=" + cid + "\ncscript slmgr.vbs /atp %CID%\ncscript slmgr.vbs /ato\npause";
    }
    return "";
}