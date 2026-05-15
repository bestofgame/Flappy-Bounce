<?php
$cid_result = ""; $error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['iid'])) {
    $iid = preg_replace('/\D/', '', $_POST['iid']);
    
    // Tentative via le domaine d'activation classique mais avec headers renforcés
    $url = "https://activation.sls.microsoft.com/sls/ws/ActivationService.asmx";

    $xml = '<?xml version="1.0" encoding="utf-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><BatchActivate xmlns="http://www.microsoft.com/DRM/SL/BatchActivation/1.0"><request><Digest>None</Digest><RequestXml>&lt;ActivationRequest xmlns="http://www.microsoft.com/DRM/SL/BatchActivation/1.0"&gt;&lt;VersionNumber&gt;3.2&lt;/VersionNumber&gt;&lt;RequestType&gt;2&lt;/RequestType&gt;&lt;Info&gt;&lt;IID&gt;'.$iid.'&lt;/IID&gt;&lt;/Info&gt;&lt;/ActivationRequest&gt;</RequestXml></request></BatchActivate></soap:Body></soap:Envelope>';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $xml,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_ENCODING => "", // Accepte la compression pour paraître humain
        CURLOPT_HTTPHEADER => [
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "http://www.microsoft.com/DRM/SL/BatchActivation/1.0/BatchActivate"',
            'Connection: keep-alive',
        ],
        // On change l'identité pour un navigateur Android cette fois
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Linux; Android 13; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Mobile Safari/537.36'
    ]);

    $res = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = "Blocage pare-feu : Ton serveur Namecheap est rejeté par Microsoft.";
    } elseif (preg_match('/&lt;CID&gt;(\d+)&lt;\/CID&gt;/', $res, $m)) {
        $cid_result = $m[1];
    } else {
        $error = "Réponse brute : " . htmlspecialchars(substr($res, 0, 300));
    }
    curl_close($ch);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MS GetCID Pro - High Precision Edition</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <style>
        :root { --primary: #0078d4; --bg: #f3f2f1; }
        body { background: var(--bg); font-family: 'Segoe UI', sans-serif; padding: 15px; }
        .card-expert { background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); padding: 25px; border: none; max-width: 600px; margin: auto; }
        .drop-zone { border: 2px dashed var(--primary); border-radius: 10px; padding: 30px; cursor: pointer; background: #f0f7ff; transition: 0.2s; text-align: center; display: block; }
        .drop-zone:hover { background: #e1efff; }
        .iid-input { font-size: 1.1rem; letter-spacing: 1px; font-weight: 600; text-align: center; }
        .cid-box { background: #1a1c1e; color: #39FF14; font-family: monospace; font-size: 1.3rem; text-align: center; border-radius: 6px; }
    </style>
</head>
<body>

<div class="container">
    <div class="card-expert">
        <h3 class="text-center fw-bold text-primary mb-4">Support Activation Expert</h3>
        
        <div class="mb-4">
            <label for="img-up" class="drop-zone">
                <span class="fs-5">📸 Charger l'image (Haute Précision)</span>
                <input type="file" id="img-up" accept="image/*" style="display:none">
            </label>
            <div id="ocr-status" class="mt-2 text-center small fw-bold" style="display:none"></div>
        </div>

        <form method="POST">
            <div class="mb-3">
                <label class="fw-bold small">INSTALLATION ID (IID) :</label>
                <div class="input-group">
                    <input type="text" id="tbxIID" name="iid" class="form-control iid-input" placeholder="000000-000000..." required>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">GET CID</button>
                </div>
                <div class="text-end text-muted mt-1" style="font-size: 10px;">LONGUEUR : <span id="cnt">0</span>/63</div>
            </div>
        </form>

        <div class="mt-4">
            <label class="fw-bold small">CONFIRMATION ID (CID) :</label>
            <input type="text" id="tbxCid" class="form-control cid-box mb-2" value="<?php echo $cid_result; ?>" readonly>
            <div class="row g-1">
                <?php 
                $labels = ['A','B','C','D','E','F','G','H'];
                for($i=0; $i<8; $i++) {
                    $val = ($cid_result) ? substr($cid_result, $i*6, 6) : "";
                    echo '<div class="col-3"><input type="text" class="form-control text-center fw-bold" value="'.$val.'" placeholder="'.$labels[$i].'" readonly></div>';
                }
                ?>
            </div>
        </div>

        <?php if($error): ?>
            <div class="alert alert-warning mt-3 p-2 small"><?php echo $error; ?></div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // 1. Formatage dynamique
    $("#tbxIID").on("input", function() {
        let t = this.value.replace(/\D/g, "");
        $("#cnt").text(t.length);
        if(t.length == 63 || t.length == 54) {
            this.value = t.match(new RegExp(".{1," + t.length/9 + "}", "g")).join("-");
        } else { this.value = t; }
    });

    // 2. OCR HAUTE PRÉCISION (Ta version optimisée)
    $("#img-up").on("change", function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const status = $("#ocr-status");
        status.show().attr("class", "mt-2 text-center small text-primary fw-bold").text("Traitement de l'image...");

        const reader = new FileReader();
        reader.onload = function(event) {
            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = img.width * 2;
                canvas.height = img.height * 2;
                ctx.scale(2, 2);
                ctx.drawImage(img, 0, 0);

                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = imageData.data;
                for (let i = 0; i < data.length; i += 4) {
                    let grayscale = data[i] * 0.3 + data[i+1] * 0.59 + data[i+2] * 0.11;
                    let binary = (grayscale > 160) ? 255 : 0;
                    data[i] = data[i+1] = data[i+2] = binary;
                }
                ctx.putImageData(imageData, 0, 0);
                
                status.text("Extraction de l'IID...");

                Tesseract.recognize(canvas.toDataURL(), 'eng').then(({ data: { text } }) => {
                    const lines = text.split('\n');
                    let bestCandidate = "";

                    lines.forEach(line => {
                        let digitsOnly = line.replace(/\D/g, '');
                        if (digitsOnly.length >= 50) {
                            bestCandidate = digitsOnly;
                        }
                    });

                    if (bestCandidate.length >= 54) {
                        let finalLen = (bestCandidate.length >= 63) ? 63 : 54;
                        $("#tbxIID").val(bestCandidate.slice(-finalLen)).trigger('input');
                        status.attr("class", "mt-2 text-center small text-success fw-bold").text("✅ IID détecté !");
                    } else {
                        let fallback = text.replace(/\D/g, '');
                        $("#tbxIID").val(fallback.slice(-63)).trigger('input');
                        status.attr("class", "mt-2 text-center small text-warning fw-bold").text("⚠️ Vérifiez l'IID.");
                    }
                });
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });
});
</script>
</body>
</html>
