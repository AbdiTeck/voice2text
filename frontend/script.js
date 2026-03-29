sendBtn.onclick = async () => {
    const blob = new Blob(audioChunks, { type: 'audio/webm' });

    const formData = new FormData();
    formData.append("audio", blob, "recording.webm");

    document.getElementById("status").innerText = "Sender...";

    try {
        const response = await fetch("/backend/upload.php", {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        console.log("API RESPONSE:", data); // 🔥 DEBUG

        if (data.error) {
            document.getElementById("result").innerText = "Feil: " + data.error;
        } else {
            document.getElementById("result").innerText = data.text;
        }

        document.getElementById("status").innerText = "Ferdig!";
    } catch (error) {
        console.error(error);
        document.getElementById("status").innerText = "Feil: " + error;
    }
};