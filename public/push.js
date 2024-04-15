function Push(options) {
    this.doNotConnect = 0;
    options = options || {};
    options.heartbeat = options.heartbeat || 25000;
    options.pingTimeout = options.pingTimeout || 10000;
    this.config = options;
    this.uid = 0;
    this.channels = {};
    this.connection = null;
    this.pingTimeoutTimer = 0;
    Push.instances.push(this);
    this.createConnection();
}

Push.prototype.checkoutPing = function () {
    var _this = this;
    _this.checkoutPingTimer && clearTimeout(_this.checkoutPingTimer);
    _this.checkoutPingTimer = setTimeout(function () {
        _this.checkoutPingTimer = 0;
        if (_this.connection.state === 'connected') {
            _this.connection.send('{"event":"pusher:ping","data":{}}');
            if (_this.pingTimeoutTimer) {
                clearTimeout(_this.pingTimeoutTimer);
                _this.pingTimeoutTimer = 0;
            }
            _this.pingTimeoutTimer = setTimeout(function () {
                _this.connection.closeAndClean();
                if (!_this.connection.doNotConnect) {
                    _this.connection.waitReconnect();
                }
            }, _this.config.pingTimeout);
        }
    }, this.config.heartbeat);
};

Push.prototype.channel = function (name) {
    return this.channels.find(name);
};
Push.prototype.allChannels = function () {
    return this.channels.all();
};
Push.prototype.createConnection = function () {
    if (this.connection) {
        throw Error('Connection already exist');
    }
    var _this = this;
    var url = this.config.url;

    function updateSubscribed() {
        for (var i in _this.channels) {
            _this.channels[i].subscribed = false;
        }
    }

    this.connection = new Connection({
        url: url,
        app_key: this.config.app_key,
        onOpen: function () {
            _this.connection.state = 'connecting';
            _this.checkoutPing();
        },
        onMessage: function (params) {
            if (_this.pingTimeoutTimer) {
                clearTimeout(_this.pingTimeoutTimer);
                _this.pingTimeoutTimer = 0;
            }

            params = JSON.parse(params.data);
            var event = params.event;
            var channel_name = params.channel;

            if (event === 'pusher:pong') {
                _this.checkoutPing();
                return;
            }
            if (event === 'pusher:error') {
                throw Error(params.data.message);
            }
            var data = JSON.parse(params.data), channel;
            if (event === 'pusher_internal:subscription_succeeded') {
                channel = _this.channels[channel_name];
                channel.subscribed = true;
                channel.processQueue();
                channel.emit('pusher:subscription_succeeded');
                return;
            }
            if (event === 'pusher:connection_established') {
                _this.connection.socket_id = data.socket_id;
                _this.connection.updateNetworkState('connected');
                _this.subscribeAll();
            }
            if (event.indexOf('pusher_internal') !== -1) {
                console.log("Event '" + event + "' not implement");
                return;
            }
            channel = _this.channels[channel_name];
            if (channel) {
                channel.emit(event, data);
            }
        },
        onClose: function () {
            updateSubscribed();
        },
        onError: function () {
            updateSubscribed();
        }
    });
};
Push.prototype.disconnect = function () {
    this.connection.doNotConnect = 1;
    this.connection.close();
};

Push.prototype.subscribeAll = function () {
    if (this.connection.state !== 'connected') {
        return;
    }
    for (var channel_name in this.channels) {
        //this.connection.send(JSON.stringify({event:"pusher:subscribe", data:{channel:channel_name}}));
        this.channels[channel_name].processSubscribe();
    }
};

