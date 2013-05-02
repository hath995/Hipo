
/*JavaScipt OBSERVER pattern
http://www.dustindiaz.com/javascript-observer-class/
Written by Dustin Diaz
This is technically more of an Observer Collection
*/
function Observer() {

    this.fns = [];

}

Observer.prototype = {

    subscribe : function(fn) {

        this.fns.push(fn);

    },

    unsubscribe : function(fn) {

        this.fns = this.fns.filter(

            function(el) {

                if ( el !== fn ) {

                    return el;

                }

            }

        );

    },

    fire : function(o, thisObj,options) {

        var scope = thisObj || window;

        this.fns.forEach(

            function(el) {

                el.call(scope, o,options);

            }

        );

    }

};
/*END OBSERVER pattern
*/

/**
* @author Aaron Elligsen
* @modified 11/7/2011
**/
var SubsCollection = new Observer;

//clear page fields
function clear_fields() {
	$("#q_id").val("");
	$("#q_num").val("");
	$("#q_type").val("");
	$("#q_text").val("");
	$("#q_label").val("");
	$("#q_options").val("");
	$("#q_correct").val("");
	$("#p_idx").val("");
	$("#p_level").val("");
	$("#p_section").val("");
	$("#p_pnum").val("");
	$("#p_from").val("");
	$("#p_next").val("");
	$("#p_type").val("");
	$("#p_title").val("");
	$("#p_content").val("");
	$("#hpreview div").remove();
	$("#p_notes").val("");
	$("#p_video").val("");
	$("#p_picture").val("");
	$("#p_questions option").remove();
	$("#p_lnav").removeAttr("checked");
	$("#p_sfp").removeAttr("checked");
	
	SubsCollection.fire('clear');
}

//Edit success flash
function edit_success(wdiv) { //param wdiv will take an id string, ie "#dpage_flash"
	$(wdiv+" > div.success").show().hide(5000);
}

/**
	@param wdiv The div container the error output will be created in
	@param message String for singular error output
	@param errorlist Array of error objects
**/
function edit_fail(wdiv,message,errorlist) {
	
	if(errorlist != undefined)
	{
		for(var error in errorlist)
		{
			//alert(errorlist[error].error)
			errorbox = errorlist[error].error;
			errorbox += "<fieldset><legend>Technical</legend><div><table>";
			errorbox += "<tr><th>Function<th><td>"+errorlist[error].function+"</td></tr>";
			errorbox += "<tr><th>Line<th><td>"+errorlist[error].line+"</td></tr>";
			errorbox += "<tr><th>Query<th><td>"+errorlist[error].query+"</td></tr>";
			errorbox += "<tr><th>Error Message<th><td>"+errorlist[error].errortext+"</td></tr>";			
			errorbox +="</table></div></fieldset>";
			edit_fail(wdiv,errorbox);
		}
	}else{
		
		var idnum = parseInt(Math.random() * 2147483647);
		$(wdiv).append('<div class="error" id="err'+idnum+'"><B>An error has ocurred:<br> '+message+'<br><br><a href="#" onclick="$(\'#err'+idnum+'\').detach()">Click here to close this message.</a>  <a href="#" onclick="$(\'div[id^=err]\').detach()">Click here to close all messages.</a></div>');
	}
}

