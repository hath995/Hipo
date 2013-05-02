
$(document).ready(function() {
	$.ajax({
		type: "POST",
		url: "page_edit_pt.php",
		data: {"function":"trackingDbExist","level":1},
		dataType: "json",
		success: function(rdata) {
			if(rdata.err == true)
			{
				edit_fail("#dheader_flash","",rdata.errorlist);	
			}
		},
		error:function(jqXHR, textStatus, errorThrown)
		{
			edit_fail("#dheader_flash",textStatus);	
		}
	});
	
	$("#navigation").append(
		"<fieldset style='float:right;'><legend>Recent Changes:</legend>"+
		"<div id=\"dchange_holder\" style='height:200px; width:250px;overflow:scroll;'></div>"+
		"</fieldset>"
		
		);
	
	$.ajax({
		type: "POST",
		url: "page_edit_pt.php",
		data: {"function":"aggregateChanges","level":1},
		dataType: "json",
		success: function(rdata) {
			for(var change in rdata.changes)
			{
				$("#dchange_holder").append("<p>"+rdata.changes[change].message+"</p>");	
			}
		},
		error:function(jqXHR, textStatus, errorThrown)
		{
			edit_fail("#dheader_flash",textStatus);	
		}
	});
});

function TrackingSubscriber() {};

TrackingSubscriber.prototype = {

	/*editPage:function() {
		
	},
	addPage:function() {
		
	},
	deletePage:function() {
	},
	
	
	questionClick:function(checkvalue) {
	
	},
	addQuestion:function() {
		
	},
	deleteQuestion:function() {
	},*/
	update:function(functionname,options) {
		var level = $("#n_level").val();
		var section = $("#p_section").val();
		var page = $("#p_pnum").val()
		var currentTime = new Date();
		var currentts = currentTime.getFullYear()+"-"+(currentTime.getMonth() + 1)+"-"+currentTime.getDate();
		currentts+= " "+currentTime.getHours()+":"+currentTime.getMinutes()+":"+currentTime.getSeconds();
		
		switch(functionname) 
		{
			case 'addPage':
				$("#dchange_holder").prepend("<p>"+currentts+": <a href=\"\">"+$username+"</a> added page "+level+"."+options.pages.snum+"."+options.pages.pnum+"</p>");
				break;
			case 'addPageAfter':
				$("#dchange_holder").prepend("<p>"+currentts+": <a href=\"\">"+$username+"</a> added page "+level+"."+options.pages.snum+"."+options.pages.pnum+"</p>");
				break;
			case 'editPage':
				$("#dchange_holder").prepend("<p>"+currentts+": <a href=\"\">"+$username+"</a> edited page "+level+"."+section+"."+page+"</p>");
				break;
			case 'deletePage':
				$("#dchange_holder").prepend("<p>"+currentts+": <a href=\"\">"+$username+"</a> deleted page "+level+"."+section+"."+page+"</p>");
				break;
			case 'editQuestion':
				var qnum = $("#q_num").val();
				$("#dchange_holder").prepend("<p>"+currentts+": <a href=\"\">"+$username+"</a> edited question "+qnum+" on page "+level+"."+section+"."+page+"</p>");
				break;
			case 'deleteQuestion': 
				$("#dchange_holder").prepend("<p>"+currentts+": <a href=\"\">"+$username+"</a> deleted a question on page "+level+"."+section+"."+page+"</p>");
				//alert("<p>"+currentts+": "+$username+" deleted a question on page "+level+"."+section+"."+page+"</p>");
				break;
			case 'addQuestion':
				$("#dchange_holder").prepend("<p>"+currentts+": <a href=\"\">"+$username+"</a> added question "+options.question.qnum+" on page "+level+"."+section+"."+page+"</p>");
				break;
			case 'redorderLevel':
				$("#dchange_holder").prepend("<p>"+currentts+": <a href=\"\">"+$username+"</a> reordered level "+level+".</p>");
				break;
		}
	}
}

var TrackingSub = new TrackingSubscriber;

SubsCollection.subscribe(TrackingSub.update);
