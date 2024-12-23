<!-- app/Views/chat-view.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>FreelanceFlow - Chat IA</title>
  <link rel="stylesheet" href="/assets/css/tailwind.css">
</head>
<body class="p-4">
  <h1 class="text-2xl font-bold mb-4">Chat Conversationnel</h1>

  <div id="chat-container" class="border p-4 mb-4 h-96 overflow-y-auto">
    <!-- Messages will be displayed here -->
  </div>

  <div id="mission-details" class="hidden border p-4 mb-4 bg-gray-50">
    <h2 class="text-xl font-semibold mb-2">Détails de la Mission</h2>
    <div id="mission-summary"></div>
    <div class="mt-4">
      <input
        type="email"
        id="clientEmail"
        class="w-full border p-2 mb-2"
        placeholder="Votre email"
        required
      >
      <button
        id="submit-mission"
        class="w-full bg-green-600 text-white px-4 py-2 rounded"
      >Soumettre la Mission</button>
    </div>
  </div>

  <form id="chat-form" class="flex">
    <input
      type="text"
      id="userMessage"
      class="flex-1 border p-2"
      placeholder="Tapez votre message..."
      required
    >
    <button
      type="submit"
      class="ml-2 bg-blue-600 text-white px-4 py-2 rounded"
    >Envoyer</button>
  </form>

  <script>
    const form = document.getElementById('chat-form');
    const input = document.getElementById('userMessage');
    const chatContainer = document.getElementById('chat-container');
    const missionDetails = document.getElementById('mission-details');
    const missionSummary = document.getElementById('mission-summary');
    const clientEmail = document.getElementById('clientEmail');
    const submitMissionBtn = document.getElementById('submit-mission');

    let currentMission = null;

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const userMsg = input.value.trim();
      if (!userMsg) return;

      // Display user message
      appendMessage('Vous', userMsg);

      try {
        // Send message to AI service
        const response = await fetch('/chat/sendMessage', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ message: userMsg })
        });

        const data = await response.json();
        
        // Display AI response
        appendMessage('Assistant', data.message);

        // If mission details are identified, show the submission form
        if (data.mission) {
          currentMission = data.mission;
          showMissionDetails(data.mission);
        }
      } catch (error) {
        console.error('Error:', error);
        appendMessage('Système', 'Une erreur est survenue. Veuillez réessayer.');
      }

      input.value = '';
    });

    submitMissionBtn.addEventListener('click', async () => {
      if (!currentMission || !clientEmail.value) return;

      try {
        const response = await fetch('/chat/submitMission', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            ...currentMission,
            clientEmail: clientEmail.value
          })
        });

        const data = await response.json();
        
        if (data.success) {
          missionDetails.classList.add('hidden');
          appendMessage('Système', 'Mission soumise avec succès ! Un email de confirmation vous a été envoyé.');
        } else {
          appendMessage('Système', 'Erreur lors de la soumission. Veuillez réessayer.');
        }
      } catch (error) {
        console.error('Error:', error);
        appendMessage('Système', 'Une erreur est survenue. Veuillez réessayer.');
      }
    });

    function appendMessage(sender, message) {
      const messageDiv = document.createElement('div');
      messageDiv.className = 'mb-2';
      messageDiv.innerHTML = `<strong>${sender}:</strong> ${message}`;
      chatContainer.appendChild(messageDiv);
      chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    function showMissionDetails(mission) {
      missionSummary.innerHTML = `
        <div class="mb-2"><strong>Service:</strong> ${mission.service}</div>
        <div class="mb-2"><strong>Description:</strong> ${mission.description}</div>
        <div class="mb-2"><strong>Prix:</strong> ${mission.price}€</div>
      `;
      missionDetails.classList.remove('hidden');
    }
  </script>
</body>
</html>