$(document).ready(function () {
	//Connects handler functions to the various buttons 
	
	
	//When someone clicks on a level
	$("#n_level").change(function() {
		$.ajax({ //get the level pages
				type: "POST",
				url: "page_edit_pt.php",
				data: {"function":"getLevelPages","level":$(this).val()},
				dataType: "json",
				success: function(rdata) {
					$("#hipage option").remove();
					$("#hipage optgroup").remove();
					var listnum = 0;
					for(var list in rdata.lists)
					{
						//alert(rdata.lists.length);
						//alert(listnum)
						var toption = "";
						if(listnum != 0)
						{
							toption += "<optgroup label=\"Orphaned List: "+(listnum)+"\">";	
						}
						listnum++;
						//alert(rdata.lists[list]['pages'][0].id);
						for(var page in rdata.lists[list]['pages'])
						{

							toption += "<option value='"+rdata.lists[list]['pages'][page].id+"' >"+rdata.lists[list]['pages'][page].snum+"."+rdata.lists[list]['pages'][page].pagenum+": "+rdata.lists[list]['pages'][page].title;
							toption +=" ("+rdata.lists[list]['pages'][page].id+")";
							if(rdata.lists[list]['pages'][page].questions == 1)
							{
								toption +="(Q)";		
							}
							toption +="</option>";
								
			
						}
						if(listnum != 0)
						{
							toption += "</optgroup>";
							
						}
						
						$("#hipage").append(toption);
					}
					clear_fields();
				},
				error:function(jqXHR, textStatus, errorThrown)
				{
					edit_fail("#dlevel_flash",textStatus);	
				}
		});
		
		$.ajax({ //get the level sections
				type: "POST",
				url: "page_edit_pt.php",
				data: {"function":"getLevelSections","level":$(this).val()},
				dataType: "json",
				success: function(rdata) {
					if(rdata.err == false)
					{
						$("#n_section option").remove();
						for(var section in rdata.sections)
						{
							toption = "<option value='"+rdata.sections[section].snum+"' >"+rdata.sections[section].snum+'. '+rdata.sections[section].sname;
							toption +="</option>";
							$("#n_section").append(toption);
						}
					}else{
						edit_fail("#dheader_flash","",rdata.errorlist);	
					}
						
				},
				error:function(jqXHR, textStatus, errorThrown)
				{
					edit_fail("#dheader_flash",textStatus);	
				}
		});
		
	});
	
	//When someone clicks on a page
	$("#hipage").click(function() {
		$.ajax({
				type: "POST",
				url: "page_edit_pt.php",
				data: {"function":"getPage","level":$("#n_level").val(),"pageid":$(this).val()},
				dataType: "json",
				success: function(rdata) {
					if(rdata.err == false)
					{
						$("#p_idx").val(rdata.page.id);
						$("#p_level").val(rdata.page.lnum);
						$("#p_section").val(rdata.page.snum);
						$("#p_pnum").val(rdata.page.pnum);
						$("#p_from").val(rdata.page.pagefrom);
						$("#p_next").val(rdata.page.pagenext);
						$("#p_type").val(rdata.page.type);
						$("#p_title").val(html_entity_decode(rdata.page.title));
						if(rdata.page.leftnav ==1)
						{
							$("#p_lnav").attr("checked","true");
						}else{
							$("#p_lnav").removeAttr("checked");
						}
						if(rdata.page.fsp ==1)
						{
							$("#p_sfp").attr("checked","true");	
						}else{
							$("#p_sfp").removeAttr("checked");	
						}
						
						$("#p_content").val(html_entity_decode(rdata.page.content_participant));
						$("#hpreview div").remove();
						$("#hpreview").append("<div id='dprev'>"+html_entity_decode(rdata.page.content_participant)+"</div>");
						
						$("#p_video").val(rdata.page.video);
						$("#p_picture").val(rdata.page.picture);
						
						
						$("#p_notes").val(rdata.page.notes);
						
						$("#p_questions option").remove();
						for(var question in rdata.questions)
						{
							//alert(rdata.pages[page].id);
							toption = "<option value='"+rdata.questions[question].id+"' >"+rdata.questions[question].qnum+": "+rdata.questions[question].qlabel+" "+rdata.questions[question].qtext.substring(0,20);
							toption +=" ("+rdata.questions[question].id+")";
							toption +="</option>";
							$("#p_questions").append(toption);	
						}
						
						$("#q_id").val("");
						$("#q_num").val("");
						$("#q_type").val("");
						$("#q_text").val("");
						$("#q_label").val("");
						$("#q_options").val("");
						$("#q_correct").val("");
						SubsCollection.fire('pageClick');
					}else{
						edit_fail("#dlevel_flash","",rdata.errorlist);	
					}
					
				},
				error:function(jqXHR, textStatus, errorThrown)
				{
					edit_fail("#dlevel_flash",textStatus);	
				}
		});
	});
	
	//Add level button
	$("#badd_level").click(function() {
			$.ajax({
				type: "POST",
				url: "page_edit_pt.php",
				data: {"function":"addLevel"},
				dataType: "json",
				success: function(rdata) {
					$("#n_level").append("<option value='"+rdata.level_num+"'>"+rdata.level_num+". "+rdata.name+"</option>");
				},
				error:function(jqXHR, textStatus, errorThrown)
				{
					edit_fail("#dlevel_flash",textStatus);	
				}
		});
	});
	
	//add page button
	$("#badd_page").click(function() {
			$.ajax({
				type: "POST",
				url: "page_edit_pt.php",
				data: {"function":"addPage","level":$("#n_level").val()},
				dataType: "json",
				success: function(rdata) {
					if(rdata.err ==false)
					{
						toption = "<option value='"+rdata.pages.id+"' >"+rdata.pages.snum+"."+rdata.pages.pnum+": "+rdata.pages.title;
						toption +=" ("+rdata.pages.id+")";
						toption +="</option>";
						$("#hipage").append(toption);
						SubsCollection.fire('addPage',window,rdata);
						clear_fields();
						
					}else{
						edit_fail("#dlevel_flash","",rdata.errorlist);
					}
				},
				error:function(jqXHR, textStatus, errorThrown)
				{
					edit_fail("#dlevel_flash",textStatus);	
				}
		});
	});
	
	//move section up
	$("#bmoveu_section").click(function() {
			$.ajax({
				type: "POST",
				url: "page_edit_pt.php",
				data: {"function":"moveSection","level":$("#n_level").val(),"section":$("#n_section").val(),"dir":1},
				dataType: "json",
				success: function(rdata) {
					clear_fields();
				},
				error:function(jqXHR, textStatus, errorThrown)
				{
					edit_fail("#dlevel_flash",textStatus);	
				}
		});
	});
	
	//move section down
	$("#bmoved_section").click(function() {
			$.ajax({
				type: "POST",
				url: "page_edit_pt.php",
				data: {"function":"moveSection","level":$("#n_level").val(),"section":$("#n_section").val(),"dir":2},
				dataType: "json",
				success: function(rdata) {
					clear_fields();
				},
				error:function(jqXHR, textStatus, errorThrown)
				{
					edit_fail("#dlevel_flash",textStatus);	
				}
		});
	});
	
	//when page edit is submitted
	$("#p_submit").click(function() {
			$.ajax({
				type: "POST",
				url: "page_edit_pt.php",
				data: {"function":"editPage","level":$("#n_level").val(),"pageid":$("#hipage").val(),"superhipo":1,"pdata":$("#page_edit").serializeArray()},
				dataType: "json",
				success: function(rdata) {
					if(rdata.err == false)
					{
						$("#hpreview div").remove();
						$("#hpreview").append("<div id='dprev'>"+$("#p_content").val()+"</div>");
						$("#hipage option:selected").text($("#p_section").val()+"."+$("#p_pnum").val()+": "+$("#p_title").val()+" ("+$("#p_idx").val()+")");
						
						edit_success("#dpage_flash");
						SubsCollection.fire('editPage',window,rdata);
					}else{
						edit_fail("#dpage_flash","",rdata.errorlist);	
					}
				},
				error:function(jqXHR, textStatus, errorThrown)
				{
					edit_fail("#dpage_flash",textStatus);	
				}
		});
	});
	
	//when a question is clicked on
	$("#p_questions").click(function() {
			$.ajax({
					type: "POST",
					url: "page_edit_pt.php",
					data: {"function":"getQuestion","qid":$(this).val(),"level":$("#n_level").val()},
					dataType:"json",
					success: function(rdata) {
						if(rdata.err ==false)
						{
							$("#q_id").val(rdata.question.id);
							$("#q_num").val(rdata.question.qnum);
							$("#q_type").val(rdata.question.qtype);
							$("#q_text").val(html_entity_decode(rdata.question.qtext));
							$("#q_label").val(rdata.question.qlabel);
							$("#q_options").val(html_entity_decode(rdata.question.qresponse));
							$("#q_correct").val(rdata.question.qcorrect);
							SubsCollection.fire('questionClick',window,rdata);
						}else{
							edit_fail("#dquestion_flash","",rdata.errorlist);
						}
						
					},
					error:function(jqXHR, textStatus, errorThrown)
					{
						edit_fail("#dquestion_flash",textStatus);	
					}
			});
	});
	
	//when a question is added
	$("#badd_question").click(function() {
		$.ajax({
					type: "POST",
					url: "page_edit_pt.php",
					data: {"function":"addQuestion","pageid":$("#hipage").val(),"level":$("#n_level").val()},
					dataType:"json",
					success: function(rdata) {
						if(rdata.err == false)
						{
							toption = "<option value='"+rdata.question.id+"' >"+rdata.question.qnum+": "+rdata.question.qlabel+" "+rdata.question.qtext.substring(0,20);
							toption +=" ("+rdata.question.id+")";
							toption +="</option>";
							$("#p_questions").append(toption);
							SubsCollection.fire('addQuestion',window,rdata);
						}else{
							edit_fail("#dquestion_flash","",rdata.errorlist);	
						}
					},
					error:function(jqXHR, textStatus, errorThrown)
					{
						edit_fail("#dquestion_flash",textStatus);	
					}
			});	
	});
	
	//delete questions
	$("#bdelete_question").click(function() {
		if($("#p_questions").val() != undefined)
		{
			$.ajax({
				type: "POST",
				url: "page_edit_pt.php",
				data: {"function":"deleteQuestion","qid":$("#p_questions").val(),"level":$("#n_level").val()},
				dataType:"json",
				success: function(rdata) {
					if(rdata.err ==false)
					{
						$("#p_questions option:selected").remove();
						$("#q_id").val("");
						$("#q_num").val("");
						$("#q_type").val("");
						$("#q_text").val("");
						$("#q_label").val("");
						$("#q_options").val("");
						$("#q_correct").val("");
						SubsCollection.fire('deleteQuestion');
						//alert(this.data);
					}else{
						edit_fail("#dquestion_flash","",rdata.errorlist);	
					}
				},
				error:function(jqXHR, textStatus, errorThrown)
				{
					edit_fail("#dquestion_flash",textStatus);	
				}
			});
		}else{
			edit_fail("#dquestion_flash","You must select a question to delete one.");	
		}
	});
	
	//submit question edit
	$("#bsubmit_question").click(function() {
		$.ajax({
			type: "POST",
			url: "page_edit_pt.php",
			data: {"function":"editQuestion","qid":$("#p_questions").val(),"level":$("#n_level").val(),"pdata":$("#page_edit").serializeArray()},
			dataType:"json",
			success: function(rdata) {
				//clear_fields();
				if(rdata.err ==false)
				{
					edit_success("#dquestion_flash");
				}else{
					edit_fail("#dquestion_flash","",rdata.errorlist);	
				}
				SubsCollection.fire('editQuestion');
			},
			error:function(jqXHR, textStatus, errorThrown)
			{
				edit_fail("#dquestion_flash",textStatus);	
			}
		});	
	});
	
	//rename a level
	$("#brename_level").click(function() {
			if($("#n_level").val() != undefined)
			{
				var newname = prompt("What should level "+$("#n_level").val()+" be called?",$("#n_level option:selected").text().substring(3));
				if(newname != undefined)
				{
					$.ajax({
						type: "POST",
						url: "page_edit_pt.php",
						dataType: "json",
						data: {"function":"renameLevel","newname":newname,"level":$("#n_level").val()},
						success: function(rdata) {
							if(rdata.edit == "success")
							{
								$("#n_level option:selected").text($("#n_level").val()+". "+newname);
							}else if(rdata.err == true){
								edit_fail("#dlevel_flash","",rdata.errorlist);
							}else{
								edit_fail("#dlevel_flash","The level could not be renamed.");	
							}
						},
						error:function(jqXHR, textStatus, errorThrown)
						{
							edit_fail("#dlevel_flash",textStatus);	
						}
					});
				}
			}
	
	});
	
	//reorder a level
	$("#breorder_level").click(function() {
			if($("#n_level").val() != undefined)
			{
				var reorder = prompt("Are you sure you want to reorder/renumber the level? (yes or no)","");
				if(reorder == undefined)
				{
					reorder = ""	
				}
				if(reorder.toLowerCase() == "yes")
				{
					reorder = true;
				}else{
					reorder = false;	
				}
				if(reorder)
				{
				
					$.ajax({
						type: "POST",
						url: "page_edit_pt.php",
						dataType: "json",
						data: {"function":"reorderLevel","level":$("#n_level").val()},
						success: function(rdata) {
							if(rdata.err == false)
							{
								SubsCollection.fire('reorderLevel');
								clear_fields();
							}else{
								edit_fail("#dlevel_flash","",rdata.errorlist);	
							}
						},
						error:function(jqXHR, textStatus, errorThrown)
						{
							edit_fail("#dlevel_flash",textStatus);	
						}
					});
				}
			}
	
	});
	
	//rename a section
	$("#brename_section").click(function() {
			if($("#n_section").val() != undefined)
			{
				var newname = prompt("What should section "+$("#n_section").val()+" be called?",$("#n_section option:selected").text().substring(3));
				if(newname != undefined)
				{
					$.ajax({
						type: "POST",
						url: "page_edit_pt.php",
						dataType: "json",
						data: {"function":"renameSection","newname":newname,"section":$("#n_section").val(),"level":$("#n_level").val()},
						success: function(rdata) {
							if(rdata.err == false)
							{
								$("#n_section option:selected").text($("#n_section").val()+". "+newname);
							}else{
								edit_fail("#dlevel_flash","",rdata.errorlist);	
							}
							
						},
						error:function(jqXHR, textStatus, errorThrown)
						{
							edit_fail("#dlevel_flash",textStatus);	
						}
					});
				}
			}
	
	});
	
	//add a section
	$("#badd_section").click(function() {
			$.ajax({
				type: "POST",
				url: "page_edit_pt.php",
				data: {"function":"addSection","level":$("#n_level").val()},
				dataType: "json",
				success: function(rdata) {
					$("#n_section").append("<option value='"+rdata.section_num+"'>"+rdata.section_num+". "+rdata.name+"</option>");
				},
				error:function(jqXHR, textStatus, errorThrown)
				{
					edit_fail("#dlevel_flash",textStatus);	
				}
		});
	});
	
	//delete a section
	$("#bdelete_section").click(function() {
		$.ajax({
			type: "POST",
			url: "page_edit_pt.php",
			data: {"function":"deleteSection","level":$("#n_level").val()},
			dataType: "json",
			success: function(rdata) {
				if(rdata.remove == "success")
				{
					$("#n_section option[value="+rdata.section+"]").remove();	
				}
			},
			error:function(jqXHR, textStatus, errorThrown)
			{
				edit_fail("#dlevel_flash",textStatus);	
			}
		});		
	});
	
	//delete a page
	$("#bdelete_page").click(function () {
		var dpage = $("#hipage").val();
		var dcheck = prompt("Are you sure you want to delete this page? Type yes, no, or hit cancel.","");
		if(dcheck == undefined)
		{
			dcheck = ""	
		}
		if(dcheck.toLowerCase() == "yes")
		{
			dcheck = true;
		}else{
			dcheck = false;	
		}
		if(dpage != undefined && dcheck)
		{
			$.ajax({
				type: "POST",
				url: "page_edit_pt.php",
				data: {"function":"deletePage","level":$("#n_level").val(),"pageid":dpage},
				dataType: "json",
				success: function(rdata) {
					if(rdata.err == false)
					{
						$("#hipage option[value="+rdata.deleted+"]").remove();
						SubsCollection.fire('deletePage');
						clear_fields();
					}else{
						edit_fail("#dlevel_flash","",rdata.errorlist);		
					}
				},
				error:function(jqXHR, textStatus, errorThrown)
				{
					edit_fail("#dlevel_flash",textStatus);	
				}
			});
		}
	});
	
	
	//add a page after the highlighted page
	$("#baddpageafter").click(function() {
		if($("#hipage").val() == undefined)
		{
			alert("A page must be selected before you can add a page after it.");	
		}else{
			$.ajax({
				type: "POST",
				url: "page_edit_pt.php",
				data: {"function":"addPageAfter","level":$("#n_level").val(),"pid":$("#hipage").val()},
				dataType: "json",
				success: function(rdata) {
					if(rdata.err == true)
					{
						edit_fail("#dlevel_flash","",rdata.errorlist);
						//alert("Error: "+rdata.err);	
					}else{
						toption = "<option value='"+rdata.pages.id+"' >"+rdata.pages.snum+"."+rdata.pages.pnum+": "+rdata.pages.title;
						toption +=" ("+rdata.pages.id+")";
						toption +="</option>";
						$("#hipage option[value="+$("#hipage").val()+"]").after(toption);
						SubsCollection.fire('addPageAfter',window,rdata);
						if(rdata.errorlist)
						{
							if(rdata.errorlist.length >0)
							{
								edit_fail("#dlevel_flash","",rdata.errorlist);
							}
						}
						clear_fields();
					}
				},
				error:function(jqXHR, textStatus, errorThrown)
				{
					edit_fail("#dlevel_flash",textStatus);	
				}
			});
		}
	}); 
	
	//alert for functions not yet implemented
	$(".nyi").click(function(){
		alert("This function is not yet implemented.");

	});
	
	/*for(var i in $("#page_edit").serializeArray())
	{
		alert(i);
	}*/
});


