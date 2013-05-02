/**
 *Notes for dv.js
 *
 * First setup canvas and other variables
 * get level data
 * set the screen size 
 * set the page content variable with level array and x,y, and scroll bar data
 * for every list of pages in a level
 * 	for every page in a list
 * 		print the page as a node on the screen
 * 	for every page of the current list print the pagelinks
 *
 */
function drawEllipse(centerX, centerY, width, height) {
	
  context = canvasBufferContext;
  context.save();
  context.beginPath();
  
  context.moveTo(centerX, centerY - height/2); // A1
  
  context.bezierCurveTo(
    centerX + width/2, centerY - height/2, // C1
    centerX + width/2, centerY + height/2, // C2
    centerX, centerY + height/2); // A2

  context.bezierCurveTo(
    centerX - width/2, centerY + height/2, // C3
    centerX - width/2, centerY - height/2, // C4
    centerX, centerY - height/2); // A1
 
  context.strokeStyle = "yellow";
  context.fillStyle = "yellow";
  context.fill();
  context.closePath();	
  context.restore();
}

function printpage(list,page) {
	var c = canvasBufferContext;
	c.save()
	
	c.fillStyle='black';
	//draw box
	pagecontent['pages']['lists'][list][page]['x'] = pagecontent.x;
	pagecontent['pages']['lists'][list][page]['y'] = pagecontent.y;
	c.strokeRect(pagecontent.x,pagecontent.y,BOXSIZE,BOXSIZE);
	//draw oval
	if(pagecontent['pages']['lists'][list][page]['fsp'] == 1){
	drawEllipse(pagecontent.x+HALFBOX,pagecontent.y+HALFBOX,BOXSIZE,HALFBOX);
	}
	//draw text
	c.font = "16pt Arial ";
	c.textAlign = "center";
	c.fillText(page, pagecontent.x+HALFBOX,pagecontent.y+30);
	
	//alert(c.measureText(page).width);
	pagecontent.x += 75;
	pagecontent.y +=0;
	
		
}



function printpagelinks2(pagelist)
{
	
	for(var page in pagelist)
	{
		printlinks(pagelist,page,'next');
		printlinks(pagelist,page,'prev');
	}
	
}

/*
How I want this function to work.
foreach(page in pages)
{
	if(page.from) exists {
		if(page.from is on my row)
		{
			if(page.from is next to me)
			{
				draw line
			}else{
				draw curve underneath between the pages.
			}
		}else{
			draw curve between pages
		}
	}else{
		draw blue circle on the missing link
	}
}
*/
function printlinks(pagelist,page,direction)
{
	var c = canvasBufferContext;
	c.save()
	currentpage = pagelist[page];
	next = pagelist[pagelist[page][direction]];
	prev = pagelist[pagelist[page][direction]];
	if (pagelist[page][direction] == null) { //if this is the end or start of a level or a broken link draw nothing
		
	}else if (pagelist[page][direction] == page) {
		c.strokeStyle = "blue";
		c.fillStyle = "blue";
		c.beginPath();
		if(direction == 'next')
		{
			c.arc(pagelist[page]['x']+BOXSIZE,pagelist[page]['y'],10,0,Math.PI*2,true);
		}else{
			c.arc(pagelist[page]['x'],pagelist[page]['y']+BOXSIZE,10,0,Math.PI*2,true);	
		}
		c.closePath();
		c.fill();
		c.stroke();
	}else if(pagelist[pagelist[page][direction]] != undefined){ //if the next/prev page exist then draws lines to them
		if(direction == 'next')
		{
			if(currentpage['y'] ==next['y']) 
			{ //if on the same line then draw green line
			
				c.beginPath();
				c.strokeStyle = "#19FF19";
				c.moveTo(currentpage['x']+BOXSIZE,currentpage['y']);
				c.lineTo(next['x'],next['y']);
				c.closePath();
				c.stroke();
				c.restore();
			}else{ //if not on the same line
				c.save()
				c.beginPath();
				c.lineWidth = 2;
				c.strokeStyle = "#19FF19";
				c.fillStyle = "#19FF19";
				//alert(next['x']+" "+next['y']+" "+currentpage['x']+" "+currentpage['y']);
				
				c.moveTo(next['x']+HALFBOX,next['y']+BOXSIZE);
				c.bezierCurveTo(next['x']+HALFBOX,
					next['y']+BOXSIZE+50,
					currentpage['x']+BOXSIZE+25,
					currentpage['y']-25,
					currentpage['x']+BOXSIZE,
					currentpage['y']);
				c.stroke();
				c.restore()
				
			}
			
		}else{
			if(currentpage['y'] ==next['y']) 
			{//if on the same line then 
				
				c.save()
				c.strokeStyle = "red";
				c.beginPath();
				c.moveTo(currentpage['x'],currentpage['y']+BOXSIZE);
				if(Math.abs(prev['x']-currentpage['x']) <= 75) //if adjacent then draw straight line
				{
					c.lineTo(prev['x']+BOXSIZE,prev['y']+BOXSIZE);
					c.closePath();
				}else{ //else draw curved line
					c.bezierCurveTo(currentpage['x'],
						currentpage['y']+BOXSIZE+25,
						prev['x']+BOXSIZE,
						prev['y']+BOXSIZE+25,
						prev['x']+BOXSIZE,
						prev['y']+BOXSIZE);
				}
				
				c.stroke();
				c.restore()
				
			}else{ //if not on the same line then draw curve
				c.save()
				c.beginPath();
				c.lineWidth = 2;
				c.strokeStyle = "red";
				c.moveTo(prev['x']+HALFBOX,prev['y']+BOXSIZE);
				c.bezierCurveTo(prev['x']+HALFBOX,
					prev['y']+BOXSIZE+50,
					currentpage['x']+25,
					currentpage['y']+BOXSIZE+50,
					currentpage['x'],
					currentpage['y']+BOXSIZE);
				c.stroke();
				c.restore()
			}
		}
			
	}else{ //if page pointed to does not exist draw circle
		c.strokeStyle = "blue";
		c.beginPath();
		if(direction == 'next')
		{
			c.arc(pagelist[page]['x']+BOXSIZE,pagelist[page]['y'],10,0,Math.PI*2,true);
		}else{
			c.arc(pagelist[page]['x'],pagelist[page]['y']+BOXSIZE,10,0,Math.PI*2,true);	
		}
		c.closePath();
		c.stroke();	
	}
	c.restore();
}

