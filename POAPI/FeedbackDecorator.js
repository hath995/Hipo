
$(document).ready(function() {
	$("#tqtable").append('<td>Question Feedback</td><td><textarea id="q_feedback" name="q_feedback" cols="40" rows="4"></textarea></td>');

});

function FeedbackSubscriber() {};

FeedbackSubscriber.prototype = {

	clear:function()
	{
		$("#q_feedback").val("");
	},
	questionClick:function(qdata) {		
		$("#q_feedback").val(qdata.question.incorrect_feedback);
		
	},
	update:function(functionname,options) {
		switch(functionname) 
		{
			case 'clear': FeedbackSubscriber.prototype.clear();
				break;
			case 'pageClick':FeedbackSubscriber.prototype.clear();
				break;
			case 'questionClick': FeedbackSubscriber.prototype.questionClick(options);
				break;
			case 'deleteQuestion': FeedbackSubscriber.prototype.clear();
				break;
		}
	}
}

var FeedbackSub = new FeedbackSubscriber;

SubsCollection.subscribe(FeedbackSub.update);
