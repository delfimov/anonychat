<?php

require_once __DIR__ . '/../vendor/autoload.php';

$config = require_once __DIR__ . '/../config/config.php';

header('Content-Type: text/html; charset=utf-8');

$wsUri = $config['protocol'] . '://' . $config['server_name'] . ':' . $config['port'];

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <title>AnonyChat</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <!--favicons-->
    <style type="text/css">
        #message-box {
            width: 100%;
            height: 300px;
            margin-bottom: 15px;
            overflow: scroll;
        }
        #name-color {
            padding: 0;
            border: 0;
            width: 30px;
        }
        .message_system, .message_service {
            color: #999;
        }
        .main_disconnected #chat-wrapper {
            opacity: .5;
        }
        .main_connected #chat-wrapper {
            opacity: 1;
        }
        .main_disconnected #disconnect-button,
        .main_connected #connect-button,
        .main_connected #autoconnect-wrapper {
            display: none;
        }
        .main_disconnected #autoconnect-wrapper {
            display: block;
        }
    </style>
</head>

<body>
<div class="col-lg-8 mx-auto p-4 py-md-5">

    <header class="d-flex align-items-center pb-3 mb-3 border-bottom">
        <a href="/" class="d-flex align-items-center text-dark text-decoration-none">
            <span class="fs-4">AnonyChat</span>
        </a>
    </header>

    <main id="main" class="main main_disconnected">

        <div id="connect-wrapper">
            <form class="row g-3">
                <div class="col-12">
                    <label for="connect-name" class="form-label">Your nickname and chat room (&laquo;/&raquo; by default)</label>
                    <div class="input-group mb-3">
                        <div class="input-group-text">
                            <input type="color" id="name-color" name="name-color" value="#000">
                        </div>
                        <input type="text" class="form-control" id="connect-name" required>
                        <input type="text" class="form-control" id="connect-room" value="/" required>
                        <button type="submit" class="btn btn-danger" id="disconnect-button">Exit</button>
                        <button type="submit" class="btn btn-primary" id="connect-button">Chat</button>
                    </div>
                </div>
                <div class="col-12 mt-0 mb-3" id="autoconnect-wrapper">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="autoconnect-checkbox">
                        <label class="form-check-label" for="autoconnect-checkbox">
                            Connect automatically
                        </label>
                    </div>
                </div>
            </form>
        </div>

        <div id="chat-wrapper">
            <div id="message-box" class="card" ></div>

            <form class="row g-3">
                <div class="col-9">
                    <input type="text" class="form-control" id="message-field" placeholder="Message" disabled>
                </div>
                <div class="col-auto">
                    <button type="submit" id="message-send" class="btn btn-primary" disabled>Send</button>
                </div>
            </form>
        </div>


    </main>
    <footer class="pt-5 my-5 text-muted border-top">
        Dmitry Elfimov &copy; 2022 &middot; <!--<a href="">t.me/AnonyChat</a> &middot; --><a href="https://github.com/delfimov/anonychat">GitHub</a>
    </footer>
</div>