function set_levelarray(x){
    levelarray = x;
}

var levelarray = new Array();
var wholescreen_w = 25;
var wholescreen_y = 100;
var canvasBuffer;
var canvasBufferContext;
var pagecontent;
var BOXSIZE = 50;
var HALFBOX = BOXSIZE/2;
var canvas;

$(document).ready(function() {
	canvas = document.getElementById('can');
	canvasBuffer = document.createElement('canvas');
	$("#n_level").change(function() {
		wholescreen_w=25;
		wholescreen_y=100;
		canvasBufferContext = canvasBuffer.getContext('2d');
		$.ajax({
				type: "POST",
				url: "page_edit_pt.php",
				async: false,
				data: {"function":"getLevelLinkedLists","level":$(this).val()},
				dataType: "json",
				success: function(rdata) {
					set_levelarray(rdata);
				}
		});
		//levelarray = temp;
		for(var i in levelarray['lists'])
		{
			wholescreen_y +=75;
		}
		for(var i in levelarray['lists'][0])
		{
			wholescreen_w +=75;	
		}
		
		if(wholescreen_w < 600)
		{
			wholescreen_w =600;
		}
		if(wholescreen_y < 380)
		{
			wholescreen_y =380;
		}
		canvasBuffer.width = wholescreen_w;
		canvasBuffer.height=wholescreen_y;
		canvas.width= canvasBuffer.width;
		canvas.height= canvasBuffer.height;
		
		pagecontent = {"pages":levelarray,"x":25,"y":50,"xbead":0,"ybead":0};
		pagelist = {};
		for(var list in levelarray['lists'])
		{
			var head = levelarray['lists'][list]['head'];
			var current = levelarray['lists'][list]['head']
			
			while(current != null)
			{
				pagelist[current] = {};
				pagelist[current]['x'] = pagecontent.x;
				pagelist[current]['y'] = pagecontent.y;
				printpage(list,current); //print the page
				
				pagelist[current]['next'] =levelarray['lists'][list][current]['next'];
				pagelist[current]['prev'] =levelarray['lists'][list][current]['prev'];
				if(levelarray['lists'][list][levelarray['lists'][list][current]['next']] !=undefined) //set next 
				{
					if(current ==levelarray['lists'][list][current]['next'])
					{
						current =null;
					}else{
						current =levelarray['lists'][list][current]['next'];
					}
				}else{
					current =null;
				}
				
			
			}
			pagecontent.x = 25;
			pagecontent.y +=75;
			/*for(var i in levelarray['lists'][list])
			{
				if(i != 'head')
				{
				printpagelinks(list,i);
				}
			}*/
			
		}
		//$("#debug").append("<pre>"+JSON.stringify(pagelist)+"</pre>");
		canvas.width= canvasBuffer.width;
		printpagelinks2(pagelist);
		//canvas.addEventListener('click',canvas_click,false);
		
		canvas.getContext('2d').drawImage(canvasBuffer,0,0,canvasBuffer.width,canvasBuffer.height,0,0,canvasBuffer.width,canvasBuffer.height);
		var thesb = document.getElementById('sb');
		thesb.style.width="600px";
		thesb.style.height="380px";
	});
});	