Push.prototype.unsubscribe = function (channel_name) {
    if (this.channels[channel_name]) {
        delete this.channels[channel_name];
        if (this.connection.state === 'connected') {
            this.connection.send(JSON.stringify({event: "pusher:unsubscribe", data: {channel: channel_name}}));
        }
    }
};
Push.prototype.unsubscribeAll = function () {
    var channels = Object.keys(this.channels);
    if (channels.length) {
        if (this.connection.state === 'connected') {
            for (var channel_name in this.channels) {
                this.unsubscribe(channel_name);
            }
        }
    }
    this.channels = {};
};
Push.prototype.subscribe = function (channel_name) {
    if (this.channels[channel_name]) {
        return this.channels[channel_name];
    }
    if (channel_name.indexOf('private-') === 0) {
        return createPrivateChannel(channel_name, this);
    }
    if (channel_name.indexOf('presence-') === 0) {
        return createPresenceChannel(channel_name, this);
    }
    return createChannel(channel_name, this);
};
Push.instances = [];

function createChannel(channel_name, push) {
    var channel = new Channel(push.connection, channel_name);
    push.channels[channel_name] = channel;
    channel.subscribeCb = function () {
        push.connection.send(JSON.stringify({event: "pusher:subscribe", data: {channel: channel_name}}));
    }
    channel.processSubscribe();
    return channel;
}

function createPrivateChannel(channel_name, push) {
    var channel = new Channel(push.connection, channel_name);
    push.channels[channel_name] = channel;
    channel.subscribeCb = function () {
        __ajax({
            url: push.config.auth,
            type: 'POST',
            data: {channel_name: channel_name, socket_id: push.connection.socket_id},
            success: function (data) {
                data = JSON.parse(data);
                data.channel = channel_name;
                push.connection.send(JSON.stringify({event: "pusher:subscribe", data: data}));
            },
            error: function (e) {
                throw Error(e);
            }
        });
    };
    channel.processSubscribe();
    return channel;
}

function createPresenceChannel(channel_name, push) {
    return createPrivateChannel(channel_name, push);
}

/*window.addEventListener('online',  function(){
    var con;
    for (var i in Push.instances) {
        con = Push.instances[i].connection;
        con.reconnectInterval = 1;
        if (con.state === 'connecting') {
            con.connect();
        }
    }
});*/


function Connection(options) {
    this.dispatcher = new Dispatcher();
    __extends(this, this.dispatcher);
    var properies = ['on', 'off', 'emit'];
    for (var i in properies) {
        this[properies[i]] = this.dispatcher[properies[i]];
    }
    this.options = options;
    this.state = 'initialized'; //initialized connecting connected disconnected
    this.doNotConnect = 0;
    this.reconnectInterval = 1;
    this.connection = null;
    this.reconnectTimer = 0;
    this.connect();
}

Connection.prototype.updateNetworkState = function (state) {
    var old_state = this.state;
    this.state = state;
    if (old_state !== state) {
        this.emit('state_change', {previous: old_state, current: state});
    }
};

Connection.prototype.connect = function () {
    this.doNotConnect = 0;
    if (this.state === 'connected') {
        console.log('networkState is "' + this.state + '" and do not need connect');
        return;
    }
    if (this.reconnectTimer) {
        clearTimeout(this.reconnectTimer);
        this.reconnectTimer = 0;
    }

    this.closeAndClean();

    var options = this.options;
    var websocket = new WebSocket(options.url + '/app/' + options.app_key);

    this.updateNetworkState('connecting');

    var _this = this;
    websocket.onopen = function (res) {
        _this.reconnectInterval = 1;
        if (_this.doNotConnect) {
            _this.updateNetworkState('disconnected');
            websocket.close();
            return;
        }
        if (options.onOpen) {
            options.onOpen(res);
        }
    };

    if (options.onMessage) {
        websocket.onmessage = options.onMessage;
    }

    websocket.onclose = function (res) {
        websocket.onmessage = websocket.onopen = websocket.onclose = websocket.onerror = null;
        _this.updateNetworkState('disconnected');
        if (!_this.doNotConnect) {
            _this.waitReconnect();
        }
        if (options.onClose) {
            options.onClose(res);
        }
    };

    websocket.onerror = function (res) {
        _this.close();
        if (!_this.doNotConnect) {
            _this.waitReconnect();
        }
        if (options.onError) {
            options.onError(res);
        }
    };
    this.connection = websocket;
}

