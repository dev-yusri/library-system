<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
    $current_page = 'helper';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Chatbot</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.3/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .chat-container {
            
            height: 300px;
            overflow-y: auto;
            padding: 10px;
            background-color: #f7fafc;
            border-radius: 10px;
            border: 1px solid #ddd;
        }
        .chat-bubble {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 10px;
            background-color: #e2e8f0;
        }
        .user-bubble {
            background-color: lightgrey;
            color: black;
            text-align: right;
        }
        .bot-bubble {
            background-color: rgb(79 70 229 / var(--tw-bg-opacity, 1));
            color:white;
            text-align: left;
        }
        .btn {
            background-color: rgb(79 70 229 / var(--tw-bg-opacity, 1));
            color: white;
            padding: 8px 16px;
            margin: 5px 0;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
        }
        .btn:hover {
            background-color:#7c42c9;
        }
    </style>
</head>
<body class="bg-gray-100">
<div class="min-h-screen flex flex-col md:flex-row">
<?php $current_page = 'helper'; include 'menu/sidebar.php'; ?>
<div class="flex justify-center items-center w-full">
<div class="p-4 md:p-6 w-full md:w-1/2 bg-white rounded-lg shadow-md m-5">
        <h2 class="text-2xl font-semibold text-center mb-4">Library Chatbot</h2>
        <div id="chat" class="chat-container">
            <!-- Chat will appear here -->
        </div>
        <div class="mt-4 flex">
            <input type="text" id="userInput" class="w-full p-2 border border-gray-300 rounded-l-md" placeholder="Ask me anything...">
            <button id="sendBtn" class="bg-gray-500 text-white p-2 rounded-r-md">Send</button>
        </div>
        <div class="mt-4">
            <button id="topFaqBtn" class="btn w-full">Top FAQs</button>
            <button id="popularSearchBtn" class="btn w-full">Popular Searches</button>
        </div>
    </div>

    <script>
        const chatContainer = document.getElementById('chat');
        const userInput = document.getElementById('userInput');
        const sendBtn = document.getElementById('sendBtn');
        const topFaqBtn = document.getElementById('topFaqBtn');
        const popularSearchBtn = document.getElementById('popularSearchBtn');

        const libraryFAQs = [
            {
                question: "Hi",
                answer: "Hi! I am LibraryBot. How can i help you today?"
            },

            {
                question: "How can I borrow books?",
                answer: "You can borrow books by visiting our catalog and searching for a book. After finding your book, click 'Borrow'."
            },
            {
                question: "How do I return a book?",
                answer: "You can return books by visiting the 'My Borrowed Books' section and clicking 'Return' next to the book you want to return."
            },
            {
                question: "What should I do if my borrowed book is overdue?",
                answer: "If your book is overdue, you will be charged a late fee. Please return it as soon as possible to avoid further charges."
            },
            {
                question: "Where can I find books on history?",
                answer: "You can find books on History in the History section of our catalog. You can also search for them in the search bar."
            },
            {
                question: "How can I check my borrowed books?",
                answer: "You can view all your borrowed books in your account under the 'My Borrowed Books' section."
            },
            {
                question: "How long can i borrow book?",
                answer: "You have 7 days to borrow book."
            }
        ];

        const popularSearches = [
            "Book",
            "Borrow",
            "Return",
            "Due date",
            "My account"
        ];

        function addMessage(content, sender = 'bot') {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('chat-bubble');
            if (sender === 'user') {
                messageDiv.classList.add('user-bubble');
            } else {
                messageDiv.classList.add('bot-bubble');
            }
            messageDiv.textContent = content;
            chatContainer.appendChild(messageDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function handleQuery(query) {
            const lowerCaseQuery = query.toLowerCase();

            // Search for a relevant FAQ or query answer
            const faq = libraryFAQs.find(faq => lowerCaseQuery.includes(faq.question.toLowerCase()));

            if (faq) {
                addMessage(faq.answer, 'bot');
            } else if (lowerCaseQuery.includes('borrow')) {
                addMessage("To borrow books, visit the library catalog and click 'Borrow' next to the book.", 'bot');
            } else if (lowerCaseQuery.includes('return')) {
                addMessage("To return books, go to 'My Borrowed Books' and click 'Return' next to the borrowed item.", 'bot');
            } else {
                addMessage("Sorry, I couldn't find an answer to your query. Please try asking about borrowing, returning books, or fines.", 'bot');
            }
        }

        function showFAQs() {
            libraryFAQs.forEach(faq => {
                addMessage(`${faq.question}: ${faq.answer}`, 'bot');
            });
        }

        function showPopularSearches() {
            popularSearches.forEach(search => {
                addMessage(`Popular Search: ${search}`, 'bot');
            });
        }

        sendBtn.addEventListener('click', () => {
            const query = userInput.value.trim();
            if (query) {
                addMessage(query, 'user');
                handleQuery(query);
                userInput.value = '';  // Clear the input field
            }
        });

        topFaqBtn.addEventListener('click', () => {
            addMessage("Here are some frequently asked questions:", 'bot');
            showFAQs();
        });

        popularSearchBtn.addEventListener('click', () => {
            addMessage("Here are some popular searches:", 'bot');
            showPopularSearches();
        });
    </script>

</body>
</html>
