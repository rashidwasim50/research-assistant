// response api
jQuery(document).ready(function ($) {
    $('#send-chat').on('click', function () {
        let userInput = $('#chat-input').val();
        const sendButton = $('#send-chat');
        const sendIcon = sendButton.find('img');
        if (userInput) {
            $('#answerText-parent').html('');
            prependMessage(userInput);
            sendIcon.attr('src', chatInterfaceAjax.imagesUrl + '/spinner.svg'); 
            $(document).ajaxSend(function() {
                $(".loaderText").css('display', 'block');
            });
            $.ajax({
                url: chatInterfaceAjax.ajax_url,
                method: 'POST',
                data: {
                    action: 'send_chat_message',
                    message: userInput,
                },
                success: function (response) {
                    $('#answerText-parent').html(response); 
                    var parentContent = $('#answerText-parent').html();
                    if (parentContent.endsWith('0')) {
                        $('#answerText-parent').html(parentContent.slice(0, -1));
                    }
                },
                complete: function () {
                    sendIcon.attr('src', chatInterfaceAjax.imagesUrl + '/arrow-up.svg');
                },
                error: function () {
                    alert('An error occurred. Please try again.');
                },
            });
        }
    });

    // Trigger send on Enter key press
    $('#chat-input').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault(); // Prevent form submission
            $('#send-chat').click(); // Trigger the send button click event
        }
    });

    function prependMessage(message) {
        $('.messages').prepend('<div class="message-outer"><div class="message-text"><p>' + message + '</p></div></div>');
    }
});
document.querySelectorAll('.jd-lists').forEach(function(list) {
    const ul = list.querySelector('.jd-unorder-list');
    if (!ul || !ul.children.length) {
        const header = list.querySelector('.jd-list-head');
        if (header) {
            header.style.display = 'none';
        }
    }
});

// Active btn bg color
document.addEventListener("DOMContentLoaded", () => {
    const inputField = document.getElementById("chat-input");
    const sendButton = document.getElementById("send-chat");
    inputField.addEventListener("input", () => {
        if (inputField.value.trim() !== "") {
            sendButton.removeAttribute("disabled");
            sendButton.classList.add("active");
        } else {
            sendButton.setAttribute("disabled", "disabled");
            sendButton.classList.remove("active");
        }
    });
});

// add span text in heading 
document.addEventListener("DOMContentLoaded", () => {
    const heading = document.querySelector(".jd-heading");
    if (heading) {
        const text = heading.textContent;
        const splitIndex = text.indexOf("answers") + "answers".length;
        const before = text.substring(0, splitIndex);
        const after = text.substring(splitIndex);
        heading.innerHTML = `${before}<span>${after}</span>`;
    }
});
