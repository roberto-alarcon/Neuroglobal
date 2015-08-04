jQuery(function(d){MPSL.SlidesPageController=can.Control.extend({},{sliderId:null,table:null,updating:!1,init:function(b,c){this._super(b,c);this.sliderId=MPSL.Vars.attrs.slider_id;this.table=this.element.find("table");this.setSlidesSortable()},disableActions:function(){this.table.sortable("disable");this.element.find(".mpsl_delete_slide, .mpsl_duplicate_slide, #create_slide").attr("disabled","disabled")},enableActions:function(){this.table.sortable("enable");this.element.find(".mpsl_delete_slide, .mpsl_duplicate_slide, #create_slide").removeAttr("disabled")},
setSlidesSortable:function(){var b=this;this.table.sortable({items:"tbody>tr",axis:"y",containment:"parent",cursor:"move",handle:".mpsl-slide-sort-handle",tolerance:"pointer",helper:"clone",start:function(c,a){b.updating=!1;b.disableActions()},stop:function(c,a){b.updating||b.enableActions()},update:function(c,a){b.updating=!0;b.updateSlidesOrder();b.enableActions()}})},updateSlidesOrder:function(){var b=this,c=this.table.find("tbody>tr"),a={};d.each(c,function(b,c){a[b]=d(c).attr("data-id")});d.ajax({type:"POST",
url:MPSL.Vars.ajax_url,data:{action:"mpsl_update_slides_order",nonce:MPSL.Vars.nonces.update_slides_order,order:a},success:function(a){MPSL.Functions.showMessage(MPSL.Vars.lang.slides_sorted,MPSL.Functions.MSG_SUCCESS_TYPE)},error:function(a){a=d.parseJSON(a.responseText);a.debug?console.log(a.message):MPSL.Functions.showMessage(a.message,MPSL.Functions.MSG_ERROR_TYPE);b.enableActions()},dataType:"JSON"})},"#create_slide click":function(b){var c=this;this.disableActions();d.ajax({type:"POST",url:MPSL.Vars.ajax_url,
data:{action:"mpsl_create_slide",nonce:MPSL.Vars.nonces.create_slide,slider_id:this.sliderId},success:function(a){a.hasOwnProperty("result")?(MPSL.Functions.showMessage(MPSL.Vars.lang.slide_created,MPSL.Functions.MSG_SUCCESS_TYPE),window.location.href=MPSL.Vars.menu_url+"&view=slide&id="+a.id):(MPSL.Functions.showMessage(MPSL.Vars.lang.slider_created_error,MPSL.Functions.MSG_ERROR_TYPE),c.enableActions())},error:function(a){a=d.parseJSON(a.responseText);a.debug?console.log(a.message):MPSL.Functions.showMessage(a.message,
MPSL.Functions.MSG_ERROR_TYPE);c.enableActions()},dataType:"JSON"})},".mpsl_delete_slide click":function(b){var c=this;this.disableActions();var a=b.attr("data-id");d.ajax({type:"POST",url:MPSL.Vars.ajax_url,data:{action:"mpsl_delete_slide",nonce:MPSL.Vars.nonces.delete_slide,id:a},success:function(b){MPSL.Functions.showMessage(MPSL.Vars.lang.slide_deleted.replace("%d",a),MPSL.Functions.MSG_SUCCESS_TYPE);window.location.reload(!0)},error:function(a){a=d.parseJSON(a.responseText);a.debug?console.log(a.message):
MPSL.Functions.showMessage(a.message,MPSL.Functions.MSG_ERROR_TYPE);c.enableActions()},dataType:"JSON"})},".mpsl_duplicate_slide click":function(b){var c=this;c.disableActions();b=b.attr("data-id");d.ajax({type:"POST",url:MPSL.Vars.ajax_url,data:{action:"mpsl_duplicate_slide",nonce:MPSL.Vars.nonces.duplicate_slide,id:b},success:function(a){a.hasOwnProperty("result")&&(MPSL.Functions.showMessage(MPSL.Vars.lang.slide_duplicated,MPSL.Functions.MSG_SUCCESS_TYPE),window.location.reload(!0));c.enableActions()},
error:function(a){a=d.parseJSON(a.responseText);a.debug?console.log(a.message):MPSL.Functions.showMessage(a.message,MPSL.Functions.MSG_ERROR_TYPE);c.enableActions()},dataType:"JSON"})}});new MPSL.SlidesPageController(".mpsl-wrapper")});