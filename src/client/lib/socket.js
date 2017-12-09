var JsonPacker = {
    pack : function(data){
        return $.toJSON(data);
    },

    unpack : function(data){
        return $.evalJSON(data);
    }
};

var JSocket = function(address, protocol, packer) {
    this.packer = packer ? packer : JsonPacker;
    this.address = address;
    this.protocol = this._formatProtocol(protocol);
    this.socket = this.create();
};

JSocket.prototype = {
    _formatProtocol : function(protocol){
        if(typeof protocol === 'function'){
            protocol = {
                onMessage : protocol
            }
        }

        if(typeof protocol.onOpen !== 'function') {
            protocol.onOpen = function(e) {
            };
        }
        if(typeof protocol.onError !== 'function') {
            protocol.onError = function(e) {
            };
        }
        if(typeof protocol.onClose !== 'function') {
            protocol.onClose = function(e) {
            };
        }
        return protocol;
    },

    create : function(){
        var address = this.address;
        var protocol = this.protocol;
        var packer = this.packer;

        var socket = new WebSocket(address);
        socket.onopen = function (evt) {
            console.log("Connected to WebSocket server.");
            protocol.onOpen(evt);
        };

        socket.onclose = function (evt) {
            console.log("Disconnected");
            protocol.onClose(evt);
        };

        socket.onmessage = function (evt) {
            console.log(evt.data);
            protocol.onMessage(packer.unpack(evt.data), evt);
        };

        socket.onerror = function (evt, e) {
            protocol.onError(evt, e);
        };

        protocol.socket = this;
        return socket;
    },

    send : function(data, reconn, timeout){
        var socket = this.socket;
        if(socket.readyState === WebSocket.OPEN){
            this._realSend(data);
        } else if(reconn && socket.readyState === WebSocket.CLOSED) {
            this._connAndSend(data, timeout)
        } else {
            console.log("Connection is not ready");
        }
    },

    _realSend : function(data){
        this.socket.send(this.packer.pack(data));
    },

    _connAndSend : function(data, timeout){
        this.socket = this.create();
        var that = this;
        setTimeout(function(){
            console.log('socket.stat:' + that.socket.readyState);
            if(that.socket.readyState === WebSocket.OPEN){
                that._realSend(data);
            } else {
                console.log("Connection is not ready");
            }
        }, timeout ? timeout : 300);
    },

    close : function(){
        this.socket.close();
    }
};