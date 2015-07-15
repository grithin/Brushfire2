bf.crud = {}
bf.crud = function(options){
  options = options || {}
  this.table = bf.url.requestVar('table') || options.table || ''
  type = bf.url.requestVar('type') || 'readMany'
  where = bf.url.requestVar('where') || options.where || ''

  $('#where [name="type"]').val(type)
  $('#where [name="where"]').val(where)

  this.content = $('#typeContent')

  $('#whereButton').click(function(){
    this.updateType()	}.bind(this))

  bf.modelPromise(this.table).then(function(value){
    this.updateType()
    for(var key in bf.model){
      if(key.charAt(0) != '_'){
        $('#crudHeader').append($('<a href="?table='+key+'">'+key+'</a>'))	}
    }
  }.bind(this)).catch(bf.logError)

}
bf.crud.prototype.createOne = function(){
  $('#where').hide()
  var form = bf.view.form.model({scope:this.table,type:'createOne',submitText:'Create'})
  var submitOptions = {url:'/model/'+this.table,
    success:function(json){
      bf.view.sm.insert({type:'success',content:'Created with id '+json.value})}}
  form.submit(bf.view.form.submit.arg(submitOptions))
  this.content.append(form)
}
bf.crud.prototype.deleteOne = function(where){
  var loadOptions = {type:'deleteOne',where:where}
  bf.loadData(this.table,loadOptions).then(function(item){
    $('#where [name="where"]').val('')
    this.updateType('readMany')	}.bind(this))
}
bf.crud.prototype.updateOne = function(where){
  var form = bf.view.form.model({scope:this.table,type:'updateOne',submitText:'Update'})
  var submitOptions = {url:'/model/'+this.table,success:'Updated'}
  form.submit(bf.view.form.submit.arg(submitOptions))
  this.content.append(form)

  var loadOptions = {type:'readOne',where:where}
  bf.loadData(this.table,loadOptions,{noCache:true}).then(function(item){
    bf.view.form.fillInputs(form,item)
    $('[type="datetime"]').each(bf.view.ele.utcFormat)	})

  return
}
bf.crud.prototype.readMany = function(where){
  var loadOptions = {type:'readMany',where:where,per:20,page:0}
  var that = this
  bf.loadData(this.table,loadOptions,{noCache:true}).then(function(json){
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
      $('#where [name="where"]').val('{"id":'+id+'}')
      that.updateType('updateOne')	})

    json.info.url = '/model/'+that.table
    json.info.handlers = {dataHandler: function(rows){
      that.content.empty().append(bf.view.items.multi(rows,shortFieldHandler(),keyHandlers)); }}
    bf.view.ps.start(json.info)

      }).catch(bf.logError)
}
bf.crud.prototype.readOne = function(where){
  var loadOptions = {type:'readOne',where:where}
  bf.loadData(this.table,loadOptions,{noCache:true}).then(function(item){
    this.content.append($('<pre></pre>').text(JSON.stringify(item,null,1)))	}.bind(this))
}


bf.crud.prototype.updateType = function(newType){
  if(newType){
    $('#where [name="type"]').val(newType)
  }
  var type = $('#where [name="type"]').val()

  this.content.empty()
  if(type == 'createOne'){
    return this.createOne()
  }

  $('#where').show()
  var where = $('#where [name="where"]').val()
  if(!where){
    where = {}
  }else{
    where = JSON.parse(where) }

  this[type](where)
}
