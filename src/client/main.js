
var JChat =  function(address) {
  this.conn = new JSocket(address, this);
  this.data = {
    messages : [],
    files : [],
    file : '',
    index: 0
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
    } else {
      if(!data.index){
        data.index = 0;
      }
      this.data.messages.push(data);
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
    var elBox = $('#messages');
    elBox.scrollTop(elBox[0].scrollHeight - elBox.height() + 250);
  }, 100);
};

chat.on('message.add', gotoBottom);

var vm = new Vue({
  el:'#app',
  data : chat.data,
  methods : {
    selFile : function(file){
      console.log(file);
      this.file = file;
      this.index = this.findIndex(file);
      gotoBottom();
    },

    findIndex: function(file){
      for(var i =0; i < this.files.length; i++){
        if(file === this.files[i]){
          return i;
        }
      }
      return 0;
    }
  },

  computed: {
    items: function () {
      var that = this;
      return this.messages.filter(function (item) {
        console.log(item.index);
        console.log(that.index);
        return item.index == that.index;
      })
    }
  }
});