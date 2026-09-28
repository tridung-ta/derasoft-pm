var state = 'none';
function showhide(layer_ref){
if(state == 'block'){
state = 'none';
}else{
state = 'block';
}
if(document.all){ //IS IE 4 or 5 (or 6 beta)
eval("document.all." +layer_ref+ ".style.display = state");
}
if (document.layers) { //IS NETSCAPE 4 or below
document.layers[layer_ref].display = state;
}
if (document.getElementById &&!document.all) {
hza = document.getElementById(layer_ref);
hza.style.display = state;
}
}


function initPopupBill(){
var relativeLink = '';
var permanentLink = '';
var domainLink = '';
if($('valueRelative')) relativeLink = $('valueRelative').value;
if($('valueFull')) permanentLink = $('valueFull').value;
if($('valuePrivate')) domainLink = $('valuePrivate').value;
var listIcons = $$('span.check');
 var popups = $(document.body).getChildren('.popup2');
 var html = '.html';
  listIcons.each(function(icon, idx){
   icon.removeEvents('click').addEvent('click', function(e){
    if(e){
     e.stop();
    }
    if($('valueRelative')) $('valueRelative').value  =  relativeLink + icon.getElement('a').getProperty('rel') + html;
	if($('valueFull')) $('valueFull').value  = permanentLink + icon.getElement('a').getProperty('rel') + html;
	if($('valuePrivate')) $('valuePrivate').value  = domainLink + icon.getElement('a').getProperty('rel') + html;
    new SMLayerFix(popups[0], this);
    
   });
  });
}
