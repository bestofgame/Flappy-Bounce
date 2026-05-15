// Dans ton <script> sur GitHub, remplace la fonction de validation par celle-ci :
async function obtenirCID(iid) {
    const status = $("#ocr-status");
    status.show().text("Connexion au serveur Namecheap...");

    try {
        const response = await fetch("https://ebooxly.com/api.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ iid: iid })
        });

        const result = await response.json();

        if (result.success) {
            $("#tbxCid").val(result.cid);
            status.attr("class", "text-success").text("✅ CID Reçu !");
        } else {
            status.attr("class", "text-danger").text("❌ Erreur : " + result.message);
        }
    } catch (err) {
        status.attr("class", "text-danger").text("❌ Impossible de contacter Namecheap.");
    }
}