Connection.prototype.closeAndClean = function () {
    if (this.connection) {
        var websocket = this.connection;
        websocket.onmessage = websocket.onopen = websocket.onclose = websocket.onerror = null;
        try {
            websocket.close();
        } catch (e) {
        }
        this.updateNetworkState('disconnected');
    }
};

Connection.prototype.waitReconnect = function () {
    if (this.state === 'connected' || this.state === 'connecting') {
        return;
    }
    if (!this.doNotConnect) {
        this.updateNetworkState('connecting');
        var _this = this;
        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
        }
        this.reconnectTimer = setTimeout(function () {
            _this.connect();
        }, this.reconnectInterval);
        if (this.reconnectInterval < 1000) {
            this.reconnectInterval = 1000;
        } else {
            // 每次重连间隔增大一倍
            this.reconnectInterval = this.reconnectInterval * 2;
        }
        // 有网络的状态下，重连间隔最大2秒
        if (this.reconnectInterval > 2000 && navigator.onLine) {
            _this.reconnectInterval = 2000;
        }
    }
}

Connection.prototype.send = function (data) {
    if (this.state !== 'connected') {
        console.trace('networkState is "' + this.state + '", can not send ' + data);
        return;
    }
    this.connection.send(data);
}

Connection.prototype.close = function () {
    this.updateNetworkState('disconnected');
    this.connection.close();
}

var __extends = (this && this.__extends) || function (d, b) {
    for (var p in b) if (b.hasOwnProperty(p)) {
        d[p] = b[p];
    }

    function __() {
        this.constructor = d;
    }

    d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
};

function Channel(connection, channel_name) {
    this.subscribed = false;
    this.dispatcher = new Dispatcher();
    this.connection = connection;
    this.channelName = channel_name;
    this.subscribeCb = null;
    this.queue = [];
    __extends(this, this.dispatcher);
    var properies = ['on', 'off', 'emit'];
    for (var i in properies) {
        this[properies[i]] = this.dispatcher[properies[i]];
    }
}

Channel.prototype.processSubscribe = function () {
    if (this.connection.state !== 'connected') {
        return;
    }
    this.subscribeCb();
};

Channel.prototype.processQueue = function () {
    if (this.connection.state !== 'connected' || !this.subscribed) {
        return;
    }
    for (var i in this.queue) {
        this.queue[i]();
    }
    this.queue = [];
};

Channel.prototype.trigger = function (event, data) {
    if (event.indexOf('client-') !== 0) {
        throw new Error("Event '" + event + "' should start with 'client-'");
    }
    var _this = this;
    this.queue.push(function () {
        _this.connection.send(JSON.stringify({event: event, data: data, channel: _this.channelName}));
    });
    this.processQueue();
};

