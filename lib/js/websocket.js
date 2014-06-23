
if (typeof PHPMake === "undefined" || !PHPMake) {
   var PHPMake = {};
}

(function() {

function FirmataWebSocket (options) {
    this.options = options || {};
    this.signatureIndex = 1;
    this.callbackTable = {};
    this.digitalPinObservers = [];
    this.analogPinObservers = [];

    this.capabilities;
    this.host = this.options.host || location.hostname;
    this.port = this.options.port;
    this.secure = this.options.secure ? true : false;
    this.url = (this.secure ? "wss" : "ws") + '://'
                + this.host + (this.port ? ':' + this.port : '');
    this.ws = new WebSocket(this.url);

    var that = this;
    this.ws.onopen = function(evt) { that.onopen(evt); };
    this.ws.onclose = function(evt) { that.onclose(evt); };
    this.ws.onerror = function(evt) { that.onerror(evt); };
    this.ws.onmessage = function(evt) { that.onmessage(evt); };
};

FirmataWebSocket.prototype.queryPinState = function(pin, callback) {
    var signature = this.addCallback(callback);
    this.send(JSON.stringify({
        command: "queryPinState",
        signature: signature,
        arguments: [pin]
    }));
};

FirmataWebSocket.prototype.queryPinInputState = function(pin, callback) {
    var signature = this.addCallback(callback);
    this.send(JSON.stringify({
        command: "queryPinInputState",
        signature: signature,
        arguments: [pin]
    }));
};

FirmataWebSocket.prototype.addCallback = function(callback) {
    var signature = null;
    if (callback) {
        signature = "sig-" + this.signatureIndex;
        this.callbackTable[signature] = callback;
        if (this.signatureIndex == 2147483647) {
            this.signatureIndex = 1;
        } else {
            this.signatureIndex++;
        }
    }

    return signature;
};

FirmataWebSocket.prototype.invokeCallback = function (signature, response) {
    if (signature in this.callbackTable) {
        (this.callbackTable[signature])(response);
        delete this.callbackTable[signature];
    }
};

FirmataWebSocket.prototype.analogWrite = function(pin, state, callback) {
    var signature = this.addCallback(callback);
    this.send(JSON.stringify({
        command: "analogWrite",
        signature: signature,
        arguments: [pin, state]
    }));
};

FirmataWebSocket.prototype.digitalWrite = function(pin, state, callback) {
    var signature = this.addCallback(callback);
    this.send(JSON.stringify({
        command: "digitalWrite",
        signature: signature,
        arguments: [pin, state]
    }));
};

FirmataWebSocket.prototype.getCapabilities = function() {
    return this.capabilities;
};

FirmataWebSocket.prototype.queryCapability = function(callback) {
    var signature = this.addCallback(callback);
    this.send(JSON.stringify({
        command: "queryCapability",
        signature: signature,
        arguments: []
    }));
};

FirmataWebSocket.prototype.processQueryCapability = function(capabilities) {
    this.capabilities = capabilities;
    console.log(this.capabilities);
};

FirmataWebSocket.prototype.addDigitalPinObserver = function(observer) {
    this.digitalPinObservers.push(observer);
};

FirmataWebSocket.prototype.removeDigitalPinObserver = function(observer) {
    var index = -1;
    for (var i = 0; i < this.digitalPinObservers.length; i++) {
        if (observer == this.digitalPinObservers[i]) {
            delete this.digitalPinObservers[i];
            index = i;
            break;
        }
    }

    if (index != -1) {
        this.digitalPinObservers.splice(index, 1);
    }
};

FirmataWebSocket.prototype.processDigitalRead = function(responseData) {
    for (var i = 0; i < this.digitalPinObservers.length; i++) {
        var observer = this.digitalPinObservers[i];
        observer(this, responseData.pin, responseData.state);
    }
};

FirmataWebSocket.prototype.addAnalogPinObserver = function(observer) {
    this.analogPinObservers.push(observer);
};

FirmataWebSocket.prototype.removeAnalogPinObserver = function(observer) {
    var index = -1;
    for (var i = 0; i < this.analogPinObservers.length; i++) {
        if (observer == this.analogPinObservers[i]) {
            delete this.analogPinObservers[i];
            index = i;
            break;
        }
    }

    if (index != -1) {
        this.analogPinObservers.splice(index, 1);
    }
};

FirmataWebSocket.prototype.processAnalogRead = function(responseData) {
    for (var i = 0; i < this.analogPinObservers.length; i++) {
        var observer = this.analogPinObservers[i];
        observer(this, responseData.pin, responseData.state);
    }
};

FirmataWebSocket.prototype.onopen = function(evt) {
    this.queryCapability(this.options.onopen);
};
FirmataWebSocket.prototype.onclose = function(evt) {
    if (this.options.onclose) {
        this.options.onclose(evt);
    }
};
FirmataWebSocket.prototype.onmessage = function(message) {
    var response = JSON.parse(message.data);
    switch (response.command) {
        case 'queryCapability':
            this.processQueryCapability(response.data);
            break;
        case 'digitalRead':
            this.processDigitalRead(response.data);
            break;
        case 'analogRead':
            this.processAnalogRead(response.data);
            break;
    }

    this.invokeCallback(response.signature, response);
};
FirmataWebSocket.prototype.onerror = function(evt) {
    if (this.options.onerror) {
        this.options.onerror(evt);
    }
};
FirmataWebSocket.prototype.send = function(message) {
    this.ws.send(message);
};
FirmataWebSocket.prototype.close = function() {
    this.ws.close();
}

PHPMake.FirmataWebSocket = FirmataWebSocket;

})();


