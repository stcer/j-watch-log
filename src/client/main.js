
var JChat =  function(address) {
  this.conn = new JSocket(address, this);
  this.data = {
    messages : [
      [], [], [], [], [],
      [], [], [], [], [],
      [], [], [], [], []
    ],

    files : [],

    windows: 2,
    crtWindow: 0,
    winMap:[
      0, 1, 2, 3
    ],

    input: ''
  };
};

$.extend(JChat.prototype, (new JEvent()), {
  onOpen : function(e) {
    console.log('connected');
  },

  addMessage: function(data, type){
    if(type)
      data.type = type;

    if(data.type === 'files'){
      this.data.files = data.files;
      this.trigger('files.change', data);
    } else {
      if(typeof data.index === 'undefined'){
        data.index = this.data.winMap[this.data.crtWindow];
      }
      this.data.messages[data.index].push(data);
      this.trigger('message.add', data);
    }
  },

  onMessage : function(data, e) {
    this.addMessage(data);
  },

  onClose : function(e) {
    this.addMessage('closed', 'cmd');
  }
});

// main
var chat = new JChat('ws://' + location.host);
var gotoBottom = function(){
  // for vue dom
  setTimeout(function(){
    var elBox = $('.content');
    elBox.each(function(index, el){
        $(this).scrollTop(this.scrollHeight, $(this).height() + 250)
    });
  }, 100);
};

chat.on('message.add', gotoBottom);

$(document).keydown(function(event){
  if (event.which == 13 ) {
      event.preventDefault();
      chat.addMessage({msg: "\n"});
  }
});

var vm = new Vue({
  el:'#app',
  data : chat.data,
  methods : {
    selFile : function(index){
      Vue.set(this.winMap, this.crtWindow, index)
      console.log(this.winMap);
      gotoBottom();
    },

    setWindows: function(n){
      this.windows = n;
    },

    selWindow: function(index){
      this.crtWindow = index;
    },

    add: function(){
      chat.conn.send({cmd : 'add', 'file': this.input}, true);
    },

    restart: function(){
      if(!confirm('Ready?')){
        return;
      }

      chat.conn.send({cmd : 'restart'}, true);

      setTimeout(function(){
        chat.conn.create();
      }, 2000);
    },

    newline: function () {
      chat.addMessage({msg: "\n"});
    }
  },

  computed: {
    items: function () {
      var that = this;
      return this.messages.filter(function (item) {
        return item.index === that.index;
      })
    }
  }
});
