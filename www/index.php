<?php

require_once __DIR__ . '/../vendor/autoload.php';

use AnonyChat\Config\Config;

header('Content-Type: text/html; charset=utf-8');

$wsUri = 'ws://' . Config::get('server') . ':' . Config::get('port');

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
        .hidden {
            display: none;
        }
        .message_system, .message_service {
            color: #999;
        }
        .main_disconnected #chat-wrapper,
        .main_disconnected #disconnect-button,
        .main_connected #connect-button,
        .main_connected #autoconnect-wrapper {
            display: none;
        }
        .main_disconnected #autoconnect-wrapper,
        .main_connected #chat-wrapper {
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
                <div class="col-12" id="autoconnect-wrapper">
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
                    <input type="text" class="form-control" id="message-field" placeholder="Message">
                </div>
                <div class="col-auto">
                    <button type="submit" id="message-send" class="btn btn-primary">Send</button>
                </div>
            </form>
        </div>


    </main>
    <footer class="pt-5 my-5 text-muted border-top">
        <!--<a href="">t.me/AnonyChat</a> &middot; --><a href="https://github.com/delfimov/anonychat">github.com/delfimov/anonychat</a> &copy; 2022
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
      // show error message and suggest to install a modern browser (Chrome or Firefox)
    } else {
      initConnect();
      initSend();
    }
  }

  function initConnect() {
    if (localStorage.getItem('autoconnect') === 'yes') {
      websocketConnect(localStorage.getItem('name'), localStorage.getItem('room'), localStorage.getItem('color'));
    }
    disconnectButton.addEventListener('click', (e) => {
      e.preventDefault();
      websocketDisconnect();
    });
    connectButton.addEventListener('click', (e) => {
      e.preventDefault();
      if (connectName.value && connectRoom.value) {
        if (autoconnectField.checked) {
          localStorage.setItem('autoconnect', 'yes');
          localStorage.setItem('name', connectName.value);
          localStorage.setItem('room', connectRoom.value);
          localStorage.setItem('color', nameColor.value);
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
      messageBox.scrollTop = messageBox.scrollHeight; // scroll to the top
      mainWrapper.classList.remove('main_disconnected');
      mainWrapper.classList.add('main_connected');
    }
    websocket.onerror = function(e) {
      showMessage({text: 'Ошибка ' + e.data, type: 'error'});
      // TODO: show error, show reconnect dialog
    }
    websocket.onclose = function(e) {
      mainWrapper.classList.add('main_disconnected');
      mainWrapper.classList.remove('main_connected');
      showMessage({text: 'Connection closed.', type: 'system'});
    };
    websocket.onmessage = function(e) {
      var response = JSON.parse(e.data);
      switch (response.type) {
        case 'text':
          showMessage(response);
          break;
        case 'service':
          showMessage(response);
          break;
        case 'color':
          setUserColor(response);
          break;
      }
      messageBox.scrollTop = messageBox.scrollHeight; // scroll to the top
    };
  }

  function initSend() {
    messageSend.addEventListener("click", (e) => {
      e.preventDefault();
      sendMessage();
    });

    messageField.addEventListener("keyup", function(e) {
      e.preventDefault();
      if (e.keyCode === 13) {
        sendMessage();
      }
    });
  }

  function sendMessage() {
    if (messageField.value && typeof websocket !== 'undefined') { // not empty and connected
      // prepare json data
      var msg = {
        type: 'text',
        text: messageField.value,
        room: connectRoom.value
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
  }

  function getUserColor(userName) {
    if (usersColors[userName]) {
      return usersColors[userName];
    } else {
      return defaultUserColor;
    }
  }

  function setUserColor(response) {
    usersColors[response.from] = response.text;
  }

</script>

</body>
</html>
