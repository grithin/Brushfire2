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

  this.createOne = function(options){
    options = options || {}
    $('#where').hide()
    var form = bf.view.form.model({scope:this.table,type:'createOne',submitText:'Create'})
    var submitOptions = {url:'/model/'+this.table,
      success: options.success || function(json){
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
    var loadOptions = {type:'readMany',where:where,per:50,page:0}
    var that = this
    return Q.next(bf.loadData(this.table,loadOptions,{noCache:true}).then(function(json){
      keyHandlers = {id:function(value,key,callback){
          value = '<span class="ln" data-id="'+value+'">'+value+'</span>'
          return callback(value,key,'html')
        },name:function(value,key,callback){
          var ele = callback(value,key,'html')
          ele.css({display:'block',float:'none'})
          return ele	}	}
      /*
      that.content.append(bf.view.items.multi(json.rows,bf.view.items.factoryShortField(),keyHandlers))
      that.content.delegate('[data-id]','click',function(){
        var id = $(this).attr('data-id')
        that.setWhere('{"id":'+id+'}')
        that.updateType('updateOne')	})
      */

      json.info.url = '/model/'+that.table
      json.info.handlers = {dataHandler: function(rows){
        that.content.empty()
        that.content.append(bf.view.items.multi(rows,bf.view.items.factoryShortField(),keyHandlers)); }}
      bf.view.ps.start(json.info)
      json.info.handlers.dataHandler(json.rows)
      return json

    }))
  }
  this.readOne = function(where){
    var loadOptions = {type:'readOne',where:where}
    var promise = bf.loadData(this.table,loadOptions,{noCache:true}).then(function(item){
      this.content.append($('<pre></pre>').text(JSON.stringify(item,null,1)))
      return item }.bind(this))
    promise.done()
    return promise
  }


  this.updateType = function(newType){
    if(newType){
      this.setType(newType)
    }
    var type = this.getType()
    this.content.empty()
    if(type == 'createOne'){
      //history.pushState({type:type},'CRUD UPDATE',bf.url.appends({type:type},null,true))
      return this.createOne()
    }

    $('#where').show()
    var where = this.getWhere()
    if(!where){
      where = {}
    }else{
      where = eval('r = '+where) }

    history.pushState({type:type,where:where},'CRUD Page',bf.url.appends({type:type,where:JSON.stringify(where)},null,true))

    return {type:type,where:where,return:this[type](where)}
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
