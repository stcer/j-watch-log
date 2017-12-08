
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
    ]
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
      if(!data.index){
        data.index = 0;
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

var vm = new Vue({
  el:'#app',
  data : chat.data,
  methods : {
    selFile : function(index){
      Vue.set(this.winMap, this.crtWindow, index)
      gotoBottom();
    },

    setWindows: function(n){
      this.windows = n;
    },

    selWindow: function(index){
      this.crtWindow = index;
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
