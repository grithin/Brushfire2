bf.view.items = {}
bf.view.items.multi = function(json, fieldHandler,keyHandlers){
  if(keyHandlers){
    var itemHandler = function(item){
      itemEle = $('<div class="item"></div>')
      for(var key in item){
        value = item[key]
        if(keyHandlers[key]){
          itemEle.append(keyHandlers[key](value, key, fieldHandler))
        }else{
          itemEle.append(fieldHandler(value, key))
        }
      }
      return itemEle;
    }
  }else{
    var itemHandler = function(item){
      itemEle = $('<div class="item"></div>')
      for(var key in item){
        value = item[key]
        itemEle.append(fieldHandler(value, key))
      }
      return itemEle;
    }
  }

  var itemsEle, itemEle;
  itemsEle = $('<div class="items"></div>')
  for(var i in json){
    itemsEle.append(itemHandler(json[i]))
  }
  return itemsEle
}
bf.view.items.factoryShortField = function(length){
  length = length || 100
  return function(value, key, format){
    if(value && value.length > length){
      value = value.substr(0,length)+'...'
    }
    return bf.view.items.field(value, key, format)
  }
}
bf.view.items.field = function(value, key, format){
  format = format || 'text'
  var ele = $('<div class="field"></div>')
  ele.append($('<div class="key"></div>').text(key))
  if(format == 'text'){
    ele.append($('<div class="value"></div>').text(value))
  }else{
    ele.append($('<div class="value"></div>').html(value))
  }
  return ele
}