<script type="text/javascript">

  var wsUri = "<?=$wsUri?>";
  var websocket;

  var mainWrapper = document.getElementById("main");
  var connectWrapper = document.getElementById("connect-wrapper");
  var chatWrapper = document.getElementById("chat-wrapper");
  var connectButton = document.getElementById("connect-button");
  var disconnectButton = document.getElementById("disconnect-button");
  var connectName = document.getElementById("connect-name");
  var connectRoom = document.getElementById("connect-room");
  var nameColor = document.getElementById("name-color");
  var messageBox = document.getElementById("message-box");
  var messageSend = document.getElementById("message-send");
  var messageField = document.getElementById("message-field");
  var autoconnectField = document.getElementById("autoconnect-checkbox");

  var keepalive;

  var defaultUserColor = '#000';
  var usersColors = {};

  document.addEventListener("DOMContentLoaded", () => {
    if (window.requestIdleCallback) {
      requestIdleCallback(() => {
        init();
      });
    } else {
      setTimeout(() => {
        init();
      }, 500);
    }
  });

  function init() {
    if (typeof WebSocket === 'undefined') {
      showMessage({text: 'You can\'t use this chat because your browser doesn\'t support <a href="https://en.wikipedia.org/wiki/WebSocket" target="_blank" rel="nofollow noopener">WebSocket</a>.<br/><br/>Please install <a href="https://www.google.com/chrome/" target="_blank" rel="nofollow noopener">Google Chrome</a> or <a href="https://www.mozilla.org/ru/firefox/" target="_blank" rel="nofollow noopener">Mozilla Firefox</a>.', type: 'browser'});
    } else {
      initConnect();
      initSend();
    }
  }

  function initConnect() {
    if (localStorage.getItem('name')) {
      connectName.value = localStorage.getItem('name');
    }
    if (window.location.hash && window.location.hash.length > 1) {
      connectRoom.value = decodeURIComponent(window.location.hash.substring(1)).replace(/[^A-ZА-ЯёЁ0-9-_\.\/]/gi, '_');
    } else if (localStorage.getItem('room')) {
      connectRoom.value = localStorage.getItem('room');
    }
    if (localStorage.getItem('color')) {
      nameColor.value = localStorage.getItem('color');
    }
    if (localStorage.getItem('autoconnect') === 'yes') {
      websocketConnect(connectName.value, connectRoom.value, connectRoom.value);
    }
    disconnectButton.addEventListener('click', (e) => {
      e.preventDefault();
      // websocket.send(JSON.stringify({type: 'system', method: 'disconnect'}));
      websocketDisconnect();
    });
    connectButton.addEventListener('click', (e) => {
      e.preventDefault();
      if (connectName.value && connectRoom.value) {
        localStorage.setItem('name', connectName.value);
        localStorage.setItem('room', connectRoom.value);
        localStorage.setItem('color', nameColor.value);
        window.location.hash = '#' + encodeURIComponent(connectRoom.value);
        if (autoconnectField.checked) {
          localStorage.setItem('autoconnect', 'yes');
        }
        websocketConnect(connectName.value, connectRoom.value, nameColor.value)
      }
    });
  }

  function websocketDisconnect() {
    if (typeof websocket !== 'undefined') {
      websocket.close(1000, 'User disconnected');
    }
  }

  function websocketConnect(name, room, color) {
    // TODO: show loader
    websocket = new WebSocket(
      wsUri
      + '?user=' + encodeURIComponent(name)
      + '&room=' + encodeURIComponent(room)
      + '&color=' + encodeURIComponent(color)
    );
    websocket.onopen = function(e) { // connection is open
      showMessage({text: 'Messages are not stored on the server.<br>Messages are only visible to connected users.', type: 'system'});
      mainWrapper.classList.remove('main_disconnected');
      mainWrapper.classList.add('main_connected');
      connectName.disabled = true;
      connectRoom.disabled = true;
      messageField.disabled = false;
      messageSend.disabled = false;
      // keepalive = setInterval(function() {websocket.send(JSON.stringify({type: 'system', method: 'keepalive'}))}, 2000);
    }
    websocket.onerror = function(e) {
      showMessage({text: 'Ошибка', type: 'error'});
      // TODO: show error, show reconnect dialog
    }
    websocket.onclose = function(e) {
      mainWrapper.classList.add('main_disconnected');
      mainWrapper.classList.remove('main_connected');
      connectName.disabled = false;
      connectRoom.disabled = false;
      messageField.disabled = true;
      messageSend.disabled = true;
      // clearInterval(keepalive);
      showMessage({text: 'Connection closed. Code: ' + e.code, type: 'system'});
    };
    websocket.onmessage = function(e) {
      handleMessageData(e.data);
    };
  }

  function handleMessageData(data) {
    if (data && data.length > 0) {
      var response = JSON.parse(data);
      if (response && response.type) {
        if (response.type === 'text') {
          showMessage(response);
        } else if (response.type === 'system' && response.data) {
          if (response.data['user_name']) {
            setUserColor(response.data['user_color']);
            connectName.value = response.data['user_name'];
            connectRoom.value = response.data['room_name'];
            nameColor.value = response.data['user_color']['color'];
          }
          if (response.data['new_user']) {
            websocket.send(JSON.stringify({type: 'system', method: 'color', color: nameColor.value}));
            showMessage({text: 'New user: ' + response.data['new_user'], type: 'system'});
          }
          if (response.data['user_color']) {
            setUserColor(response.data['user_color']);
          }
          if (response.data['room_timeout']) {
            console.log('Room timeout: ', response.data['room_timeout']);
          }
          if (response.data['room_users']) {
            console.log('Room users: ', response.data['room_users']);
          }
          if (response.data['disconnected_users']) {
            showMessage({text: 'Disconnected user' + (response.data['disconnected_users'].length > 1 ? 's' : '') + ': ' + response.data['disconnected_users'].join(', '), type: 'system'});
            if (response.data['disconnected_users'].includes(connectName.value)) {
              websocketDisconnect();
            }
          }
          if (response.data['keepalive']) {
            console.log('Keepalive: ', response.data['keepalive']);
          }
        }
      }
    }
  }

  function initSend() {
    messageSend.addEventListener('click', (e) => {
      e.preventDefault();
      sendMessage();
    });

    messageField.addEventListener('keyup', function(e) {
      e.preventDefault();
      if (e.keyCode === 13) {
        sendMessage();
      }
    });

    nameColor.addEventListener('change', (e) => {
      localStorage.setItem('color', nameColor.value);
      setUserColor({user: connectName.value, color: nameColor.value});
      if (websocket.readyState === WebSocket.OPEN) {
        websocket.send(JSON.stringify({type: 'system', method: 'color', color: nameColor.value}));
      }
    });
  }

  function sendMessage() {
    if (messageField.value && typeof websocket !== 'undefined') { // not empty and connected
      // prepare json data
      var msg = {
        type: 'text',
        text: messageField.value
      };
      // convert and send data to server
      websocket.send(JSON.stringify(msg));
      messageField.value = ''; // reset message input
    }
  }

  function showMessage(response) {
    if (response.type === 'text') {
      messageBox.innerHTML += ''
        + '<div class="message message_' + response.type + '">'
        + '<span class="message__from" style="color:' + getUserColor(response.from) + '">' + response.from + '</span>: '
        + '<span class="message__text">' + response.text + '</span>'
        + '</div>';
    } else {
      messageBox.innerHTML += '<div class="message message_' + response.type + '">' + response.text + '</div>';
    }
    messageBox.scrollTop = messageBox.scrollHeight; // scroll to the top
  }

  function getUserColor(userName) {
    if (usersColors[userName]) {
      return usersColors[userName];
    } else {
      return defaultUserColor;
    }
  }

  function setUserColor(userColor) {
    usersColors[userColor.user] = userColor.color;
  }

</script>

</body>
</html>
