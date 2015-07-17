bf.crud = {}
bf.crud = function(options){
  options = options || {}

  this.getWhere = function(){
    return $('#where [name="where"]').val()
  }
  this.setWhere = function(value){
    return $('#where [name="where"]').val(value)
  }
  this.getType = function(){
    return $('#where [name="type"]').val()
  }
  this.setType = function(value){
    return $('#where [name="type"]').val(value)
  }

  this.createOne = function(){
    $('#where').hide()
    console.log(this.table)
    var form = bf.view.form.model({scope:this.table,type:'createOne',submitText:'Create'})
    var submitOptions = {url:'/model/'+this.table,
      success:function(json){
        bf.view.sm.insert({type:'success',content:'Created with id '+json.value})}}
    form.submit(bf.view.form.submit.arg(submitOptions))
    this.content.append(form)
    return Q.promised()
  }
  this.deleteOne = function(where){
    var loadOptions = {type:'deleteOne',where:where}
    return bf.loadData(this.table,loadOptions).then(function(item){
      this.setWhere('')
      this.updateType('readMany')	}.bind(this))
  }
  this.updateOne = function(where){
    var form = bf.view.form.model({scope:this.table,type:'updateOne',submitText:'Update'})
    var submitOptions = {url:'/model/'+this.table,success:'Updated'}
    form.submit(bf.view.form.submit.arg(submitOptions))
    this.content.append(form)

    var loadOptions = {type:'readOne',where:where}
    return bf.loadData(this.table,loadOptions,{noCache:true}).then(function(item){
      bf.view.form.fillInputs(form,item)
      $('[type="datetime"]').each(bf.view.ele.utcFormat)	})

    return
  }
  this.readMany = function(where){
    var loadOptions = {type:'readMany',where:where,per:20,page:0}
    var that = this
    return bf.loadData(this.table,loadOptions,{noCache:true}).then(function(json){
      keyHandlers = {id:function(value,key,callback){
          value = '<span class="ln" data-id="'+value+'">'+value+'</span>'
          return callback(value,key,'html')
        },name:function(value,key,callback){
          var ele = callback(value,key,'html')
          ele.css({display:'block',float:'none'})
          return ele	}	}
      that.content.append(bf.view.items.multi(json.rows,bf.view.items.factoryShortField(),keyHandlers))
      that.content.delegate('[data-id]','click',function(){
        var id = $(this).attr('data-id')
        that.setWhere('{"id":'+id+'}')
        that.updateType('updateOne')	})

      json.info.url = '/model/'+that.table
      json.info.handlers = {dataHandler: function(rows){
        that.content.empty().append(bf.view.items.multi(rows,shortFieldHandler(),keyHandlers)); }}
      bf.view.ps.start(json.info)

        }).catch(bf.logError)
  }
  this.readOne = function(where){
    var loadOptions = {type:'readOne',where:where}
    return bf.loadData(this.table,loadOptions,{noCache:true}).then(function(item){
      this.content.append($('<pre></pre>').text(JSON.stringify(item,null,1)))	}.bind(this))
  }


  this.updateType = function(newType){
    if(newType){
      this.setType(newType)
    }
    var type = this.getType()

    this.content.empty()
    if(type == 'createOne'){
      return this.createOne()
    }

    $('#where').show()
    var where = this.getWhere()
    if(!where){
      where = {}
    }else{
      where = JSON.parse(where) }

    this[type](where)
  }

  //handle defaults,  get model data
  this.table = bf.url.requestVar('table') || options.table || ''
  type = bf.url.requestVar('type') || 'readMany'
  where = bf.url.requestVar('where') || options.where || ''

  this.setType(type)
  this.setWhere(where)

  this.content = $('#typeContent')

  $('#whereButton').click(function(){
    this.updateType()	}.bind(this))

  this.modelPromise = bf.modelPromise(this.table).then(function(value){
    for(var key in bf.model){
      if(key.charAt(0) != '_'){
        $('#crudHeader').append($('<a href="?table='+key+'">'+key+'</a>'))	}  }
    $('#crudHeader a:contains("'+this.table+'")').css({backgroundColor:'rgba(82, 206, 149,.5)'})
  }.bind(this)).catch(bf.logError)
}