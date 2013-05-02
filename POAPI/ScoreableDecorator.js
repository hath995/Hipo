
$(document).ready(function() {
	$("#question_second_column").append('<td>Question Scoreable?</td><td><input type="checkbox" id="q_scoreable" name="q_scoreable"></td>');
	$.ajax({
		type: "POST",
		url: "page_edit_pt.php",
		data: {"function":"scoreableDbExist","level":1},
		dataType: "json",
		success: function(rdata) {
			if(rdata.err == 'true')
			{
				edit_fail("#dheader_flash",rdata.error);	
			}
		},
		error:function(jqXHR, textStatus, errorThrown)
		{
			edit_fail("#dheader_flash",textStatus);	
		}
	});
});



function ScoreableSubscriber() {};

ScoreableSubscriber.prototype = {

	
	
	removeCheck:function() {
		
		$("#q_scoreable").removeAttr("checked");
	},
	
	questionClick:function(checkvalue) {
		checkvalue = checkvalue.question.qscoreable;
		if(checkvalue ==1)
		{
			$("#q_scoreable").attr("checked","true");
		}else{
			$("#q_scoreable").removeAttr("checked");
		}
	},
	update:function(functionname,options) {
		switch(functionname) 
		{
			case 'clear': ScoreableSubscriber.prototype.removeCheck();
				break;
			case 'pageClick':ScoreableSubscriber.prototype.removeCheck();
				break;
			case 'questionClick': ScoreableSubscriber.prototype.questionClick(options);
				break;
			case 'deleteQuestion': ScoreableSubscriber.prototype.removeCheck();
				break;
		}
	}
}

var ScoreSub = new ScoreableSubscriber;

SubsCollection.subscribe(ScoreSub.update);