////////////////
var Collections = (function () {
    var exports = {};

    function extend(target) {
        var sources = [];
        for (var _i = 1; _i < arguments.length; _i++) {
            sources[_i - 1] = arguments[_i];
        }
        for (var i = 0; i < sources.length; i++) {
            var extensions = sources[i];
            for (var property in extensions) {
                if (extensions[property] && extensions[property].constructor &&
                    extensions[property].constructor === Object) {
                    target[property] = extend(target[property] || {}, extensions[property]);
                } else {
                    target[property] = extensions[property];
                }
            }
        }
        return target;
    }

    exports.extend = extend;

    function stringify() {
        var m = ["Push"];
        for (var i = 0; i < arguments.length; i++) {
            if (typeof arguments[i] === "string") {
                m.push(arguments[i]);
            } else {
                m.push(safeJSONStringify(arguments[i]));
            }
        }
        return m.join(" : ");
    }

    exports.stringify = stringify;

    function arrayIndexOf(array, item) {
        var nativeIndexOf = Array.prototype.indexOf;
        if (array === null) {
            return -1;
        }
        if (nativeIndexOf && array.indexOf === nativeIndexOf) {
            return array.indexOf(item);
        }
        for (var i = 0, l = array.length; i < l; i++) {
            if (array[i] === item) {
                return i;
            }
        }
        return -1;
    }

    exports.arrayIndexOf = arrayIndexOf;

    function objectApply(object, f) {
        for (var key in object) {
            if (Object.prototype.hasOwnProperty.call(object, key)) {
                f(object[key], key, object);
            }
        }
    }

    exports.objectApply = objectApply;

    function keys(object) {
        var keys = [];
        objectApply(object, function (_, key) {
            keys.push(key);
        });
        return keys;
    }

    exports.keys = keys;

    function values(object) {
        var values = [];
        objectApply(object, function (value) {
            values.push(value);
        });
        return values;
    }

    exports.values = values;

    function apply(array, f, context) {
        for (var i = 0; i < array.length; i++) {
            f.call(context || (window), array[i], i, array);
        }
    }

    exports.apply = apply;

    function map(array, f) {
        var result = [];
        for (var i = 0; i < array.length; i++) {
            result.push(f(array[i], i, array, result));
        }
        return result;
    }

    exports.map = map;

    function mapObject(object, f) {
        var result = {};
        objectApply(object, function (value, key) {
            result[key] = f(value);
        });
        return result;
    }

    exports.mapObject = mapObject;

    function filter(array, test) {
        test = test || function (value) {
            return !!value;
        };
        var result = [];
        for (var i = 0; i < array.length; i++) {
            if (test(array[i], i, array, result)) {
                result.push(array[i]);
            }
        }
        return result;
    }

    exports.filter = filter;

    function filterObject(object, test) {
        var result = {};
        objectApply(object, function (value, key) {
            if ((test && test(value, key, object, result)) || Boolean(value)) {
                result[key] = value;
            }
        });
        return result;
    }

    exports.filterObject = filterObject;

    function flatten(object) {
        var result = [];
        objectApply(object, function (value, key) {
            result.push([key, value]);
        });
        return result;
    }

    exports.flatten = flatten;

    function any(array, test) {
        for (var i = 0; i < array.length; i++) {
            if (test(array[i], i, array)) {
                return true;
            }
        }
        return false;
    }

    exports.any = any;

    function all(array, test) {
        for (var i = 0; i < array.length; i++) {
            if (!test(array[i], i, array)) {
                return false;
            }
        }
        return true;
    }

    exports.all = all;

    function encodeParamsObject(data) {
        return mapObject(data, function (value) {
            if (typeof value === "object") {
                value = safeJSONStringify(value);
            }
            return encodeURIComponent(base64_1["default"](value.toString()));
        });
    }

    exports.encodeParamsObject = encodeParamsObject;

    function buildQueryString(data) {
        var params = filterObject(data, function (value) {
            return value !== undefined;
        });
        return map(flatten(encodeParamsObject(params)), util_1["default"].method("join", "=")).join("&");
    }

    exports.buildQueryString = buildQueryString;

    function decycleObject(object) {
        var objects = [], paths = [];
        return (function derez(value, path) {
            var i, name, nu;
            switch (typeof value) {
                case 'object':
                    if (!value) {
                        return null;
                    }
                    for (i = 0; i < objects.length; i += 1) {
                        if (objects[i] === value) {
                            return {$ref: paths[i]};
                        }
                    }
                    objects.push(value);
                    paths.push(path);
                    if (Object.prototype.toString.apply(value) === '[object Array]') {
                        nu = [];
                        for (i = 0; i < value.length; i += 1) {
                            nu[i] = derez(value[i], path + '[' + i + ']');
                        }
                    } else {
                        nu = {};
                        for (name in value) {
                            if (Object.prototype.hasOwnProperty.call(value, name)) {
                                nu[name] = derez(value[name], path + '[' + JSON.stringify(name) + ']');
                            }
                        }
                    }
                    return nu;
                case 'number':
                case 'string':
                case 'boolean':
                    return value;
            }
        }(object, '$'));
    }

    exports.decycleObject = decycleObject;

    function safeJSONStringify(source) {
        try {
            return JSON.stringify(source);
        } catch (e) {
            return JSON.stringify(decycleObject(source));
        }
    }

    exports.safeJSONStringify = safeJSONStringify;
    return exports;
})();

