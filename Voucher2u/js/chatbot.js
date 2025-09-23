// Voucher2u/js/chatbot.js

document.addEventListener('DOMContentLoaded', function() {
    const chatbotButton = document.getElementById('chatbot-button');
    const chatbotWindow = document.getElementById('chatbot-window');
    const closeChatbotButton = document.getElementById('close-chatbot');
    const chatMessages = document.getElementById('chatbot-messages');
    const chatInput = document.getElementById('chatbot-input-field');
    const chatSendButton = document.getElementById('chatbot-send-button');

    // Toggle chatbot window visibility
    chatbotButton.addEventListener('click', function() {
        chatbotWindow.style.display = (chatbotWindow.style.display === 'flex') ? 'none' : 'flex';
        if (chatbotWindow.style.display === 'flex') {
            chatMessages.scrollTop = chatMessages.scrollHeight; // Scroll to bottom on open
            chatInput.focus(); // Focus input field

            // Display initial bot message if not already present
            if (chatMessages.children.length === 0) {
                const userName = localStorage.getItem('userName');
                const greeting = userName ? `Hi ${userName}! I\'m Optima Bot, can I help you?` : `Hi! I\'m Optima Bot, can I help you?`;
                addMessage(greeting, 'bot');
            }
        }
    });

    closeChatbotButton.addEventListener('click', function() {
        chatbotWindow.style.display = 'none';
    });

    // Function to add a message to the chat window
    function addMessage(message, sender) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('chat-message', `${sender}-message`);
        messageElement.innerHTML = `<div class="message-bubble">${message}</div>`;
        chatMessages.appendChild(messageElement);
        chatMessages.scrollTop = chatMessages.scrollHeight; // Scroll to the latest message
    }

    // Function to send message to API
    async function sendMessageToBot(message) {
        addMessage(message, 'user');
        chatInput.value = ''; // Clear input field

        const userId = localStorage.getItem('userId'); // Get userId from localStorage

        try {
            const response = await fetch('../php/chat_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: message, userId: userId }) // Include userId
            });
            const data = await response.json();

            if (data.success) {
                addMessage(data.chat_response, 'bot');
            } else {
                addMessage(`Error: ${data.message}`, 'bot');
                console.error('Chat API Error:', data.message);
            }
        } catch (error) {
            addMessage('Error connecting to the chatbot. Please try again later.', 'bot');
            console.error('Network error or failed to parse JSON:', error);
        }
    }

    // Event listener for send button
    chatSendButton.addEventListener('click', function() {
        const message = chatInput.value.trim();
        if (message) {
            sendMessageToBot(message);
        }
    });

    // Event listener for enter key in input field
    chatInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const message = chatInput.value.trim();
            if (message) {
                sendMessageToBot(message);
            }
        }
    });

    // Initial bot message when chatbot opens (optional)
    // chatbotButton.addEventListener('click', function() {
    //     if (chatbotWindow.style.display === 'flex' && chatMessages.children.length === 0) {
    //         addMessage('Hi there! How can I help you today?', 'bot');
    //     }
    // });
});
