var JEvent = function(){

};

JEvent.prototype = {
    listeners : {},

    on : function(evt, callback){
        if(!this.listeners[evt]){
            this.listeners[evt] = [];
        }
        this.listeners[evt].push(callback);
        return this;
    },

    trigger : function(evt){
        if(!this.listeners[evt]){
            return;
        }

        args = [].slice.call( arguments, 1);
        var len = this.listeners[evt].length;
        for(var i = 0; i < len; i++){
            this.listeners[evt][i].apply(this, args);
        }
    }
};