var Dispatcher = (function () {
    function Dispatcher(failThrough) {
        this.callbacks = new CallbackRegistry();
        this.global_callbacks = [];
        this.failThrough = failThrough;
    }

    Dispatcher.prototype.on = function (eventName, callback, context) {
        this.callbacks.add(eventName, callback, context);
        return this;
    };
    Dispatcher.prototype.on_global = function (callback) {
        this.global_callbacks.push(callback);
        return this;
    };
    Dispatcher.prototype.off = function (eventName, callback, context) {
        this.callbacks.remove(eventName, callback, context);
        return this;
    };
    Dispatcher.prototype.emit = function (eventName, data) {
        var i;
        for (i = 0; i < this.global_callbacks.length; i++) {
            this.global_callbacks[i](eventName, data);
        }
        var callbacks = this.callbacks.get(eventName);
        if (callbacks && callbacks.length > 0) {
            for (i = 0; i < callbacks.length; i++) {
                callbacks[i].fn.call(callbacks[i].context || (window), data);
            }
        } else if (this.failThrough) {
            this.failThrough(eventName, data);
        }
        return this;
    };
    return Dispatcher;
}());

var CallbackRegistry = (function () {
    function CallbackRegistry() {
        this._callbacks = {};
    }

    CallbackRegistry.prototype.get = function (name) {
        return this._callbacks[prefix(name)];
    };
    CallbackRegistry.prototype.add = function (name, callback, context) {
        var prefixedEventName = prefix(name);
        this._callbacks[prefixedEventName] = this._callbacks[prefixedEventName] || [];
        this._callbacks[prefixedEventName].push({
            fn: callback,
            context: context
        });
    };
    CallbackRegistry.prototype.remove = function (name, callback, context) {
        if (!name && !callback && !context) {
            this._callbacks = {};
            return;
        }
        var names = name ? [prefix(name)] : Collections.keys(this._callbacks);
        if (callback || context) {
            this.removeCallback(names, callback, context);
        } else {
            this.removeAllCallbacks(names);
        }
    };
    CallbackRegistry.prototype.removeCallback = function (names, callback, context) {
        Collections.apply(names, function (name) {
            this._callbacks[name] = Collections.filter(this._callbacks[name] || [], function (oning) {
                return (callback && callback !== oning.fn) ||
                    (context && context !== oning.context);
            });
            if (this._callbacks[name].length === 0) {
                delete this._callbacks[name];
            }
        }, this);
    };
    CallbackRegistry.prototype.removeAllCallbacks = function (names) {
        Collections.apply(names, function (name) {
            delete this._callbacks[name];
        }, this);
    };
    return CallbackRegistry;
}());

function prefix(name) {
    return "_" + name;
}

function __ajax(options) {
    options = options || {};
    options.type = (options.type || 'GET').toUpperCase();
    options.dataType = options.dataType || 'json';
    params = formatParams(options.data);

    var xhr;
    if (window.XMLHttpRequest) {
        xhr = new XMLHttpRequest();
    } else {
        xhr = ActiveXObject('Microsoft.XMLHTTP');
    }

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            var status = xhr.status;
            if (status >= 200 && status < 300) {
                options.success && options.success(xhr.responseText, xhr.responseXML);
            } else {
                options.error && options.error(status);
            }
        }
    }

    if (options.type === 'GET') {
        xhr.open('GET', options.url + '?' + params, true);
        xhr.send(null);
    } else if (options.type === 'POST') {
        xhr.open('POST', options.url, true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.send(params);
    }
}

function formatParams(data) {
    var arr = [];
    for (var name in data) {
        arr.push(encodeURIComponent(name) + '=' + encodeURIComponent(data[name]));
    }
    return arr.join('&');
}
