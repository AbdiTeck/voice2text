let mediaRecorder;
let audioChunks = [];

const startBtn = document.getElementById("startBtn");
const stopBtn = document.getElementById("stopBtn");
const sendBtn = document.getElementById("sendBtn");

startBtn.onclick = async () => {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

    mediaRecorder = new MediaRecorder(stream);
    audioChunks = [];

    mediaRecorder.start();

    mediaRecorder.ondataavailable = event => {
        audioChunks.push(event.data);
    };

    startBtn.disabled = true;
    stopBtn.disabled = false;
};

stopBtn.onclick = () => {
    mediaRecorder.stop();

    stopBtn.disabled = true;
    sendBtn.disabled = false;
};

sendBtn.onclick = async () => {
    const blob = new Blob(audioChunks, { type: 'audio/webm' });

    const formData = new FormData();
    formData.append("audio", blob, "recording.webm");

    document.getElementById("status").innerText = "Sender...";

    try {
        // ✅ FIX: riktig path (samme server)
        const response = await fetch("/backend/upload.php", {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        // 🔥 DEBUG (VIKTIG)
        console.log("API RESPONSE:", data);

        // ✅ FIX: håndter error
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