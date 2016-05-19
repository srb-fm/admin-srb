$(document).ready(function()
{
	//slides the element with class "menu_body" when paragraph with class "menu_head" is clicked 
	$("#jq_slide_by_click_1 p.menu_head").click(function()
    {
		$(this).next("div.menu_body").slideToggle(300).siblings("div.menu_body").slideUp("slow");
	});
		
	//slides
	$("#jq_slide_by_click div.content_row_toggle_head_1").click(function()
    {
		$(this).next("div.content_row_toggle_body_1").slideToggle(300).siblings("div.content_row_toggle_body_1").slideUp("slow");   	
	});

	//slides
	$("#jq_slide_by_click div.content_row_toggle_head_2").click(function()
    {
		$(this).next("div.content_row_toggle_body_2").slideToggle(300).siblings("div.content_row_toggle_body_2").slideUp("slow");   	
	});

	//slides
	$("#jq_slide_by_click div.content_row_toggle_head_3").click(function()
    {
		$(this).next("div.content_row_toggle_body_3").slideToggle(300).siblings("div.content_row_toggle_body_3").slideUp("slow");   	
	});

	// scroll-button, muss ausserhalb des main-divs platziert werden	
	$(function () {  
        $(window).scroll(function () {  
            if ($(this).scrollTop() > 100) {  
                $('#back-to-top').fadeIn();  
            } else {  
                $('#back-to-top').fadeOut();  
            }  
        });  
  
        $('#back-to-top').click(function () {  
            $('body,html').animate({  
                scrollTop: 0  
            }, 800);  
            return false;  
        });  
    });  
	// scroll-button ende

	// blink-elements
	// class blink in style_srb_jq_2.css
	setInterval(function () {
        //$('.blink').fadeIn(1000).delay(1000).fadeOut(500).delay(500).fadeIn(1000);
        $('.blink').animate( { backgroundColor: 'red' }, 500).animate( { backgroundColor: 'pink' }, 800);
        //$('.blink').delay(1000).css("background-color","blue").delay(500).css("background-color","red");
        //$('.blink').fadeIn(1000).delay(1000).fadeTo(500, 0.5).delay(500).fadeIn(1000);
    }, 3000);
	// blink-elements ende

	// colorboxes
	// benoetigt jquery und:
	// colorbox.css, jquery.colorbox.js
	// geht nur wenns hier in dieser lib ist (nicht in der algemeinen jq_tools)
	$(".c_box").colorbox({width:"850px", height:"690px", opacity:"0.33", overlayClose:false, iframe:true});
	$(".c_box_1").colorbox({width:"1250px", height:"800px", opacity:"0.33", overlayClose:false, iframe:true});
	$(".c_box_2").colorbox({width:"835px", height:"520px", opacity:"0.33", overlayClose:false, iframe:true});
	$(".c_box_3").colorbox({width:"835px", height:"480px", opacity:"0.33", overlayClose:false, iframe:true});
	$(".c_box_4").colorbox({width:"850px", height:"730px", opacity:"0.33", overlayClose:false, iframe:true});
	
	// colorboxes ende
});